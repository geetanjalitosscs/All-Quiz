<?php
// ===============================
// File: submit_quiz.php
// ===============================
include 'db.php';

$user_id = $_POST['user_id'];

// Check if user has already submitted responses (prevent duplicate submission)
$checkResponse = $conn->query("SELECT COUNT(*) as count FROM responses WHERE user_id = $user_id");
$responseCount = $checkResponse->fetch_assoc()['count'];

if ($responseCount > 0) {
    // User already submitted, redirect to result page
    header("Location: show_result.php?user_id=$user_id");
    exit;
}

$answers = $_POST['answers'];

foreach ($answers as $qid => $selected) {
    $correct = $conn->query("SELECT correct_option FROM questions WHERE id = $qid")->fetch_assoc()['correct_option'];
    $is_correct = ($correct == $selected) ? 1 : 0;
    $conn->query("INSERT INTO responses (user_id, question_id, selected_option, is_correct) VALUES ($user_id, $qid, '$selected', $is_correct)");
}

header("Location: show_result.php?user_id=$user_id");
?>