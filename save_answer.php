<?php
// ===============================
// File: save_answer.php
// Purpose: Auto-save answer when candidate selects an option
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
$question_id = $_POST['question_id'] ?? null;
$selected_option = $_POST['selected_option'] ?? null;

// Validate required parameters
if (!$attempt_id || !$question_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required parameters: attempt_id and question_id']);
    exit;
}

// Validate selected_option (A, B, C, D, or null for clearing answer)
if ($selected_option !== null && !in_array(strtoupper($selected_option), ['A', 'B', 'C', 'D'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid selected_option. Must be A, B, C, D, or null']);
    exit;
}

// Normalize option to uppercase or null
$selected_option = $selected_option ? strtoupper($selected_option) : null;

try {
    // CRITICAL: Verify attempt exists, belongs to current user, and is in progress
    // This prevents one candidate from accessing/modifying another candidate's data
    $user_id = $_SESSION['quiz_user_id'] ?? null;
    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'User not authenticated']);
        exit;
    }
    
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
    
    // Insert or update answer using ON DUPLICATE KEY UPDATE
    // This handles both new answers and answer changes efficiently
    $insertStmt = $conn->prepare("
        INSERT INTO quiz_answers (attempt_id, question_id, selected_option)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE
            selected_option = VALUES(selected_option),
            saved_at = CURRENT_TIMESTAMP
    ");
    $insertStmt->bind_param("iis", $attempt_id, $question_id, $selected_option);
    
    if (!$insertStmt->execute()) {
        throw new Exception("Failed to save answer: " . $insertStmt->error);
    }
    $insertStmt->close();
    
    // Update last_activity_time in quiz_attempts
    $updateStmt = $conn->prepare("
        UPDATE quiz_attempts 
        SET last_activity_time = NOW() 
        WHERE attempt_id = ? AND status = 'in_progress'
    ");
    $updateStmt->bind_param("i", $attempt_id);
    $updateStmt->execute();
    $updateStmt->close();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Answer saved successfully',
        'attempt_id' => $attempt_id,
        'question_id' => $question_id,
        'selected_option' => $selected_option
    ]);
    
} catch (Exception $e) {
    error_log("save_answer.php error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>

