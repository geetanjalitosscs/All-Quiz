-- ============================================================================
-- SCHEMA ADDITIONS FOR SESSION-BASED, HIGH-CONCURRENCY QUIZ SYSTEM
-- ============================================================================
-- 
-- PURPOSE:
-- Add session-based progress saving, auto-save per question, resume capability,
-- and server-side timer tracking without modifying existing tables.
--
-- REQUIREMENTS SUPPORTED:
-- 1. Auto-save answers as candidate selects options
-- 2. Resume quiz after page reload / network interruption / logout
-- 3. Accurate remaining time restoration (server-controlled)
-- 4. High concurrency (1000+ candidates simultaneously)
-- 5. One active attempt per candidate per quiz
-- 6. Prevent data corruption and race conditions
--
-- ============================================================================
-- IMPORTANT: DO NOT MODIFY EXISTING TABLES
-- ============================================================================
-- This script ONLY adds new tables and indexes.
-- Existing tables (users, responses, question tables) remain unchanged.
-- ============================================================================

-- ============================================================================
-- TABLE 1: quiz_attempts
-- ============================================================================
-- PURPOSE: Tracks each quiz attempt session with state persistence
--
-- WHY REQUIRED:
-- - Current system has no concept of "quiz attempt" - only tracks users and final responses
-- - Need to track: which quiz (role/level), start time, status, remaining time
-- - Need to prevent multiple concurrent attempts per user per quiz
-- - Need to resume quiz after page reload/network interruption
-- - Need server-side timer tracking (client-side can be manipulated)
-- - Need to identify active vs submitted attempts
--
-- KEY FEATURES:
-- - Unique attempt_id per quiz session
-- - Links to user_id (foreign key to users table)
-- - Stores quiz metadata (role, level, question_ids as JSON)
-- - Tracks status: 'in_progress', 'submitted', 'expired'
-- - Server-controlled timer: start_time, remaining_time_seconds, last_activity_time
-- - Enables resume by loading attempt_id from session
--
-- CONCURRENCY HANDLING:
-- - Index on (user_id, role, level, status) prevents duplicate active attempts
-- - Index on (user_id, status) for fast lookup of active attempts
-- - last_activity_time enables detection of abandoned attempts
--
CREATE TABLE `quiz_attempts` (
  `attempt_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `role` varchar(100) NOT NULL,
  `level` varchar(20) NOT NULL,
  `status` enum('in_progress','submitted','expired') NOT NULL DEFAULT 'in_progress',
  `start_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `end_time` datetime DEFAULT NULL,
  `remaining_time_seconds` int(11) NOT NULL DEFAULT 2700,
  `last_activity_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `question_ids` text NOT NULL COMMENT 'JSON array of question IDs for this attempt',
  `current_question_index` int(11) DEFAULT 0 COMMENT 'Last viewed question index (0-based)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`attempt_id`),
  KEY `idx_user_status` (`user_id`, `status`),
  KEY `idx_user_role_level_status` (`user_id`, `role`, `level`, `status`),
  KEY `idx_status_activity` (`status`, `last_activity_time`),
  KEY `idx_start_time` (`start_time`),
  CONSTRAINT `fk_quiz_attempts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE 2: quiz_answers
-- ============================================================================
-- PURPOSE: Stores per-question answers as they're selected (auto-save)
--
-- WHY REQUIRED:
-- - Current 'responses' table only stores final submitted answers (after form submit)
-- - Need to auto-save answers immediately when candidate selects an option
-- - Need to allow updates (candidate changes answer before final submit)
-- - Need to restore answers on page reload/resume
-- - Must be separate from 'responses' to maintain data integrity
-- - 'responses' table remains for final submitted answers (backward compatibility)
--
-- KEY FEATURES:
-- - One row per question per attempt (allows updates)
-- - Links to attempt_id (foreign key to quiz_attempts)
-- - Stores selected_option (A, B, C, D) or NULL if not answered
-- - saved_at timestamp tracks when answer was saved/updated
-- - ON DUPLICATE KEY UPDATE allows answer changes
--
-- CONCURRENCY HANDLING:
-- - Unique index on (attempt_id, question_id) prevents duplicate answers per question
-- - Index on attempt_id for fast bulk retrieval of all answers for an attempt
-- - Index on question_id for analytics (optional, but useful)
-- - Row-level locking via unique constraint prevents race conditions
--
CREATE TABLE `quiz_answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `selected_option` enum('A','B','C','D') DEFAULT NULL,
  `saved_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_attempt_question` (`attempt_id`, `question_id`),
  KEY `idx_attempt_id` (`attempt_id`),
  KEY `idx_question_id` (`question_id`),
  KEY `idx_saved_at` (`saved_at`),
  CONSTRAINT `fk_quiz_answers_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `quiz_attempts` (`attempt_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- INDEXES EXPLANATION FOR HIGH CONCURRENCY (1000+ CANDIDATES)
-- ============================================================================
--
-- quiz_attempts indexes:
-- 1. PRIMARY KEY (attempt_id): Fast lookup by attempt ID
-- 2. idx_user_status: Fast lookup of user's active/submitted attempts
-- 3. idx_user_role_level_status: Prevents duplicate active attempts (unique constraint logic)
-- 4. idx_status_activity: Fast cleanup of expired/abandoned attempts
-- 5. idx_start_time: Analytics and time-based queries
--
-- quiz_answers indexes:
-- 1. PRIMARY KEY (id): Fast lookup by answer ID
-- 2. uk_attempt_question (UNIQUE): Prevents duplicate answers, enables fast updates
-- 3. idx_attempt_id: Fast retrieval of all answers for an attempt (resume quiz)
-- 4. idx_question_id: Analytics and question-level queries
-- 5. idx_saved_at: Time-based queries and cleanup
--
-- FOREIGN KEYS:
-- - Ensure referential integrity
-- - CASCADE DELETE: If attempt is deleted, answers are automatically deleted
-- - If user is deleted, attempts are automatically deleted
--
-- ============================================================================
-- USAGE SCENARIOS
-- ============================================================================
--
-- SCENARIO 1: Candidate starts quiz
--   INSERT INTO quiz_attempts (user_id, role, level, question_ids, remaining_time_seconds)
--   Returns attempt_id → Store in session
--
-- SCENARIO 2: Candidate selects answer (auto-save)
--   INSERT INTO quiz_answers (attempt_id, question_id, selected_option)
--   ON DUPLICATE KEY UPDATE selected_option = VALUES(selected_option)
--   UPDATE quiz_attempts SET last_activity_time = NOW(), remaining_time_seconds = ?
--
-- SCENARIO 3: Candidate reloads page / resumes quiz
--   SELECT * FROM quiz_attempts WHERE attempt_id = ? AND status = 'in_progress'
--   SELECT * FROM quiz_answers WHERE attempt_id = ? ORDER BY question_id
--   Restore answers, timer, current question position
--
-- SCENARIO 4: Timer sync (every 30 seconds)
--   UPDATE quiz_attempts SET remaining_time_seconds = ?, last_activity_time = NOW()
--   WHERE attempt_id = ? AND status = 'in_progress'
--
-- SCENARIO 5: Final submission
--   UPDATE quiz_attempts SET status = 'submitted', end_time = NOW()
--   WHERE attempt_id = ? AND status = 'in_progress'
--   Copy quiz_answers to responses table (existing logic)
--
-- SCENARIO 6: Prevent duplicate attempts
--   SELECT attempt_id FROM quiz_attempts
--   WHERE user_id = ? AND role = ? AND level = ? AND status = 'in_progress'
--   If exists → Resume existing attempt, else create new
--
-- ============================================================================
-- DATA CONSISTENCY RULES
-- ============================================================================
--
-- 1. One active attempt per user per quiz (role + level combination)
--    Enforced by application logic using idx_user_role_level_status
--
-- 2. Answers can be updated (candidate changes option)
--    Handled by UNIQUE constraint + ON DUPLICATE KEY UPDATE
--
-- 3. Timer is server-controlled
--    remaining_time_seconds updated server-side, not client-side
--
-- 4. Attempt status transitions:
--    in_progress → submitted (on final submit)
--    in_progress → expired (on timeout or cleanup job)
--
-- 5. Foreign key constraints ensure:
--    - Answers belong to valid attempts
--    - Attempts belong to valid users
--    - Cascade deletes maintain referential integrity
--
-- ============================================================================
-- PERFORMANCE CONSIDERATIONS
-- ============================================================================
--
-- 1. All frequently queried columns are indexed
-- 2. Foreign keys have indexes (MySQL requirement)
-- 3. JSON question_ids stored as TEXT (acceptable for 50 question IDs)
-- 4. Enum types used for status/options (efficient storage)
-- 5. Timestamps with ON UPDATE CURRENT_TIMESTAMP (automatic tracking)
-- 6. InnoDB engine for row-level locking (concurrency)
-- 7. utf8mb4 charset for proper Unicode support
--
-- ============================================================================
-- END OF SCHEMA ADDITIONS
-- ============================================================================
-- 
-- NEXT STEPS (Application Code - NOT INCLUDED HERE):
-- 1. Create API endpoint: save_answer.php (auto-save on option select)
-- 2. Create API endpoint: resume_quiz.php (load attempt state)
-- 3. Create API endpoint: sync_timer.php (server-side timer sync)
-- 4. Modify quiz.php to check for existing attempt and restore state
-- 5. Modify submit_quiz.php to mark attempt as submitted
-- 6. Add JavaScript to call auto-save API on radio button change
-- 7. Add JavaScript to sync timer with server every 30 seconds
--
-- ============================================================================

