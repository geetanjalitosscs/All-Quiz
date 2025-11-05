<?php
// ===============================
// File: submit_answers.php
// ===============================
include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'];
$answers = $data['answers']; // array of [question_id => selected_option]

foreach ($answers as $qid => $selected) {
    $correct = $conn->query("SELECT correct_option FROM questions WHERE id = $qid")->fetch_assoc()['correct_option'];
    $is_correct = ($correct == $selected) ? 1 : 0;
    $conn->query("INSERT INTO responses (user_id, question_id, selected_option, is_correct) VALUES ($user_id, $qid, '$selected', $is_correct)");
}

echo json_encode(["status" => "success"]);
?>