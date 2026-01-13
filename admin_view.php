<?php
// ===============================
// File: admin_view.php
// ===============================
include 'db.php';

$users = $conn->query("SELECT * FROM users ORDER BY submitted_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - User Submissions</title>
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
                    <span class="brand-title">Assessment administration</span>
                    <span class="brand-subtitle">Candidate submissions overview</span>
                </div>
            </div>
        </div>
    </header>
    <main class="app-main">
        <div class="app-main-inner">
            <section class="card">
                <div class="card-header">
                    <div class="badge badge-neutral">User submissions</div>
                    <h1 class="card-title">Completed assessments</h1>
                    <p class="card-subtitle">Search, filter, and drill down into individual candidate results.</p>
                </div>

                <hr class="card-divider">

                <div class="filter-bar">
                    <span class="filter-bar-label">Filters</span>
                    <input
                        type="text"
                        id="filterSearch"
                        class="form-control filter-input"
                        placeholder="Search by name, email, phone, role, or location"
                    >
                </div>

                <div class="table-shell">
                    <table class="table" id="usersTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Candidate</th>
                                <th>Role &amp; Level</th>
                                <th>Score</th>
                                <th>Time Details</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
            <?php
            $count = 1;
            if ($users && $users->num_rows > 0) {
                while ($u = $users->fetch_assoc()) {
                    $user_id = $u['id'];
                    $total = 50; // Total questions in the quiz
                    $correct = 0;

                    // First check if user has submitted responses
                    $responseCountStmt = $conn->prepare("SELECT COUNT(*) AS c FROM responses WHERE user_id = ?");
                    $responseCountStmt->bind_param("i", $user_id);
                    $responseCountStmt->execute();
                    $responseCountRes = $responseCountStmt->get_result();
                    $responseCount = $responseCountRes ? (int)$responseCountRes->fetch_assoc()['c'] : 0;
                    $responseCountStmt->close();

                    if ($responseCount > 0) {
                        // User has submitted - get score from responses table
                        $correctResult = $conn->query("SELECT COUNT(*) as correct FROM responses WHERE user_id = $user_id AND is_correct = 1");
                        $correct = $correctResult ? (int)$correctResult->fetch_assoc()['correct'] : 0;
                    } else {
                        // User hasn't submitted - check for in-progress attempt
                        $role = $u['role'] ?? '';
                        $roleToTable = [
                            'Backend Developer' => 'backend_mcq_questions',
                            'Python Developer'  => 'python_mcq_questions',
                            'Flutter Developer' => 'flutter_mcq_questions',
                            'Mern Developer'    => 'mern_mcq_questions',
                            'Full Stack Developer' => 'fullstack_mcq_questions',
                        ];
                        $questionsTable = $roleToTable[$role] ?? null;

                        if ($questionsTable) {
                            // Find the most recent attempt for this user (for live score)
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
                                    // Load questions with correct_option
                                    $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
                                    $types = str_repeat('i', count($questionIds));
                                    $qSql = "
                                        SELECT id, correct_option
                                        FROM {$questionsTable}
                                        WHERE id IN ($placeholders)
                                    ";
                                    $qStmt = $conn->prepare($qSql);
                                    $qStmt->bind_param($types, ...$questionIds);
                                    $qStmt->execute();
                                    $qRes = $qStmt->get_result();

                                    $questionMap = [];
                                    while ($qRow = $qRes->fetch_assoc()) {
                                        $questionMap[$qRow['id']] = $qRow['correct_option'];
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

                                    // Calculate live score
                                    foreach ($questionIds as $qid) {
                                        if (!isset($questionMap[$qid]) || !isset($answerMap[$qid])) {
                                            continue;
                                        }
                                        $selected = $answerMap[$qid];
                                        $correctOption = $questionMap[$qid];
                                        if ($selected !== null && $selected !== '' && $correctOption !== null && $selected === $correctOption) {
                                            $correct++;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // Start time, submit time, and duration (minutes) for display
                    $startTimeDisplay = '-';
                    $submitTimeDisplay = '-';
                    $durationDisplay = '-';

                    // Prefer quiz_attempts for timing (supports in-progress and submitted)
                    $timeStmt = $conn->prepare("
                        SELECT start_time, end_time
                        FROM quiz_attempts
                        WHERE user_id = ?
                        ORDER BY start_time DESC
                        LIMIT 1
                    ");
                    $timeStmt->bind_param("i", $user_id);
                    $timeStmt->execute();
                    $timeRes = $timeStmt->get_result();
                    $timeRow = $timeRes ? $timeRes->fetch_assoc() : null;
                    $timeStmt->close();

                    if ($timeRow && !empty($timeRow['start_time'])) {
                        try {
                            $startDt = new DateTime($timeRow['start_time']);
                            $startTs = $startDt->getTimestamp();
                            $startTimeDisplay = htmlspecialchars($startDt->format('Y-m-d h:i:s A'));

                            if (!empty($timeRow['end_time'])) {
                                $endDt = new DateTime($timeRow['end_time']);
                                $endTs = $endDt->getTimestamp();
                                $submitTimeDisplay = htmlspecialchars($endDt->format('Y-m-d h:i:s A'));

                                // Duration in whole minutes between start and end
                                $diffSeconds = max(0, $endTs - $startTs);
                                $durationMinutes = floor($diffSeconds / 60);
                                $durationDisplay = $durationMinutes . ' min';
                            } else {
                                // In-progress: no end_time yet, show duration till now
                                $nowTs = time();
                                $diffSeconds = max(0, $nowTs - $startTs);
                                $durationMinutes = floor($diffSeconds / 60);
                                $durationDisplay = $durationMinutes . ' min (running)';
                                $submitTimeDisplay = 'In progress';
                            }
                        } catch (Exception $e) {
                            // If parsing fails, fall back to raw user submitted_at
                        }
                    }

                    // Fallback if no quiz_attempts record found
                    if ($startTimeDisplay === '-' && !empty($u['submitted_at'])) {
                        try {
                            $submittedDateTime = new DateTime($u['submitted_at']);
                            $startTimeDisplay = htmlspecialchars($submittedDateTime->format('Y-m-d h:i:s A'));
                        } catch (Exception $e) {
                            $startTimeDisplay = htmlspecialchars($u['submitted_at']);
                        }
                    }

                    $mobile = !empty($u['mobile']) ? htmlspecialchars($u['mobile']) : '-';
                    echo "<tr>";
                    echo "<td>{$count}</td>";
                    echo "<td><div>" . htmlspecialchars($u['name']) . "</div><div class='text-muted' style='font-size:12px;'>" . htmlspecialchars($u['email']) . "</div><div class='text-muted' style='font-size:12px;'>" . $mobile . "</div></td>";
                    echo "<td><span class='pill-role'>" . htmlspecialchars($u['role']) . "</span><br><span style='font-size:12px;' class='text-muted'>" . htmlspecialchars($u['level']) . " · " . htmlspecialchars($u['place']) . "</span></td>";
                    echo "<td><span class='chip'>{$correct} / {$total}</span></td>";
                    echo "<td><div style='font-size:12px; line-height:1.4;'>";
                    echo "<div><strong>Start:</strong> " . $startTimeDisplay . "</div>";
                    echo "<div><strong>Submit:</strong> " . $submitTimeDisplay . "</div>";
                    echo "<div><strong>Duration:</strong> " . $durationDisplay . "</div>";
                    echo "</div></td>";
                    echo "<td><a href='admin_result.php?user_id={$u['id']}' class='muted-link'>View breakdown →</a></td>";
                    echo "</tr>";
                    $count++;
                }
            } else {
                    echo "<tr><td colspan='6'><div class='empty-state'>No user submissions found.</div></td></tr>";
            }
            ?>
                        </tbody>
                    </table>
                </div>
            </section>
    </div>
    </main>
    <script>
        // Simple client-side filtering (UI-only)
        const filterInput = document.getElementById('filterSearch');
        const usersTable = document.getElementById('usersTable');
        if (filterInput && usersTable) {
            filterInput.addEventListener('input', function () {
                const query = this.value.toLowerCase().trim();
                const rows = usersTable.querySelectorAll('tbody tr');
                rows.forEach((row) => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(query) ? '' : 'none';
                });
            });
        }
    </script>
</body>
</html>
