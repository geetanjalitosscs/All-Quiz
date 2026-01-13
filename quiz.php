<?php
// ===============================
// File: quiz.php (Quiz UI Page)
// ===============================
include 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Helper to fetch question rows from a prepared statement without relying on mysqlnd get_result().
 *
 * @param mysqli_stmt $stmt
 * @return array<int, array<string, mixed>>
 */
function fetchQuestionsFromStatement(mysqli_stmt $stmt): array
{
    $questions = [];
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        return $questions;
    }

    $stmt->bind_result($id, $question, $optionA, $optionB, $optionC, $optionD);
    while ($stmt->fetch()) {
        $questions[] = [
            'id'        => $id,
            'question'  => $question ?? '',
            'option_a'  => $optionA ?? '',
            'option_b'  => $optionB ?? '',
            'option_c'  => $optionC ?? '',
            'option_d'  => $optionD ?? '',
        ];
    }

    return $questions;
}

// ============================================
// HANDLE BOTH POST (NEW QUIZ) AND GET (RESUME)
// ============================================

// Handle GET request (resume quiz on page reload)
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Check if session has quiz attempt
    if (!isset($_SESSION['quiz_attempt_id']) || !isset($_SESSION['quiz_user_id'])) {
        header("Location: index.php");
        exit;
    }
    
    $attempt_id = $_SESSION['quiz_attempt_id'];
    $user_id = $_SESSION['quiz_user_id'];
    $role = $_SESSION['quiz_role'] ?? '';
    $level = $_SESSION['quiz_level'] ?? '';
    
    if (empty($role) || empty($level)) {
        header("Location: index.php");
        exit;
    }
    
    // Get attempt details with expires_at (SERVER-CONTROLLED TIMER)
    $attemptStmt = $conn->prepare("
        SELECT attempt_id, user_id, role, level, question_ids, current_question_index, 
               remaining_time_seconds, start_time, expires_at, duration_minutes, status
        FROM quiz_attempts 
        WHERE attempt_id = ? AND user_id = ? AND status = 'in_progress'
    ");
    $attemptStmt->bind_param("ii", $attempt_id, $user_id);
    $attemptStmt->execute();
    $attemptResult = $attemptStmt->get_result();
    
    if ($attemptResult->num_rows === 0) {
        unset($_SESSION['quiz_attempt_id']);
        header("Location: index.php");
        exit;
    }
    
    $attempt = $attemptResult->fetch_assoc();
    $attemptStmt->close();
    
    if ($attempt['role'] !== $role || $attempt['level'] !== $level) {
        header("Location: index.php");
        exit;
    }
    
    // CRITICAL: Verify credentials match the in-progress attempt
    // Get user details from database
    $userCheckStmt = $conn->prepare("SELECT name, email, mobile, role, level FROM users WHERE id = ?");
    $userCheckStmt->bind_param("i", $user_id);
    $userCheckStmt->execute();
    $userCheckResult = $userCheckStmt->get_result();
    
    if ($userCheckResult->num_rows === 0) {
        unset($_SESSION['quiz_attempt_id']);
        header("Location: index.php");
        exit;
    }
    
    $userData = $userCheckResult->fetch_assoc();
    $userCheckStmt->close();
    
    // Compare session credentials with database user data
    $sessionName = $_SESSION['quiz_name'] ?? '';
    $sessionMobile = $_SESSION['quiz_mobile'] ?? '';
    $sessionRole = $_SESSION['quiz_role'] ?? '';
    $sessionLevel = $_SESSION['quiz_level'] ?? '';
    
    // Check if credentials match
    $nameMatch = strtolower(trim($userData['name'])) === strtolower(trim($sessionName));
    $mobileMatch = $userData['mobile'] === $sessionMobile;
    $roleMatch = strtolower(trim($userData['role'])) === strtolower(trim($sessionRole));
    $levelMatch = strtolower(trim($userData['level'])) === strtolower(trim($sessionLevel));
    
    if (!$nameMatch || !$mobileMatch || !$roleMatch || !$levelMatch) {
        // Credentials don't match - clear session and redirect with error
        unset($_SESSION['quiz_attempt_id']);
        unset($_SESSION['quiz_user_id']);
        unset($_SESSION['quiz_role']);
        unset($_SESSION['quiz_level']);
        unset($_SESSION['quiz_name']);
        unset($_SESSION['quiz_mobile']);
        
        $userName = htmlspecialchars($userData['name'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $userEmail = htmlspecialchars($userData['email'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $userMobile = htmlspecialchars($userData['mobile'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $userRole = htmlspecialchars($userData['role'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $userLevel = htmlspecialchars($userData['level'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        
        $redirectUrl = 'index.php?name=' . urlencode($userData['name'] ?? '') . '&email=' . urlencode($userData['email'] ?? '') . '&mobile=' . urlencode($userData['mobile'] ?? '') . '&role=' . urlencode($userData['role'] ?? '') . '&level=' . urlencode($userData['level'] ?? '');
        
        echo "<!DOCTYPE html>
<html>
<head>
    <title>Credentials Mismatch - Toss Consultancy Services</title>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <link rel='stylesheet' href='assets/app.css'>
</head>
<body class='app-shell'>
    <div class='modal-overlay' style='display: flex;'>
        <div class='modal-dialog'>
            <div class='modal-header'>
                <h2 class='modal-title'>Credentials Mismatch</h2>
            </div>
            <div class='modal-body'>
                <p class='modal-message'>
                    <strong>Credentials do not match the in-progress quiz.</strong><br><br>
                    Please use the same name, phone number, email, role, and level that you used to start this quiz.
                </p>
                <div style='background: #f9fafb; padding: 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e5e7eb;'>
                    <div style='font-size: 13px; font-weight: 600; color: #6b7280; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px;'>Current Attempt Details:</div>
                    <div style='display: grid; gap: 8px; font-size: 14px; color: #1f2937;'>
                        <div><strong>Name:</strong> {$userName}</div>
                        <div><strong>Email:</strong> {$userEmail}</div>
                        <div><strong>Phone:</strong> {$userMobile}</div>
                        <div><strong>Role:</strong> {$userRole}</div>
                        <div><strong>Level:</strong> {$userLevel}</div>
                    </div>
                </div>
                <div class='modal-actions'>
                    <button type='button' class='modal-btn modal-btn-primary' onclick='window.location.href=\"{$redirectUrl}\"' style='width: 100%;'>
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>";
        exit;
    }
    
    $question_ids = json_decode($attempt['question_ids'], true);
    if (!is_array($question_ids) || empty($question_ids)) {
        header("Location: index.php");
        exit;
    }
    
    // Map role to table
    $roleToTable = [
        'Backend Developer' => 'backend_mcq_questions',
        'Python Developer'  => 'python_mcq_questions',
        'Flutter Developer' => 'flutter_mcq_questions',
        'Mern Developer'    => 'mern_mcq_questions',
        'Full Stack Developer' => 'fullstack_mcq_questions',
    ];
    $questionsTable = $roleToTable[$role] ?? null;
    if ($questionsTable === null) {
        die("Unsupported role.");
    }
    
    // Fetch questions by IDs
    $placeholders = implode(',', array_fill(0, count($question_ids), '?'));
    $fetchStmt = $conn->prepare("
        SELECT id, question, option_a, option_b, option_c, option_d 
        FROM {$questionsTable} 
        WHERE id IN ({$placeholders})
    ");
    $fetchStmt->bind_param(str_repeat('i', count($question_ids)), ...$question_ids);
    $fetchStmt->execute();
    $question_data = fetchQuestionsFromStatement($fetchStmt);
    $fetchStmt->close();
    
    // Reorder to match saved order
    $ordered_questions = [];
    foreach ($question_ids as $qid) {
        foreach ($question_data as $q) {
            if ($q['id'] == $qid) {
                $ordered_questions[] = $q;
                break;
            }
        }
    }
    $question_data = $ordered_questions;
    
    if (count($question_data) === 0) {
        die("Questions not found.");
    }
    
    // Get saved answers
    $answersStmt = $conn->prepare("SELECT question_id, selected_option FROM quiz_answers WHERE attempt_id = ?");
    $answersStmt->bind_param("i", $attempt_id);
    $answersStmt->execute();
    $answersResult = $answersStmt->get_result();
    $saved_answers = [];
    while ($row = $answersResult->fetch_assoc()) {
        $saved_answers[$row['question_id']] = $row['selected_option'];
    }
    $answersStmt->close();
    
    // CRITICAL: Calculate remaining time from expires_at (SERVER-CONTROLLED)
    // This ensures timer NEVER resets on back/refresh/browser close
    if (!empty($attempt['expires_at']) && $attempt['expires_at'] != '0000-00-00 00:00:00') {
        $expires_datetime = new DateTime($attempt['expires_at'], new DateTimeZone('Asia/Kolkata'));
        $now_datetime = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        $remaining_time_seconds = max(0, $expires_datetime->getTimestamp() - $now_datetime->getTimestamp());
    } else {
        // Fallback: Calculate from start_time (for old records without expires_at)
        $start_datetime = new DateTime($attempt['start_time'], new DateTimeZone('Asia/Kolkata'));
        $now_datetime = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        $elapsed = $now_datetime->getTimestamp() - $start_datetime->getTimestamp();
        $duration_minutes = $attempt['duration_minutes'] ?? 45;
        $remaining_time_seconds = max(0, ($duration_minutes * 60) - $elapsed);
        
        // Update expires_at for this record (migration)
        $expires_at = date('Y-m-d H:i:s', strtotime($attempt['start_time'] . " +{$duration_minutes} minutes"));
        $updateExpiresStmt = $conn->prepare("UPDATE quiz_attempts SET expires_at = ? WHERE attempt_id = ?");
        $updateExpiresStmt->bind_param("si", $expires_at, $attempt_id);
        $updateExpiresStmt->execute();
        $updateExpiresStmt->close();
    }
    
    // Ensure timer doesn't exceed maximum (safety check)
    if ($remaining_time_seconds > 2700) {
        $remaining_time_seconds = 2700;
    }
    $is_resuming = true;
    $current_question_index = (int)$attempt['current_question_index'];
    
    // Get user details
    $userStmt = $conn->prepare("SELECT name, mobile FROM users WHERE id = ?");
    $userStmt->bind_param("i", $user_id);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $userData = $userResult->fetch_assoc();
    $userName = $userData['name'] ?? $_SESSION['quiz_name'] ?? '';
    $userMobile = $userData['mobile'] ?? $_SESSION['quiz_mobile'] ?? '';
    $userStmt->close();
    
    // Now continue to HTML rendering (same as POST)
    // Skip the POST block and go directly to HTML
    goto render_quiz;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // NEW QUIZ START - Read role and level from form (required)
    $role  = trim($_POST['role'] ?? '');
    $level = trim($_POST['level'] ?? '');

    if ($role === '' || $level === '') {
        die("Please select both role and level.");
    }

    // Map UI strings to table names
    $roleToTable = [
        'Backend Developer' => 'backend_mcq_questions',
        'Python Developer'  => 'python_mcq_questions',
        'Flutter Developer' => 'flutter_mcq_questions',
        'Mern Developer'    => 'mern_mcq_questions',
        'Full Stack Developer' => 'fullstack_mcq_questions',
    ];

    if (!isset($roleToTable[$role])) {
        die("Unsupported role selected.");
    }

    $questionsTable = $roleToTable[$role];

    // Normalize level to match table enums (lowercase; some tables use 'advance')
    $normalizedLevel = strtolower($level); // 'beginner' | 'intermediate' | 'advanced'
    if ($normalizedLevel === 'advanced') {
        // Tables that use 'advance' (without 'd')
        $usesAdvance = in_array($questionsTable, ['python_mcq_questions', 'fullstack_mcq_questions', 'flutter_mcq_questions'], true);
        if ($usesAdvance) {
            $normalizedLevel = 'advance';
        }
    }
    // Basic validation
    $allowed = ['beginner', 'intermediate', 'advanced', 'advance'];
    if (!in_array($normalizedLevel, $allowed, true)) {
        die("Invalid level provided.");
    }
    // Validate phone number: must be exactly 10 digits starting with 6, 7, 8, or 9
    $mobile = $_POST['mobile'] ?? '';
    $mobile = preg_replace('/[^0-9]/', '', $mobile); // Remove any non-numeric characters
    
    if (empty($mobile) || !preg_match('/^[6789]\d{9}$/', $mobile)) {
        die("Invalid phone number. Phone number must be exactly 10 digits starting with 6, 7, 8, or 9.");
    }
    
    $email = trim(strtolower($_POST['email'] ?? ''));
    
    // CRITICAL: Check if user with same email or mobile already exists (case-insensitive, trimmed)
    // This ensures browser close/re-login finds the same user
    // Use LOWER(TRIM()) on both sides to handle any case/whitespace differences in database
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE LOWER(TRIM(email)) = ? OR mobile = ?");
    $checkStmt->bind_param("ss", $email, $mobile);
    $checkStmt->execute();
    $checkStmt->store_result();

    $existingUserId = null;
    if ($checkStmt->num_rows > 0) {
        $checkStmt->bind_result($existingUserId);
        $checkStmt->fetch();
    }
    $checkStmt->free_result();
    $checkStmt->close();

    if ($existingUserId !== null) {
        // CRITICAL: Check if user has already submitted responses OR has submitted/expired quiz attempt
        // Allow in_progress attempts (user can resume from same browser)
        // Block only submitted or expired attempts
        $responseCheck = $conn->query("SELECT COUNT(*) as count FROM responses WHERE user_id = $existingUserId");
        $responseCount = $responseCheck->fetch_assoc()['count'];
        
        // Check if user has submitted or expired quiz attempt (block these)
        // in_progress attempts are allowed (user can resume)
        $attemptCheckStmt = $conn->prepare("SELECT COUNT(*) as count FROM quiz_attempts WHERE user_id = ? AND (status = 'submitted' OR status = 'expired')");
        $attemptCheckStmt->bind_param("i", $existingUserId);
        $attemptCheckStmt->execute();
        $attemptCheckResult = $attemptCheckStmt->get_result();
        $completedAttemptCount = 0;
        if ($attemptCheckResult && ($attemptRow = $attemptCheckResult->fetch_assoc())) {
            $completedAttemptCount = (int)$attemptRow['count'];
        }
        $attemptCheckStmt->close();
        
        // User has attempted if they have submitted responses OR submitted/expired quiz attempt
        // in_progress attempts are NOT blocked (user can resume)
        if ($responseCount > 0 || $completedAttemptCount > 0) {
            // User already attempted quiz -> show popup and send back to start
            echo "<!DOCTYPE html>
<html>
<head>
    <title>Already Attempted - Toss Consultancy Services</title>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <link rel='stylesheet' href='assets/app.css'>
</head>
<body class='app-shell'>
    <div class='modal-overlay' style='display: flex;'>
        <div class='modal-dialog'>
            <div class='modal-header'>
                <h2 class='modal-title'>Already Attempted</h2>
            </div>
            <div class='modal-body'>
                <p class='modal-message'>
                    <strong>User already attempted this assessment.</strong><br><br>
                    Please use a different phone number and email to take the quiz again.
                </p>
                <div class='modal-actions'>
                    <button type='button' class='modal-btn modal-btn-primary' onclick='window.location.href=\"index.php\"' style='width: 100%;'>
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>";
            exit;
        } else {
            // User exists but hasn't submitted quiz
            // CRITICAL: Update location if different (only location can be updated)
            $newPlace = trim($_POST['place'] ?? '');
            if (!empty($newPlace)) {
                $updatePlaceStmt = $conn->prepare("UPDATE users SET place = ? WHERE id = ?");
                $updatePlaceStmt->bind_param("si", $newPlace, $existingUserId);
                $updatePlaceStmt->execute();
                $updatePlaceStmt->close();
            }
            
            // CRITICAL: Check if there's an in-progress attempt and verify credentials match
            $inProgressCheck = $conn->prepare("
                SELECT attempt_id, role, level 
                FROM quiz_attempts 
                WHERE user_id = ? AND status = 'in_progress'
                ORDER BY start_time DESC 
                LIMIT 1
            ");
            $inProgressCheck->bind_param("i", $existingUserId);
            $inProgressCheck->execute();
            $inProgressResult = $inProgressCheck->get_result();
            
            if ($inProgressResult->num_rows > 0) {
                $inProgressAttempt = $inProgressResult->fetch_assoc();
                
                // Get user details from database
                $userDetailsStmt = $conn->prepare("SELECT name, email, mobile, role, level FROM users WHERE id = ?");
                $userDetailsStmt->bind_param("i", $existingUserId);
                $userDetailsStmt->execute();
                $userDetailsResult = $userDetailsStmt->get_result();
                $userDetails = $userDetailsResult->fetch_assoc();
                $userDetailsStmt->close();
                
                // Compare current form data with database user data and attempt data
                $nameMatch = strtolower(trim($userDetails['name'])) === strtolower(trim($_POST['name'] ?? ''));
                $emailMatch = strtolower(trim($userDetails['email'])) === strtolower(trim($email));
                $mobileMatch = $userDetails['mobile'] === $mobile;
                $roleMatch = strtolower(trim($userDetails['role'])) === strtolower(trim($role)) && 
                             strtolower(trim($inProgressAttempt['role'])) === strtolower(trim($role));
                $levelMatch = strtolower(trim($userDetails['level'])) === strtolower(trim($level)) && 
                              strtolower(trim($inProgressAttempt['level'])) === strtolower(trim($level));
                
                if (!$nameMatch || !$emailMatch || !$mobileMatch || !$roleMatch || !$levelMatch) {
                    $inProgressCheck->close();
                    
                    $userName = htmlspecialchars($userDetails['name'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
                    $userEmail = htmlspecialchars($userDetails['email'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
                    $userMobile = htmlspecialchars($userDetails['mobile'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
                    $userRole = htmlspecialchars($userDetails['role'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
                    $userLevel = htmlspecialchars($userDetails['level'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
                    
                    $redirectUrl = 'index.php?name=' . urlencode($userDetails['name'] ?? '') . '&email=' . urlencode($userDetails['email'] ?? '') . '&mobile=' . urlencode($userDetails['mobile'] ?? '') . '&role=' . urlencode($userDetails['role'] ?? '') . '&level=' . urlencode($userDetails['level'] ?? '');
                    
                    echo "<!DOCTYPE html>
<html>
<head>
    <title>In-Progress Quiz Found - Toss Consultancy Services</title>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <link rel='stylesheet' href='assets/app.css'>
</head>
<body class='app-shell'>
    <div class='modal-overlay' style='display: flex;'>
        <div class='modal-dialog'>
            <div class='modal-header'>
                <h2 class='modal-title'>In-Progress Quiz Found</h2>
            </div>
            <div class='modal-body'>
                <p class='modal-message'>
                    <strong>You have an in-progress quiz.</strong><br><br>
                    Please use the same name, phone number, email, role, and level that you used to start the quiz.
                </p>
                <div style='background: #f9fafb; padding: 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e5e7eb;'>
                    <div style='font-size: 13px; font-weight: 600; color: #6b7280; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px;'>Current Attempt Details:</div>
                    <div style='display: grid; gap: 8px; font-size: 14px; color: #1f2937;'>
                        <div><strong>Name:</strong> {$userName}</div>
                        <div><strong>Email:</strong> {$userEmail}</div>
                        <div><strong>Phone:</strong> {$userMobile}</div>
                        <div><strong>Role:</strong> {$userRole}</div>
                        <div><strong>Level:</strong> {$userLevel}</div>
                    </div>
                </div>
                <div class='modal-actions'>
                    <button type='button' class='modal-btn modal-btn-primary' onclick='window.location.href=\"{$redirectUrl}\"' style='width: 100%;'>
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>";
                    exit;
                }
                
                $inProgressCheck->close();
            }
            
            // Use existing user_id
            $user_id = $existingUserId;
        }
    } else {
        // New user, insert into database (role + level columns expected)
        $stmt = $conn->prepare("INSERT INTO users (name, role, level, place, mobile, email) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $_POST['name'], $role, $level, $_POST['place'], $mobile, $email);
        $stmt->execute();
        $user_id = $stmt->insert_id;
    }
    // CRITICAL: Persist user session with proper isolation
    // Regenerate session ID to prevent session fixation attacks
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    
    $_SESSION['quiz_user_id'] = $user_id;
    $_SESSION['quiz_role'] = $role;
    $_SESSION['quiz_level'] = $level;
    $_SESSION['quiz_name'] = $_POST['name'] ?? '';
    $_SESSION['quiz_mobile'] = $mobile;
    
    // Fetch user details for display
    $userStmt = $conn->prepare("SELECT name, mobile FROM users WHERE id = ?");
    $userStmt->bind_param("i", $user_id);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $userData = $userResult->fetch_assoc();
    $userName = $userData['name'] ?? $_POST['name'] ?? '';
    $userMobile = $userData['mobile'] ?? $mobile ?? '';
    $userStmt->close();

    // Fetch 50 random questions from the selected role table filtered by level.
    // Match level/role case-insensitively and accept both 'advanced' and 'advance'.
    $levelCandidates = [$normalizedLevel];
    if ($normalizedLevel === 'advanced') {
        $levelCandidates[] = 'advance';
    } elseif ($normalizedLevel === 'advance') {
        $levelCandidates[] = 'advanced';
    }

    $baseSelect = "SELECT id, question, option_a, option_b, option_c, option_d FROM {$questionsTable}";
    $hasRoleCol = in_array($questionsTable, ['backend_mcq_questions','mern_mcq_questions','python_mcq_questions','fullstack_mcq_questions','flutter_mcq_questions'], true);

    // First attempt: filter by level (IN) and role (case-insensitive) where applicable
    if ($hasRoleCol) {
        $sql = $baseSelect . " WHERE LOWER(level) IN (?, ?) AND LOWER(role) = LOWER(?) ORDER BY RAND() LIMIT 50";
        $stmtQ = $conn->prepare($sql);
        $levelA = strtolower($levelCandidates[0]);
        $levelB = isset($levelCandidates[1]) ? strtolower($levelCandidates[1]) : strtolower($levelCandidates[0]);
        $stmtQ->bind_param("sss", $levelA, $levelB, $role);
    } else {
        $sql = $baseSelect . " WHERE LOWER(level) IN (?, ?) ORDER BY RAND() LIMIT 50";
        $stmtQ = $conn->prepare($sql);
        $levelA = strtolower($levelCandidates[0]);
        $levelB = isset($levelCandidates[1]) ? strtolower($levelCandidates[1]) : strtolower($levelCandidates[0]);
        $stmtQ->bind_param("ss", $levelA, $levelB);
    }
    $stmtQ->execute();
    $question_data = fetchQuestionsFromStatement($stmtQ);

    // Fallback: if none found and table has role, ignore role filter and just match level
    if (count($question_data) === 0 && $hasRoleCol) {
        $stmtQ->close();
        $sql = $baseSelect . " WHERE LOWER(level) IN (?, ?) ORDER BY RAND() LIMIT 50";
        $stmtQ = $conn->prepare($sql);
        $stmtQ->bind_param("ss", $levelA, $levelB);
        $stmtQ->execute();
        $question_data = fetchQuestionsFromStatement($stmtQ);
    }
    if (count($question_data) === 0) {
        die("No questions found for {$role} ({$level}). Please contact the administrator.");
    }
    
    // ============================================
    // SESSION-BASED QUIZ ATTEMPT MANAGEMENT
    // ============================================
    // CRITICAL: Check for existing active attempt (SINGLE ACTIVE SESSION LOGIC)
    // NEVER create new if active session exists (prevents data overwrite)
    // Also check for recently expired attempts (within last 5 minutes) that weren't submitted
    // This handles browser close scenario where timer might have expired
    $existingAttemptStmt = $conn->prepare("
        SELECT attempt_id, question_ids, current_question_index, remaining_time_seconds, 
               start_time, expires_at, duration_minutes, status
        FROM quiz_attempts 
        WHERE user_id = ? 
          AND LOWER(TRIM(role)) = LOWER(TRIM(?)) 
          AND LOWER(TRIM(level)) = LOWER(TRIM(?))
          AND (
              status = 'in_progress' 
              OR (status = 'expired' AND expires_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE))
          )
        ORDER BY start_time DESC
        LIMIT 1
    ");
    $existingAttemptStmt->bind_param("iss", $user_id, $role, $level);
    $existingAttemptStmt->execute();
    $existingAttemptResult = $existingAttemptStmt->get_result();
    
    $attempt_id = null;
    $is_resuming = false;
    $saved_answers = [];
    $current_question_index = 0;
    $remaining_time_seconds = 2700; // Default 45 minutes
    
    if ($existingAttemptResult->num_rows > 0) {
        // Resume existing attempt
        $existingAttempt = $existingAttemptResult->fetch_assoc();
        $attempt_id = $existingAttempt['attempt_id'];
        $is_resuming = true;
        $current_question_index = (int)$existingAttempt['current_question_index'];
        
        // If attempt was expired but recently, reactivate it
        if ($existingAttempt['status'] === 'expired') {
            // Reactivate the attempt
            $reactivateStmt = $conn->prepare("
                UPDATE quiz_attempts 
                SET status = 'in_progress' 
                WHERE attempt_id = ?
            ");
            $reactivateStmt->bind_param("i", $attempt_id);
            $reactivateStmt->execute();
            $reactivateStmt->close();
        }
        
        // CRITICAL: Calculate remaining time from expires_at (SERVER-CONTROLLED)
        // This ensures timer NEVER resets on back/refresh/browser close
        if (!empty($existingAttempt['expires_at']) && $existingAttempt['expires_at'] != '0000-00-00 00:00:00') {
            $expires_datetime = new DateTime($existingAttempt['expires_at'], new DateTimeZone('Asia/Kolkata'));
            $now_datetime = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
            $remaining_time_seconds = max(0, $expires_datetime->getTimestamp() - $now_datetime->getTimestamp());
        } else {
            // Fallback: Calculate from start_time (for old records without expires_at)
            $start_datetime = new DateTime($existingAttempt['start_time'], new DateTimeZone('Asia/Kolkata'));
            $now_datetime = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
            $elapsed = $now_datetime->getTimestamp() - $start_datetime->getTimestamp();
            $duration_minutes = $existingAttempt['duration_minutes'] ?? 45;
            $remaining_time_seconds = max(0, ($duration_minutes * 60) - $elapsed);
            
            // Update expires_at for this record (migration)
            $expires_at = date('Y-m-d H:i:s', strtotime($existingAttempt['start_time'] . " +{$duration_minutes} minutes"));
            $updateExpiresStmt = $conn->prepare("UPDATE quiz_attempts SET expires_at = ? WHERE attempt_id = ?");
            $updateExpiresStmt->bind_param("si", $expires_at, $attempt_id);
            $updateExpiresStmt->execute();
            $updateExpiresStmt->close();
        }
        
        // Ensure timer doesn't exceed maximum (safety check)
        if ($remaining_time_seconds > 2700) {
            $remaining_time_seconds = 2700;
        }
        
        // Get saved answers
        $answersStmt = $conn->prepare("
            SELECT question_id, selected_option
            FROM quiz_answers
            WHERE attempt_id = ?
        ");
        $answersStmt->bind_param("i", $attempt_id);
        $answersStmt->execute();
        $answersResult = $answersStmt->get_result();
        while ($row = $answersResult->fetch_assoc()) {
            $saved_answers[$row['question_id']] = $row['selected_option'];
        }
        $answersStmt->close();
        
        // CRITICAL: Use saved question_ids to fetch questions in EXACT same order
        // This ensures answers always match the correct questions (prevents wrong answers showing)
        $saved_question_ids = json_decode($existingAttempt['question_ids'], true);
        
        if (is_array($saved_question_ids) && !empty($saved_question_ids)) {
            // CRITICAL: Fetch questions by saved IDs in exact order (not RAND())
            // This prevents answers from showing on wrong questions
            $placeholders = implode(',', array_fill(0, count($saved_question_ids), '?'));
            $fetchByIdsSql = "SELECT id, question, option_a, option_b, option_c, option_d FROM {$questionsTable} WHERE id IN ($placeholders)";
            $fetchStmt = $conn->prepare($fetchByIdsSql);
            $fetchStmt->bind_param(str_repeat('i', count($saved_question_ids)), ...$saved_question_ids);
            $fetchStmt->execute();
            $fetched_questions = fetchQuestionsFromStatement($fetchStmt);
            $fetchStmt->close();
            
            // Reorder fetched questions to match saved question_ids order EXACTLY
            $question_map = [];
            foreach ($fetched_questions as $q) {
                $question_map[$q['id']] = $q;
            }
            $question_data = [];
            foreach ($saved_question_ids as $qid) {
                if (isset($question_map[$qid])) {
                    $question_data[] = $question_map[$qid];
                }
            }
            
            // Filter saved answers to only include questions that exist
            $filtered_answers = [];
            foreach ($saved_answers as $qid => $option) {
                if (isset($question_map[$qid])) {
                    $filtered_answers[$qid] = $option;
                }
            }
            $saved_answers = $filtered_answers;
        }
        // If saved_question_ids is invalid, keep using question_data from RAND() fetch above
    }
    
    $existingAttemptStmt->close();
    
    // Debug logging (can be removed in production)
    if ($is_resuming) {
        error_log("RESUME QUIZ: User ID=$user_id, Role=$role, Level=$level, Attempt ID=$attempt_id, Remaining Time=$remaining_time_seconds seconds");
    } else {
        error_log("NEW QUIZ: User ID=$user_id, Role=$role, Level=$level, No existing attempt found");
    }
    
    // CRITICAL: Create new attempt ONLY if no active session exists
    // NEVER create new if active session found (prevents data overwrite)
    if (!$is_resuming) {
        $question_ids_json = json_encode(array_column($question_data, 'id'));
        $duration_minutes = 45; // Fixed quiz duration
        
        // CRITICAL: Use database NOW() for both start_time and expires_at to avoid timezone issues
        // expires_at will be calculated in database using DATE_ADD
        $createAttemptStmt = $conn->prepare("
            INSERT INTO quiz_attempts (user_id, role, level, question_ids, remaining_time_seconds, duration_minutes, expires_at, start_time)
            VALUES (?, ?, ?, ?, 2700, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), NOW())
        ");
        // Type string: i=user_id, s=role, s=level, s=question_ids_json, i=duration_minutes, i=duration_minutes_for_dateadd (6 parameters)
        $createAttemptStmt->bind_param("isssii", $user_id, $role, $level, $question_ids_json, $duration_minutes, $duration_minutes);
        $createAttemptStmt->execute();
        $attempt_id = $createAttemptStmt->insert_id;
        $createAttemptStmt->close();
        
        // Initialize variables for new attempt
        $saved_answers = [];
        $current_question_index = 0;
        $remaining_time_seconds = 2700;
    }
    
    // CRITICAL: Store attempt_id in session (ensures data isolation per candidate)
    // Each candidate gets their own unique attempt_id, preventing data leakage
    // All API endpoints verify user_id matches attempt_id to prevent cross-candidate access
    $_SESSION['quiz_attempt_id'] = $attempt_id;
    
    render_quiz:
    // Variables are already set above (either from GET resume or POST new/continue)
    ?>

<!DOCTYPE html>
<html>
<head>
    <title>Quiz - Toss Consultancy Services</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/app.css">
</head>
<body class="app-shell app-shell-quiz">
    <!-- <div class="warning-banner" style="background-color: #28a745; color: white;">
        <strong>✓ Auto-save enabled.</strong> Your progress is saved automatically. You can safely refresh or return later.
    </div> -->

    <header class="app-header">
        <div class="app-header-inner">
            <div class="brand-lockup">
                <span class="brand-pill">Toss Consultancy Services</span>
                <div class="brand-text">
                    <span class="brand-title">Assessment in progress</span>
                    <span class="brand-subtitle">
                        <?php echo htmlspecialchars($userName); ?> · <?php echo htmlspecialchars($userMobile); ?>
                    </span>
                    <span class="brand-subtitle" style="font-size: 11px; margin-top: 2px; opacity: 0.9;">
                        Role: <?php echo htmlspecialchars($role); ?> · Level: <?php echo htmlspecialchars($level); ?>
                    </span>
                </div>
            </div>
            <div class="header-meta">
                <button type="button" class="theme-toggle-btn" id="themeToggle" aria-label="Toggle dark mode">
                    <span class="theme-toggle-icon" id="themeToggleIcon">🌙</span>
                    <span class="theme-toggle-label" id="themeToggleLabel">Dark</span>
                </button>
                <span class="header-meta-pill">Timer: <span id="timer" class="timer-display">45:00</span></span>
                <span>50 questions · Single attempt</span>
            </div>
        </div>
    </header>

    <!-- Modal Popup for Warnings -->
    <div class="modal-overlay" id="warningModal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h2 class="modal-title">Warning: Progress Will Be Lost</h2>
            </div>
            <div class="modal-body">
                <p class="modal-message">
                    <strong>Your progress is automatically saved!</strong><br><br>
                    You can safely reload the page or return later. Your answers and timer will be restored automatically. However, navigating away may interrupt your assessment flow.
                </p>
                <div class="modal-actions">
                    <button type="button" class="modal-btn modal-btn-secondary" onclick="closeWarningModal()">
                        Stay on Page
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Popup for Auto-Submit -->
    <div class="modal-overlay" id="autoSubmitModal" style="display: none;">
        <div class="modal-dialog">
            <div class="modal-header" style="background: linear-gradient(135deg, #fee2e2, #fef2f2);">
                <h2 class="modal-title" style="color: #b91c1c;">Time is Up</h2>
            </div>
            <div class="modal-body">
                <p class="modal-message">
                    <strong>Your time has expired!</strong><br><br>
                    The quiz will be automatically submitted now. Your answers have been saved.
                </p>
                <div class="modal-actions">
                    <button type="button" class="modal-btn modal-btn-primary" id="autoSubmitBtn" style="width: 100%;">
                        Submitting...
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Popup for Credentials Mismatch -->
    <div class="modal-overlay" id="credentialsModal">
        <div class="modal-dialog">
            <div class="modal-header" style="background: linear-gradient(135deg, #fef3c7, #fde68a);">
                <h2 class="modal-title" style="color: #92400e;">Credentials Mismatch</h2>
            </div>
            <div class="modal-body">
                <p class="modal-message" id="credentialsMessage" style="color: #374151; margin-bottom: 20px;"></p>
                <div id="credentialsDetails" style="background: #f9fafb; padding: 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e5e7eb;"></div>
                <div class="modal-actions">
                    <button type="button" class="modal-btn modal-btn-primary" onclick="closeCredentialsModal()" style="width: 100%;">
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <main class="app-main">
        <div class="app-main-inner">
            <section class="quiz-layout">
                <div class="card quiz-main-card">
                    <div class="card-header">
                        <div class="badge badge-neutral">Question set</div>
                        <h1 class="card-title">Technical multiple-choice questions</h1>
                        <div class="quiz-header-meta">
                            <span>You can skip questions if needed.</span>
                        </div>
                    </div>

                    <hr class="card-divider">

                    <form action="submit_quiz.php" method="POST" id="quizForm">
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                        <input type="hidden" name="attempt_id" value="<?php echo $attempt_id; ?>">
                        <input type="hidden" name="role" value="<?php echo htmlspecialchars($role); ?>">
                        <input type="hidden" name="level" value="<?php echo htmlspecialchars($level); ?>">
                        <input type="hidden" name="all_question_ids" value="<?php echo htmlspecialchars(json_encode(array_column($question_data, 'id'))); ?>">
                        <?php
                        $page = 0;
                        $block = -1;
                        foreach ($question_data as $index => $q) {
                            $currentBlock = intdiv($index, 1);
                            
                            // Open new block when we enter a new block
                            if ($currentBlock != $block) {
                                // Close previous block if exists
                                if ($block >= 0) {
                                    echo "</div>";
                                }
                                $block = $currentBlock;
                                // Set active class for the saved block if resuming, otherwise block 0
                                $activeClass = ($block == $current_question_index && $is_resuming) ? ' active' : (($block == 0 && !$is_resuming) ? ' active' : '');
                                echo "<div class='quiz-question-block question-block{$activeClass}' id='block-$block'>";
                            }

                            echo "<article class='quiz-question-item'>";
                            echo "<h2 class='quiz-question-title'>Q" . ($index + 1) . ". " . htmlspecialchars($q['question'] ?? '', ENT_QUOTES, 'UTF-8') . "</h2>";
                            // Restore saved answer if exists
                            $saved_option = $saved_answers[$q['id']] ?? null;
                            $checked_a = ($saved_option === 'A') ? ' checked' : '';
                            $checked_b = ($saved_option === 'B') ? ' checked' : '';
                            $checked_c = ($saved_option === 'C') ? ' checked' : '';
                            $checked_d = ($saved_option === 'D') ? ' checked' : '';
                            $opt_a = htmlspecialchars($q['option_a'] ?? '', ENT_QUOTES, 'UTF-8');
                            $opt_b = htmlspecialchars($q['option_b'] ?? '', ENT_QUOTES, 'UTF-8');
                            $opt_c = htmlspecialchars($q['option_c'] ?? '', ENT_QUOTES, 'UTF-8');
                            $opt_d = htmlspecialchars($q['option_d'] ?? '', ENT_QUOTES, 'UTF-8');
                            echo "<label class='quiz-option'><input type='radio' name='answers[{$q['id']}]' value='A' data-question-id='{$q['id']}'{$checked_a}> <span>A) {$opt_a}</span></label>";
                            echo "<label class='quiz-option'><input type='radio' name='answers[{$q['id']}]' value='B' data-question-id='{$q['id']}'{$checked_b}> <span>B) {$opt_b}</span></label>";
                            echo "<label class='quiz-option'><input type='radio' name='answers[{$q['id']}]' value='C' data-question-id='{$q['id']}'{$checked_c}> <span>C) {$opt_c}</span></label>";
                            echo "<label class='quiz-option'><input type='radio' name='answers[{$q['id']}]' value='D' data-question-id='{$q['id']}'{$checked_d}> <span>D) {$opt_d}</span></label>";
                            echo "</article>";
                        }
                        // Close the last block
                        if ($block >= 0) {
                            echo "</div>";
                        }
                        ?>
                        <div class="quiz-nav">
                            <button type="button" class="btn btn-outline btn-sm" id="prevBtn" onclick="changePage(-1)" disabled>
                                ← Previous
                            </button>
                            <div class="quiz-nav-right">
                                <button type="button" class="btn btn-outline btn-sm" id="nextBtn" onclick="changePage(1)">
                                    Next →
                                </button>
                                <button type="submit" class="btn btn-primary btn-sm" id="submitBtn" style="display:none;">
                                    Submit quiz
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <aside class="card quiz-sidebar-card">
                    <div class="card-section-title">Session overview</div>
                    <div class="timer-shell">
                        <div>
                            <div class="timer-label">Time remaining</div>
                            <div id="timerSidebar" class="timer-value">45:00</div>
                        </div>
                        <div class="badge badge-danger">Auto submit on timeout</div>
                    </div>

                    <hr class="card-divider">

                    <div class="card-section-title">Question progress</div>
                    <div class="quiz-sidebar-meta">
                        <span><strong>Total</strong>: <?php echo count($question_data); ?> questions</span>
                        <span id="answeredCountLabel"><strong>Answered</strong>: 0</span>
                    </div>
                    <div class="progress-grid" id="progressGrid">
                        <?php
                        $totalQuestions = count($question_data);
                        for ($i = 0; $i < $totalQuestions; $i++) {
                            $questionNumber = $i + 1;
                            echo "<div class='progress-dot' data-question-number='{$questionNumber}' title='Jump to question {$questionNumber}'>{$questionNumber}</div>";
                        }
                        ?>
                    </div>
                </aside>
            </section>
        </div>
    </main>

    <script>
        // Theme toggle (light / dark) for quiz page
        (function() {
            const body = document.body;
            const toggleBtn = document.getElementById('themeToggle');
            const toggleIcon = document.getElementById('themeToggleIcon');
            const toggleLabel = document.getElementById('themeToggleLabel');

            function applyTheme(theme) {
                if (theme === 'dark') {
                    body.classList.add('dark-mode');
                    if (toggleIcon) toggleIcon.textContent = '☀️';
                    if (toggleLabel) toggleLabel.textContent = 'Light';
                } else {
                    body.classList.remove('dark-mode');
                    if (toggleIcon) toggleIcon.textContent = '🌙';
                    if (toggleLabel) toggleLabel.textContent = 'Dark';
                }
            }

            // Initial theme from sessionStorage (resets for each new candidate session)
            // Always start with light mode for new candidates
            const stored = window.sessionStorage.getItem('quiz-theme');
            const initialTheme = stored || 'light';
            applyTheme(initialTheme);

            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    const isDark = body.classList.contains('dark-mode');
                    const nextTheme = isDark ? 'light' : 'dark';
                    applyTheme(nextTheme);
                    window.sessionStorage.setItem('quiz-theme', nextTheme);
                });
            }
        })();
    </script>
    <script>
        // Basic deterrent: disable context menu and common developer shortcuts
        document.addEventListener('contextmenu', event => event.preventDefault());
        document.onkeydown = function(e) {
            if (e.keyCode === 123) return false;
            if (e.ctrlKey && e.shiftKey && (e.keyCode === 73 || e.keyCode === 74)) return false;
            if (e.ctrlKey && (e.keyCode === 85 || e.keyCode === 83)) return false;
        };
    </script>
    <script>
        // Modal functions
        function showWarningModal() {
            const modal = document.getElementById('warningModal');
            if (modal) {
                modal.classList.add('active');
            }
        }

        function closeWarningModal() {
            const modal = document.getElementById('warningModal');
            if (modal) {
                modal.classList.remove('active');
            }
        }

        function showAutoSubmitModal() {
            const modal = document.getElementById('autoSubmitModal');
            const submitBtn = document.getElementById('autoSubmitBtn');
            if (modal) {
                modal.style.display = 'block';
                // Disable button and show submitting state
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Submitting...';
                }
                // Auto-submit after a brief delay to show the modal
                setTimeout(() => {
                    document.getElementById("quizForm").submit();
                }, 1000);
            } else {
                // Fallback if modal not found
                document.getElementById("quizForm").submit();
            }
        }

        // Close modal when clicking outside
        document.getElementById('warningModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeWarningModal();
            }
        });

        // Disable context menu and common developer shortcuts
        const blockMessage = 'This action is disabled on this page.';
        document.addEventListener('contextmenu', (event) => {
            event.preventDefault();
            showWarningModal();
        });
        document.addEventListener('keydown', (event) => {
            const key = event.key.toLowerCase();
            if (
                event.key === 'F12' ||
                (event.ctrlKey && event.shiftKey && ['i', 'j', 'c', 'k', 'p'].includes(key)) ||
                (event.ctrlKey && ['u', 's', 'p'].includes(key)) ||
                (event.ctrlKey && event.altKey && key === 'i')
            ) {
                event.preventDefault();
                event.stopPropagation();
                showWarningModal();
                return false;
            }
            return true;
        });

        // Guard: Allow reload (progress is auto-saved), but warn on navigation away
        let guardEnabled = true;
        
        // onbeforeunload: Show warning, DON'T stop timer (user might cancel)
        window.onbeforeunload = function(e) {
            if (!guardEnabled) return;
            
            // Show warning message
            const message = 'Are you sure you want to leave? Your progress is saved, but you may lose your current position.';
            e = e || window.event;
            if (e) e.returnValue = message;
            
            // DON'T stop timer here - user might click Cancel
            return message;
        };
        
        // unload: Fires ONLY when page is actually unloading (after user confirms Leave)
        // This does NOT fire when user clicks Cancel
        window.addEventListener('unload', function(e) {
            // Page is actually leaving - stop timer
            window.stopQuizTimer();
            if (timerInterval) {
                clearInterval(timerInterval);
                timerInterval = null;
            }
            timeLeft = -1;
        });
        
        // visibilitychange: Handle tab switch (don't stop timer)
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                // Page is hidden (tab switch, minimize) - don't stop timer
                // Timer will sync when page becomes visible again
            }
        });
        // Disable F5 / Ctrl+R
        window.addEventListener('keydown', function(e) {
            if (e.key === 'F5' || (e.ctrlKey && e.key.toLowerCase() === 'r')) {
                e.preventDefault();
                showWarningModal();
            }
        });
        // Note: Page reload is now supported - quiz state is restored from server
        // sessionStorage is kept for backward compatibility but no longer blocks reload
        if (!sessionStorage.getItem('quizStarted')) {
            sessionStorage.setItem('quizStarted', '1');
        }

        // Session-based quiz state
        const attemptId = <?php echo $attempt_id; ?>;
        const isResuming = <?php echo $is_resuming ? 'true' : 'false'; ?>;
        const savedAnswers = <?php echo json_encode($saved_answers); ?>;
        let currentBlock = <?php echo $current_question_index; ?>;
        const totalBlocks = <?php echo ceil(count($question_data) / 1); ?>;
        const questionIds = <?php echo json_encode(array_column($question_data, 'id')); ?>;

        // Immediately scroll to top before DOM loads
        window.scrollTo(0, 0);
        document.documentElement.scrollTop = 0;
        document.body.scrollTop = 0;
        
        // Ensure first block is active on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Scroll to top first
            window.scrollTo(0, 0);
            document.documentElement.scrollTop = 0;
            document.body.scrollTop = 0;
            
            // Also scroll the main container and card
            const mainContainer = document.querySelector('.app-main');
            if (mainContainer) {
                mainContainer.scrollTop = 0;
            }
            const card = document.querySelector('.quiz-main-card');
            if (card) {
                card.scrollTop = 0;
            }
            const form = document.getElementById('quizForm');
            if (form) {
                form.scrollTop = 0;
            }
            
            // Check if a block is already active (set by PHP)
            let foundActiveBlock = -1;
            for (let i = 0; i < totalBlocks; i++) {
                const block = document.getElementById(`block-${i}`);
                if (block && block.classList.contains('active')) {
                    foundActiveBlock = i;
                    break;
                }
            }
            
            // If no block is active, restore to saved position or start at block 0
            if (foundActiveBlock === -1) {
                const targetBlock = isResuming ? currentBlock : 0;
                
                // Remove active from all blocks first
                for (let i = 0; i < totalBlocks; i++) {
                const block = document.getElementById(`block-${i}`);
                if (block) {
                    block.classList.remove('active');
                }
            }
                
                // Activate the target block
                const targetBlockEl = document.getElementById(`block-${targetBlock}`);
                if (targetBlockEl) {
                    targetBlockEl.classList.add('active');
                    currentBlock = targetBlock;
                } else {
                    // Fallback to block 0 if target block not found
                    const firstBlock = document.getElementById('block-0');
                    if (firstBlock) {
                        firstBlock.classList.add('active');
                        currentBlock = 0;
                    }
                }
            } else {
                // Use the already active block
                currentBlock = foundActiveBlock;
            }
            
            updateNav();
            updateAnsweredProgress();
            
            // Force scroll to top multiple times to ensure
            setTimeout(() => {
                window.scrollTo(0, 0);
                document.documentElement.scrollTop = 0;
                document.body.scrollTop = 0;
            }, 50);
            setTimeout(() => {
                window.scrollTo(0, 0);
                document.documentElement.scrollTop = 0;
                document.body.scrollTop = 0;
            }, 200);
        });

        function updateNav() {
            document.getElementById("prevBtn").disabled = currentBlock === 0;
            document.getElementById("nextBtn").style.display = currentBlock === totalBlocks - 1 ? "none" : "inline-flex";
            document.getElementById("submitBtn").style.display = currentBlock === totalBlocks - 1 ? "inline-flex" : "none";
        }
        function changePage(step) {
            document.getElementById(`block-${currentBlock}`).classList.remove("active");
            currentBlock += step;
            document.getElementById(`block-${currentBlock}`).classList.add("active");
            updateNav();
            updateAnsweredProgress();
            
            // Update question position on server
            updateQuestionPosition(currentBlock);
        }

        // Update current question position on server
        async function updateQuestionPosition(index) {
            try {
                await fetch('update_question_position.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        attempt_id: attemptId,
                        question_index: index
                    })
                });
            } catch (error) {
                console.error('Position update error:', error);
            }
        }

        // Timer logic - Server-controlled
        let timeLeft = <?php echo (int)$remaining_time_seconds; ?>;
        
        // Debug: Log initial timer value
        console.log('Initial timer from server:', timeLeft, 'seconds =', Math.floor(timeLeft / 60) + ':' + (timeLeft % 60 < 10 ? '0' : '') + (timeLeft % 60));
        console.log('Is resuming:', <?php echo $is_resuming ? 'true' : 'false'; ?>);
        
        // Safety check: Ensure timer is within valid range (0 to 2700 seconds = 45 minutes)
        if (timeLeft > 2700) {
            console.warn('Timer value too high (' + timeLeft + '), resetting to 45 minutes');
            timeLeft = 2700;
        }
        if (timeLeft < 0) {
            console.warn('Timer value negative (' + timeLeft + '), resetting to 0');
            timeLeft = 0;
        }
        
        let timerInterval = null;
        
        // Global function to stop timer forcefully (accessible from anywhere)
        window.stopQuizTimer = function() {
            if (timerInterval) {
                clearInterval(timerInterval);
                timerInterval = null;
            }
            // Also try to clear any other intervals that might be running
            for (let i = 1; i < 9999; i++) {
                clearInterval(i);
            }
        };
        
        function updateTimerDisplay() {
            // Ensure timeLeft is valid
            if (timeLeft < 0) timeLeft = 0;
            if (timeLeft > 2700) timeLeft = 2700;
            
            let min = Math.floor(timeLeft / 60);
            let sec = timeLeft % 60;
            const formatted = `${min}:${sec < 10 ? '0' : ''}${sec}`;
            const timerTop = document.getElementById('timer');
            const timerSidebar = document.getElementById('timerSidebar');
            if (timerTop) timerTop.textContent = formatted;
            if (timerSidebar) timerSidebar.textContent = formatted;
        }
        
        function startTimer() {
            // Don't start if timer is already stopped or invalid
            if (timeLeft < 0) {
                return;
            }
            
            // Clear any existing interval first
            if (timerInterval) {
                clearInterval(timerInterval);
            }
            
            updateTimerDisplay();
            timerInterval = setInterval(() => {
                // Check if timer was stopped (timeLeft < 0 means stopped)
                if (timeLeft < 0 || !timerInterval) {
                    clearInterval(timerInterval);
                    timerInterval = null;
                    return;
                }
                
            if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    timerInterval = null;
                showAutoSubmitModal();
                } else {
            timeLeft--;
                    updateTimerDisplay();
                }
        }, 1000);
        }
        
        // CRITICAL: Sync timer with server FIRST before starting
        // This ensures timer is always accurate when page loads (especially after back button)
        // Timer will NOT start until sync completes - this prevents timer from running with wrong value
        (async () => {
            // Ensure timer is stopped initially (in case of page reload/back button)
            if (timerInterval) {
                clearInterval(timerInterval);
                timerInterval = null;
            }
            
            try {
                const response = await fetch('sync_timer.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        attempt_id: attemptId,
                        client_remaining_seconds: timeLeft
                    })
                });
                const data = await response.json();
                
                if (data.success) {
                    if (data.expired) {
                        // Only submit if actually expired (not on new quiz start)
                        // For new quiz, if expired is true, it's a calculation error
                        if (isResuming && timeLeft > 0) {
                            showAutoSubmitModal();
                            return;
                        } else {
                            // New quiz or calculation error - use default timer
                            console.warn('Timer sync returned expired for new quiz, using default 2700 seconds');
                            timeLeft = 2700;
                            updateTimerDisplay();
                        }
                    } else {
                        // Server time is authoritative - ALWAYS use server value
                        // This accounts for time elapsed while away (back button, reload, etc.)
                        const oldTime = timeLeft;
                        const newTime = data.remaining_seconds;
                        
                        // Safety check: If new quiz and server returns 0 or negative, use default
                        if (!isResuming && (newTime <= 0 || newTime > 2700)) {
                            console.warn('Timer sync returned invalid value for new quiz:', newTime, ', using default 2700 seconds');
                            timeLeft = 2700;
                        } else {
                            // Always update timer with server value (no threshold check)
                            // This ensures timer resumes from correct time, not from where it was left
                            timeLeft = newTime;
                        }
                        
                        // Update display immediately with correct time
                        updateTimerDisplay();
                        
                        if (oldTime !== newTime && newTime > 0) {
                            console.log('Timer synced on page load:', oldTime, '->', newTime, 'seconds (elapsed while away:', (oldTime - newTime), 'seconds)');
                        }
                    }
                }
            } catch (error) {
                console.error('Initial timer sync error:', error);
                // Continue with PHP value if sync fails
            }
            
            // Start timer ONLY AFTER sync completes (with accurate server time)
            // This ensures timer never runs with wrong value
            startTimer();
        })();
        
        // Sync timer with server every 30 seconds
        setInterval(async () => {
            if (timeLeft <= 0) return;
            
            try {
                const response = await fetch('sync_timer.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        attempt_id: attemptId,
                        client_remaining_seconds: timeLeft
                    })
                });
                const data = await response.json();
                
                if (data.success) {
                    if (data.expired) {
                        clearInterval(timerInterval);
                        showAutoSubmitModal();
                    } else if (data.needs_correction || Math.abs(timeLeft - data.remaining_seconds) > 5) {
                        // Server time is authoritative - update client timer
                        // Stop current timer interval
                        if (timerInterval) {
                            clearInterval(timerInterval);
                        }
                        
                        // Update timer value
                        timeLeft = data.remaining_seconds;
                        
                        // Restart timer with new value
                        startTimer();
                        
                        console.log('Timer corrected during periodic sync:', timeLeft, 'seconds');
                    }
                }
            } catch (error) {
                console.error('Timer sync error:', error);
                // Continue with client timer on error
            }
        }, 30000); // Every 30 seconds

        // Progress tracking and jump navigation for questions (UI-only)
        const progressGrid = document.getElementById('progressGrid');
        const answeredCountLabel = document.getElementById('answeredCountLabel');

        function updateAnsweredProgress() {
            if (!progressGrid) return;
            const dots = progressGrid.querySelectorAll('.progress-dot');
            let answered = 0;

            questionIds.forEach((qid, idx) => {
                const hasAnswer = !!document.querySelector(`input[name="answers[${qid}]"]:checked`);
                const dot = dots[idx];
                if (dot) {
                    dot.classList.toggle('progress-dot-answered', hasAnswer);
                    dot.classList.toggle('progress-dot-current', Math.floor(idx / 1) === currentBlock);
                }
                if (hasAnswer) answered++;
            });

            if (answeredCountLabel) {
                answeredCountLabel.textContent = `Answered: ${answered}`;
            }
        }

        // Allow clicking on progress dots to jump directly to a question/page
        if (progressGrid) {
            const dots = progressGrid.querySelectorAll('.progress-dot');
            dots.forEach((dot, idx) => {
                dot.addEventListener('click', () => {
                    const targetIndex = idx; // zero-based index in questionIds
                    const targetBlock = Math.floor(targetIndex / 1);

                    // Switch page if needed
                    if (targetBlock !== currentBlock) {
                        const currentBlockEl = document.getElementById(`block-${currentBlock}`);
                        const targetBlockEl = document.getElementById(`block-${targetBlock}`);
                        if (currentBlockEl && targetBlockEl) {
                            currentBlockEl.classList.remove('active');
                            currentBlock = targetBlock;
                            targetBlockEl.classList.add('active');
                            updateNav();
                            updateQuestionPosition(currentBlock);
                        }
                    }

                    // Only scroll if page is at the top, otherwise don't scroll
                    const isAtTop = window.scrollY === 0 || document.documentElement.scrollTop === 0;
                    if (isAtTop) {
                        // Scroll to top of main content area first
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        
                        // Then scroll to the specific question within the block after a short delay
                        setTimeout(() => {
                            const qid = questionIds[targetIndex];
                            const anyOption = document.querySelector(`input[name="answers[${qid}]"]`);
                            if (anyOption) {
                                const questionItem = anyOption.closest('.quiz-question-item');
                                if (questionItem) {
                                    questionItem.scrollIntoView({
                                        behavior: 'smooth',
                                        block: 'center'
                                    });
                                }
                            }
                        }, 300);
                    }

                    updateAnsweredProgress();
                });
            });
        }

        // Auto-save answer when candidate selects an option
        async function saveAnswer(questionId, selectedOption) {
            try {
                const response = await fetch('save_answer.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        attempt_id: attemptId,
                        question_id: questionId,
                        selected_option: selectedOption
                    })
                });
                const data = await response.json();
                
                if (data.success) {
                    // Answer saved successfully
                    console.log(`Answer saved: Q${questionId} = ${selectedOption}`);
                } else {
                    console.error('Failed to save answer:', data.error);
                }
            } catch (error) {
                console.error('Save answer error:', error);
                // Continue even if save fails (network issue, etc.)
            }
        }
        
        // Add event listeners for auto-save
        document.querySelectorAll('#quizForm input[type="radio"]').forEach((input) => {
            input.addEventListener('change', function() {
                updateAnsweredProgress();
                const questionId = this.getAttribute('data-question-id');
                const selectedOption = this.value;
                if (questionId && selectedOption) {
                    saveAnswer(questionId, selectedOption);
                }
            });
        });
        updateAnsweredProgress();
        
        // Position restoration is already handled in DOMContentLoaded above
        // This code is redundant but kept for safety

        // Prevent back button navigation and clear quiz data
        history.pushState(null, null, location.href);
        window.onpopstate = function(event) {
            history.pushState(null, null, location.href);
            
            // CRITICAL: Timer is SERVER-CONTROLLED
            // We DON'T stop client timer - server will calculate correct time when user comes back
            // Client timer is just for display, server is source of truth
            
            // Show modal warning - COMMENTED OUT as per user request
            // showWarningModal();
            
            // IMMEDIATELY redirect
            // Server will handle timer calculation on resume
            window.location.href = 'index.php';
            
            // The code below won't execute due to immediate redirect above
            // But keeping it commented for reference
            /*
            // Sync timer immediately when back button is pressed (user might be away for a while)
            // This saves current timer state to server before redirect
            setTimeout(async () => {
                try {
                    const response = await fetch('sync_timer.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            attempt_id: attemptId,
                            client_remaining_seconds: timeLeft
                        })
                    });
                    const data = await response.json();
                    
                    if (data.success && !data.expired) {
                        console.log('Timer synced before redirect:', data.remaining_seconds, 'seconds');
                    }
                } catch (error) {
                    console.error('Timer sync error after back button:', error);
                }
            }, 500);
            
            // Clear all form data (quiz answers) after a delay
            setTimeout(function() {
                document.getElementById('quizForm').reset();
                localStorage.clear();
                sessionStorage.clear();
                setTimeout(function() {
                    window.location.href = 'index.php';
                }, 2000);
            }, 500);
            */
        };
        
        // Also sync timer when page becomes visible again (user switched tabs/windows or came back)
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                // Page is visible again, sync timer to account for time elapsed
                setTimeout(async () => {
                    try {
                        const response = await fetch('sync_timer.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({
                                attempt_id: attemptId,
                                client_remaining_seconds: timeLeft
                            })
                        });
                        const data = await response.json();
                        
                        if (data.success && !data.expired) {
                            const oldTime = timeLeft;
                            const newTime = data.remaining_seconds;
                            
                            // Only update if there's a significant difference (more than 2 seconds)
                            if (Math.abs(oldTime - newTime) > 2) {
                                // Stop current timer interval
                                if (timerInterval) {
                                    clearInterval(timerInterval);
                                }
                                
                                // Update timer value
                                timeLeft = newTime;
                                
                                // Restart timer with new value
                                startTimer();
                                
                                console.log('Timer synced after page visible:', oldTime, '->', newTime, 'seconds');
                            }
                        }
                    } catch (error) {
                        console.error('Timer sync error after visibility change:', error);
                    }
                }, 500);
            }
        });

        // When submitting, allow navigation (remove beforeunload) and block double-submit
        const form = document.getElementById('quizForm');
        let submitted = false;
        form.addEventListener('submit', function(e) {
            // Allow submission even if some questions are unanswered (questions can be skipped)
            if (submitted) {
                e.preventDefault();
                return false;
            }
            submitted = true;
            guardEnabled = false;
            window.onbeforeunload = null;
        });
    </script>
</body>
</html>
<?php
}
?>
