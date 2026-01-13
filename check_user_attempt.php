<?php
// ===============================
// File: check_user_attempt.php
// Purpose: AJAX endpoint to check if a user (by email or mobile) already attempted
// CRITICAL: Checks both responses table AND quiz_attempts table to prevent duplicate attempts
// ===============================
header('Content-Type: application/json');
require_once 'db.php';

$email  = isset($_POST['email']) ? trim($_POST['email']) : '';
$mobile = isset($_POST['mobile']) ? preg_replace('/[^0-9]/', '', $_POST['mobile']) : '';

$response = ['ok' => true, 'exists' => false, 'attempted' => false];

if ($email === '' && $mobile === '') {
    echo json_encode($response);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM users WHERE LOWER(TRIM(email)) = LOWER(TRIM(?)) OR mobile = ? LIMIT 1");
$stmt->bind_param("ss", $email, $mobile);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $userId = (int)$row['id'];
    $response['exists'] = true;
    
    // CRITICAL: Check if user has submitted responses (legacy check)
    $rc = $conn->query("SELECT COUNT(*) AS c FROM responses WHERE user_id = {$userId}");
    $responseCount = (int)$rc->fetch_assoc()['c'];
    
    // CRITICAL: Check if user has submitted or expired quiz attempt
    // Allow in_progress attempts (user can resume from same browser)
    // Block only submitted or expired attempts
    $attemptCheckStmt = $conn->prepare("
        SELECT COUNT(*) AS c 
        FROM quiz_attempts 
        WHERE user_id = ? AND (status = 'submitted' OR status = 'expired')
    ");
    $attemptCheckStmt->bind_param("i", $userId);
    $attemptCheckStmt->execute();
    $attemptResult = $attemptCheckStmt->get_result();
    $completedAttemptCount = 0;
    if ($attemptResult && ($attemptRow = $attemptResult->fetch_assoc())) {
        $completedAttemptCount = (int)$attemptRow['c'];
    }
    $attemptCheckStmt->close();
    
    // User has attempted if:
    // 1. Has submitted responses (legacy), OR
    // 2. Has submitted or expired quiz attempt
    // NOTE: in_progress attempts are allowed (user can resume)
    $response['attempted'] = ($responseCount > 0 || $completedAttemptCount > 0);
}

echo json_encode($response);
exit;
?>


