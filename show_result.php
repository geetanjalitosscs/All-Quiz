<?php
// ===============================
// File: show_result.php
// ===============================
include 'db.php';
$user_id = (int)($_GET['user_id'] ?? 0);

// Determine role → table name for this user
$roleResult = $conn->query("SELECT role FROM users WHERE id = {$user_id} LIMIT 1");
$roleRow = $roleResult ? $roleResult->fetch_assoc() : null;
$role = $roleRow ? $roleRow['role'] : '';
$roleToTable = [
    'Backend Developer' => 'backend_mcq_questions',
    'Python Developer'  => 'python_mcq_questions',
    'Flutter Developer' => 'flutter_mcq_questions',
    'Mern Developer'    => 'mern_mcq_questions',
    'Full Stack Developer' => 'fullstack_mcq_questions',
    'Data Analytics' => 'data_analytics_mcq',
];
$questionsTable = $roleToTable[$role] ?? null;
if ($questionsTable === null) {
    die('Invalid role for result view.');
}

// Load responses joined with the correct questions table
$stmt = $conn->prepare("
    SELECT q.question, q.option_a, q.option_b, q.option_c, q.option_d, q.correct_option,
           r.selected_option, r.is_correct
    FROM responses r
    JOIN {$questionsTable} q ON r.question_id = q.id
    WHERE r.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$score = 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Assessment Completed - Toss Consultancy Services</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/app.css">
</head>
<body class="app-shell">
    <header class="app-header">
        <div class="app-header-inner">
            <div class="brand-lockup">
                <span class="brand-pill">TOSS CONSULTANCY SERVICES</span>
                <div class="brand-text">
                    <span class="brand-title">ASSESSMENT COMPLETED</span>
                    <span class="brand-subtitle">Thank you for your participation</span>
                </div>
            </div>
        </div>
    </header>
    <main class="app-main">
        <div class="app-main-inner card confirmation-card">
            <div class="card-header">
                <div class="badge badge-success">SUBMISSION SUCCESSFUL</div>
                <h1 class="card-title">THANK YOU FOR COMPLETING THE ASSESSMENT</h1>
                <p class="card-subtitle">Your responses have been submitted successfully.</p>
                <p class="card-subtitle" style="margin-top: 12px;">Our team will review your assessment and contact you regarding the next steps.</p>
            </div>
        </div>
    </main>
</body>
</html>