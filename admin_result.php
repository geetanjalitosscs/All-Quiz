<?php
// ===============================
// File: admin_result.php
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

// Also check raw responses count (without join) to distinguish
// between legacy submitted users and never-submitted users
$rawCount = 0;
$countStmt = $conn->prepare("SELECT COUNT(*) AS c FROM responses WHERE user_id = ?");
$countStmt->bind_param("i", $user_id);
$countStmt->execute();
$countRes = $countStmt->get_result();
if ($countRes && ($cRow = $countRes->fetch_assoc())) {
    $rawCount = (int)$cRow['c'];
}
$countStmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Result - Toss Consultancy Services</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/app.css">
</head>
<body class="app-shell">
    <header class="app-header">
        <div class="app-header-inner">
            <div class="brand-lockup">
                <span class="brand-pill">Toss Consultancy Services</span>
                <div class="brand-text">
                    <span class="brand-title">Candidate result (admin)</span>
                    <span class="brand-subtitle">Detailed response breakdown</span>
                </div>
            </div>
        </div>
    </header>
    <main class="app-main">
        <div class="app-main-inner card">
            <div class="card-header">
                <div class="badge badge-neutral">Result summary</div>
                <h1 class="card-title">Candidate performance</h1>
                <p class="card-subtitle">Use this view for evaluation, feedback, or export.</p>
            </div>
        <?php
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['is_correct'])) {
                $score++;
            }
            $rows[] = $row;
        }
        $totalQuestions = count($rows);

        // If there are submitted responses but join returned 0 rows (old data / questions changed),
        // compute score from responses table only (without question text) so old users are not 0/0.
        if ($totalQuestions === 0 && $rawCount > 0) {
            $rows = [];
            $score = 0;

            $legacyStmt = $conn->prepare("
                SELECT selected_option, is_correct
                FROM responses
                WHERE user_id = ?
            ");
            $legacyStmt->bind_param("i", $user_id);
            $legacyStmt->execute();
            $legacyRes = $legacyStmt->get_result();

            while ($lRow = $legacyRes->fetch_assoc()) {
                if (!empty($lRow['is_correct'])) {
                    $score++;
                }
                $rows[] = [
                    'question'        => 'Question text unavailable (legacy record)',
                    'option_a'        => null,
                    'option_b'        => null,
                    'option_c'        => null,
                    'option_d'        => null,
                    'correct_option'  => '',
                    'selected_option' => $lRow['selected_option'],
                    'is_correct'      => !empty($lRow['is_correct']) ? 1 : 0,
                ];
            }
            $legacyStmt->close();

            $totalQuestions = count($rows);
        }

        // If there is no submitted data at all, fall back to live in-progress attempt (quiz_answers)
        if ($totalQuestions === 0 && $rawCount === 0) {
            // Find the most recent attempt for this user
            $attemptStmt = $conn->prepare("
                SELECT attempt_id, question_ids
                FROM quiz_attempts
                WHERE user_id = ?
                ORDER BY start_time DESC
                LIMIT 1
            ");
            $attemptStmt->bind_param("i", $user_id);
            $attemptStmt->execute();
            $attemptRes = $attemptStmt->get_result();
            $attemptRow = $attemptRes ? $attemptRes->fetch_assoc() : null;
            $attemptStmt->close();

            if ($attemptRow && !empty($attemptRow['question_ids'])) {
                $attemptId = (int)$attemptRow['attempt_id'];
                $questionIds = json_decode($attemptRow['question_ids'], true);

                if (is_array($questionIds) && !empty($questionIds)) {
                    // Load all questions for this attempt in the same order
                    $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
                    $types = str_repeat('i', count($questionIds));
                    $qSql = "
                        SELECT id, question, option_a, option_b, option_c, option_d, correct_option
                        FROM {$questionsTable}
                        WHERE id IN ($placeholders)
                    ";
                    $qStmt = $conn->prepare($qSql);
                    $qStmt->bind_param($types, ...$questionIds);
                    $qStmt->execute();
                    $qRes = $qStmt->get_result();

                    $questionMap = [];
                    while ($qRow = $qRes->fetch_assoc()) {
                        $questionMap[$qRow['id']] = $qRow;
                    }
                    $qStmt->close();

                    // Load saved answers for this attempt
                    $aStmt = $conn->prepare("
                        SELECT question_id, selected_option
                        FROM quiz_answers
                        WHERE attempt_id = ?
                    ");
                    $aStmt->bind_param("i", $attemptId);
                    $aStmt->execute();
                    $aRes = $aStmt->get_result();
                    $answerMap = [];
                    while ($aRow = $aRes->fetch_assoc()) {
                        $answerMap[$aRow['question_id']] = $aRow['selected_option'];
                    }
                    $aStmt->close();

                    // Build rows in the saved order and calculate live score
                    $rows = [];
                    $score = 0;
                    foreach ($questionIds as $qid) {
                        if (!isset($questionMap[$qid])) {
                            continue;
                        }
                        $qRow = $questionMap[$qid];
                        $selected = $answerMap[$qid] ?? null;
                        $correctOption = $qRow['correct_option'] ?? null;
                        $isCorrect = $selected !== null && $selected !== '' && $correctOption !== null && $selected === $correctOption;
                        if ($isCorrect) {
                            $score++;
                        }

                        $rows[] = [
                            'question'        => $qRow['question'],
                            'option_a'        => $qRow['option_a'],
                            'option_b'        => $qRow['option_b'],
                            'option_c'        => $qRow['option_c'],
                            'option_d'        => $qRow['option_d'],
                            'correct_option'  => $correctOption,
                            'selected_option' => $selected,
                            'is_correct'      => $isCorrect ? 1 : 0,
                        ];
                    }

                    $totalQuestions = count($rows);
                }
            }
        }

        // Recalculate score from final $rows to ensure header KPI matches table status
        $score = 0;
        foreach ($rows as $row) {
            if (!empty($row['is_correct'])) {
                $score++;
            }
        }

        $percentage = $totalQuestions > 0 ? ($score / $totalQuestions) * 100 : 0;
        
        // Calculate breakdown: correct, incorrect, and not attempted
        $correctCount = $score;
        $notAttemptedCount = 0;
        $incorrectCount = 0;
        foreach ($rows as $row) {
            $selectedOption = $row['selected_option'];
            $isAttempted = $selectedOption !== null && $selectedOption !== '';
            if (!$isAttempted) {
                $notAttemptedCount++;
            } elseif (!$row['is_correct']) {
                $incorrectCount++;
            }
        }
        ?>
            <div class="kpi-row">
                <div class="kpi-card">
                    <div class="kpi-label">Total score</div>
                    <div class="kpi-value"><?php echo $score; ?> / <?php echo $totalQuestions; ?></div>
                    <div class="kpi-sub">Correct answers submitted</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Percentage</div>
                    <div class="kpi-value"><?php echo number_format($percentage, 1); ?>%</div>
                    <div class="kpi-sub">Accuracy across all questions</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Breakdown</div>
                    <div class="kpi-value">
                        <span class="status-chip status-chip-pass"><?php echo $correctCount; ?> correct</span>
                        <span class="status-chip status-chip-fail"><?php echo $incorrectCount; ?> incorrect</span>
                        <span class="status-chip" style="background: #f3f4f6; color: #6b7280; border-color: #d1d5db;"><?php echo $notAttemptedCount; ?> not attempted</span>
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
                $selectedOption = $row['selected_option'];
                $isAttempted = $selectedOption !== null && $selectedOption !== '';
                
                if (!$isAttempted) {
                    $status = '<span class="status-chip" style="background: #f3f4f6; color: #6b7280; border-color: #d1d5db;">⊘ Not Attempted</span>';
                    $selectedDisplay = '<span style="color: #9ca3af; font-style: italic;">-</span>';
                } else {
                    $status = $row['is_correct']
                        ? '<span class="status-chip status-chip-pass">✔ Correct</span>'
                        : '<span class="status-chip status-chip-fail">✖ Incorrect</span>';
                    $selectedDisplay = "<strong>{$selectedOption}</strong>";
                }
                
                echo "<tr>";
                echo "<td class='question-text'>{$row['question']}</td>";
                echo "<td>{$selectedDisplay}</td>";
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
