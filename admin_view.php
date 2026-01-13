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
                                <th>Submitted at</th>
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

                    $correctResult = $conn->query("SELECT COUNT(*) as correct FROM responses WHERE user_id = $user_id AND is_correct = 1");
                    $correct = $correctResult ? (int)$correctResult->fetch_assoc()['correct'] : 0;
                        $submittedAt = !empty($u['submitted_at']) ? htmlspecialchars($u['submitted_at']) : '-';

                        $mobile = !empty($u['mobile']) ? htmlspecialchars($u['mobile']) : '-';
                        echo "<tr>";
                        echo "<td>{$count}</td>";
                        echo "<td><div>" . htmlspecialchars($u['name']) . "</div><div class='text-muted' style='font-size:12px;'>" . htmlspecialchars($u['email']) . "</div><div class='text-muted' style='font-size:12px;'>" . $mobile . "</div></td>";
                        echo "<td><span class='pill-role'>" . htmlspecialchars($u['role']) . "</span><br><span style='font-size:12px;' class='text-muted'>" . htmlspecialchars($u['level']) . " · " . htmlspecialchars($u['place']) . "</span></td>";
                        echo "<td><span class='chip'>{$correct} / {$total}</span></td>";
                        echo "<td><span style='font-size:12px;'>" . $submittedAt . "</span></td>";
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
