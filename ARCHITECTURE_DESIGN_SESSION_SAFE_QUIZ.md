# Architecture Design: Session-Safe, High-Concurrency Quiz System

## Executive Summary

This document outlines the **database schema additions** required to transform the existing quiz system into a production-grade, session-safe assessment platform that supports:
- **Auto-save** answers as candidates select options
- **Resume capability** after page reload, network interruption, or logout
- **Server-controlled timer** that persists accurately
- **1000+ concurrent candidates** without data corruption or race conditions

**CRITICAL CONSTRAINT**: No existing tables, columns, or code are modified. Only **additions** are provided.

---

## Current System Analysis

### Existing Schema
- **`users`** table: Stores candidate information (id, name, role, level, mobile, email, submitted_at)
- **`responses`** table: Stores final submitted answers (id, user_id, question_id, selected_option, is_correct, submitted_at)
- **Question tables**: backend_mcq_questions, python_mcq_questions, etc.

### Current Limitations
1. ❌ **No quiz attempt tracking**: System only tracks users and final responses
2. ❌ **No auto-save**: Answers stored only in browser form (lost on reload)
3. ❌ **No resume capability**: Page reload = start over
4. ❌ **Client-side timer**: Can be manipulated, not persistent
5. ❌ **No session state**: No way to identify and restore quiz progress
6. ❌ **No concurrency protection**: Multiple attempts possible, no state isolation

---

## Required Schema Additions

### Table 1: `quiz_attempts`

**Purpose**: Track each quiz attempt session with complete state persistence.

**Why Required**:
- Current system has no concept of a "quiz attempt" - it only knows about users and final responses
- Need to uniquely identify each quiz session (attempt_id)
- Need to track quiz metadata (role, level, question_ids)
- Need to track attempt status (in_progress, submitted, expired)
- Need server-side timer tracking (start_time, remaining_time_seconds, last_activity_time)
- Need to prevent multiple concurrent attempts per user per quiz
- Need to enable resume by loading attempt_id from session

**Key Columns**:
- `attempt_id`: Unique identifier for each quiz attempt (primary key)
- `user_id`: Links to users table (foreign key)
- `role`, `level`: Quiz metadata (which quiz is being attempted)
- `status`: 'in_progress', 'submitted', 'expired' (enables state management)
- `start_time`: When quiz started (for timer calculation)
- `remaining_time_seconds`: Server-controlled remaining time (prevents manipulation)
- `last_activity_time`: Last interaction timestamp (for cleanup of abandoned attempts)
- `question_ids`: JSON array of question IDs for this attempt (restore exact question set)
- `current_question_index`: Last viewed question (resume position)

**Concurrency Handling**:
- Index on `(user_id, role, level, status)` prevents duplicate active attempts
- Index on `(user_id, status)` for fast lookup of user's active attempts
- Index on `(status, last_activity_time)` for cleanup of expired attempts
- Foreign key ensures referential integrity

---

### Table 2: `quiz_answers`

**Purpose**: Store per-question answers as they're selected (auto-save before final submission).

**Why Required**:
- Current `responses` table only stores final submitted answers (after form submit)
- Need to auto-save answers immediately when candidate selects an option
- Need to allow updates (candidate changes answer before final submit)
- Need to restore answers on page reload/resume
- Must be separate from `responses` to maintain backward compatibility
- `responses` table remains for final submitted answers (existing logic unchanged)

**Key Columns**:
- `id`: Primary key
- `attempt_id`: Links to quiz_attempts (foreign key)
- `question_id`: Which question was answered
- `selected_option`: A, B, C, D, or NULL if not answered
- `saved_at`: Timestamp of when answer was saved/updated

**Concurrency Handling**:
- **UNIQUE constraint** on `(attempt_id, question_id)` prevents duplicate answers per question
- Enables `ON DUPLICATE KEY UPDATE` for efficient answer updates
- Index on `attempt_id` for fast bulk retrieval (resume quiz)
- Index on `question_id` for analytics
- Row-level locking via unique constraint prevents race conditions

---

## Architecture Flow

### 1. Quiz Start Flow

```
Candidate logs in → quiz.php
  ↓
Check for existing active attempt:
  SELECT attempt_id FROM quiz_attempts 
  WHERE user_id = ? AND role = ? AND level = ? AND status = 'in_progress'
  ↓
If exists → Resume existing attempt
If not → Create new attempt:
  INSERT INTO quiz_attempts (user_id, role, level, question_ids, remaining_time_seconds)
  Returns attempt_id
  ↓
Store attempt_id in PHP session: $_SESSION['quiz_attempt_id'] = $attempt_id
  ↓
Load questions and render quiz UI
```

### 2. Auto-Save Answer Flow

