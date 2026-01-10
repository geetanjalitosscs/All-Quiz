-- ============================================================================
-- SCHEMA UPDATE: SERVER-CONTROLLED TIMER & SINGLE ACTIVE SESSION
-- ============================================================================
-- 
-- PURPOSE:
-- Update quiz_attempts table to use server-controlled timer with expires_at
-- This ensures timer NEVER resets on back/refresh/browser close
--
-- ============================================================================

-- Add new columns for server-controlled timer
ALTER TABLE `quiz_attempts`
ADD COLUMN `duration_minutes` int(11) NOT NULL DEFAULT 45 AFTER `remaining_time_seconds`,
ADD COLUMN `expires_at` datetime NULL AFTER `duration_minutes`;

-- Update existing records to set expires_at based on start_time
UPDATE `quiz_attempts` 
SET `duration_minutes` = 45,
    `expires_at` = DATE_ADD(`start_time`, INTERVAL 45 MINUTE)
WHERE `expires_at` IS NULL OR `expires_at` = '0000-00-00 00:00:00';

-- Add index on expires_at for fast timeout checks
ALTER TABLE `quiz_attempts`
ADD KEY `idx_expires_at` (`expires_at`);

-- Note: MySQL doesn't support partial unique indexes
-- Single active session is enforced by application logic:
-- SELECT * FROM quiz_attempts 
-- WHERE user_id = ? AND role = ? AND level = ? AND status = 'in_progress'
-- LIMIT 1
-- If found → Resume, NEVER create new
-- The existing idx_user_role_level_status index helps with fast lookup

-- ============================================================================
-- USAGE:
-- 
-- Timer Calculation (Server-side, ALWAYS):
--   SELECT expires_at FROM quiz_attempts WHERE attempt_id = ?
--   remaining_seconds = TIMESTAMPDIFF(SECOND, NOW(), expires_at)
--   
-- Single Active Session Check:
--   SELECT * FROM quiz_attempts 
--   WHERE user_id = ? AND role = ? AND level = ? AND status = 'in_progress'
--   LIMIT 1
--   
--   If FOUND → Resume (NEVER create new)
--   If NOT FOUND → Create new
--
-- ============================================================================

