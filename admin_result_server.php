<?php
// ===============================
// File: k.php
// ===============================
include 'db.php';

// Fetch each user's total score
$query = "
SELECT 
    u.id AS user_id,
    u.name,
    u.email,
    COUNT(r.id) AS total_questions,
    SUM(r.is_correct) AS correct_answers
FROM responses r
JOIN users u ON r.user_id = u.id
GROUP BY u.id, u.name, u.email
ORDER BY u.id ASC
";

$result = $conn->query($query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Submissions - Toss Consultancy Services</title>
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
        .warning-banner {
            background: linear-gradient(135deg, #d32f2f 0%, #f44336 100%);
            color: white;
            text-align: center;
            padding: 15px;
            font-weight: 600;
            font-size: 16px;
            border-bottom: 3px solid #b71c1c;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
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
        .user-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 15px 20px;
            margin-bottom: 15px;
            border-radius: 10px;
            border: 1px solid #e3e8ff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.2s ease-in-out;
        }
        .user-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .user-info {
            font-size: 16px;
            color: #003366;
        }
        .user-info strong {
            font-weight: 600;
        }
        .score-badge {
            background: #004080;
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        }
        .no-data {
            text-align: center;
            color: #666;
            font-size: 18px;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="warning-banner">
        ⚠️ DO NOT GO BACK OR RELOAD THE PAGE - Otherwise your all progress will be gone!
    </div>
    <header>Toss Consultancy Services</header>
    <div class="container">
        <h2>User Submissions</h2>
        <?php
        if ($result->num_rows > 0) {
            $count = 1;
            while ($row = $result->fetch_assoc()) {
                $score = $row['correct_answers'] ?? 0;
                $total = $row['total_questions'] ?? 0;
                echo "
                <div class='user-card'>
                    <div class='user-info'>
                        <strong>{$count}. {$row['name']}</strong> - {$row['email']}
                    </div>
                    <div class='score-badge'>Score: {$score}/{$total}</div>
                </div>";
                $count++;
            }
        } else {
            echo "<p class='no-data'>No user submissions found.</p>";
        }
        ?>
    </div>
    <script>
        // Prevent back button navigation
        history.pushState(null, null, location.href);
        window.onpopstate = function(event) {
            history.pushState(null, null, location.href);
            alert('⚠️ DO NOT GO BACK OR RELOAD THE PAGE - Otherwise your all progress will be gone!');
        };
    </script>
</body>
</html>
