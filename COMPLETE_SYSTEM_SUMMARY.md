# Complete System Summary - All Assessment Quiz

## 📋 Table of Contents
1. [User Flow Overview](#user-flow-overview)
2. [Scenario-Based Logic Breakdown](#scenario-based-logic-breakdown)
3. [Database States & Status Flow](#database-states--status-flow)
4. [Security & Validation Checks](#security--validation-checks)
5. [Timer Management](#timer-management)
6. [Answer Saving Mechanism](#answer-saving-mechanism)
7. [Modal Scenarios](#modal-scenarios)
8. [Potential Missing Logic / Edge Cases](#potential-missing-logic--edge-cases)

---

## 🎯 User Flow Overview

### Entry Point: `index.php`
- User fills registration form (name, email, mobile, role, level, location)
- **Client-side validation**: AJAX call to `check_user_attempt.php` before form submit
- **Purpose**: Prevent duplicate attempts early (UX improvement)

### Main Quiz Page: `quiz.php`
- Handles **TWO request types**:
  1. **GET Request**: Resume existing quiz (page reload, browser back)
  2. **POST Request**: Start new quiz (form submission from index.php)

### Submission: `submit_quiz.php`
- Processes quiz submission
- Calculates scores
- Redirects to `show_result.php`

### Timer Sync: `sync_timer.php`
- Server-side timer synchronization (every 30 seconds)
- Auto-expires attempts when time runs out

---

## 🔄 Scenario-Based Logic Breakdown

### **SCENARIO 1: New User Starting Quiz (POST Request)**

**Flow:**
1. User submits form from `index.php`
2. `quiz.php` receives POST request
3. **Validation checks:**
   - Role and level are provided
   - Phone number: exactly 10 digits, starts with 6/7/8/9
   - Email: valid format
4. **User lookup:**
   - Check if user exists by email OR mobile (case-insensitive)
5. **If user NOT found:**
   - Create new user in `users` table
   - Generate new `user_id`
6. **If user EXISTS:**
   - Check if user has ANY quiz attempt (in_progress, submitted, or expired)
   - **If attempt exists → Show "Already Attempted" modal → Block**
   - **If no attempt → Continue**
   - **Location update:** If location is different, update `users.place` field
7. **In-progress check:**
   - Check if user has `in_progress` attempt for same role+level
   - **If found:**
     - Verify credentials match (name, email, mobile, role, level)
     - **If mismatch → Show "In-Progress Quiz Found" modal with correct credentials → Block**
     - **If match → Resume existing attempt**
8. **Create/Resume attempt:**
   - If no in-progress attempt → Create new `quiz_attempts` record
   - If in-progress exists → Use existing `attempt_id`
9. **Question selection:**
   - Fetch 50 random questions from role-specific table
   - Filter by level (handles both 'advanced' and 'advance')
   - Fallback: If no questions with role filter, try without role filter
10. **Session setup:**
    - Store `quiz_user_id`, `quiz_role`, `quiz_level`, `quiz_name`, `quiz_mobile` in `$_SESSION`
    - Store `quiz_attempt_id` in `$_SESSION`
11. **Render quiz page** with questions and timer

**Key Logic Points:**
- ✅ Prevents duplicate attempts (checks both `responses` and `quiz_attempts` tables)
- ✅ Allows location update without blocking
- ✅ Prevents role/level overwrite (credential matching)
- ✅ Handles browser close/re-login scenario

---

### **SCENARIO 2: User Resuming Quiz (GET Request)**

**Flow:**
1. User reloads page or navigates back to `quiz.php`
2. `quiz.php` receives GET request
3. **Session check:**
   - Verify `$_SESSION['quiz_attempt_id']` and `$_SESSION['quiz_user_id']` exist
   - **If missing → Redirect to `index.php`**
4. **Attempt verification:**
   - Fetch attempt from `quiz_attempts` table
   - Verify `status = 'in_progress'`
   - Verify `user_id` matches session
   - **If not found or wrong status → Redirect to `index.php`**
5. **Role/Level verification:**
   - Compare session role/level with attempt role/level
   - **If mismatch → Redirect to `index.php`**
6. **CRITICAL: Credential matching:**
   - Fetch user details from `users` table
   - Compare session credentials (name, mobile, role, level) with database
   - **If mismatch → Show "Credentials Mismatch" modal → Redirect to `index.php` with pre-filled form**
7. **Question loading:**
   - Load questions from `question_ids` JSON in attempt
   - Fetch question details from role-specific table
   - Load saved answers from `quiz_answers` table
8. **Timer calculation:**
   - Calculate remaining time from `expires_at` (server-controlled)
   - Fallback: Calculate from `start_time` if `expires_at` is missing
9. **Render quiz page** with saved state

**Key Logic Points:**
- ✅ Prevents unauthorized access (session + database verification)
- ✅ Prevents credential tampering (name, mobile, role, level must match)
- ✅ Restores exact quiz state (questions, answers, timer)
- ✅ Server-controlled timer (cannot be manipulated)

---

### **SCENARIO 3: User Already Attempted (Duplicate Prevention)**

**Trigger Points:**
1. **Client-side (index.php):** AJAX call to `check_user_attempt.php` before form submit
2. **Server-side (quiz.php POST):** Check after user lookup

**Logic:**
- Check `responses` table: `COUNT(*) WHERE user_id = ?`
- Check `quiz_attempts` table: `COUNT(*) WHERE user_id = ?` (any status)
- **If either > 0 → Block attempt**

**User Experience:**
- Client-side: Shows inline error message in form
- Server-side: Shows "Already Attempted" modal → Redirects to `index.php`

**Key Logic Points:**
- ✅ Blocks at multiple points (defense in depth)
- ✅ Checks both legacy `responses` table and new `quiz_attempts` table
- ✅ Prevents attempts from different browsers/devices

---

### **SCENARIO 4: In-Progress Quiz with Credential Mismatch**

**When it occurs:**
- User started quiz with Role A, Level B
- User tries to login again with same email/mobile but different Role/Level
- OR user tries to login with different name/email/mobile

**Flow:**
1. User lookup finds existing user
2. System finds `in_progress` attempt
3. **Credential comparison:**
   - Compare form data (name, email, mobile, role, level) with:
     - `users` table data
     - `quiz_attempts` table data (role, level)
4. **If mismatch:**
   - Show "In-Progress Quiz Found" modal
   - Display correct credentials from database
   - Pre-fill form on redirect (via URL parameters)
   - **Block new attempt**

**Key Logic Points:**
- ✅ Prevents question overwrite (different role/level)
- ✅ Prevents data corruption (name/email mismatch)
- ✅ User-friendly: Shows what credentials to use
- ✅ Form pre-fill: Better UX

---

### **SCENARIO 5: Timer Expires (Auto-Submit)**

**Flow:**
1. **Client-side timer:**
   - JavaScript countdown runs every second
   - When `timeLeft <= 0` → Call `showAutoSubmitModal()`
   - Modal shows "Time is Up" message
   - After 1 second → Auto-submit form
2. **Server-side timer sync:**
   - `sync_timer.php` called every 30 seconds
   - Calculates remaining time from `expires_at` (server-controlled)
   - **If `server_remaining_seconds <= 0`:**
     - Update `quiz_attempts.status = 'expired'`
     - Return `expired: true` in JSON
   - Client receives `expired: true` → Show modal → Auto-submit
3. **Submission:**
   - `submit_quiz.php` accepts both `in_progress` and `expired` status
   - Marks attempt as `submitted`
   - Processes answers and calculates score

**Key Logic Points:**
- ✅ Server-controlled timer (cannot be manipulated)
- ✅ Dual check: Client + Server
- ✅ Custom modal (no "localhost says" alert)
- ✅ Graceful auto-submit

---

### **SCENARIO 6: Manual Quiz Submission**

**Flow:**
1. User clicks "Submit Quiz" button
2. Form submits to `submit_quiz.php`
3. **Validation:**
   - Verify `attempt_id` exists
   - Verify `user_id` matches attempt
   - Verify status is `in_progress` OR `expired`
4. **Atomic lock:**
   - `UPDATE quiz_attempts SET status = 'submitted' WHERE attempt_id = ? AND (status = 'in_progress' OR status = 'expired')`
   - **If `affected_rows = 0` → Already submitted → Redirect to result**
5. **Answer processing:**
   - Load answers from `quiz_answers` table (more reliable than POST)
   - For each question:
     - Check if answered
     - Compare with `correct_option` from question table
     - Insert into `responses` table with `is_correct` flag
6. **Redirect to `show_result.php`**

**Key Logic Points:**
- ✅ Atomic operation (prevents double submission)
- ✅ Uses `quiz_answers` table (not POST data) for reliability
- ✅ Handles both answered and unanswered questions
- ✅ Race condition protection

---

### **SCENARIO 7: Answer Auto-Save**

**Flow:**
1. User selects an option (radio button)
2. JavaScript triggers `saveAnswer()` function
3. **AJAX call to `save_answer.php`:**
   - Sends `attempt_id`, `question_id`, `selected_option`
4. **Server-side (`save_answer.php`):**
   - Upsert into `quiz_answers` table
   - `INSERT ... ON DUPLICATE KEY UPDATE`
   - Updates `current_question_index` in `quiz_attempts`
5. **Success response:** Updates UI (question marked as answered)

**Key Logic Points:**
- ✅ Real-time save (no data loss on browser close)
- ✅ Upsert pattern (handles answer changes)
- ✅ Tracks current question index

---

### **SCENARIO 8: Location Update**

**When it occurs:**
- Existing user logs in with different location (but same email/mobile)

**Flow:**
1. User lookup finds existing user
2. **Before checking in-progress attempts:**
   - Compare new location with existing `users.place`
   - **If different → Update `users.place = new_location`**
3. Continue with normal flow

**Key Logic Points:**
- ✅ Only location can be updated (other fields locked)
- ✅ Update happens before credential checks
- ✅ Admin page shows updated location

---

## 📊 Database States & Status Flow

### `quiz_attempts.status` Values:

1. **`in_progress`**
   - **When:** Quiz is active, user is answering
   - **Can transition to:**
     - `submitted` (manual or auto-submit)
     - `expired` (timer runs out, then `submitted`)

2. **`expired`**
   - **When:** Timer reached 0, but quiz not yet submitted
   - **Can transition to:**
     - `submitted` (when `submit_quiz.php` processes it)

3. **`submitted`**
   - **When:** Quiz is complete, answers processed
   - **Final state:** No further transitions

### Status Transition Diagram:
```
in_progress ──[Timer expires]──> expired ──[Auto-submit]──> submitted
     │                                              │
     └──────────[Manual submit]───────────────────┘
```

---

## 🔒 Security & Validation Checks

### 1. **Session Security**
- ✅ Session regeneration on quiz start (`session_regenerate_id(true)`)
- ✅ Session variables verified against database
- ✅ Session timeout handled (redirects to index.php)

### 2. **Credential Verification**
- ✅ Name, email, mobile, role, level must match
- ✅ Case-insensitive comparison (handles typos)
- ✅ Trimmed comparison (handles whitespace)

### 3. **User Authorization**
- ✅ `user_id` must match attempt owner
- ✅ Attempt must belong to current session user
- ✅ Role/level must match attempt

### 4. **Input Validation**
- ✅ Phone: 10 digits, starts with 6/7/8/9
- ✅ Email: Valid format
- ✅ Role: Must be from allowed list
- ✅ Level: Must be from allowed list

### 5. **SQL Injection Prevention**
- ✅ All queries use prepared statements
- ✅ Parameter binding for all user inputs

### 6. **XSS Prevention**
- ✅ `htmlspecialchars()` on all outputs
- ✅ URL encoding for parameters

---

## ⏱️ Timer Management

### Server-Controlled Timer (Authoritative)
- **Source of truth:** `quiz_attempts.expires_at` (calculated from `start_time + duration_minutes`)
- **Calculation:** `remaining_time = expires_at - NOW()`
- **Why:** Prevents client-side manipulation

### Client-Side Timer (Display Only)
- **Purpose:** Smooth countdown display
- **Sync:** Every 30 seconds with `sync_timer.php`
- **Correction:** If difference > 5 seconds, client timer updates

### Timer Sync Flow:
1. Client sends `attempt_id` and `client_remaining_seconds` to `sync_timer.php`
2. Server calculates `server_remaining_seconds` from `expires_at`
3. **If expired:**
   - Update `status = 'expired'`
   - Return `expired: true`
4. **If not expired:**
   - Return `remaining_seconds` and `needs_correction` flag
   - Client updates display if needed

---

## 💾 Answer Saving Mechanism

### Tables Involved:
1. **`quiz_answers`** (Real-time auto-save)
   - Stores: `attempt_id`, `question_id`, `selected_option`
   - Updated on every answer change
   - Used for quiz restoration

2. **`responses`** (Final submission)
   - Stores: `user_id`, `question_id`, `selected_option`, `is_correct`
   - Created only on quiz submission
   - Used for result calculation

### Answer Flow:
```
User selects option
    ↓
Auto-save to quiz_answers (via AJAX)
    ↓
Quiz submission
    ↓
Read from quiz_answers (not POST)
    ↓
Calculate is_correct
    ↓
Insert into responses
```

**Why `quiz_answers` is authoritative:**
- More reliable than POST data
- Handles browser close scenarios
- Prevents data loss

---

## 🎭 Modal Scenarios

### 1. **"Credentials Mismatch" Modal**
- **When:** GET request resume, credentials don't match database
- **Action:** Shows correct credentials, redirects to index.php with pre-filled form
- **File:** `quiz.php` lines 132-171

### 2. **"Already Attempted" Modal**
- **When:** User tries to start quiz but has existing attempt
- **Action:** Blocks attempt, redirects to index.php
- **File:** `quiz.php` lines 360-389

### 3. **"In-Progress Quiz Found" Modal**
- **When:** POST request, in-progress attempt exists, credentials mismatch
- **Action:** Shows correct credentials, redirects to index.php with pre-filled form
- **File:** `quiz.php` lines 444-483

### 4. **"Time is Up" Modal (Auto-Submit)**
- **When:** Timer reaches 0
- **Action:** Shows message, auto-submits after 1 second
- **File:** `quiz.php` lines 794-812, function `showAutoSubmitModal()`

### 5. **"Warning: Progress Will Be Lost" Modal**
- **When:** User tries to navigate away (context menu, shortcuts)
- **Action:** Informs user that progress is auto-saved
- **File:** `quiz.php` lines 774-792

---

## ⚠️ Potential Missing Logic / Edge Cases

### 1. **Concurrent Browser Sessions**
- **Current:** User can only have one `in_progress` attempt per role+level
- **Missing:** What if user opens quiz in two browsers simultaneously?
  - **Current behavior:** Second browser might create new attempt or resume existing
  - **Potential issue:** Race condition on attempt creation
  - **Recommendation:** Add `UNIQUE` constraint or lock mechanism

### 2. **Session Expiry**
- **Current:** Session variables checked, but no explicit session timeout
- **Missing:** What if PHP session expires but quiz is in progress?
  - **Current behavior:** User redirected to index.php
  - **Potential issue:** User loses progress if session expires
  - **Recommendation:** Extend session lifetime or handle gracefully

### 3. **Network Interruption**
- **Current:** Auto-save via AJAX, but no retry mechanism
- **Missing:** What if network fails during auto-save?
  - **Current behavior:** Answer might not save
  - **Potential issue:** Data loss
  - **Recommendation:** Implement retry logic or offline queue

### 4. **Question Availability**
- **Current:** Fallback removes role filter if no questions found
- **Missing:** What if table has < 50 questions for a level?
  - **Current behavior:** Shows available questions (might be < 50)
  - **Potential issue:** Inconsistent quiz length
  - **Recommendation:** Validate question count before quiz start

### 5. **Timer Edge Cases**
- **Current:** Server timer is authoritative
- **Missing:** What if server time changes (DST, manual adjustment)?
  - **Current behavior:** Timer might be incorrect
  - **Potential issue:** Unfair time allocation
  - **Recommendation:** Use UTC or handle timezone changes

### 6. **Database Connection Failure**
- **Current:** No explicit error handling for DB connection loss
- **Missing:** What if database goes down during quiz?
  - **Current behavior:** PHP fatal error
  - **Potential issue:** User loses progress
  - **Recommendation:** Implement connection retry and graceful degradation

### 7. **Large Concurrent Load**
- **Current:** `ORDER BY RAND()` used for question selection
- **Missing:** Performance under 20-25 concurrent users
  - **Current behavior:** Might be slow (identified earlier)
  - **Potential issue:** Blank screens, timeouts
  - **Recommendation:** Optimize query or pre-generate question sets

### 8. **Answer Validation**
- **Current:** Answers saved as-is from client
- **Missing:** What if client sends invalid option (e.g., 'E' or 'Z')?
  - **Current behavior:** Saved but might cause issues in result calculation
  - **Potential issue:** Data corruption
  - **Recommendation:** Validate option is A/B/C/D before saving

### 9. **Expired Attempt Cleanup**
- **Current:** Expired attempts remain in database
- **Missing:** What if user tries to resume expired attempt?
  - **Current behavior:** Attempt not found (status != 'in_progress')
  - **Potential issue:** User confusion
  - **Recommendation:** Show message explaining quiz expired

### 10. **Admin Access Control**
- **Current:** No authentication for admin pages
- **Missing:** What if unauthorized user accesses admin pages?
  - **Current behavior:** Anyone can view results
  - **Potential issue:** Privacy breach
  - **Recommendation:** Add admin authentication

---

## 📝 Summary Checklist

### ✅ Implemented Features:
- [x] New user registration
- [x] Existing user lookup
- [x] Duplicate attempt prevention
- [x] In-progress quiz resume
- [x] Credential matching and validation
- [x] Location update
- [x] Server-controlled timer
- [x] Auto-save answers
- [x] Auto-submit on timeout
- [x] Manual quiz submission
- [x] Custom modals (no browser alerts)
- [x] Form pre-fill on redirect
- [x] Session management
- [x] SQL injection prevention
- [x] XSS prevention

### ⚠️ Potential Improvements:
- [ ] Concurrent session handling
- [ ] Network retry mechanism
- [ ] Question count validation
- [ ] Answer option validation
- [ ] Expired attempt user messaging
- [ ] Admin authentication
- [ ] Performance optimization (`ORDER BY RAND()`)
- [ ] Database connection error handling
- [ ] Session timeout handling
- [ ] Timezone change handling

---

## 🔍 Quick Reference: File Responsibilities

| File | Purpose | Key Logic |
|------|---------|-----------|
| `index.php` | Registration form | Client-side duplicate check, form pre-fill |
| `quiz.php` | Quiz display & logic | GET (resume) / POST (new), credential checks, question loading |
| `submit_quiz.php` | Quiz submission | Atomic lock, answer processing, score calculation |
| `sync_timer.php` | Timer synchronization | Server-calculated remaining time, auto-expire |
| `save_answer.php` | Answer auto-save | Upsert to `quiz_answers` table |
| `check_user_attempt.php` | Duplicate check (AJAX) | Check `responses` and `quiz_attempts` tables |
| `show_result.php` | Result display | Calculate and display score |
| `admin_view.php` | Admin dashboard | List all submissions |

---

**Last Updated:** Based on current codebase analysis
**Version:** 1.0

