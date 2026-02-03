<?php
// ===============================
// File: update_question_position.php
// Purpose: Update current question index when candidate navigates
// ===============================
include 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get attempt_id from session or POST
$attempt_id = $_POST['attempt_id'] ?? $_SESSION['quiz_attempt_id'] ?? null;
$question_index = isset($_POST['question_index']) ? (int)$_POST['question_index'] : null;

// Validate required parameters
if (!$attempt_id || $question_index === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required parameters: attempt_id and question_index']);
    exit;
}

// Validate question_index (must be non-negative)
if ($question_index < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid question_index. Must be >= 0']);
    exit;
}

try {
    // CRITICAL: Verify user is authenticated
    $user_id = $_SESSION['quiz_user_id'] ?? null;
    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'User not authenticated']);
        exit;
    }
    
    // CRITICAL: Verify attempt exists, belongs to current user, and is in progress
    // This prevents one candidate from accessing/modifying another candidate's data
    $checkStmt = $conn->prepare("SELECT attempt_id, user_id, status FROM quiz_attempts WHERE attempt_id = ?");
    $checkStmt->bind_param("i", $attempt_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Quiz attempt not found']);
        $checkStmt->close();
        exit;
    }
    
    $attempt = $checkResult->fetch_assoc();
    
    // CRITICAL: Verify attempt belongs to current user (prevent data leakage)
    if ((int)$attempt['user_id'] !== (int)$user_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied: This attempt does not belong to you']);
        $checkStmt->close();
        exit;
    }
    
    if ($attempt['status'] !== 'in_progress') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Quiz attempt is not in progress']);
        $checkStmt->close();
        exit;
    }
    $checkStmt->close();
    
    // Update current_question_index
    $updateStmt = $conn->prepare("
        UPDATE quiz_attempts 
        SET current_question_index = ?,
            last_activity_time = NOW()
        WHERE attempt_id = ? AND status = 'in_progress'
    ");
    $updateStmt->bind_param("ii", $question_index, $attempt_id);
    
    if (!$updateStmt->execute()) {
        throw new Exception("Failed to update question position: " . $updateStmt->error);
    }
    $updateStmt->close();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Question position updated successfully',
        'attempt_id' => $attempt_id,
        'question_index' => $question_index
    ]);
    
} catch (Exception $e) {
    error_log("update_question_position.php error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