```
Candidate selects option (radio button change event)
  ↓
JavaScript: Call save_answer.php API
  POST: { attempt_id, question_id, selected_option }
  ↓
Server (save_answer.php):
  INSERT INTO quiz_answers (attempt_id, question_id, selected_option)
  ON DUPLICATE KEY UPDATE selected_option = VALUES(selected_option)
  ↓
Update attempt activity:
  UPDATE quiz_attempts 
  SET last_activity_time = NOW() 
  WHERE attempt_id = ?
  ↓
Return success response
  ↓
JavaScript: Update UI (mark question as answered)
```

**Why ON DUPLICATE KEY UPDATE**:
- First selection: INSERT new row
- Candidate changes answer: UPDATE existing row (no duplicate)
- Efficient, atomic operation (no race conditions)

### 3. Resume Quiz Flow

```
Candidate returns (page reload / network reconnection)
  ↓
quiz.php checks session: $_SESSION['quiz_attempt_id']
  ↓
If session exists:
  SELECT * FROM quiz_attempts 
  WHERE attempt_id = ? AND status = 'in_progress'
  ↓
If attempt found:
  Load attempt state:
    - remaining_time_seconds (restore timer)
    - current_question_index (restore position)
    - question_ids (restore question set)
  ↓
  SELECT * FROM quiz_answers 
  WHERE attempt_id = ? 
  ORDER BY question_id
  ↓
  Restore answers:
    - Pre-select radio buttons based on saved answers
    - Update progress indicators
    - Restore timer from remaining_time_seconds
    - Navigate to current_question_index
  ↓
  Resume quiz from exact last state
```

### 4. Timer Sync Flow

```
Client-side timer runs (for UI display)
  ↓
Every 30 seconds: Call sync_timer.php API
  POST: { attempt_id, client_remaining_seconds }
  ↓
Server (sync_timer.php):
  SELECT remaining_time_seconds, start_time FROM quiz_attempts 
  WHERE attempt_id = ? AND status = 'in_progress'
  ↓
Calculate server-side remaining time:
  elapsed = NOW() - start_time
  server_remaining = total_time - elapsed
  ↓
If client_remaining differs significantly (>5 seconds):
  Return server_remaining (authoritative)
  Client updates timer
  ↓
Update last_activity_time:
  UPDATE quiz_attempts 
  SET last_activity_time = NOW() 
  WHERE attempt_id = ?
```

**Why Server-Side Timer**:
- Client-side timer can be manipulated (browser DevTools, JavaScript injection)
- Server calculates time based on `start_time` (immutable)
- Client syncs with server every 30 seconds
- Server time is authoritative

### 5. Final Submission Flow

```
Candidate clicks "Submit Quiz"
  ↓
JavaScript: Prevent double-submit
  ↓
Form submits to submit_quiz.php
  ↓
Server (submit_quiz.php):
  Check attempt status:
    SELECT status FROM quiz_attempts WHERE attempt_id = ?
  ↓
  If status != 'in_progress':
    Redirect to result (already submitted)
  ↓
  Lock attempt:
    UPDATE quiz_attempts 
    SET status = 'submitted', end_time = NOW() 
    WHERE attempt_id = ? AND status = 'in_progress'
  ↓
  Load all answers from quiz_answers:
    SELECT * FROM quiz_answers WHERE attempt_id = ?
  ↓
  Calculate correctness and insert into responses table:
    (Existing logic - unchanged)
    INSERT INTO responses (user_id, question_id, selected_option, is_correct)
  ↓
  Redirect to show_result.php
```

**Why Lock Attempt**:
- Prevents multiple submissions
- Atomic update (only succeeds if status = 'in_progress')
- Ensures data consistency

---

## Data Persistence Strategy

### 1. Answer Persistence

**Storage**: `quiz_answers` table
- **When**: Immediately when candidate selects option (auto-save)
- **How**: INSERT with ON DUPLICATE KEY UPDATE
- **Why**: 
  - No data loss on page reload
  - Candidate can change answers (updates existing row)
  - Fast retrieval for resume

**Final Submission**: Copy to `responses` table
- **When**: On final submit
- **Why**: Maintain backward compatibility with existing result logic
- **How**: Existing `submit_quiz.php` logic (unchanged)

### 2. Timer Persistence

**Storage**: `quiz_attempts.remaining_time_seconds`
- **When**: 
  - Initialized on quiz start (2700 seconds = 45 minutes)
  - Updated every 30 seconds (sync with server)
  - Updated on answer save (optional, for activity tracking)
- **How**: Server calculates based on `start_time`
- **Why**: 
  - Client-side timer can be manipulated
  - Server time is authoritative
  - Accurate restoration on resume

### 3. State Persistence

