<?php
// ===============================
// File: check_user_attempt.php
// Purpose: AJAX endpoint to check if a user (by email or mobile) already attempted
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

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR mobile = ? LIMIT 1");
$stmt->bind_param("ss", $email, $mobile);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $userId = (int)$row['id'];
    $response['exists'] = true;
    $rc = $conn->query("SELECT COUNT(*) AS c FROM responses WHERE user_id = {$userId}");
    $cnt = (int)$rc->fetch_assoc()['c'];
    $response['attempted'] = $cnt > 0;
}

echo json_encode($response);
exit;
?>


