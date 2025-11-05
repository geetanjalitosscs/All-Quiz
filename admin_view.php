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
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        header {
            background: linear-gradient(135deg, #004080 0%, #0056b3 100%);
            color: white;
            text-align: center;
            padding: 25px 20px;
            font-size: 28px;
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            letter-spacing: 0.5px;
        }
        .container {
            max-width: 900px;
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
        ul {
            list-style: none;
            padding: 0;
        }
        li {
            margin: 15px 0;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #004080;
        }
        li:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            background: linear-gradient(135deg, #e3f2fd 0%, #ffffff 100%);
        }
        a {
            text-decoration: none;
            color: #004080;
            font-weight: 600;
            font-size: 16px;
            transition: color 0.3s ease;
        }
        a:hover {
            color: #0056b3;
        }
        .score-badge {
            display: inline-block;
            background: linear-gradient(135deg, #004080 0%, #0056b3 100%);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-left: 10px;
        }
        .no-users {
            text-align: center;
            color: #777;
            margin-top: 30px;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 10px;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <header>Toss Consultancy Services</header>
    <div class="container">
        <h2>User Submissions</h2>
        <ul>
            <?php
            $count = 1;
            if ($users && $users->num_rows > 0) {
                while ($u = $users->fetch_assoc()) {
                    $user_id = $u['id'];

                    $total = 50; // Total questions in the quiz

                    $correctResult = $conn->query("SELECT COUNT(*) as correct FROM responses WHERE user_id = $user_id AND is_correct = 1");
                    $correct = $correctResult ? (int)$correctResult->fetch_assoc()['correct'] : 0;

                    echo "<li>{$count}. <a href='admin_result.php?user_id={$u['id']}'>" . htmlspecialchars($u['name']) . " - " . htmlspecialchars($u['email']) . "</a> <span class='score-badge'>Score: {$correct}/{$total}</span></li>";
                    $count++;
                }
            } else {
                echo "<p class='no-users'>No user submissions found.</p>";
            }
            ?>
        </ul>
    </div>
    <script>
    </script>
</body>
</html>
