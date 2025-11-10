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
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Result - Toss Consultancy Services</title>
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        header {
            background: linear-gradient(135deg, #004080 0%, #0056b3 100%);
            color: white;
            padding: 25px 20px;
            text-align: center;
            font-size: 28px;
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            letter-spacing: 0.5px;
        }
        .container {
            max-width: 1100px;
            margin: 30px auto;
            background: white;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            border-radius: 12px;
        }
        h2 {
            color: #004080;
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 600;
        }
        .score-card {
            background: linear-gradient(135deg, #004080 0%, #0056b3 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 64, 128, 0.3);
        }
        .score-card h3 {
            margin: 0;
            font-size: 36px;
            font-weight: 700;
        }
        .score-card p {
            margin: 10px 0 0 0;
            font-size: 18px;
            opacity: 0.9;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin: 20px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        th {
            background: linear-gradient(135deg, #004080 0%, #0056b3 100%);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
        }
        tr:hover {
            background: #f8f9fa;
        }
        tr:last-child td {
            border-bottom: none;
        }
        .status-correct {
            color: #2e7d32;
            font-weight: 600;
            font-size: 18px;
        }
        .status-incorrect {
            color: #d32f2f;
            font-weight: 600;
            font-size: 18px;
        }
        .question-text {
            max-width: 500px;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <header>Toss Consultancy Services</header>
    <div class="container">
        <h2>Result</h2>
        <?php
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            if ($row['is_correct']) $score++;
            $rows[] = $row;
        }
        $totalQuestions = count($rows);
        $percentage = $totalQuestions > 0 ? ($score / $totalQuestions) * 100 : 0;
        ?>
        <div class="score-card">
            <h3>Total Score: <?php echo $score; ?> / <?php echo $totalQuestions; ?></h3>
            <p>Percentage: <?php echo number_format($percentage, 1); ?>%</p>
        </div>
        <table>
            <tr>
                <th>Question</th>
                <th>Selected</th>
                <th>Correct</th>
                <th>Status</th>
            </tr>
            <?php
            foreach ($rows as $row) {
                $status = $row['is_correct'] ? '<span class="status-correct">✔️ Correct</span>' : '<span class="status-incorrect">❌ Incorrect</span>';
                echo "<tr>";
                echo "<td class='question-text'>{$row['question']}</td>";
                echo "<td><strong>{$row['selected_option']}</strong></td>";
                echo "<td><strong>{$row['correct_option']}</strong></td>";
                echo "<td>$status</td>";
                echo "</tr>";
            }
            ?>
        </table>
    </div>
</body>
</html>