**Storage**: `quiz_attempts` table + PHP session
- **Session**: Stores `attempt_id` (lightweight, fast lookup)
- **Database**: Stores complete state (persistent, survives server restart)
- **Why Both**:
  - Session: Fast access, temporary
  - Database: Persistent, survives logout/reload

### 4. Position Persistence

**Storage**: `quiz_attempts.current_question_index`
- **When**: Updated when candidate navigates to different question
- **How**: JavaScript calls API on page change
- **Why**: Resume at exact last viewed question

---

## Concurrency Handling Approach

### 1. Prevent Duplicate Attempts

**Mechanism**: Application logic + database index

```sql
-- Check for existing active attempt
SELECT attempt_id FROM quiz_attempts 
WHERE user_id = ? AND role = ? AND level = ? AND status = 'in_progress'
LIMIT 1;

-- If exists: Resume
-- If not: Create new
```

**Index**: `idx_user_role_level_status` on `(user_id, role, level, status)`
- Fast lookup
- Prevents multiple active attempts per user per quiz

### 2. Answer Updates (Race Condition Prevention)

**Mechanism**: UNIQUE constraint + ON DUPLICATE KEY UPDATE

```sql
INSERT INTO quiz_answers (attempt_id, question_id, selected_option)
VALUES (?, ?, ?)
ON DUPLICATE KEY UPDATE 
  selected_option = VALUES(selected_option),
  saved_at = NOW();
```

**Why Safe**:
- UNIQUE constraint on `(attempt_id, question_id)` ensures only one row per question
- MySQL handles race condition: second INSERT becomes UPDATE
- Atomic operation (no partial writes)

### 3. Attempt Status Updates (Locking)

**Mechanism**: Conditional UPDATE with status check

```sql
-- Lock attempt on submission
UPDATE quiz_attempts 
SET status = 'submitted', end_time = NOW() 
WHERE attempt_id = ? AND status = 'in_progress';
```

**Why Safe**:
- Only succeeds if status is 'in_progress'
- Prevents double-submission
- Atomic operation

### 4. High Concurrency (1000+ Candidates)

**Database Design**:
- **InnoDB engine**: Row-level locking (not table-level)
- **Indexes on all query columns**: Fast lookups, minimal locking
- **Foreign keys with indexes**: Fast joins, referential integrity
- **UNIQUE constraints**: Prevent duplicates, enable efficient updates

**Query Optimization**:
- Use prepared statements (already in code)
- Index all WHERE clause columns
- Limit result sets (LIMIT 1 for existence checks)
- Use transactions only when necessary (MySQL autocommit is fine for single-row operations)

**Application Design**:
- Fast API endpoints (save_answer.php, sync_timer.php)
- Minimal database operations per request
- Batch operations where possible (bulk answer retrieval on resume)

---

## Failure-Safe Design

### 1. Server Restart

**Scenario**: Server restarts while candidates are taking quiz

**Solution**:
- State stored in database (not memory)
- On resume: Load attempt from database using `attempt_id` from session
- Timer recalculated from `start_time` (accurate)

### 2. Network Interruption

**Scenario**: Candidate loses internet connection

**Solution**:
- Answers auto-saved to database (not lost)
- On reconnection: Resume from last saved state
- Timer synced with server (accurate)

### 3. Abandoned Attempts

**Scenario**: Candidate starts quiz but never returns

**Solution**:
- `last_activity_time` tracks last interaction
- Cleanup job (cron) marks old attempts as 'expired':
  ```sql
  UPDATE quiz_attempts 
  SET status = 'expired' 
  WHERE status = 'in_progress' 
  AND last_activity_time < NOW() - INTERVAL 2 HOUR;
  ```

### 4. Concurrent Access (Same User, Multiple Devices)

**Scenario**: Candidate opens quiz on phone and laptop simultaneously

**Solution**:
- Only one active attempt per user per quiz (enforced by index)
- First device creates attempt
- Second device resumes same attempt (same attempt_id)
- Last activity wins (last_activity_time updated)

---

## Index Strategy for High Concurrency

### `quiz_attempts` Indexes

1. **PRIMARY KEY (attempt_id)**
   - Fast lookup by attempt ID
   - Used in all foreign key references

2. **idx_user_status (user_id, status)**
   - Fast lookup of user's active/submitted attempts
   - Used in: "Get my active attempts"

3. **idx_user_role_level_status (user_id, role, level, status)**
   - Prevents duplicate active attempts
   - Used in: "Check if attempt exists before creating"

4. **idx_status_activity (status, last_activity_time)**
   - Fast cleanup of expired/abandoned attempts
   - Used in: Cleanup cron job

5. **idx_start_time (start_time)**
   - Analytics and time-based queries
   - Used in: "Find attempts started in last hour"

