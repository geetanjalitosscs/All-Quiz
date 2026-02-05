<?php
// ===============================
// File: delete_users.php
// ===============================
include 'db.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get and validate input
$userIdsInput = $_POST['user_ids'] ?? '';
$type = $_POST['type'] ?? '';

if (empty($userIdsInput)) {
    echo json_encode(['success' => false, 'error' => 'No user IDs provided']);
    exit;
}

// Convert to array of integers
$userIds = array_map('intval', array_filter(explode(',', $userIdsInput)));

if (empty($userIds)) {
    echo json_encode(['success' => false, 'error' => 'Invalid user IDs']);
    exit;
}

// Start transaction for atomic deletion
$conn->begin_transaction();

try {
    $deletedUsers = [];
    
    foreach ($userIds as $userId) {
        // Verify user exists before deletion
        $checkUser = $conn->prepare("SELECT id, name FROM users WHERE id = ?");
        $checkUser->bind_param("i", $userId);
        $checkUser->execute();
        $userResult = $checkUser->get_result();
        
        if ($userResult->num_rows === 0) {
            $checkUser->close();
            continue; // Skip if user doesn't exist
        }
        
        $userData = $userResult->fetch_assoc();
        $userName = $userData['name'];
        $checkUser->close();
        
        // Delete from quiz_answers (all attempts for this user)
        $deleteAnswers = $conn->prepare("
            DELETE qa FROM quiz_answers qa
            INNER JOIN quiz_attempts qa_att ON qa.attempt_id = qa_att.attempt_id
            WHERE qa_att.user_id = ?
        ");
        $deleteAnswers->bind_param("i", $userId);
        $deleteAnswers->execute();
        $deleteAnswers->close();
        
        // Delete from quiz_attempts
        $deleteAttempts = $conn->prepare("DELETE FROM quiz_attempts WHERE user_id = ?");
        $deleteAttempts->bind_param("i", $userId);
        $deleteAttempts->execute();
        $deleteAttempts->close();
        
        // Delete from responses
        $deleteResponses = $conn->prepare("DELETE FROM responses WHERE user_id = ?");
        $deleteResponses->bind_param("i", $userId);
        $deleteResponses->execute();
        $deleteResponses->close();
        
        // Finally delete the user
        $deleteUser = $conn->prepare("DELETE FROM users WHERE id = ?");
        $deleteUser->bind_param("i", $userId);
        $deleteUser->execute();
        $deleteUser->close();
        
        $deletedUsers[] = $userName;
    }
    
    // Commit transaction
    $conn->commit();
    
    // Log the deletion
    $deletedCount = count($deletedUsers);
    $deletedNames = implode(', ', $deletedUsers);
    error_log("ADMIN DELETE: {$deletedCount} user(s) deleted: {$deletedNames}");
    
    echo json_encode([
        'success' => true,
        'deleted_count' => $deletedCount,
        'deleted_users' => $deletedUsers
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    error_log("ADMIN DELETE ERROR: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
