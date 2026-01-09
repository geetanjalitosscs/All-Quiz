<?php
// ===============================
// File: quiz.php (Quiz UI Page)
// ===============================
include 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Helper to fetch question rows from a prepared statement without relying on mysqlnd get_result().
 *
 * @param mysqli_stmt $stmt
 * @return array<int, array<string, mixed>>
 */
function fetchQuestionsFromStatement(mysqli_stmt $stmt): array
{
    $questions = [];
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        return $questions;
    }

    $stmt->bind_result($id, $question, $optionA, $optionB, $optionC, $optionD);
    while ($stmt->fetch()) {
        $questions[] = [
            'id'        => $id,
            'question'  => $question,
            'option_a'  => $optionA,
            'option_b'  => $optionB,
            'option_c'  => $optionC,
            'option_d'  => $optionD,
        ];
    }

    return $questions;
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
    $checkStmt->store_result();

    $existingUserId = null;
    if ($checkStmt->num_rows > 0) {
        $checkStmt->bind_result($existingUserId);
        $checkStmt->fetch();
    }
    $checkStmt->free_result();
    $checkStmt->close();

    if ($existingUserId !== null) {
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
    $_SESSION['quiz_name'] = $_POST['name'] ?? '';
    $_SESSION['quiz_mobile'] = $mobile;
    
    // Fetch user details for display
    $userStmt = $conn->prepare("SELECT name, mobile FROM users WHERE id = ?");
    $userStmt->bind_param("i", $user_id);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $userData = $userResult->fetch_assoc();
    $userName = $userData['name'] ?? $_POST['name'] ?? '';
    $userMobile = $userData['mobile'] ?? $mobile ?? '';
    $userStmt->close();

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
    $question_data = fetchQuestionsFromStatement($stmtQ);

    // Fallback: if none found and table has role, ignore role filter and just match level
    if (count($question_data) === 0 && $hasRoleCol) {
        $stmtQ->close();
        $sql = $baseSelect . " WHERE LOWER(level) IN (?, ?) ORDER BY RAND() LIMIT 50";
        $stmtQ = $conn->prepare($sql);
        $stmtQ->bind_param("ss", $levelA, $levelB);
        $stmtQ->execute();
        $question_data = fetchQuestionsFromStatement($stmtQ);
    }
    if (count($question_data) === 0) {
        die("No questions found for {$role} ({$level}). Please contact the administrator.");
    }
    ?>

<!DOCTYPE html>
<html>
<head>
    <title>Quiz - Toss Consultancy Services</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/app.css">
</head>
<body class="app-shell app-shell-quiz">
    <div class="warning-banner">
        <strong>Do not go back or reload.</strong> Your assessment progress will be lost.
    </div>

    <header class="app-header">
        <div class="app-header-inner">
            <div class="brand-lockup">
                <span class="brand-pill">Toss Consultancy Services</span>
                <div class="brand-text">
                    <span class="brand-title">Assessment in progress</span>
                    <span class="brand-subtitle">
                        <?php echo htmlspecialchars($userName); ?> · <?php echo htmlspecialchars($userMobile); ?>
                    </span>
                    <span class="brand-subtitle" style="font-size: 11px; margin-top: 2px; opacity: 0.9;">
                        Role: <?php echo htmlspecialchars($role); ?> · Level: <?php echo htmlspecialchars($level); ?>
                    </span>
                </div>
            </div>
            <div class="header-meta">
                <button type="button" class="theme-toggle-btn" id="themeToggle" aria-label="Toggle dark mode">
                    <span class="theme-toggle-icon" id="themeToggleIcon">🌙</span>
                    <span class="theme-toggle-label" id="themeToggleLabel">Dark</span>
                </button>
                <span class="header-meta-pill">Timer: <span id="timer" class="timer-display">60:00</span></span>
                <span>50 questions · Single attempt</span>
            </div>
        </div>
    </header>

    <!-- Modal Popup for Warnings -->
    <div class="modal-overlay" id="warningModal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h2 class="modal-title">Warning: Progress Will Be Lost</h2>
            </div>
            <div class="modal-body">
                <p class="modal-message">
                    <strong>Do not go back or reload the page!</strong><br><br>
                    If you navigate away or reload this page, all your progress and answers will be permanently lost. You will need to start the assessment from the beginning.
                </p>
                <div class="modal-actions">
                    <button type="button" class="modal-btn modal-btn-secondary" onclick="closeWarningModal()">
                        Stay on Page
                    </button>
                </div>
            </div>
        </div>
    </div>

    <main class="app-main">
        <div class="app-main-inner">
            <section class="quiz-layout">
                <div class="card quiz-main-card">
                    <div class="card-header">
                        <div class="badge badge-neutral">Question set</div>
                        <h1 class="card-title">Technical multiple-choice questions</h1>
                        <div class="quiz-header-meta">
                            <span>You can skip questions if needed.</span>
                            <span class="chip">Pagination: 1 question per page</span>
                        </div>
                    </div>

                    <hr class="card-divider">

                    <form action="submit_quiz.php" method="POST" id="quizForm">
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                        <input type="hidden" name="role" value="<?php echo htmlspecialchars($role); ?>">
                        <input type="hidden" name="level" value="<?php echo htmlspecialchars($level); ?>">
                        <input type="hidden" name="all_question_ids" value="<?php echo htmlspecialchars(json_encode(array_column($question_data, 'id'))); ?>">
                        <?php
                        $page = 0;
                        $block = -1;
                        foreach ($question_data as $index => $q) {
                            $currentBlock = intdiv($index, 1);
                            
                            // Open new block when we enter a new block
                            if ($currentBlock != $block) {
                                // Close previous block if exists
                                if ($block >= 0) {
                                    echo "</div>";
                                }
                                $block = $currentBlock;
                                $activeClass = ($block == 0) ? ' active' : '';
                                echo "<div class='quiz-question-block question-block{$activeClass}' id='block-$block'>";
                            }

                            echo "<article class='quiz-question-item'>";
                            echo "<h2 class='quiz-question-title'>Q" . ($index + 1) . ". {$q['question']}</h2>";
                            echo "<label class='quiz-option'><input type='radio' name='answers[{$q['id']}]' value='A'> <span>A) {$q['option_a']}</span></label>";
                            echo "<label class='quiz-option'><input type='radio' name='answers[{$q['id']}]' value='B'> <span>B) {$q['option_b']}</span></label>";
                            echo "<label class='quiz-option'><input type='radio' name='answers[{$q['id']}]' value='C'> <span>C) {$q['option_c']}</span></label>";
                            echo "<label class='quiz-option'><input type='radio' name='answers[{$q['id']}]' value='D'> <span>D) {$q['option_d']}</span></label>";
                            echo "</article>";
                        }
                        // Close the last block
                        if ($block >= 0) {
                            echo "</div>";
                        }
                        ?>
                        <div class="quiz-nav">
                            <button type="button" class="btn btn-outline btn-sm" id="prevBtn" onclick="changePage(-1)" disabled>
                                ← Previous
                            </button>
                            <div class="quiz-nav-right">
                                <span class="pill-counter" id="pageIndicator">Page 1 of <?php echo ceil(count($question_data) / 1); ?></span>
                                <button type="button" class="btn btn-outline btn-sm" id="nextBtn" onclick="changePage(1)">
                                    Next →
                                </button>
                                <button type="submit" class="btn btn-primary btn-sm" id="submitBtn" style="display:none;">
                                    Submit quiz
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <aside class="card quiz-sidebar-card">
                    <div class="card-section-title">Session overview</div>
                    <div class="timer-shell">
                        <div>
                            <div class="timer-label">Time remaining</div>
                            <div id="timerSidebar" class="timer-value">60:00</div>
                        </div>
                        <div class="badge badge-danger">Auto submit on timeout</div>
                    </div>

                    <hr class="card-divider">

                    <div class="card-section-title">Question progress</div>
                    <div class="quiz-sidebar-meta">
                        <span><strong>Total</strong>: <?php echo count($question_data); ?> questions</span>
                        <span id="answeredCountLabel"><strong>Answered</strong>: 0</span>
                    </div>
                    <div class="progress-grid" id="progressGrid">
                        <?php
                        $totalQuestions = count($question_data);
                        for ($i = 0; $i < $totalQuestions; $i++) {
                            $questionNumber = $i + 1;
                            echo "<div class='progress-dot' data-question-number='{$questionNumber}' title='Jump to question {$questionNumber}'>{$questionNumber}</div>";
                        }
                        ?>
                    </div>
                </aside>
            </section>
        </div>
    </main>

    <script>
        // Theme toggle (light / dark) for quiz page
        (function() {
            const body = document.body;
            const toggleBtn = document.getElementById('themeToggle');
            const toggleIcon = document.getElementById('themeToggleIcon');
            const toggleLabel = document.getElementById('themeToggleLabel');

            function applyTheme(theme) {
                if (theme === 'dark') {
                    body.classList.add('dark-mode');
                    if (toggleIcon) toggleIcon.textContent = '☀️';
                    if (toggleLabel) toggleLabel.textContent = 'Light';
                } else {
                    body.classList.remove('dark-mode');
                    if (toggleIcon) toggleIcon.textContent = '🌙';
                    if (toggleLabel) toggleLabel.textContent = 'Dark';
                }
            }

            // Initial theme from localStorage or prefers-color-scheme
            const stored = window.localStorage.getItem('quiz-theme');
            const initialTheme = stored || 'light';
            applyTheme(initialTheme);

            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    const isDark = body.classList.contains('dark-mode');
                    const nextTheme = isDark ? 'light' : 'dark';
                    applyTheme(nextTheme);
                    window.localStorage.setItem('quiz-theme', nextTheme);
                });
            }
        })();
    </script>
    <script>
        // Basic deterrent: disable context menu and common developer shortcuts
        document.addEventListener('contextmenu', event => event.preventDefault());
        document.onkeydown = function(e) {
            if (e.keyCode === 123) return false;
            if (e.ctrlKey && e.shiftKey && (e.keyCode === 73 || e.keyCode === 74)) return false;
            if (e.ctrlKey && (e.keyCode === 85 || e.keyCode === 83)) return false;
        };
    </script>
    <script>
        // Modal functions
        function showWarningModal() {
            const modal = document.getElementById('warningModal');
            if (modal) {
                modal.classList.add('active');
            }
        }

        function closeWarningModal() {
            const modal = document.getElementById('warningModal');
            if (modal) {
                modal.classList.remove('active');
            }
        }

        // Close modal when clicking outside
        document.getElementById('warningModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeWarningModal();
            }
        });

        // Disable context menu and common developer shortcuts
        const blockMessage = 'This action is disabled on this page.';
        document.addEventListener('contextmenu', (event) => {
            event.preventDefault();
            showWarningModal();
        });
        document.addEventListener('keydown', (event) => {
            const key = event.key.toLowerCase();
            if (
                event.key === 'F12' ||
                (event.ctrlKey && event.shiftKey && ['i', 'j', 'c', 'k', 'p'].includes(key)) ||
                (event.ctrlKey && ['u', 's', 'p'].includes(key)) ||
                (event.ctrlKey && event.altKey && key === 'i')
            ) {
                event.preventDefault();
                event.stopPropagation();
                showWarningModal();
                return false;
            }
            return true;
        });

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
                showWarningModal();
            }
        });
        // Mark quiz as started; if page is reloaded, send user back to index
        if (!sessionStorage.getItem('quizStarted')) {
            sessionStorage.setItem('quizStarted', '1');
        } else {
            // Show alert and redirect
            alert('⚠️ You reloaded the quiz page. Redirecting to start to avoid duplicate attempt.');
            window.location.href = 'index.php';
        }

        let currentBlock = 0;
        const totalBlocks = <?php echo ceil(count($question_data) / 1); ?>;
        const questionIds = <?php echo json_encode(array_column($question_data, 'id')); ?>;

        // Immediately scroll to top before DOM loads
        window.scrollTo(0, 0);
        document.documentElement.scrollTop = 0;
        document.body.scrollTop = 0;
        
        // Ensure first block is active on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Scroll to top first
            window.scrollTo(0, 0);
            document.documentElement.scrollTop = 0;
            document.body.scrollTop = 0;
            
            // Also scroll the main container and card
            const mainContainer = document.querySelector('.app-main');
            if (mainContainer) {
                mainContainer.scrollTop = 0;
            }
            const card = document.querySelector('.quiz-main-card');
            if (card) {
                card.scrollTop = 0;
            }
            const form = document.getElementById('quizForm');
            if (form) {
                form.scrollTop = 0;
            }
            
            const firstBlock = document.getElementById('block-0');
            if (firstBlock) {
                firstBlock.classList.add('active');
                firstBlock.classList.add('question-block');
            }
            // Remove active from other blocks
            for (let i = 1; i < totalBlocks; i++) {
                const block = document.getElementById(`block-${i}`);
                if (block) {
                    block.classList.remove('active');
                }
            }
            updateNav();
            updateAnsweredProgress();
            
            // Force scroll to top multiple times to ensure
            setTimeout(() => {
                window.scrollTo(0, 0);
                document.documentElement.scrollTop = 0;
                document.body.scrollTop = 0;
            }, 50);
            setTimeout(() => {
                window.scrollTo(0, 0);
                document.documentElement.scrollTop = 0;
                document.body.scrollTop = 0;
            }, 200);
        });

        function updateNav() {
            document.getElementById("prevBtn").disabled = currentBlock === 0;
            document.getElementById("nextBtn").style.display = currentBlock === totalBlocks - 1 ? "none" : "inline-flex";
            document.getElementById("submitBtn").style.display = currentBlock === totalBlocks - 1 ? "inline-flex" : "none";
            const pageIndicator = document.getElementById("pageIndicator");
            if (pageIndicator) {
                pageIndicator.textContent = `Page ${currentBlock + 1} of ${totalBlocks}`;
            }
        }
        function changePage(step) {
            document.getElementById(`block-${currentBlock}`).classList.remove("active");
            currentBlock += step;
            document.getElementById(`block-${currentBlock}`).classList.add("active");
            updateNav();
            updateAnsweredProgress();
        }

        // Timer logic
        let timeLeft = 3600;
        const timer = setInterval(() => {
            let min = Math.floor(timeLeft / 60);
            let sec = timeLeft % 60;
            const formatted = `${min}:${sec < 10 ? '0' : ''}${sec}`;
            const timerTop = document.getElementById('timer');
            const timerSidebar = document.getElementById('timerSidebar');
            if (timerTop) timerTop.textContent = formatted;
            if (timerSidebar) timerSidebar.textContent = formatted;
            if (timeLeft <= 0) {
                clearInterval(timer);
                alert('Time is up! Submitting quiz...');
                document.getElementById("quizForm").submit();
            }
            timeLeft--;
        }, 1000);

        // Progress tracking and jump navigation for questions (UI-only)
        const progressGrid = document.getElementById('progressGrid');
        const answeredCountLabel = document.getElementById('answeredCountLabel');

        function updateAnsweredProgress() {
            if (!progressGrid) return;
            const dots = progressGrid.querySelectorAll('.progress-dot');
            let answered = 0;

            questionIds.forEach((qid, idx) => {
                const hasAnswer = !!document.querySelector(`input[name="answers[${qid}]"]:checked`);
                const dot = dots[idx];
                if (dot) {
                    dot.classList.toggle('progress-dot-answered', hasAnswer);
                    dot.classList.toggle('progress-dot-current', Math.floor(idx / 1) === currentBlock);
                }
                if (hasAnswer) answered++;
            });

            if (answeredCountLabel) {
                answeredCountLabel.textContent = `Answered: ${answered}`;
            }
        }

        // Allow clicking on progress dots to jump directly to a question/page
        if (progressGrid) {
            const dots = progressGrid.querySelectorAll('.progress-dot');
            dots.forEach((dot, idx) => {
                dot.addEventListener('click', () => {
                    const targetIndex = idx; // zero-based index in questionIds
                    const targetBlock = Math.floor(targetIndex / 1);

                    // Switch page if needed
                    if (targetBlock !== currentBlock) {
                        const currentBlockEl = document.getElementById(`block-${currentBlock}`);
                        const targetBlockEl = document.getElementById(`block-${targetBlock}`);
                        if (currentBlockEl && targetBlockEl) {
                            currentBlockEl.classList.remove('active');
                            currentBlock = targetBlock;
                            targetBlockEl.classList.add('active');
                            updateNav();
                        }
                    }

                    // Only scroll if page is at the top, otherwise don't scroll
                    const isAtTop = window.scrollY === 0 || document.documentElement.scrollTop === 0;
                    if (isAtTop) {
                        // Scroll to top of main content area first
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        
                        // Then scroll to the specific question within the block after a short delay
                        setTimeout(() => {
                            const qid = questionIds[targetIndex];
                            const anyOption = document.querySelector(`input[name="answers[${qid}]"]`);
                            if (anyOption) {
                                const questionItem = anyOption.closest('.quiz-question-item');
                                if (questionItem) {
                                    questionItem.scrollIntoView({
                                        behavior: 'smooth',
                                        block: 'center'
                                    });
                                }
                            }
                        }, 300);
                    }

                    updateAnsweredProgress();
                });
            });
        }

        document.querySelectorAll('#quizForm input[type="radio"]').forEach((input) => {
            input.addEventListener('change', updateAnsweredProgress);
        });
        updateAnsweredProgress();

        // Prevent back button navigation and clear quiz data
        history.pushState(null, null, location.href);
        window.onpopstate = function(event) {
            history.pushState(null, null, location.href);
            
            // Show modal warning
            showWarningModal();
            
            // Clear all form data (quiz answers) after a delay
            setTimeout(function() {
                document.getElementById('quizForm').reset();
                
                // Clear any stored data
                localStorage.clear();
                sessionStorage.clear();
                
                // Redirect to index page after showing warning
                setTimeout(function() {
                    window.location.href = 'index.php';
                }, 2000);
            }, 500);
        };

        // When submitting, allow navigation (remove beforeunload) and block double-submit
        const form = document.getElementById('quizForm');
        let submitted = false;
        form.addEventListener('submit', function(e) {
            // Allow submission even if some questions are unanswered (questions can be skipped)
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
