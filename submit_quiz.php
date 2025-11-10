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

// Prepare statements for performance and safety
$stmtCorrect = $conn->prepare("SELECT correct_option FROM {$questionsTable} WHERE id = ?");
$stmtInsert  = $conn->prepare("INSERT INTO responses (user_id, question_id, selected_option, is_correct) VALUES (?, ?, ?, ?)");

foreach ($answers as $qid => $selected) {
    $qid = (int)$qid;
    $selected = strtoupper(substr($selected, 0, 1));

    $stmtCorrect->bind_param("i", $qid);
    $stmtCorrect->execute();
    $res = $stmtCorrect->get_result();
    if ($res && $row = $res->fetch_assoc()) {
        $correct = $row['correct_option'];
        $is_correct = ($correct === $selected) ? 1 : 0;

        $stmtInsert->bind_param("iisi", $user_id, $qid, $selected, $is_correct);
        $stmtInsert->execute();
    }
}

header("Location: show_result.php?user_id=$user_id");
?>