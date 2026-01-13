<?php
// ===============================
// File: submit_quiz.php
// ===============================
include 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_POST['user_id'] ?? null;
$attempt_id = $_POST['attempt_id'] ?? $_SESSION['quiz_attempt_id'] ?? null;
$role    = $_POST['role'] ?? '';
$level   = $_POST['level'] ?? '';

// Validate required parameters
if (!$user_id) {
    die('Missing user_id.');
}

// Map role to respective questions table
$roleToTable = [
    'Backend Developer' => 'backend_mcq_questions',
    'Python Developer'  => 'python_mcq_questions',
    'Flutter Developer' => 'flutter_mcq_questions',
    'Mern Developer'    => 'mern_mcq_questions',
    'Full Stack Developer' => 'fullstack_mcq_questions',
];
$questionsTable = $roleToTable[$role] ?? null;
if ($questionsTable === null) {
    die('Invalid role provided.');
}

// ============================================
// SESSION-BASED ATTEMPT MANAGEMENT
// ============================================
// If attempt_id is provided, use session-based submission
if ($attempt_id) {
    // Verify attempt exists and is in progress
    $checkAttemptStmt = $conn->prepare("
        SELECT attempt_id, status, user_id, question_ids
        FROM quiz_attempts 
        WHERE attempt_id = ?
    ");
    $checkAttemptStmt->bind_param("i", $attempt_id);
    $checkAttemptStmt->execute();
    $attemptResult = $checkAttemptStmt->get_result();
    
    if ($attemptResult->num_rows === 0) {
        die('Quiz attempt not found.');
    }
    
    $attempt = $attemptResult->fetch_assoc();
    $checkAttemptStmt->close();
    
    // Verify user_id matches
    if ($attempt['user_id'] != $user_id) {
        die('Unauthorized: Attempt does not belong to this user.');
    }
    
    // Check if already submitted
    // Allow both 'in_progress' and 'expired' status for submission (expired = auto-submit case)
    if ($attempt['status'] !== 'in_progress' && $attempt['status'] !== 'expired') {
        // Already submitted, redirect to result
        header("Location: show_result.php?user_id=$user_id");
        exit;
    }
    
    // Lock attempt: Mark as submitted (atomic operation)
    // Accept both 'in_progress' and 'expired' status (expired = timer ran out, now submitting)
    $lockStmt = $conn->prepare("
        UPDATE quiz_attempts 
        SET status = 'submitted', 
            end_time = NOW(),
            remaining_time_seconds = 0
        WHERE attempt_id = ? AND (status = 'in_progress' OR status = 'expired')
    ");
    $lockStmt->bind_param("i", $attempt_id);
    $lockStmt->execute();
    
    if ($lockStmt->affected_rows === 0) {
        // Another process already submitted (race condition)
        header("Location: show_result.php?user_id=$user_id");
        exit;
    }
    $lockStmt->close();
    
    // Get all answers from quiz_answers table (more reliable than POST)
    $answersStmt = $conn->prepare("
        SELECT question_id, selected_option
        FROM quiz_answers
        WHERE attempt_id = ?
    ");
    $answersStmt->bind_param("i", $attempt_id);
    $answersStmt->execute();
    $answersResult = $answersStmt->get_result();
    
    $answers = [];
    while ($row = $answersResult->fetch_assoc()) {
        $answers[$row['question_id']] = $row['selected_option'];
    }
    $answersStmt->close();
    
    // Get question_ids from attempt
    $allQuestionIds = json_decode($attempt['question_ids'], true);
    if (!is_array($allQuestionIds)) {
        die('Invalid question_ids in attempt.');
    }
} else {
    // Fallback: Use POST data (backward compatibility)
// Check if user has already submitted responses (prevent duplicate submission)
$checkResponse = $conn->query("SELECT COUNT(*) as count FROM responses WHERE user_id = $user_id");
$responseCount = $checkResponse->fetch_assoc()['count'];

if ($responseCount > 0) {
    // User already submitted, redirect to result page
    header("Location: show_result.php?user_id=$user_id");
    exit;
}

$answers = $_POST['answers'] ?? [];
$allQuestionIds = json_decode($_POST['all_question_ids'] ?? '[]', true);
}

// Prepare statements for performance and safety
$stmtCorrect = $conn->prepare("SELECT correct_option FROM {$questionsTable} WHERE id = ?");
$stmtInsertAnswered = $conn->prepare("INSERT INTO responses (user_id, question_id, selected_option, is_correct) VALUES (?, ?, ?, ?)");
$stmtInsertUnanswered = $conn->prepare("INSERT INTO responses (user_id, question_id, selected_option, is_correct) VALUES (?, ?, NULL, NULL)");

// Save all questions - answered and unanswered
foreach ($allQuestionIds as $qid) {
    $qid = (int)$qid;
    $selected = isset($answers[$qid]) ? strtoupper(substr($answers[$qid], 0, 1)) : null;

    $stmtCorrect->bind_param("i", $qid);
    $stmtCorrect->execute();
    $res = $stmtCorrect->get_result();
    if ($res && $row = $res->fetch_assoc()) {
        if ($selected !== null) {
            // Question was answered
            $correct = $row['correct_option'];
            $is_correct = ($correct === $selected) ? 1 : 0;
            $stmtInsertAnswered->bind_param("iisi", $user_id, $qid, $selected, $is_correct);
            $stmtInsertAnswered->execute();
        } else {
            // Question was not attempted - use separate statement for NULL values
            $stmtInsertUnanswered->bind_param("ii", $user_id, $qid);
            $stmtInsertUnanswered->execute();
        }
    }
}

header("Location: show_result.php?user_id=$user_id");
?>