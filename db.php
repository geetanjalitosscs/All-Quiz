<?php
// ===============================
// File: db.php
// ===============================
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'all_assessment_quiz';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