### `quiz_answers` Indexes

1. **PRIMARY KEY (id)**
   - Fast lookup by answer ID
   - Standard primary key

2. **UNIQUE KEY uk_attempt_question (attempt_id, question_id)**
   - Prevents duplicate answers per question
   - Enables ON DUPLICATE KEY UPDATE
   - Fast lookup of specific answer

3. **idx_attempt_id (attempt_id)**
   - Fast bulk retrieval of all answers for an attempt
   - Used in: Resume quiz (load all answers)

4. **idx_question_id (question_id)**
   - Analytics and question-level queries
   - Used in: "How many candidates answered question X correctly"

5. **idx_saved_at (saved_at)**
   - Time-based queries
   - Used in: "Find answers saved in last minute"

---

## Data Consistency Rules

### 1. One Active Attempt Per User Per Quiz

**Rule**: A user can have only one 'in_progress' attempt for a given (role, level) combination.

**Enforcement**:
- Application logic: Check before creating new attempt
- Database index: Fast lookup to prevent duplicates
- Status check: Only resume if status = 'in_progress'

### 2. Answers Can Be Updated

**Rule**: Candidate can change answer before final submission.

**Enforcement**:
- UNIQUE constraint on `(attempt_id, question_id)`
- ON DUPLICATE KEY UPDATE allows changes
- No data loss (previous answer overwritten)

### 3. Timer Is Server-Controlled

**Rule**: Remaining time calculated server-side, not client-side.

**Enforcement**:
- `remaining_time_seconds` stored in database
- Server calculates: `remaining = total_time - (NOW() - start_time)`
- Client syncs every 30 seconds

### 4. Attempt Status Transitions

**Rule**: Status can only transition: in_progress → submitted or expired

**Enforcement**:
- Conditional UPDATE: Only update if current status = 'in_progress'
- Atomic operation prevents race conditions

### 5. Foreign Key Integrity

**Rule**: Answers must belong to valid attempts, attempts must belong to valid users.

**Enforcement**:
- Foreign key constraints with CASCADE DELETE
- Database enforces referential integrity
- No orphaned records

---

## Performance Considerations

### 1. Query Optimization

- **All frequently queried columns are indexed**
- **Prepared statements** (already in code) prevent SQL injection and enable query caching
- **LIMIT clauses** used for existence checks (fast)
- **SELECT only required columns** (not SELECT *)

### 2. Write Optimization

- **ON DUPLICATE KEY UPDATE** for answer saves (single query, not INSERT + UPDATE)
- **Batch operations** where possible (bulk answer retrieval)
- **Minimal columns updated** (only changed fields)

### 3. Storage Optimization

- **Enum types** for status/options (efficient storage, fast comparisons)
- **INT for IDs** (4 bytes, fast)
- **TEXT for question_ids JSON** (acceptable for 50 IDs, ~200 bytes)
- **TIMESTAMP with ON UPDATE** (automatic, no application logic needed)

### 4. Concurrency Optimization

- **InnoDB engine** (row-level locking, not table-level)
- **Indexes reduce lock contention** (fast lookups, minimal rows locked)
- **Foreign keys have indexes** (MySQL requirement, also improves joins)

---

## Summary

### What Was Added

1. **`quiz_attempts` table**: Tracks quiz sessions with state persistence
2. **`quiz_answers` table**: Stores per-question answers (auto-save)
3. **Indexes**: Optimized for high concurrency (1000+ candidates)
4. **Foreign keys**: Ensure referential integrity

### What Was NOT Changed

- ✅ No existing tables modified
- ✅ No existing columns removed or renamed
- ✅ No existing constraints altered
- ✅ Existing `users` table unchanged
- ✅ Existing `responses` table unchanged (still used for final submission)
- ✅ Existing question tables unchanged

### Next Steps (Application Code - Not Included)

The following endpoints/logic need to be added (but are NOT part of this schema document):

1. **save_answer.php**: Auto-save API endpoint
2. **resume_quiz.php**: Resume quiz API endpoint
3. **sync_timer.php**: Timer sync API endpoint
4. **quiz.php modifications**: Check for existing attempt, restore state
5. **submit_quiz.php modifications**: Mark attempt as submitted
6. **JavaScript modifications**: Auto-save on radio change, timer sync

---

## SQL File

All SQL statements are provided in: **`SCHEMA_ADDITIONS_FOR_SESSION_SAFE_QUIZ.sql`**

**Usage**:
1. Open phpMyAdmin
2. Select database: `all_assessment_quiz`
3. Go to SQL tab
4. Copy and paste SQL from the file
5. Execute

**Result**: New tables created, ready for application code integration.

---

**END OF ARCHITECTURE DOCUMENT**

