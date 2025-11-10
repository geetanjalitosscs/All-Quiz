<?php
// ===============================
// File: quiz.php (Quiz UI Page)
// ===============================
include 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Read role and level from form (required)
    $role  = trim($_POST['role'] ?? '');
    $level = trim($_POST['level'] ?? '');

    if ($role === '' || $level === '') {
        die("Please select both role and level.");
    }

    // Map UI strings to table names
    $roleToTable = [
        'Backend Developer' => 'backend_mcq_questions',
        'Python Developer'  => 'python_mcq_questions',
        'Flutter Developer' => 'flutter_mcq_questions',
        'Mern Developer'    => 'mern_mcq_questions',
        'Full Stack Developer' => 'fullstack_mcq_questions',
    ];

    if (!isset($roleToTable[$role])) {
        die("Unsupported role selected.");
    }

    $questionsTable = $roleToTable[$role];

    // Normalize level to match table enums (lowercase; some tables use 'advance')
    $normalizedLevel = strtolower($level); // 'beginner' | 'intermediate' | 'advanced'
    if ($normalizedLevel === 'advanced') {
        // Tables that use 'advance' (without 'd')
        $usesAdvance = in_array($questionsTable, ['python_mcq_questions', 'fullstack_mcq_questions', 'flutter_mcq_questions'], true);
        if ($usesAdvance) {
            $normalizedLevel = 'advance';
        }
    }
    // Basic validation
    $allowed = ['beginner', 'intermediate', 'advanced', 'advance'];
    if (!in_array($normalizedLevel, $allowed, true)) {
        die("Invalid level provided.");
    }
    // Validate phone number: must be exactly 10 digits starting with 6, 7, 8, or 9
    $mobile = $_POST['mobile'] ?? '';
    $mobile = preg_replace('/[^0-9]/', '', $mobile); // Remove any non-numeric characters
    
    if (empty($mobile) || !preg_match('/^[6789]\d{9}$/', $mobile)) {
        die("Invalid phone number. Phone number must be exactly 10 digits starting with 6, 7, 8, or 9.");
    }
    
    $email = $_POST['email'] ?? '';
    
    // Check if user with same email or mobile already exists and has submitted quiz
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR mobile = ?");
    $checkStmt->bind_param("ss", $email, $mobile);
    $checkStmt->execute();
    $existingUser = $checkStmt->get_result();
    
    if ($existingUser->num_rows > 0) {
        $existingUserData = $existingUser->fetch_assoc();
        $existingUserId = $existingUserData['id'];
        
        // Check if user has already submitted responses
        $responseCheck = $conn->query("SELECT COUNT(*) as count FROM responses WHERE user_id = $existingUserId");
        $responseCount = $responseCheck->fetch_assoc()['count'];
        
        if ($responseCount > 0) {
            // User already submitted quiz -> show popup and send back to start
            echo "<script>
                alert('User already attempted. Please use a different phone number and email.');
                window.location.href = 'index.php';
            </script>";
            exit;
        } else {
            // User exists but hasn't submitted quiz, use existing user_id
            $user_id = $existingUserId;
        }
    } else {
        // New user, insert into database (role + level columns expected)
        $stmt = $conn->prepare("INSERT INTO users (name, role, level, place, mobile, email) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $_POST['name'], $role, $level, $_POST['place'], $mobile, $email);
        $stmt->execute();
        $user_id = $stmt->insert_id;
    }
    // Persist user session
    $_SESSION['quiz_user_id'] = $user_id;
    $_SESSION['quiz_role'] = $role;
    $_SESSION['quiz_level'] = $level;

    // Fetch 50 random questions from the selected role table filtered by level.
    // Match level/role case-insensitively and accept both 'advanced' and 'advance'.
    $levelCandidates = [$normalizedLevel];
    if ($normalizedLevel === 'advanced') {
        $levelCandidates[] = 'advance';
    } elseif ($normalizedLevel === 'advance') {
        $levelCandidates[] = 'advanced';
    }

    $baseSelect = "SELECT id, question, option_a, option_b, option_c, option_d FROM {$questionsTable}";
    $hasRoleCol = in_array($questionsTable, ['backend_mcq_questions','mern_mcq_questions','python_mcq_questions','fullstack_mcq_questions','flutter_mcq_questions'], true);

    // First attempt: filter by level (IN) and role (case-insensitive) where applicable
    if ($hasRoleCol) {
        $sql = $baseSelect . " WHERE LOWER(level) IN (?, ?) AND LOWER(role) = LOWER(?) ORDER BY RAND() LIMIT 50";
        $stmtQ = $conn->prepare($sql);
        $levelA = strtolower($levelCandidates[0]);
        $levelB = isset($levelCandidates[1]) ? strtolower($levelCandidates[1]) : strtolower($levelCandidates[0]);
        $stmtQ->bind_param("sss", $levelA, $levelB, $role);
    } else {
        $sql = $baseSelect . " WHERE LOWER(level) IN (?, ?) ORDER BY RAND() LIMIT 50";
        $stmtQ = $conn->prepare($sql);
        $levelA = strtolower($levelCandidates[0]);
        $levelB = isset($levelCandidates[1]) ? strtolower($levelCandidates[1]) : strtolower($levelCandidates[0]);
        $stmtQ->bind_param("ss", $levelA, $levelB);
    }
    $stmtQ->execute();
    $questions = $stmtQ->get_result();

    // Fallback: if none found and table has role, ignore role filter and just match level
    if ($questions->num_rows === 0 && $hasRoleCol) {
        $stmtQ->close();
        $sql = $baseSelect . " WHERE LOWER(level) IN (?, ?) ORDER BY RAND() LIMIT 50";
        $stmtQ = $conn->prepare($sql);
        $stmtQ->bind_param("ss", $levelA, $levelB);
        $stmtQ->execute();
        $questions = $stmtQ->get_result();
    }
    $question_data = [];
    while ($row = $questions->fetch_assoc()) {
        $question_data[] = $row;
    }
    if (count($question_data) === 0) {
        die("No questions found for {$role} ({$level}). Please contact the administrator.");
    }
    ?>

<!DOCTYPE html>
<html>
<head>
    <title>Quiz - Toss Consultancy Services</title>
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
            padding: 25px 20px;
            text-align: center;
            font-size: 28px;
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            letter-spacing: 0.5px;
        }
        .container {
            max-width: 900px;
            margin: 20px auto;
            background: white;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            border-radius: 12px;
        }
        .question-block {
            display: none;
        }
        .question-block.active {
            display: block;
        }
        .question-item {
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #004080;
        }
        .question-item p {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        .question-item label {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            margin: 8px 0;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 15px;
        }
        .question-item label:hover {
            background: #f0f7ff;
            border-color: #004080;
            transform: translateX(5px);
        }
        .question-item input[type="radio"] {
            margin-right: 10px;
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #004080;
        }
        .question-item input[type="radio"]:checked {
            background: #004080;
        }
        .question-item label:has(input[type="radio"]:checked) {
            background: #e3f2fd;
            border-color: #004080;
            font-weight: 600;
        }
        .question-item hr {
            border: none;
            border-top: 2px solid #e0e0e0;
            margin: 20px 0;
        }
        #timer {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 25px;
            color: #d32f2f;
            text-align: center;
            padding: 15px;
            background: #fff3cd;
            border-radius: 8px;
            border: 2px solid #ffc107;
            box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
        }
        .btn {
            padding: 12px 30px;
            background: linear-gradient(135deg, #004080 0%, #0056b3 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 64, 128, 0.3);
            margin: 0 10px;
        }
        .btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 64, 128, 0.4);
        }
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            opacity: 0.6;
        }
        .btn-group {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
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
    </style>
</head>
<body>
    <div class="warning-banner">
        ⚠️ DO NOT GO BACK OR RELOAD THE PAGE - Otherwise your all progress will be gone!
    </div>
    <header>Toss Consultancy Services</header>
    <div class="container">
        <div id="timer">Time Left: 60:00</div>
        <form action="submit_quiz.php" method="POST" id="quizForm">
            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
            <input type="hidden" name="role" value="<?php echo htmlspecialchars($role); ?>">
            <input type="hidden" name="level" value="<?php echo htmlspecialchars($level); ?>">
            <?php
            $page = 0;
            foreach ($question_data as $index => $q) {
                $block = intdiv($index, 10);
                if ($index % 10 == 0) echo "<div class='question-block" . ($block == 0 ? " active" : "") . "' id='block-$block'>";

                echo "<div class='question-item'>";
                echo "<p><strong>Q" . ($index + 1) . ". {$q['question']}</strong></p>";
                echo "<label><input type='radio' name='answers[{$q['id']}]' value='A'> A) {$q['option_a']}</label>";
                echo "<label><input type='radio' name='answers[{$q['id']}]' value='B'> B) {$q['option_b']}</label>";
                echo "<label><input type='radio' name='answers[{$q['id']}]' value='C'> C) {$q['option_c']}</label>";
                echo "<label><input type='radio' name='answers[{$q['id']}]' value='D'> D) {$q['option_d']}</label>";
                echo "</div>";

                if ($index % 10 == 9 || $index == count($question_data) - 1) {
                    echo "</div>";
                    $page++;
                }
            }
            ?>
            <div class="btn-group">
                <button type="button" class="btn" id="prevBtn" onclick="changePage(-1)" disabled>← Previous</button>
                <button type="button" class="btn" id="nextBtn" onclick="changePage(1)">Next →</button>
                <input type="submit" class="btn" id="submitBtn" value="Submit Quiz" style="display:none;">
            </div>
        </form>
    </div>

    <script>
        // Guard: prevent refresh/reload/back while on quiz page
        let guardEnabled = true;
        window.onbeforeunload = function(e) {
            if (!guardEnabled) return;
            const message = 'Do not reload or go back. Your progress may be lost.';
            e = e || window.event;
            if (e) e.returnValue = message;
            return message;
        };
        // Disable F5 / Ctrl+R
        window.addEventListener('keydown', function(e) {
            if (e.key === 'F5' || (e.ctrlKey && e.key.toLowerCase() === 'r')) {
                e.preventDefault();
                alert('⚠️ DO NOT RELOAD THE PAGE - Otherwise your progress will be lost!');
            }
        });
        // Mark quiz as started; if page is reloaded, send user back to index
        if (!sessionStorage.getItem('quizStarted')) {
            sessionStorage.setItem('quizStarted', '1');
        } else {
            alert('You reloaded the quiz page. Redirecting to start to avoid duplicate attempt.');
            window.location.href = 'index.php';
        }

        let currentBlock = 0;
        const totalBlocks = <?php echo ceil(count($question_data) / 10); ?>;
        const questionIds = <?php echo json_encode(array_column($question_data, 'id')); ?>;

        function updateNav() {
            document.getElementById("prevBtn").disabled = currentBlock === 0;
            document.getElementById("nextBtn").style.display = currentBlock === totalBlocks - 1 ? "none" : "inline-block";
            document.getElementById("submitBtn").style.display = currentBlock === totalBlocks - 1 ? "inline-block" : "none";
        }
        function changePage(step) {
            document.getElementById(`block-${currentBlock}`).classList.remove("active");
            currentBlock += step;
            document.getElementById(`block-${currentBlock}`).classList.add("active");
            updateNav();
            // Only scroll to top when moving forward (Next). Do not scroll on Previous.
            if (step > 0) {
                window.scrollTo({ top: 0, behavior: 'smooth' });
                setTimeout(() => {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }, 0);
            }
        }

        // Timer logic
        let timeLeft = 3600;
        const timer = setInterval(() => {
            let min = Math.floor(timeLeft / 60);
            let sec = timeLeft % 60;
            document.getElementById('timer').innerText = `Time Left: ${min}:${sec < 10 ? '0' : ''}${sec}`;
            if (timeLeft <= 0) {
                clearInterval(timer);
                alert('Time is up! Submitting quiz...');
                document.getElementById("quizForm").submit();
            }
            timeLeft--;
        }, 1000);

        // Prevent back button navigation and clear quiz data
        history.pushState(null, null, location.href);
        window.onpopstate = function(event) {
            history.pushState(null, null, location.href);
            
            // Show popup warning
            alert('⚠️ DO NOT GO BACK OR RELOAD THE PAGE - Otherwise your all progress will be gone!');
            
            // Clear all form data (quiz answers)
            document.getElementById('quizForm').reset();
            
            // Clear any stored data
            localStorage.clear();
            sessionStorage.clear();
            
            // Redirect to index page
            setTimeout(function() {
                window.location.href = 'index.php';
            }, 100);
        };

        // When submitting, allow navigation (remove beforeunload) and block double-submit
        const form = document.getElementById('quizForm');
        let submitted = false;
        form.addEventListener('submit', function(e) {
            // Custom validation: ensure every question has an answer selected
            for (let i = 0; i < questionIds.length; i++) {
                const qid = questionIds[i];
                if (!document.querySelector(`input[name=\"answers[${qid}]\"]:checked`)) {
                    e.preventDefault();
                    alert(`Please answer question ${i + 1} before submitting.`);
                    // Jump to the block that contains this question
                    const anyOption = document.querySelector(`input[name=\"answers[${qid}]\"]`);
                    if (anyOption) {
                        const blockEl = anyOption.closest('.question-block');
                        if (blockEl && blockEl.id && blockEl.id.startsWith('block-')) {
                            document.getElementById(`block-${currentBlock}`).classList.remove('active');
                            currentBlock = parseInt(blockEl.id.replace('block-', ''), 10) || 0;
                            document.getElementById(`block-${currentBlock}`).classList.add('active');
                            updateNav();
                            anyOption.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }
                    return false;
                }
            }
            if (submitted) {
                e.preventDefault();
                return false;
            }
            submitted = true;
            guardEnabled = false;
            window.onbeforeunload = null;
        });
    </script>
</body>
</html>
<?php
}
?>
