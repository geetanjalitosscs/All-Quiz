<?php
// ===============================
// File: submit_quiz.php
// ===============================
include 'db.php';

$user_id = $_POST['user_id'];
$role    = $_POST['role'] ?? '';
$level   = $_POST['level'] ?? '';

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