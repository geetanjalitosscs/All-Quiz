<?php
// ===============================
// File: get_questions.php
// ===============================
include 'db.php';

$result = $conn->query("SELECT id, question, option_a, option_b, option_c, option_d FROM questions ORDER BY RAND() LIMIT 50");
$questions = [];

while ($row = $result->fetch_assoc()) {
    $questions[] = $row;
}

echo json_encode($questions);
?>