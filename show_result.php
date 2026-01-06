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
    <title>Your Result - Toss Consultancy Services</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/app.css">
</head>
<body class="app-shell">
    <header class="app-header">
        <div class="app-header-inner">
            <div class="brand-lockup">
                <span class="brand-pill">Toss Consultancy</span>
                <div class="brand-text">
                    <span class="brand-title">Assessment results</span>
                    <span class="brand-subtitle">Candidate performance summary</span>
                </div>
            </div>
        </div>
    </header>
    <main class="app-main">
        <div class="app-main-inner card">
            <div class="card-header">
                <div class="badge badge-neutral">Overall performance</div>
                <h1 class="card-title">Your result</h1>
                <p class="card-subtitle">Review your score and question-wise breakdown below.</p>
            </div>
        <?php
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            if ($row['is_correct']) $score++;
            $rows[] = $row;
        }
        $totalQuestions = count($rows);
        $percentage = $totalQuestions > 0 ? ($score / $totalQuestions) * 100 : 0;
        ?>
            <?php
            $correctCount = $score;
            $incorrectCount = max($totalQuestions - $correctCount, 0);
            ?>
            <div class="kpi-row">
                <div class="kpi-card">
                    <div class="kpi-label">Total score</div>
                    <div class="kpi-value"><?php echo $score; ?> / <?php echo $totalQuestions; ?></div>
                    <div class="kpi-sub">Questions answered correctly</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Percentage</div>
                    <div class="kpi-value"><?php echo number_format($percentage, 1); ?>%</div>
                    <div class="kpi-sub">Overall accuracy for this assessment</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Breakdown</div>
                    <div class="kpi-value">
                        <span class="status-chip status-chip-pass"><?php echo $correctCount; ?> correct</span>
                        <span class="status-chip status-chip-fail"><?php echo $incorrectCount; ?> incorrect</span>
                    </div>
                    <div class="kpi-sub">Question-level performance</div>
                </div>
            </div>

            <hr class="card-divider">

            <div class="card-section-title">Question-wise breakdown</div>
            <div class="table-shell">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width:50%;">Question</th>
                            <th>Selected</th>
                            <th>Correct</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
            <?php
            foreach ($rows as $row) {
                $status = $row['is_correct']
                    ? '<span class="status-chip status-chip-pass">✔ Correct</span>'
                    : '<span class="status-chip status-chip-fail">✖ Incorrect</span>';
                echo "<tr>";
                echo "<td class='question-text'>{$row['question']}</td>";
                echo "<td><strong>{$row['selected_option']}</strong></td>";
                echo "<td><strong>{$row['correct_option']}</strong></td>";
                echo "<td>$status</td>";
                echo "</tr>";
            }
            ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>