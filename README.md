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
  - First, check which php.ini is being used:
    - Create a file `check_php.php` in your project folder (already created for you)
    - Visit: `http://localhost/All-Assessment-Quiz/check_php.php`
    - It will show you the exact php.ini file location
  
  **Step 2: Enable mysqli extension**
  1. Open XAMPP Control Panel
  2. Click **"Config"** button next to Apache
  3. Select **"PHP (php.ini)"** from the dropdown
  4. The php.ini file will open in Notepad
  5. Press **Ctrl+F** to search for: `extension=mysqli`
  6. You might find multiple lines. Look for:
     - `;extension=mysqli` (with semicolon - DISABLED)
     - `extension=mysqli` (without semicolon - ENABLED)
  7. If you see `;extension=mysqli`, remove the semicolon (`;`) at the beginning
  8. It should now be: `extension=mysqli` (no semicolon)
  9. **Important:** Also check for `extension_dir` - it should point to your extensions folder:
     - Look for: `extension_dir = "ext"` or `extension_dir = "C:\xampp\php\ext"`
  10. Save the file (Ctrl+S)
  11. Close Notepad
  
  **Step 3: Restart Apache**
  1. In XAMPP Control Panel, click **"Stop"** on Apache
  2. Wait 2-3 seconds
  3. Click **"Start"** on Apache
  4. Make sure it shows "Running" in green
  
  **Step 4: Verify**
  - Visit: `http://localhost/All-Assessment-Quiz/check_php.php`
  - It should now show "mysqli Extension: ✓ LOADED" in green
  - If still not loaded, check the error log: `C:\xampp\apache\logs\error.log`

  **Alternative method (if Config button doesn't work):**
  - Navigate to: `C:\xampp\php\php.ini`
  - Open with Notepad (Run as Administrator if you can't save)
  - Search for `extension=mysqli`
  - Remove the semicolon if present
  - Save and restart Apache

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
├── index.php              # Registration page (entry point)
├── quiz.php               # Quiz interface
├── db.php                 # Database connection
├── config.php             # Configuration file
├── all_assessment_quiz.sql # Database dump
├── submit_quiz.php        # Quiz submission handler
├── show_result.php        # Results page
└── ... (other files)
```

## Default Access URLs

- **Home/Registration:** `http://localhost/All-Assessment-Quiz/`
- **phpMyAdmin:** `http://localhost/phpmyadmin`

## Notes

- The application uses session management, so cookies must be enabled
- Right-click and developer tools are disabled for quiz security
- Quiz timer is set to 60 minutes (configurable in quiz.php)
- All user data and quiz responses are stored in the MySQL database

## Support

If you encounter any issues:
1. Check XAMPP Control Panel - both services should be running
2. Check Apache error logs: `C:\xampp\apache\logs\error.log`
3. Check PHP error logs: `C:\xampp\php\logs\php_error_log`
4. Verify database connection in phpMyAdmin

