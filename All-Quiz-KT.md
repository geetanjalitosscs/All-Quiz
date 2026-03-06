# Knowledge Transfer (KT) Document
## All Assessment Quiz - Toss Consultancy Services

---

**Document Version:** 1.0  
**Project Name:** All Assessment Quiz  
**Project Type:** Web Application (Assessment/Quiz System)  
**Tech Stack:** PHP 7.4+, MySQL 5.7+, JavaScript (ES6), HTML5, CSS3, XAMPP (Local), VPS (Production)
**Deployment Environment:** XAMPP (Local Development), VPS/cPanel (Production)

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [System Architecture](#2-system-architecture)
3. [Technology Stack](#3-technology-stack)
4. [Repository and Access Details](#4-repository-and-access-details)
5. [Project Setup Guide](#5-project-setup-guide)
6. [Folder Structure Explanation](#6-folder-structure-explanation)
7. [Module-wise Functional Explanation](#7-module-wise-functional-explanation)
8. [API Documentation](#8-api-documentation)
9. [Database Structure](#9-database-structure)
10. [Deployment Process](#10-deployment-process)
11. [Logs and Monitoring](#11-logs-and-monitoring)
12. [Common Issues and Troubleshooting](#12-common-issues-and-troubleshooting)
13. [Maintenance Guide](#13-maintenance-guide)
14. [Security Considerations](#14-security-considerations)
15. [Third Party Integrations](#15-third-party-integrations)
16. [Known Limitations or Pending Tasks](#16-known-limitations-or-pending-tasks)
17. [Important Contacts](#17-important-contacts)
18. [Best Practices for Future Developers](#18-best-practices-for-future-developers)

---

## 1. Project Overview

### 1.1 Purpose
The **All Assessment Quiz** system is a comprehensive web-based technical assessment platform designed for **Toss Consultancy Services**. It enables standardized evaluation of candidates across multiple technical roles through timed multiple-choice question (MCQ) assessments.

### 1.2 Business Objective
- **Primary Goal:** Conduct fair, standardized technical assessments for candidates applying to various developer positions
- **Key Features:**
  - Multi-role support (Backend, Python, Flutter, MERN, Full Stack, Data Analytics)
  - Level-based question filtering (Beginner, Intermediate, Advanced)
  - Server-controlled timer (45 minutes) to prevent manipulation
  - Auto-save functionality to prevent data loss
  - Admin dashboard for result review and candidate management
  - Duplicate attempt prevention
  - Session-based quiz resumption

### 1.3 Problem Solved
- **Standardization:** Ensures all candidates receive similar assessment experience
- **Security:** Prevents cheating through timer control, duplicate prevention, and answer validation
- **Reliability:** Auto-save mechanism prevents data loss on browser close or network interruption
- **Efficiency:** Admin dashboard provides quick access to candidate results and performance metrics
- **Scalability:** Supports multiple roles and levels with role-specific question banks

### 1.4 Target Users
- **Candidates:** Job applicants taking technical assessments
- **Administrators:** HR/Technical team reviewing candidate performance

---

## 2. System Architecture

### 2.1 High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        CLIENT LAYER                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ Registration │  │  Quiz Page   │  │ Admin Pages  │      │
│  │   (index.php)│  │  (quiz.php)  │  │(admin_view)  │      │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘      │
│         │                  │                  │              │
│         └──────────────────┼──────────────────┘              │
│                            │                                  │
│                    ┌───────▼────────┐                        │
│                    │  AJAX Endpoints │                        │
│                    │  (save_answer, │                        │
│                    │   sync_timer)   │                        │
│                    └───────┬────────┘                        │
└────────────────────────────┼──────────────────────────────────┘
                             │
┌────────────────────────────▼──────────────────────────────────┐
│                      APPLICATION LAYER                        │
│  ┌──────────────────────────────────────────────────────┐    │
│  │              PHP Backend (Session Management)        │    │
│  │  • User Registration & Validation                   │    │
│  │  • Quiz State Management                            │    │
│  │  • Answer Processing                                │    │
│  │  • Timer Synchronization                            │    │
│  │  • Security & Authorization                          │    │
│  └──────────────────┬───────────────────────────────────┘    │
│                     │                                         │
│  ┌──────────────────▼───────────────────────────────────┐    │
│  │         Database Connection Layer (db.php)           │    │
│  │         Configuration Layer (config.php)             │    │
│  └──────────────────┬───────────────────────────────────┘    │
└────────────────────────┼───────────────────────────────────────┘
                         │
┌────────────────────────▼───────────────────────────────────────┐
│                        DATA LAYER                               │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │              MySQL Database (all_assessment_quiz)        │  │
│  │  • users                                                 │  │
│  │  • quiz_attempts                                         │  │
│  │  • quiz_answers                                          │  │
│  │  • responses                                             │  │
│  │  • backend_mcq_questions                                │  │
│  │  • python_mcq_questions                                  │  │
│  │  • flutter_mcq_questions                                │  │
│  │  • mern_mcq_questions                                    │  │
│  │  • fullstack_mcq_questions                               │  │
│  │  • data_analytics_mcq                                    │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

### 2.2 Frontend Architecture
- **Technology:** Vanilla JavaScript (ES6), HTML5, CSS3
- **Design System:** Custom CSS framework (`assets/app.css`)
- **Key Features:**
  - Responsive design (mobile-friendly)
  - Dark mode support
  - Real-time timer display
  - Auto-save via AJAX
  - Progress tracking
  - Modal dialogs for user feedback

### 2.3 Backend Architecture
- **Technology:** PHP 7.4+ (procedural with some object-oriented patterns)
- **Session Management:** PHP native sessions
- **Database Access:** MySQLi with prepared statements
- **Key Components:**
  - Environment-aware configuration (`config.php`)
  - Database connection handler (`db.php`)
  - Session-based state management
  - Server-controlled timer system

### 2.4 Database Architecture
- **Database Engine:** MySQL 5.7+ / MariaDB
- **Connection:** MySQLi extension
- **Key Tables:**
  - `users`: Candidate registration data
  - `quiz_attempts`: Quiz session management
  - `quiz_answers`: Real-time answer storage
  - `responses`: Final submitted answers with scoring
  - Role-specific question tables (6 tables)

### 2.5 APIs and Integrations
- **Internal APIs:** AJAX endpoints for real-time operations
  - `save_answer.php`: Auto-save answers
  - `sync_timer.php`: Timer synchronization
  - `check_user_attempt.php`: Duplicate check
  - `update_question_position.php`: Navigation tracking
  - `get_quiz_state.php`: State restoration
- **External Integrations:** None (standalone system)

### 2.6 Infrastructure
- **Local Development:** XAMPP (Windows)
- **Production:** VPS with cPanel or similar hosting
- **Session Storage:** File-based (local) or custom directory (production)

---

## 3. Technology Stack

### 3.1 Backend Technologies

| Technology | Version | Purpose |
|------------|---------|---------|
| PHP | 7.4+ | Server-side scripting, session management |
| MySQL | 5.7+ | Relational database |
| MySQLi Extension | Built-in | Database connectivity |

### 3.2 Frontend Technologies

| Technology | Version | Purpose |
|------------|---------|---------|
| HTML5 | Latest | Markup structure |
| CSS3 | Latest | Styling and responsive design |
| JavaScript | ES6 | Client-side interactivity, AJAX calls |
| Fetch API | Native | AJAX requests |

### 3.3 Development Tools

| Tool | Purpose |
|------|---------|
| XAMPP | Local development environment (Apache + MySQL + PHP) |
| phpMyAdmin | Database management |
| Git | Version control (if applicable) |
| Text Editor/IDE | Code editing (VS Code, PhpStorm, etc.) |

### 3.4 Production Environment

| Component | Details |
|-----------|---------|
| Web Server | Apache (via XAMPP or VPS) |
| PHP Version | 7.4+ recommended |
| MySQL Version | 5.7+ or MariaDB equivalent |
| Session Storage | File system or custom directory |

---

## 4. Repository and Access Details

### 4.1 Git Repository
- **Repository URL:** [To be filled by current developer]
- **Branching Strategy:** 
  - `main` or `master`: Production-ready code
  - `develop`: Development branch (if applicable)
  - Feature branches: For new features (if applicable)

### 4.2 Access Requirements
- **Code Repository:** Git credentials (if applicable)
- **Database Access:** MySQL credentials (stored in `config.php`)
- **Server Access:** SSH/FTP credentials for production deployment
- **Admin Access:** Direct file system access for configuration

### 4.3 Required Tools
1. **XAMPP** (for local development)
   - Download: https://www.apachefriends.org/
   - Includes: Apache, MySQL, PHP, phpMyAdmin
2. **Web Browser** (Chrome, Firefox, Edge recommended)
3. **Text Editor/IDE** (VS Code, PhpStorm, Sublime Text, etc.)
4. **Git Client** (if using version control)

---

## 5. Project Setup Guide

### 5.1 Prerequisites

#### 5.1.1 Software Requirements
- **Operating System:** Windows 10/11 (for XAMPP) or Linux/Mac (for production)
- **XAMPP:** Version 7.4+ (includes PHP 7.4+, MySQL 5.7+)
- **Web Browser:** Chrome, Firefox, or Edge (latest version)
- **Text Editor:** VS Code, PhpStorm, or any PHP-capable editor

#### 5.1.2 System Requirements
- **RAM:** Minimum 2GB (4GB recommended)
- **Disk Space:** 500MB for application + database
- **Network:** Internet connection (for initial setup only)

### 5.2 Installation Steps

#### Step 1: Install XAMPP
1. Download XAMPP from https://www.apachefriends.org/
2. Run installer and follow installation wizard
3. Install to default location: `C:\xampp\`
4. **Important:** Do not install to `Program Files` (permissions issues)

#### Step 2: Clone/Download Project
1. **If using Git:**
   ```bash
   git clone [repository-url]
   cd All-Quiz-Github
   ```
2. **If downloading manually:**
   - Extract project files to: `C:\xampp\htdocs\All-Quiz-Github\`

#### Step 3: Start XAMPP Services
1. Open **XAMPP Control Panel**
2. Click **"Start"** button for **Apache**
3. Click **"Start"** button for **MySQL**
4. Verify both show **"Running"** status (green)

#### Step 4: Create Database
1. Open web browser
2. Navigate to: `http://localhost/phpmyadmin`
3. Click **"New"** in left sidebar
4. Database name: `all_assessment_quiz`
5. Collation: `utf8mb4_general_ci` (or leave default)
6. Click **"Create"**

#### Step 5: Import Database Schema
1. In phpMyAdmin, select `all_assessment_quiz` database
2. Click **"Import"** tab
3. Click **"Choose File"**
4. Navigate to: `C:\xampp\htdocs\All-Quiz-Github\all_assessment_quiz.sql`
5. Click **"Go"** or **"Import"**
6. Wait for success message

**Note:** If SQL file is not present, you may need to create tables manually (see Database Structure section).

#### Step 6: Configure Database Connection
1. Open `config.php` in text editor
2. Verify database configuration:
   ```php
   // For local development (XAMPP)
   $db_config = [
       'host' => 'localhost',
       'user' => 'root',
       'pass' => '',  // Empty for default XAMPP
       'name' => 'all_assessment_quiz',
       'port' => 3306,
   ];
   ```
3. **If MySQL has password:** Update `'pass' => 'your_password'`

#### Step 7: Verify Installation
1. Open web browser
2. Navigate to: `http://localhost/All-Quiz-Github/`
3. You should see registration form
4. **Test registration:**
   - Fill form with test data
   - Submit form
   - Verify redirect to quiz page

### 5.3 Environment Variables

#### 5.3.1 Local Development (.env.local - Optional)
Create `.env.local` file in project root (optional):
```
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=all_assessment_quiz
DB_PORT=3306
```

#### 5.3.2 Production Environment (.env)
For production server, create `.env` file:
```
DB_HOST=127.0.0.1
DB_USER=quiz_user
DB_PASS=your_secure_password
DB_NAME=all_assessment_quiz
DB_PORT=3306
```

### 5.4 Configuration Files

#### 5.4.1 config.php
- **Purpose:** Environment-aware database configuration
- **Location:** Project root
- **Key Features:**
  - Auto-detects localhost vs server
  - Supports .env file override
  - Configures session storage
  - Sets timezone (Asia/Kolkata)

#### 5.4.2 db.php
- **Purpose:** Database connection handler
- **Location:** Project root
- **Dependencies:** Requires `config.php` to be loaded first
- **Features:**
  - Error handling with user-friendly messages
  - UTF-8 charset configuration
  - Connection retry logic

### 5.5 Database Setup

#### 5.5.1 Required Tables
See **Section 9: Database Structure** for complete table schemas.

**Quick Checklist:**
- [ ] `users` table exists
- [ ] `quiz_attempts` table exists
- [ ] `quiz_answers` table exists
- [ ] `responses` table exists
- [ ] Role-specific question tables exist (6 tables)

#### 5.5.2 Sample Data
- **Questions:** Import question data for each role/level combination
- **Test Users:** Create test users for testing (optional)

### 5.6 Running the Project

#### 5.6.1 Local Development
1. **Start XAMPP:**
   - Apache: Running
   - MySQL: Running
2. **Access Application:**
   - URL: `http://localhost/All-Quiz-Github/`
3. **Access Admin:**
   - URL: `http://localhost/All-Quiz-Github/admin_view.php`

#### 5.6.2 Production Deployment
See **Section 10: Deployment Process** for detailed steps.

---

## 6. Folder Structure Explanation

```
All-Quiz-Github/
│
├── index.php                      # Entry point - Registration form
├── quiz.php                       # Main quiz interface (50 questions, timer)
├── submit_quiz.php                # Quiz submission handler
├── show_result.php                # Result confirmation page
├── check_user_attempt.php          # AJAX endpoint - Duplicate check
├── save_answer.php                # AJAX endpoint - Auto-save answers
├── sync_timer.php                 # AJAX endpoint - Timer synchronization
├── update_question_position.php    # AJAX endpoint - Navigation tracking
├── get_quiz_state.php             # AJAX endpoint - State restoration
├── get_ip.php                     # Utility - IP address detection
├── delete_users.php               # Admin utility - Bulk user deletion
│
├── admin_view.php                 # Admin - User submissions list
├── admin_result.php               # Admin - Individual user results
├── admin_result_server.php        # Admin - API endpoint (if needed)
│
├── config.php                     # Environment-aware configuration
├── db.php                         # Database connection handler
│
├── assets/
│   ├── app.css                   # Main stylesheet (design system)
│   └── old_app.css               # Legacy stylesheet (backup)
│
├── prompt/                        # AI question generation utilities
│   ├── AI_QUESTION_GENERATOR_PROMPT.md
│   ├── GENERATION_GUIDE_500_QUESTIONS.md
│   ├── PROMPT_BEGINNER.txt
│   ├── PROMPT_INTERMEDIATE.txt
│   ├── PROMPT_ADVANCED.txt
│   ├── READY_TO_USE_PROMPT.txt
│   ├── json_to_sql_converter.php
│   └── batch_converter.php
│
├── old_admin_view.php             # Legacy admin view (backup)
├── old_quiz.php                   # Legacy quiz page (backup)
│
├── website-flow.html              # Visual flow diagram (documentation)
├── MODAL_SCENARIOS.md             # Modal scenarios documentation
├── COMPLETE_SYSTEM_SUMMARY.md     # System summary documentation
├── README.md                       # Setup guide
├── KT.md                          # This Knowledge Transfer document
│
└── all_assessment_quiz.sql        # Database schema dump (if available)
```

### 6.1 Key Files Explained

| File | Purpose | Critical Level |
|------|---------|----------------|
| `index.php` | User registration entry point | ⭐⭐⭐ Critical |
| `quiz.php` | Main quiz interface | ⭐⭐⭐ Critical |
| `submit_quiz.php` | Quiz submission processing | ⭐⭐⭐ Critical |
| `config.php` | Database configuration | ⭐⭐⭐ Critical |
| `db.php` | Database connection | ⭐⭐⭐ Critical |
| `save_answer.php` | Auto-save functionality | ⭐⭐ Important |
| `sync_timer.php` | Timer synchronization | ⭐⭐ Important |
| `admin_view.php` | Admin dashboard | ⭐⭐ Important |
| `check_user_attempt.php` | Duplicate prevention | ⭐⭐ Important |

---

## 7. Module-wise Functional Explanation

### 7.1 Registration Module (`index.php`)

#### 7.1.1 Purpose
- Collect candidate information (name, email, phone, role, level, location)
- Validate input data
- Prevent duplicate attempts
- Initialize quiz session

#### 7.1.2 Workflow
1. **User fills registration form:**
   - Name (alphabets only)
   - Email (valid email format)
   - Phone (10 digits, starts with 6/7/8/9)
   - Role (dropdown: Backend, Python, Flutter, MERN, Full Stack, Data Analytics)
   - Level (dropdown: Beginner, Intermediate, Advanced)
   - Location (free text)

2. **Client-side validation:**
   - HTML5 pattern validation
   - JavaScript phone number validation
   - AJAX duplicate check (`check_user_attempt.php`)

3. **Form submission:**
   - POST request to `quiz.php`
   - Server-side validation
   - User lookup/create
   - Quiz initialization

#### 7.1.3 Key Components
- **Form Fields:** HTML form with validation attributes
- **AJAX Handler:** Duplicate check before submit
- **Security:** Context menu disabled, developer shortcuts blocked

### 7.2 Quiz Module (`quiz.php`)

#### 7.2.1 Purpose
- Display 50 questions (one per page)
- Manage quiz timer (45 minutes)
- Auto-save answers
- Track progress
- Handle quiz resumption

#### 7.2.2 Workflow

**Scenario A: New Quiz Start (POST Request)**
1. Receive POST data from registration
2. Validate user credentials
3. Check for duplicate attempts
4. Check for in-progress attempts
5. Create new `quiz_attempts` record
6. Fetch 50 random questions (balanced distribution)
7. Initialize timer (2700 seconds = 45 minutes)
8. Store session variables
9. Render quiz page

**Scenario B: Quiz Resumption (GET Request)**
1. Check session for `quiz_attempt_id`
2. Verify attempt exists and is `in_progress`
3. Verify credentials match database
4. Load saved questions and answers
5. Calculate remaining time from `expires_at`
6. Restore quiz state
7. Render quiz page

#### 7.2.3 Key Components
- **Question Display:** One question per page with pagination
- **Timer System:** Server-controlled, syncs every 30 seconds
- **Auto-save:** Saves answer on option selection
- **Progress Tracking:** Visual progress dots
- **Navigation:** Previous/Next buttons, jump to question

#### 7.2.4 Timer Management
- **Server Authority:** Timer calculated from `expires_at` (database)
- **Client Display:** JavaScript countdown (display only)
- **Sync Mechanism:** AJAX call to `sync_timer.php` every 30 seconds
- **Auto-submit:** When timer reaches 0, quiz auto-submits

### 7.3 Answer Saving Module (`save_answer.php`)

#### 7.3.1 Purpose
- Save answers in real-time as user selects options
- Prevent data loss on browser close
- Update last activity timestamp

#### 7.3.2 Workflow
1. User selects option (radio button)
2. JavaScript triggers `saveAnswer()` function
3. AJAX POST to `save_answer.php`
4. Server validates attempt and user
5. Upsert into `quiz_answers` table
6. Update `last_activity_time` in `quiz_attempts`
7. Return success response

#### 7.3.3 Key Features
- **Upsert Pattern:** `INSERT ... ON DUPLICATE KEY UPDATE`
- **Authorization:** Verifies attempt belongs to user
- **Status Check:** Only allows saves for `in_progress` attempts

### 7.4 Timer Synchronization Module (`sync_timer.php`)

#### 7.4.1 Purpose
- Synchronize client timer with server (authoritative)
- Auto-expire attempts when time runs out
- Prevent timer manipulation

#### 7.4.2 Workflow
1. Client sends `attempt_id` and `client_remaining_seconds`
2. Server calculates `server_remaining_seconds` from `expires_at`
3. **If expired:**
   - Update `status = 'expired'`
   - Return `expired: true`
   - Client shows auto-submit modal
4. **If not expired:**
   - Return `remaining_seconds`
   - Client updates display if difference > 5 seconds

#### 7.4.3 Key Features
- **Server Authority:** Timer calculated from database timestamp
- **Timezone Handling:** Uses Asia/Kolkata timezone
- **Correction Threshold:** Updates client if difference > 5 seconds

### 7.5 Submission Module (`submit_quiz.php`)

#### 7.5.1 Purpose
- Process quiz submission
- Calculate scores
- Save final answers to `responses` table
- Mark attempt as `submitted`

#### 7.5.2 Workflow
1. Receive POST data with `attempt_id` and `user_id`
2. Verify attempt exists and belongs to user
3. **Atomic Lock:** Update `status = 'submitted'` (prevents double submission)
4. Load answers from `quiz_answers` table (not POST data)
5. For each question:
   - Compare selected option with `correct_option`
   - Calculate `is_correct` flag
   - Insert into `responses` table
6. Redirect to `show_result.php`

#### 7.5.3 Key Features
- **Atomic Operation:** Prevents race conditions
- **Answer Source:** Uses `quiz_answers` table (more reliable than POST)
- **Score Calculation:** Calculates `is_correct` during submission

### 7.6 Admin Module (`admin_view.php`, `admin_result.php`)

#### 7.6.1 Purpose
- View all candidate submissions
- View individual candidate results
- Filter and search candidates
- Export results (if implemented)

#### 7.6.2 Workflow

**Admin View (`admin_view.php`):**
1. Fetch all users from `users` table
2. Calculate scores from `responses` table
3. Display table with:
   - Candidate name, email, phone
   - Role and level
   - Score (correct/total)
   - Time details (start, submit, duration)
   - Link to detailed results

**Admin Result (`admin_result.php`):**
1. Receive `user_id` parameter
2. Load user's responses joined with questions
3. Calculate score breakdown:
   - Total score
   - Percentage
   - Correct/Incorrect/Not attempted counts
4. Display question-wise breakdown

#### 7.6.3 Key Features
- **Live Score:** Shows in-progress quiz scores from `quiz_answers`
- **Legacy Support:** Handles old data from `responses` table
- **Search/Filter:** Client-side filtering by name, email, phone, role, location

---

## 8. API Documentation

### 8.1 Internal API Endpoints

All endpoints return JSON responses unless otherwise specified.

#### 8.1.1 `check_user_attempt.php`
**Purpose:** Check if user already attempted quiz (AJAX duplicate check)

**Method:** POST

**Request Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `email` | string | Yes | User email address |
| `mobile` | string | Yes | User phone number |

**Response Format:**
```json
{
  "ok": true,
  "exists": false,
  "attempted": false
}
```

**Response Fields:**
- `exists`: User exists in database
- `attempted`: User has submitted quiz or has expired attempt

**Example Request:**
```javascript
fetch('check_user_attempt.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
  body: new URLSearchParams({ email: 'user@example.com', mobile: '9876543210' })
})
```

---

#### 8.1.2 `save_answer.php`
**Purpose:** Auto-save answer when user selects option

**Method:** POST

**Request Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `attempt_id` | integer | Yes | Quiz attempt ID |
| `question_id` | integer | Yes | Question ID |
| `selected_option` | string | Yes | Selected option (A, B, C, D) |

**Response Format:**
```json
{
  "success": true,
  "message": "Answer saved successfully",
  "attempt_id": 123,
  "question_id": 456,
  "selected_option": "A"
}
```

**Error Responses:**
- `400`: Missing parameters
- `401`: User not authenticated
- `403`: Access denied or attempt not in progress
- `404`: Attempt not found
- `500`: Internal server error

**Example Request:**
```javascript
fetch('save_answer.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
  body: new URLSearchParams({
    attempt_id: 123,
    question_id: 456,
    selected_option: 'A'
  })
})
```

---

#### 8.1.3 `sync_timer.php`
**Purpose:** Synchronize timer with server (authoritative)

**Method:** POST

**Request Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `attempt_id` | integer | Yes | Quiz attempt ID |
| `client_remaining_seconds` | integer | No | Client-side remaining time |

**Response Format (Not Expired):**
```json
{
  "success": true,
  "remaining_seconds": 1800,
  "elapsed_seconds": 900,
  "server_time": "2024-01-15 10:30:00",
  "needs_correction": false
}
```

**Response Format (Expired):**
```json
{
  "success": true,
  "remaining_seconds": 0,
  "expired": true,
  "message": "Time has expired"
}
```

**Error Responses:**
- `400`: Missing attempt_id
- `401`: User not authenticated
- `403`: Access denied or attempt not in progress
- `404`: Attempt not found
- `500`: Internal server error

**Example Request:**
```javascript
fetch('sync_timer.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
  body: new URLSearchParams({
    attempt_id: 123,
    client_remaining_seconds: 1800
  })
})
```

---

#### 8.1.4 `update_question_position.php`
**Purpose:** Update current question index when user navigates

**Method:** POST

**Request Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `attempt_id` | integer | Yes | Quiz attempt ID |
| `question_index` | integer | Yes | Current question index (0-based) |

**Response Format:**
```json
{
  "success": true,
  "message": "Question position updated successfully",
  "attempt_id": 123,
  "question_index": 5
}
```

**Error Responses:**
- `400`: Missing parameters or invalid index
- `401`: User not authenticated
- `403`: Access denied or attempt not in progress
- `404`: Attempt not found
- `500`: Internal server error

---

#### 8.1.5 `get_quiz_state.php`
**Purpose:** Get complete quiz state for restoration

**Method:** GET or POST

**Request Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `attempt_id` | integer | Yes | Quiz attempt ID |

**Response Format:**
```json
{
  "success": true,
  "attempt_id": 123,
  "user_id": 456,
  "role": "Backend Developer",
  "level": "Intermediate",
  "question_ids": [1, 2, 3, ...],
  "current_question_index": 5,
  "remaining_seconds": 1800,
  "elapsed_seconds": 900,
  "start_time": "2024-01-15 10:00:00",
  "answers": {
    "1": "A",
    "2": "B",
    "3": null
  }
}
```

**Error Responses:**
- `400`: Missing attempt_id
- `401`: User not authenticated
- `403`: Access denied
- `404`: Attempt not found
- `500`: Internal server error

---

### 8.2 Authentication

**Session-Based Authentication:**
- All API endpoints verify `$_SESSION['quiz_user_id']`
- Attempt ownership verified: `attempt.user_id === session.user_id`
- Status check: Only `in_progress` attempts can be modified

**Security Headers:**
- `Content-Type: application/json` for responses
- HTTP status codes for error handling

---

## 9. Database Structure

### 9.1 Database Overview

**Database Name:** `all_assessment_quiz`  
**Character Set:** `utf8mb4`  
**Collation:** `utf8mb4_general_ci`

### 9.2 Core Tables

#### 9.2.1 `users` Table
**Purpose:** Stores candidate registration information

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | Unique user ID |
| `name` | VARCHAR(255) | NOT NULL | Candidate full name |
| `email` | VARCHAR(255) | NOT NULL | Email address (unique identifier) |
| `mobile` | VARCHAR(10) | NOT NULL | Phone number (unique identifier) |
| `role` | VARCHAR(100) | NOT NULL | Selected role (Backend Developer, etc.) |
| `level` | VARCHAR(50) | NOT NULL | Selected level (Beginner, Intermediate, Advanced) |
| `place` | VARCHAR(255) | NULL | Location/city |
| `submitted_at` | TIMESTAMP | NULL | Legacy submission timestamp |

**Indexes:**
- PRIMARY KEY on `id`
- Index on `email` (for lookup)
- Index on `mobile` (for lookup)

**Relationships:**
- One-to-many with `quiz_attempts`
- One-to-many with `responses`

---

#### 9.2.2 `quiz_attempts` Table
**Purpose:** Manages quiz sessions and timer state

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `attempt_id` | INT | PRIMARY KEY, AUTO_INCREMENT | Unique attempt ID |
| `user_id` | INT | NOT NULL, FOREIGN KEY | Reference to `users.id` |
| `role` | VARCHAR(100) | NOT NULL | Role for this attempt |
| `level` | VARCHAR(50) | NOT NULL | Level for this attempt |
| `question_ids` | JSON/TEXT | NOT NULL | Array of question IDs (JSON) |
| `current_question_index` | INT | DEFAULT 0 | Current question position |
| `remaining_time_seconds` | INT | DEFAULT 2700 | Remaining time (seconds) |
| `duration_minutes` | INT | DEFAULT 45 | Quiz duration (minutes) |
| `start_time` | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Quiz start time |
| `expires_at` | TIMESTAMP | NULL | Quiz expiration time (server-controlled) |
| `end_time` | TIMESTAMP | NULL | Quiz submission time |
| `status` | ENUM | DEFAULT 'in_progress' | Status: 'in_progress', 'submitted', 'expired' |
| `last_activity_time` | TIMESTAMP | NULL | Last answer save time |

**Indexes:**
- PRIMARY KEY on `attempt_id`
- INDEX on `user_id`
- INDEX on `status`
- INDEX on `expires_at`

**Relationships:**
- Many-to-one with `users`
- One-to-many with `quiz_answers`

**Status Values:**
- `in_progress`: Quiz is active
- `submitted`: Quiz completed and submitted
- `expired`: Timer expired (will be submitted)

---

#### 9.2.3 `quiz_answers` Table
**Purpose:** Real-time answer storage (auto-save)

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | Unique answer ID |
| `attempt_id` | INT | NOT NULL, FOREIGN KEY | Reference to `quiz_attempts.attempt_id` |
| `question_id` | INT | NOT NULL | Question ID |
| `selected_option` | VARCHAR(1) | NULL | Selected option (A, B, C, D, or NULL) |
| `saved_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Answer save timestamp |

**Indexes:**
- PRIMARY KEY on `id`
- UNIQUE KEY on (`attempt_id`, `question_id`) - Prevents duplicates
- INDEX on `attempt_id`
- INDEX on `question_id`

**Relationships:**
- Many-to-one with `quiz_attempts`

**Note:** Uses `ON DUPLICATE KEY UPDATE` for upsert pattern.

---

#### 9.2.4 `responses` Table
**Purpose:** Final submitted answers with scoring

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | Unique response ID |
| `user_id` | INT | NOT NULL, FOREIGN KEY | Reference to `users.id` |
| `question_id` | INT | NOT NULL | Question ID |
| `selected_option` | VARCHAR(1) | NULL | Selected option (A, B, C, D, or NULL) |
| `is_correct` | TINYINT(1) | NULL | 1 if correct, 0 if incorrect, NULL if not attempted |

**Indexes:**
- PRIMARY KEY on `id`
- INDEX on `user_id`
- INDEX on `question_id`
- INDEX on `is_correct`

**Relationships:**
- Many-to-one with `users`

**Note:** Created only during quiz submission. Used for score calculation and admin results.

---

### 9.3 Question Tables

Each role has its own question table:

1. `backend_mcq_questions`
2. `python_mcq_questions`
3. `flutter_mcq_questions`
4. `mern_mcq_questions`
5. `fullstack_mcq_questions`
6. `data_analytics_mcq`

#### 9.3.1 Question Table Structure

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | Unique question ID |
| `question` | TEXT | NOT NULL | Question text |
| `option_a` | TEXT | NOT NULL | Option A |
| `option_b` | TEXT | NOT NULL | Option B |
| `option_c` | TEXT | NOT NULL | Option C |
| `option_d` | TEXT | NOT NULL | Option D |
| `correct_option` | VARCHAR(1) | NOT NULL | Correct answer (A, B, C, D) |
| `level` | VARCHAR(50) | NOT NULL | Level (beginner, intermediate, advanced/advance) |
| `role` | VARCHAR(100) | NULL | Role (if table supports multiple roles) |

**Indexes:**
- PRIMARY KEY on `id`
- INDEX on `level`
- INDEX on `role` (if applicable)

**Note:** Some tables use `'advance'` instead of `'advanced'` (legacy). Code handles both.

---

### 9.4 Database Relationships Diagram

```
users (1) ──────< (many) quiz_attempts (1) ──────< (many) quiz_answers
  │                                                      │
  │                                                      │
  └───────< (many) responses                            │
                                                         │
                                                         │
                    question_id ────────────────────────┘
                    (references role-specific tables)
```

---

## 10. Deployment Process

### 10.1 Local Development Deployment

**Already covered in Section 5: Project Setup Guide**

### 10.2 Production Deployment (VPS/cPanel)

#### Step 1: Prepare Production Environment
1. **Server Requirements:**
   - PHP 7.4+ installed
   - MySQL 5.7+ installed
   - Apache web server
   - SSH/FTP access

2. **Create Database:**
   ```sql
   CREATE DATABASE all_assessment_quiz CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
   ```

3. **Create Database User:**
   ```sql
   CREATE USER 'quiz_user'@'localhost' IDENTIFIED BY 'secure_password';
   GRANT ALL PRIVILEGES ON all_assessment_quiz.* TO 'quiz_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

#### Step 2: Upload Project Files
1. **Via FTP/SFTP:**
   - Upload all project files to `public_html/` or `www/` directory
   - Maintain folder structure

2. **Via Git (if applicable):**
   ```bash
   git clone [repository-url]
   cd All-Quiz-Github
   ```

#### Step 3: Configure Environment
1. **Create `.env` file:**
   ```
   DB_HOST=127.0.0.1
   DB_USER=quiz_user
   DB_PASS=secure_password
   DB_NAME=all_assessment_quiz
   DB_PORT=3306
   ```

2. **Update `config.php` if needed:**
   - Verify environment detection works correctly
   - Check session storage directory permissions

#### Step 4: Import Database
1. **Via phpMyAdmin:**
   - Select `all_assessment_quiz` database
   - Import `all_assessment_quiz.sql`

2. **Via Command Line:**
   ```bash
   mysql -u quiz_user -p all_assessment_quiz < all_assessment_quiz.sql
   ```

#### Step 5: Set Permissions
1. **Session Directory:**
   ```bash
   mkdir -p sessions
   chmod 755 sessions
   ```

2. **File Permissions:**
   ```bash
   chmod 644 *.php
   chmod 755 assets/
   ```

#### Step 6: Verify Deployment
1. **Test Registration:**
   - Access: `https://yourdomain.com/`
   - Fill registration form
   - Verify database connection

2. **Test Quiz:**
   - Start quiz
   - Verify timer works
   - Verify auto-save works

3. **Test Admin:**
   - Access: `https://yourdomain.com/admin_view.php`
   - Verify user list loads

### 10.3 Build Steps

**No build process required** - PHP is interpreted language.

**Pre-deployment Checklist:**
- [ ] Database credentials updated
- [ ] `.env` file created (production)
- [ ] Session directory created and writable
- [ ] Database imported
- [ ] File permissions set correctly
- [ ] Error reporting disabled in production (`display_errors = Off`)

### 10.4 CI/CD Pipeline

**Not currently implemented.** Can be added using:
- GitHub Actions
- GitLab CI/CD
- Jenkins

**Suggested Pipeline:**
1. Code push triggers build
2. Run PHP syntax check
3. Deploy to staging
4. Run tests (if implemented)
5. Deploy to production (manual approval)

---

## 11. Logs and Monitoring

### 11.1 Log Locations

#### 11.1.1 Apache Error Logs
**Local (XAMPP):**
- Path: `C:\xampp\apache\logs\error.log`
- Contains: PHP errors, Apache errors

**Production:**
- Path: `/var/log/apache2/error.log` (Linux)
- Or: Check cPanel error logs

#### 11.1.2 PHP Error Logs
**Local (XAMPP):**
- Path: `C:\xampp\php\logs\php_error_log`
- Contains: PHP runtime errors

**Production:**
- Path: Check `php.ini` `error_log` directive
- Or: Check cPanel error logs

#### 11.1.3 Application Logs
**Session Directory:**
- Path: `sessions/` (if created)
- Contains: Session files (not human-readable)

**Database Logs:**
- MySQL error log (if enabled)
- Query log (if enabled)

### 11.2 Error Logging in Code

**Current Implementation:**
- Uses `error_log()` function for critical errors
- Example: `error_log("Database connection error: " . $e->getMessage());`

**Logging Locations:**
- Database connection errors: Logged to PHP error log
- Quiz submission errors: Logged to PHP error log
- Admin deletion: Logged with `error_log("ADMIN DELETE: ...")`

### 11.3 Monitoring

#### 11.3.1 What to Monitor
1. **Database Connection:**
   - Failed connection attempts
   - Slow queries

2. **Quiz Submissions:**
   - Failed submissions
   - Timer expiration events

3. **Server Resources:**
   - Disk space (session files)
   - Memory usage
   - CPU usage

#### 11.3.2 Monitoring Tools
**Recommended:**
- **Server Monitoring:** cPanel metrics, server monitoring tools
- **Database Monitoring:** phpMyAdmin, MySQL Workbench
- **Application Monitoring:** Custom logging (can be enhanced)

### 11.4 Troubleshooting Using Logs

**Common Log Patterns:**

1. **Database Connection Error:**
   ```
   Database connection failed (2002): No connection could be made
   ```
   - **Solution:** Check MySQL service is running

2. **Session Error:**
   ```
   session_start(): Failed to initialize storage module
   ```
   - **Solution:** Check session directory permissions

3. **Quiz Submission Error:**
   ```
   Quiz attempt not found
   ```
   - **Solution:** Check `quiz_attempts` table, verify `attempt_id`

---

## 12. Common Issues and Troubleshooting

### 12.1 Database Connection Issues

#### Issue: "Database connection failed"
**Symptoms:**
- Error message: "Database connection error! MySQL server is not running"
- Page shows database error

**Solutions:**
1. **Check MySQL Service:**
   - XAMPP: Verify MySQL is running in Control Panel
   - Production: `sudo systemctl status mysql`

2. **Check Credentials:**
   - Verify `config.php` has correct credentials
   - Check `.env` file (if used)

3. **Check Database Exists:**
   - Verify `all_assessment_quiz` database exists
   - Check user has permissions

4. **Check Port:**
   - Default MySQL port: 3306
   - Verify port is not blocked by firewall

---

#### Issue: "Access denied for user"
**Symptoms:**
- Error: "Database authentication failed"

**Solutions:**
1. **Verify Username/Password:**
   - Check `config.php` credentials
   - Test connection in phpMyAdmin

2. **Check User Permissions:**
   ```sql
   SHOW GRANTS FOR 'quiz_user'@'localhost';
   ```

3. **Reset Password (if needed):**
   ```sql
   ALTER USER 'quiz_user'@'localhost' IDENTIFIED BY 'new_password';
   ```

---

### 12.2 Session Issues

#### Issue: "Session not persisting"
**Symptoms:**
- User redirected to registration after page reload
- Quiz state lost

**Solutions:**
1. **Check Session Directory:**
   - Verify `sessions/` directory exists
   - Check permissions: `chmod 755 sessions`

2. **Check PHP Session Settings:**
   ```php
   // In config.php, verify:
   ini_set('session.save_path', __DIR__ . '/sessions');
   ```

3. **Check Browser Cookies:**
   - Verify cookies are enabled
   - Check browser console for cookie errors

---

### 12.3 Timer Issues

#### Issue: "Timer resets on page reload"
**Symptoms:**
- Timer shows 45:00 after reload
- Timer doesn't sync with server

**Solutions:**
1. **Check `expires_at` Field:**
   - Verify `expires_at` is set in `quiz_attempts` table
   - Check timezone settings (Asia/Kolkata)

2. **Check `sync_timer.php`:**
   - Verify endpoint is accessible
   - Check browser console for AJAX errors

3. **Check Server Time:**
   - Verify server time is correct
   - Check timezone configuration

---

### 12.4 Quiz Submission Issues

#### Issue: "Quiz not submitting"
**Symptoms:**
- Submit button doesn't work
- Redirects to registration

**Solutions:**
1. **Check Attempt Status:**
   ```sql
   SELECT status FROM quiz_attempts WHERE attempt_id = ?;
   ```
   - Status should be `in_progress` or `expired`

2. **Check Session:**
   - Verify `$_SESSION['quiz_attempt_id']` exists
   - Check session hasn't expired

3. **Check JavaScript Errors:**
   - Open browser console (F12)
   - Look for JavaScript errors

---

### 12.5 Question Loading Issues

#### Issue: "No questions found"
**Symptoms:**
- Error: "No questions found for [role] ([level])"

**Solutions:**
1. **Check Question Tables:**
   ```sql
   SELECT COUNT(*) FROM backend_mcq_questions WHERE level = 'beginner';
   ```
   - Verify questions exist for role/level combination

2. **Check Level Spelling:**
   - Some tables use `'advance'` instead of `'advanced'`
   - Code handles both, but verify data consistency

3. **Check Role Mapping:**
   - Verify role name matches table name mapping
   - Check `$roleToTable` array in `quiz.php`

---

### 12.6 Admin Page Issues

#### Issue: "Admin page shows no users"
**Symptoms:**
- Empty table in `admin_view.php`

**Solutions:**
1. **Check Database:**
   ```sql
   SELECT COUNT(*) FROM users;
   ```
   - Verify users exist

2. **Check Query:**
   - Verify `admin_view.php` query is correct
   - Check for SQL errors in logs

3. **Check Permissions:**
   - Verify database user has SELECT permissions

---

### 12.7 Performance Issues

#### Issue: "Slow page load"
**Symptoms:**
- Quiz page takes long to load
- Admin page slow with many users

**Solutions:**
1. **Optimize Queries:**
   - Add indexes on frequently queried columns
   - Use `LIMIT` for large result sets

2. **Check `ORDER BY RAND()`:**
   - `ORDER BY RAND()` is slow for large tables
   - Consider pre-generating question sets

3. **Check Server Resources:**
   - Monitor CPU and memory usage
   - Consider upgrading server

---

## 13. Maintenance Guide

### 13.1 Routine Tasks

#### Daily Tasks
- **Monitor Error Logs:**
  - Check Apache/PHP error logs
  - Look for database connection errors
  - Check for failed quiz submissions

#### Weekly Tasks
- **Database Backup:**
  ```bash
  mysqldump -u quiz_user -p all_assessment_quiz > backup_$(date +%Y%m%d).sql
  ```
- **Check Disk Space:**
  - Monitor session directory size
  - Clean old session files if needed

#### Monthly Tasks
- **Review Performance:**
  - Check slow query log
  - Review user submission patterns
  - Optimize database indexes

- **Update Questions:**
  - Add new questions if needed
  - Update existing questions
  - Verify question distribution (A/B/C/D)

### 13.2 Database Maintenance

#### Backup Strategy
1. **Full Backup (Daily):**
   ```bash
   mysqldump -u quiz_user -p all_assessment_quiz > daily_backup.sql
   ```

2. **Incremental Backup (Optional):**
   - Use MySQL binary logs
   - Or: Backup only new records

3. **Backup Storage:**
   - Store backups off-server
   - Keep at least 7 days of backups
   - Test restore procedure periodically

#### Cleanup Tasks
1. **Old Session Files:**
   ```bash
   find sessions/ -type f -mtime +7 -delete
   ```

2. **Expired Attempts:**
   ```sql
   -- Archive expired attempts older than 30 days
   DELETE FROM quiz_attempts 
   WHERE status = 'expired' 
   AND end_time < DATE_SUB(NOW(), INTERVAL 30 DAY);
   ```

3. **Old Responses (Optional):**
   ```sql
   -- Archive responses older than 1 year
   DELETE FROM responses 
   WHERE user_id IN (
     SELECT id FROM users 
     WHERE submitted_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)
   );
   ```

### 13.3 Question Management

#### Adding New Questions
1. **Prepare Question Data:**
   - Format: JSON or SQL
   - Include: question, options (A-D), correct_option, level, role

2. **Import to Database:**
   ```sql
   INSERT INTO backend_mcq_questions 
   (question, option_a, option_b, option_c, option_d, correct_option, level, role)
   VALUES 
   ('Question text?', 'Option A', 'Option B', 'Option C', 'Option D', 'A', 'beginner', 'Backend Developer');
   ```

3. **Verify Distribution:**
   ```sql
   SELECT correct_option, COUNT(*) 
   FROM backend_mcq_questions 
   WHERE level = 'beginner' 
   GROUP BY correct_option;
   ```
   - Ensure balanced distribution (A/B/C/D)

#### Updating Questions
1. **Update Question Text:**
   ```sql
   UPDATE backend_mcq_questions 
   SET question = 'Updated question text?' 
   WHERE id = 123;
   ```

2. **Update Correct Answer:**
   ```sql
   UPDATE backend_mcq_questions 
   SET correct_option = 'B' 
   WHERE id = 123;
   ```
   - **Warning:** This affects existing quiz results

### 13.4 User Management

#### Viewing Users
- **Admin Dashboard:** `admin_view.php`
- **Database Query:**
  ```sql
  SELECT * FROM users ORDER BY submitted_at DESC;
  ```

#### Deleting Users
- **Via Admin:** Use `delete_users.php` (if implemented)
- **Via Database:**
  ```sql
  -- Delete user and related data
  DELETE FROM quiz_answers WHERE attempt_id IN (
    SELECT attempt_id FROM quiz_attempts WHERE user_id = 123
  );
  DELETE FROM quiz_attempts WHERE user_id = 123;
  DELETE FROM responses WHERE user_id = 123;
  DELETE FROM users WHERE id = 123;
  ```

---

## 14. Security Considerations

### 14.1 Authentication and Authorization

#### Current Implementation
- **Session-Based:** Uses PHP native sessions
- **User Verification:** Verifies `user_id` matches `attempt_id`
- **Status Checks:** Only `in_progress` attempts can be modified

#### Security Measures
1. **Session Regeneration:**
   ```php
   session_regenerate_id(true); // Prevents session fixation
   ```

2. **Credential Verification:**
   - Compares session credentials with database
   - Prevents credential tampering

3. **Attempt Ownership:**
   - All API endpoints verify attempt belongs to user
   - Prevents cross-user data access

### 14.2 Input Validation

#### Server-Side Validation
- **Phone Number:** Exactly 10 digits, starts with 6/7/8/9
- **Email:** Valid email format
- **Role/Level:** Must be from allowed list
- **Answer Options:** Must be A, B, C, D, or NULL

#### SQL Injection Prevention
- **Prepared Statements:** All queries use prepared statements
- **Parameter Binding:** All user inputs are bound as parameters
- **Example:**
  ```php
  $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  ```

### 14.3 XSS Prevention

#### Output Escaping
- **HTML Escaping:** All outputs use `htmlspecialchars()`
- **URL Encoding:** Parameters encoded with `urlencode()`
- **Example:**
  ```php
  echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');
  ```

### 14.4 Session Security

#### Session Configuration
- **HTTP Only:** `session.cookie_httponly = 1`
- **SameSite:** `session.cookie_samesite = Lax`
- **Secure (Production):** Enable `session.cookie_secure = 1` for HTTPS

#### Session Storage
- **Custom Directory:** Uses project `sessions/` directory (production)
- **Permissions:** Directory set to 755 (readable, writable by owner)

### 14.5 Timer Security

#### Server-Controlled Timer
- **Authoritative Source:** Timer calculated from `expires_at` (database)
- **Client Display Only:** JavaScript timer is for display only
- **Sync Mechanism:** Server corrects client timer every 30 seconds
- **Prevents Manipulation:** Client cannot extend timer

### 14.6 Admin Security

#### Current State
- **No Authentication:** Admin pages are publicly accessible
- **Risk:** Anyone can view candidate results

#### Recommended Improvements
1. **Add Admin Authentication:**
   ```php
   // Add to admin pages
   session_start();
   if (!isset($_SESSION['admin_logged_in'])) {
       header('Location: admin_login.php');
       exit;
   }
   ```

2. **IP Whitelist (Optional):**
   ```php
   $allowed_ips = ['192.168.1.100', '10.0.0.50'];
   if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
       die('Access denied');
   }
   ```

3. **Password Protection:**
   - Use strong passwords
   - Implement password hashing (bcrypt)
   - Add login attempt limiting

### 14.7 Data Protection

#### Sensitive Data
- **User Information:** Name, email, phone stored in database
- **Quiz Answers:** Stored in `quiz_answers` and `responses` tables

#### Protection Measures
1. **Database Access:** Restrict database user permissions
2. **Backup Encryption:** Encrypt database backups
3. **HTTPS:** Use HTTPS in production (encrypts data in transit)
4. **Access Logs:** Monitor access to admin pages

---

## 15. Third Party Integrations

### 15.1 Current Integrations

**None** - This is a standalone system with no external dependencies.

### 15.2 Potential Future Integrations

#### Email Notifications
- **Purpose:** Send quiz completion emails to candidates
- **Options:** PHPMailer, SendGrid, AWS SES

#### SMS Notifications
- **Purpose:** Send quiz reminders or completion notifications
- **Options:** Twilio, AWS SNS

#### Analytics
- **Purpose:** Track user behavior, quiz performance metrics
- **Options:** Google Analytics, Custom analytics

#### Payment Gateway (If Needed)
- **Purpose:** Charge for assessments (if monetized)
- **Options:** Stripe, PayPal, Razorpay

---

## 16. Known Limitations or Pending Tasks

### 16.1 Known Limitations

#### 1. Admin Authentication
- **Issue:** Admin pages are publicly accessible
- **Impact:** Security risk - anyone can view results
- **Priority:** High
- **Recommendation:** Implement admin login system

#### 2. Performance with Large Question Sets
- **Issue:** `ORDER BY RAND()` is slow for large tables
- **Impact:** Slow quiz initialization with 500+ questions
- **Priority:** Medium
- **Recommendation:** Pre-generate question sets or use better randomization

#### 3. Concurrent Browser Sessions
- **Issue:** User can potentially have multiple active attempts
- **Impact:** Data inconsistency, confusion
- **Priority:** Medium
- **Recommendation:** Add unique constraint or lock mechanism

#### 4. Session Expiry Handling
- **Issue:** No explicit session timeout handling
- **Impact:** User may lose progress if session expires
- **Priority:** Low
- **Recommendation:** Extend session lifetime or handle gracefully

#### 5. Network Interruption
- **Issue:** No retry mechanism for failed auto-saves
- **Impact:** Potential data loss on network failure
- **Priority:** Medium
- **Recommendation:** Implement retry logic or offline queue

#### 6. Question Count Validation
- **Issue:** No validation that enough questions exist for role/level
- **Impact:** Quiz may have fewer than 50 questions
- **Priority:** Low
- **Recommendation:** Validate question count before quiz start

### 16.2 Pending Tasks

#### High Priority
1. **Admin Authentication:** Implement login system for admin pages
2. **HTTPS:** Enable HTTPS in production
3. **Error Handling:** Improve error messages for users

#### Medium Priority
1. **Performance Optimization:** Optimize question selection query
2. **Retry Mechanism:** Add retry logic for auto-save
3. **Question Validation:** Validate question count before quiz start

#### Low Priority
1. **Export Functionality:** Add CSV/Excel export for admin results
2. **Email Notifications:** Send completion emails to candidates
3. **Analytics Dashboard:** Add analytics for quiz performance

---

## 17. Important Contacts

### 17.1 Team Roles

| Role | Responsibility | Contact Information |
|------|----------------|---------------------|
| **Project Manager** | Overall project coordination | [To be filled] |
| **Backend Developer** | PHP development, database | [To be filled] |
| **Frontend Developer** | UI/UX, JavaScript | [To be filled] |
| **Database Administrator** | Database maintenance, backups | [To be filled] |
| **System Administrator** | Server management, deployment | [To be filled] |
| **QA/Testing** | Quality assurance, testing | [To be filled] |

### 17.2 External Contacts

| Service | Purpose | Contact Information |
|---------|---------|---------------------|
| **Hosting Provider** | VPS/server management | [To be filled] |
| **Domain Registrar** | Domain management | [To be filled] |
| **SSL Certificate Provider** | HTTPS certificates | [To be filled] |

### 17.3 Escalation Path

1. **Level 1:** Developer/Technical Team
2. **Level 2:** Project Manager
3. **Level 3:** Senior Management

---

## 18. Best Practices for Future Developers

### 18.1 Code Standards

#### PHP Coding Standards
1. **Use Prepared Statements:**
   ```php
   // ✅ Good
   $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
   $stmt->bind_param("i", $id);
   
   // ❌ Bad
   $result = $conn->query("SELECT * FROM users WHERE id = $id");
   ```

2. **Escape Output:**
   ```php
   // ✅ Good
   echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');
   
   // ❌ Bad
   echo $userName;
   ```

3. **Error Handling:**
   ```php
   // ✅ Good
   try {
       $stmt->execute();
   } catch (Exception $e) {
       error_log("Error: " . $e->getMessage());
       // Handle error gracefully
   }
   ```

#### JavaScript Coding Standards
1. **Use Modern JavaScript:**
   ```javascript
   // ✅ Good - Use async/await
   async function saveAnswer() {
       const response = await fetch('save_answer.php', {...});
   }
   
   // ❌ Bad - Use callbacks
   function saveAnswer() {
       fetch('save_answer.php', {...}).then(response => {...});
   }
   ```

2. **Error Handling:**
   ```javascript
   // ✅ Good
   try {
       const data = await fetch(...);
   } catch (error) {
       console.error('Error:', error);
       // Show user-friendly message
   }
   ```

### 18.2 Database Best Practices

1. **Always Use Indexes:**
   - Add indexes on frequently queried columns
   - Foreign keys should have indexes

2. **Normalize Data:**
   - Avoid data duplication
   - Use foreign keys for relationships

3. **Backup Regularly:**
   - Daily backups for production
   - Test restore procedure

4. **Monitor Performance:**
   - Check slow query log
   - Optimize queries as needed

### 18.3 Security Best Practices

1. **Never Trust User Input:**
   - Always validate and sanitize
   - Use prepared statements

2. **Protect Sensitive Data:**
   - Use HTTPS in production
   - Encrypt database backups
   - Restrict database user permissions

3. **Session Security:**
   - Regenerate session ID on login
   - Set appropriate session timeouts
   - Use secure cookies in production

4. **Error Messages:**
   - Don't expose sensitive information
   - Log detailed errors server-side
   - Show user-friendly messages

### 18.4 Testing Best Practices

1. **Test All Scenarios:**
   - New user registration
   - Quiz resumption
   - Timer expiration
   - Duplicate attempt prevention

2. **Test Edge Cases:**
   - Empty question sets
   - Network interruptions
   - Session expiry
   - Concurrent sessions

3. **Test Security:**
   - SQL injection attempts
   - XSS attempts
   - Session hijacking attempts

### 18.5 Documentation Best Practices

1. **Code Comments:**
   - Comment complex logic
   - Document function parameters
   - Explain business rules

2. **Update Documentation:**
   - Update this KT document when making changes
   - Document new features
   - Update API documentation

3. **Version Control:**
   - Use meaningful commit messages
   - Tag releases
   - Maintain changelog

### 18.6 Deployment Best Practices

1. **Test in Staging First:**
   - Never deploy directly to production
   - Test all features in staging
   - Verify database migrations

2. **Backup Before Deployment:**
   - Backup database
   - Backup code (if not using Git)
   - Document rollback procedure

3. **Monitor After Deployment:**
   - Check error logs
   - Monitor performance
   - Verify all features work

---

## Appendix A: Quick Reference

### A.1 File Responsibilities

| File | Purpose | Key Functions |
|------|---------|---------------|
| `index.php` | Registration | Form validation, duplicate check |
| `quiz.php` | Quiz interface | Question display, timer, auto-save |
| `submit_quiz.php` | Submission | Score calculation, final save |
| `save_answer.php` | Auto-save | Real-time answer storage |
| `sync_timer.php` | Timer sync | Server timer synchronization |
| `admin_view.php` | Admin list | User submissions overview |
| `admin_result.php` | Admin detail | Individual user results |

### A.2 Database Quick Reference

**Core Tables:**
- `users` - Candidate information
- `quiz_attempts` - Quiz sessions
- `quiz_answers` - Real-time answers
- `responses` - Final submitted answers

**Question Tables:**
- `backend_mcq_questions`
- `python_mcq_questions`
- `flutter_mcq_questions`
- `mern_mcq_questions`
- `fullstack_mcq_questions`
- `data_analytics_mcq`

### A.3 Common SQL Queries

**Get User Attempts:**
```sql
SELECT * FROM quiz_attempts WHERE user_id = ? ORDER BY start_time DESC;
```

**Get User Score:**
```sql
SELECT COUNT(*) as total, SUM(is_correct) as correct 
FROM responses WHERE user_id = ?;
```

**Get Questions for Role/Level:**
```sql
SELECT * FROM backend_mcq_questions 
WHERE level = 'beginner' 
ORDER BY RAND() 
LIMIT 50;
```

---

## Appendix B: Change Log

### Version 1.0 (Current)
- Initial Knowledge Transfer document
- Complete system documentation
- Setup and deployment guides

---

## Document End

**This Knowledge Transfer document is intended to provide comprehensive information for new developers to understand, maintain, and extend the All Assessment Quiz system.**

**For questions or clarifications, please refer to the codebase or contact the previous developer.**

---
