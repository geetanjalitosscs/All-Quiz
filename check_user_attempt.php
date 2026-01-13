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
    
    // CRITICAL: Check if user has ANY quiz attempt (in_progress, submitted, or expired)
    // This prevents duplicate attempts from different browsers
    $attemptCheckStmt = $conn->prepare("
        SELECT COUNT(*) AS c 
        FROM quiz_attempts 
        WHERE user_id = ?
    ");
    $attemptCheckStmt->bind_param("i", $userId);
    $attemptCheckStmt->execute();
    $attemptResult = $attemptCheckStmt->get_result();
    $attemptCount = 0;
    if ($attemptResult && ($attemptRow = $attemptResult->fetch_assoc())) {
        $attemptCount = (int)$attemptRow['c'];
    }
    $attemptCheckStmt->close();
    
    // User has attempted if:
    // 1. Has submitted responses (legacy), OR
    // 2. Has any quiz attempt (in_progress, submitted, or expired)
    $response['attempted'] = ($responseCount > 0 || $attemptCount > 0);
}

echo json_encode($response);
exit;
?>


