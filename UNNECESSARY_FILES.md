# 🗑️ Unnecessary Files in All-Assessment-Quiz Project

## Files That Can Be Safely Deleted

### ❌ **Legacy API Files (Not Used in Main Application Flow)**

These files query a generic `questions` table that **doesn't exist** in the current database schema. The application uses role-specific tables instead.

1. **`get_questions.php`**
   - **Reason:** Queries non-existent `questions` table
   - **Replacement:** Questions are fetched directly in `quiz.php` using role-specific tables
   - **Status:** ❌ Unused

2. **`get_results.php`**
   - **Reason:** Queries non-existent `questions` table
   - **Replacement:** Results are shown via `show_result.php` which uses role-specific tables
   - **Status:** ❌ Unused

3. **`submit_answers.php`**
   - **Reason:** Queries non-existent `questions` table, uses insecure direct queries (SQL injection risk)
   - **Replacement:** `submit_quiz.php` handles submissions with proper prepared statements
   - **Status:** ❌ Unused + Security Risk

4. **`submit_user.php`**
   - **Reason:** Uses `position` field instead of `role` and `level`, not compatible with current schema
   - **Replacement:** User registration is handled directly in `quiz.php` via POST from `index.php`
   - **Status:** ❌ Unused + Schema Mismatch

5. **`replace_questions.php`**
   - **Reason:** Queries non-existent generic `questions` table
   - **Note:** This appears to be a migration script for an old schema
   - **Status:** ❌ Unused (Old Migration Script)

---

### 🔧 **Development/Utility Files (Not Needed in Production)**

6. **`check_php.php`**
   - **Reason:** Utility file for debugging PHP configuration (mysqli extension check)
   - **Usage:** Only needed during initial setup/troubleshooting
   - **Status:** ⚠️ Development Only (Can be deleted after setup)

---

### 🖥️ **Server Configuration Files (Not Needed for XAMPP)**

These files are only needed if running PHP built-in server on a VPS, not for XAMPP local development.

7. **`router.php`**
   - **Reason:** PHP built-in server router, only used with `php -S` command
   - **Usage:** Not needed for Apache/XAMPP setup
   - **Status:** ⚠️ VPS Only (Not needed for XAMPP)

8. **`start-server.sh`**
   - **Reason:** Bash script to start PHP built-in server on Linux VPS
   - **Usage:** Only for Ubuntu/Linux VPS deployment
   - **Status:** ⚠️ VPS Only (Not needed for XAMPP/Windows)

9. **`quiz-api.service`**
   - **Reason:** Systemd service file for Linux VPS
   - **Usage:** Only for production Linux server deployment
   - **Status:** ⚠️ VPS Only (Not needed for XAMPP/Windows)

---

### 📦 **Database Files (Potential Redundancy)**

10. **`all_assessment_quiz_new.sql`**
    - **Reason:** Appears to be a backup or alternate version
    - **Action:** Verify if this is newer than `all_assessment_quiz.sql` before deleting
    - **Status:** ⚠️ Verify First (May be redundant)

11. **`flutter_mcq_questions.sql`**
    - **Reason:** Separate SQL file for Flutter questions
    - **Action:** Check if Flutter questions are already in main SQL file
    - **Status:** ⚠️ Verify First (May be redundant)

---

### 📁 **Entire Subdirectory (Separate/Legacy Project)**

12. **`backend_developer_quiz/`** (Entire Directory)
    - **Reason:** Appears to be a separate/legacy quiz module focused on C language
    - **Contains:** Duplicate files (quiz.php, submit_quiz.php, etc.) with different logic
    - **Status:** ⚠️ Separate Project (May be intentionally separate)

---

## 📋 Summary

### **Safe to Delete Immediately:**
1. ✅ `get_questions.php`
2. ✅ `get_results.php`
3. ✅ `submit_answers.php`
4. ✅ `submit_user.php`
5. ✅ `replace_questions.php`

### **Delete After Setup (Development Files):**
6. ✅ `check_php.php` (after confirming mysqli works)

### **Delete if Using XAMPP Only:**
7. ✅ `router.php`
8. ✅ `start-server.sh`
9. ✅ `quiz-api.service`

### **Verify Before Deleting:**
10. ⚠️ `all_assessment_quiz_new.sql` (check if it's newer)
11. ⚠️ `flutter_mcq_questions.sql` (check if already in main SQL)

### **Keep if Separate Project:**
12. ⚠️ `backend_developer_quiz/` (verify if this is intentionally separate)

---

## 🎯 Recommended Action

**For Clean Production Deployment:**
- Delete items 1-5 immediately (legacy API files)
- Delete items 7-9 if using Apache/XAMPP (server config files)
- Keep `check_php.php` for troubleshooting, or delete after setup
- Verify items 10-11 before deleting
- Decide on `backend_developer_quiz/` based on project requirements

**Total Files That Can Be Deleted:** ~9-12 files

---

## ⚠️ Important Notes

1. **Backup First:** Always backup before deleting files
2. **Test After Deletion:** Ensure application still works correctly
3. **Version Control:** If using Git, these deletions can be reverted if needed
4. **Documentation:** Update `README.md` if it references deleted files

---

*Generated: 2024*

