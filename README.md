# All Assessment Quiz - Localhost Setup Guide

## Prerequisites
- XAMPP installed on Windows
- Web browser (Chrome, Firefox, Edge, etc.)

## Step-by-Step Setup Instructions

### 1. Start XAMPP Services

1. Open **XAMPP Control Panel**
2. Start **Apache** (click "Start" button)
3. Start **MySQL** (click "Start" button)
4. Both services should show green "Running" status

### 2. Create Database

1. Open your web browser
2. Go to: `http://localhost/phpmyadmin`
3. Click on **"New"** in the left sidebar to create a new database
4. Database name: `all_assessment_quiz`
5. Collation: `utf8mb4_general_ci` (or leave default)
6. Click **"Create"**

### 3. Import Database

1. In phpMyAdmin, select the `all_assessment_quiz` database (click on it in left sidebar)
2. Click on **"Import"** tab at the top
3. Click **"Choose File"** button
4. Navigate to: `C:\xampp\htdocs\All-Assessment-Quiz\all_assessment_quiz.sql`
5. Click **"Go"** or **"Import"** button at the bottom
6. Wait for the import to complete (you should see a success message)

### 4. Verify Database Configuration

The application is already configured for XAMPP default settings:
- **Host:** `localhost`
- **Username:** `root`
- **Password:** (empty - default XAMPP)
- **Database:** `all_assessment_quiz`

If your XAMPP MySQL has a different password, edit `config.php` and update the credentials.

### 5. Access the Application

1. Open your web browser
2. Go to: `http://localhost/All-Assessment-Quiz/`
3. You should see the quiz registration form

## Troubleshooting

### Error: "Database connection failed"
- **Solution:** Make sure MySQL is running in XAMPP Control Panel
- Check that the database `all_assessment_quiz` exists
- Verify credentials in `config.php` match your XAMPP MySQL settings

### Error: "Access denied for user 'root'@'localhost'"
- **Solution:** 
  - If you set a MySQL password, update `config.php`:
    ```php
    $db_pass = 'your_password_here';
    ```
  - Or reset MySQL password in XAMPP to empty (default)

### Error: "No questions found"
- **Solution:** Make sure you imported the SQL file completely
- Check in phpMyAdmin that tables like `backend_mcq_questions`, `python_mcq_questions`, etc. exist and have data

### Page shows "500 Internal Server Error"
- **Solution:** 
  - Check Apache error log: `C:\xampp\apache\logs\error.log`
  - Make sure PHP is enabled in XAMPP
  - Check file permissions (files should be readable)

### Error: "The mysqli extension is missing" or "Class mysqli not found"
- **Solution:** Enable mysqli extension in PHP:
  
  **Step 1: Find the correct php.ini file**
  - Open XAMPP Control Panel
  - Click **"Config"** button next to Apache
  - Select **"PHP (php.ini)"** from the dropdown
  - The php.ini file will open in Notepad
  - **Alternative:** Navigate to `C:\xampp\php\php.ini` and open with Notepad (Run as Administrator if needed)
  
  **Step 2: Enable mysqli extension**
  1. In the php.ini file, press **Ctrl+F** to search for: `extension=mysqli`
  2. You might find multiple lines. Look for:
     - `;extension=mysqli` (with semicolon - DISABLED)
     - `extension=mysqli` (without semicolon - ENABLED)
  3. If you see `;extension=mysqli`, remove the semicolon (`;`) at the beginning
  4. It should now be: `extension=mysqli` (no semicolon)
  5. **Important:** Also check for `extension_dir` - it should point to your extensions folder:
     - Look for: `extension_dir = "ext"` or `extension_dir = "C:\xampp\php\ext"`
  6. Save the file (Ctrl+S)
  7. Close Notepad
  
  **Step 3: Restart Apache**
  1. In XAMPP Control Panel, click **"Stop"** on Apache
  2. Wait 2-3 seconds
  3. Click **"Start"** on Apache
  4. Make sure it shows "Running" in green
  
  **Step 4: Verify**
  - Try accessing the application: `http://localhost/All-Assessment-Quiz/`
  - If still not working, check the error log: `C:\xampp\apache\logs\error.log`

  **If extension DLL is missing:**
  - Check if `C:\xampp\php\ext\php_mysqli.dll` exists
  - If missing, you may need to reinstall XAMPP or download the DLL file

### Can't access phpMyAdmin
- **Solution:** 
  - Make sure Apache and MySQL are both running
  - Try: `http://127.0.0.1/phpmyadmin` instead
  - Check if port 80 is not being used by another application

## File Structure

```
All-Assessment-Quiz/
├── index.php                  # Registration page (entry point)
├── quiz.php                   # Quiz interface (50 questions, 45-min timer)
├── submit_quiz.php             # Quiz submission handler
├── show_result.php             # Results page
├── check_user_attempt.php     # AJAX duplicate check endpoint
├── db.php                      # Database connection handler
├── config.php                  # Environment-aware configuration
├── all_assessment_quiz.sql     # Main database dump
├── assets/
│   └── app.css                 # Stylesheet (design system)
├── admin_view.php              # Admin: View all user submissions
├── admin_result.php            # Admin: Individual user results
├── admin_result_server.php     # Admin: API endpoint
├── unused_files/               # Legacy/unused files (can be deleted)
│   ├── get_questions.php      # (Legacy - not used)
│   ├── get_results.php        # (Legacy - not used)
│   ├── submit_answers.php      # (Legacy - not used)
│   ├── submit_user.php         # (Legacy - not used)
│   ├── replace_questions.php  # (Legacy - not used)
│   ├── check_php.php           # (Development utility)
│   ├── router.php              # (VPS only - not needed for XAMPP)
│   ├── start-server.sh         # (VPS only - not needed for XAMPP)
│   └── quiz-api.service        # (VPS only - not needed for XAMPP)
└── website-flow.html           # Visual flow diagram
```

## Default Access URLs

- **Home/Registration:** `http://localhost/All-Assessment-Quiz/`
- **phpMyAdmin:** `http://localhost/phpmyadmin`

## Notes

- The application uses session management, so cookies must be enabled
- Right-click and developer tools are disabled for quiz security
- Quiz timer is set to **45 minutes** (2700 seconds, configurable in quiz.php)
- All user data and quiz responses are stored in the MySQL database
- The application supports 5 developer roles: Backend, Python, Flutter, MERN, Full Stack
- Each quiz contains 50 randomly selected questions based on role and level
- Questions are paginated (1 question per page) for better user experience
- Dark mode is available in the quiz interface

## Application Flow

The application follows this flow:
1. **Registration** (`index.php`) - User enters details (name, role, level, place, phone, email)
2. **Validation** (`check_user_attempt.php`) - AJAX check for duplicate attempts
3. **Quiz** (`quiz.php`) - 50 questions with 45-minute timer, pagination, progress tracking
4. **Submission** (`submit_quiz.php`) - Processes answers and saves to database
5. **Results** (`show_result.php`) - Displays completion confirmation

**Admin Flow:**
- `admin_view.php` - View all user submissions
- `admin_result.php` - View individual user results
- `admin_result_server.php` - API endpoint for admin data

For a visual representation, open `website-flow.html` in your browser.

## Support

If you encounter any issues:
1. Check XAMPP Control Panel - both services should be running
2. Check Apache error logs: `C:\xampp\apache\logs\error.log`
3. Check PHP error logs: `C:\xampp\php\logs\php_error_log`
4. Verify database connection in phpMyAdmin
5. Ensure all required tables exist in the database (users, responses, and role-specific question tables)

