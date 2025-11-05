<?php
// ===============================
// File: get_results.php
// ===============================
include 'db.php';

$user_id = $_GET['user_id'];

$query = "
SELECT q.question, q.option_a, q.option_b, q.option_c, q.option_d, q.correct_option,
       r.selected_option, r.is_correct
FROM responses r
JOIN questions q ON r.question_id = q.id
WHERE r.user_id = $user_id
";

$result = $conn->query($query);
$responses = [];
$score = 0;

while ($row = $result->fetch_assoc()) {
    if ($row['is_correct']) $score++;
    $responses[] = $row;
}

echo json_encode(["score" => $score, "responses" => $responses]);
?>
