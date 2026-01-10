<?php
// ===============================
// File: get_quiz_state.php
// Purpose: Get quiz state for resuming (answers, timer, position)
// ===============================
include 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Only accept GET or POST requests
$attempt_id = $_GET['attempt_id'] ?? $_POST['attempt_id'] ?? $_SESSION['quiz_attempt_id'] ?? null;

if (!$attempt_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required parameter: attempt_id']);
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
    
    // Get attempt details
    // CRITICAL: Also verify attempt belongs to current user (prevent data leakage)
    $stmt = $conn->prepare("
        SELECT 
            attempt_id,
            user_id,
            role,
            level,
            status,
            start_time,
            remaining_time_seconds,
            question_ids,
            current_question_index,
            TIMESTAMPDIFF(SECOND, start_time, NOW()) as elapsed_seconds
        FROM quiz_attempts 
        WHERE attempt_id = ?
    ");
    $stmt->bind_param("i", $attempt_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Quiz attempt not found']);
        $stmt->close();
        exit;
    }
    
    $attempt = $result->fetch_assoc();
    $stmt->close();
    
    // CRITICAL: Verify attempt belongs to current user (prevent data leakage)
    if ((int)$attempt['user_id'] !== (int)$user_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied: This attempt does not belong to you']);
        exit;
    }
    
    if ($attempt['status'] !== 'in_progress') {
        echo json_encode([
            'success' => false,
            'error' => 'Quiz attempt is not in progress',
            'status' => $attempt['status']
        ]);
        exit;
    }
    
    // Calculate server-side remaining time (authoritative)
    $TOTAL_QUIZ_TIME = 2700; // 45 minutes
    $elapsed_seconds = (int)$attempt['elapsed_seconds'];
    $server_remaining_seconds = max(0, $TOTAL_QUIZ_TIME - $elapsed_seconds);
    
    // Get all saved answers for this attempt
    $answersStmt = $conn->prepare("
        SELECT question_id, selected_option
        FROM quiz_answers
        WHERE attempt_id = ?
        ORDER BY question_id
    ");
    $answersStmt->bind_param("i", $attempt_id);
    $answersStmt->execute();
    $answersResult = $answersStmt->get_result();
    
    $answers = [];
    while ($row = $answersResult->fetch_assoc()) {
        $answers[$row['question_id']] = $row['selected_option'];
    }
    $answersStmt->close();
    
    // Parse question_ids JSON
    $question_ids = json_decode($attempt['question_ids'], true);
    if (!is_array($question_ids)) {
        $question_ids = [];
    }
    
    // Return complete state
    echo json_encode([
        'success' => true,
        'attempt_id' => (int)$attempt['attempt_id'],
        'user_id' => (int)$attempt['user_id'],
        'role' => $attempt['role'],
        'level' => $attempt['level'],
        'question_ids' => $question_ids,
        'current_question_index' => (int)$attempt['current_question_index'],
        'remaining_seconds' => $server_remaining_seconds,
        'elapsed_seconds' => $elapsed_seconds,
        'start_time' => $attempt['start_time'],
        'answers' => $answers
    ]);
    
} catch (Exception $e) {
    error_log("get_quiz_state.php error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>

