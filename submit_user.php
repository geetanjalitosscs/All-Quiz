<?php
// ===============================
// File: submit_user.php
// ===============================
include 'db.php';

$name = $_POST['name'];
$position = $_POST['position'];
$place = $_POST['place'];
$mobile = $_POST['mobile'] ?? '';
$email = $_POST['email'];

// Validate phone number: must be exactly 10 digits starting with 6, 7, 8, or 9
$mobile = preg_replace('/[^0-9]/', '', $mobile); // Remove any non-numeric characters

if (empty($mobile) || !preg_match('/^[6789]\d{9}$/', $mobile)) {
    echo json_encode(["error" => "Invalid phone number. Phone number must be exactly 10 digits starting with 6, 7, 8, or 9."]);
    exit;
}

// Check if user with same email or mobile already exists and has submitted quiz
$checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR mobile = ?");
$checkStmt->bind_param("ss", $email, $mobile);
$checkStmt->execute();
$existingUser = $checkStmt->get_result();

if ($existingUser->num_rows > 0) {
    $existingUserData = $existingUser->fetch_assoc();
    $existingUserId = $existingUserData['id'];
    
    // Check if user has already submitted responses
    $responseCheck = $conn->query("SELECT COUNT(*) as count FROM responses WHERE user_id = $existingUserId");
    $responseCount = $responseCheck->fetch_assoc()['count'];
    
    if ($responseCount > 0) {
        // User already submitted quiz
        echo json_encode(["error" => "You have already submitted the quiz. Duplicate submission not allowed.", "user_id" => $existingUserId]);
        exit;
    } else {
        // User exists but hasn't submitted quiz, use existing user_id
        $user_id = $existingUserId;
        echo json_encode(["user_id" => $user_id]);
    }
} else {
    // New user, insert into database
    $sql = "INSERT INTO users (name, position, place, mobile, email) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $name, $position, $place, $mobile, $email);
    $stmt->execute();
    
    $user_id = $stmt->insert_id;
    echo json_encode(["user_id" => $user_id]);
}
?>