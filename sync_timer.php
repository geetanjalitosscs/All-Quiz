<?php
// ===============================
// File: sync_timer.php
// Purpose: Synchronize timer with server (server is authoritative)
// ===============================
include 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get attempt_id from session or POST
$attempt_id = $_POST['attempt_id'] ?? $_SESSION['quiz_attempt_id'] ?? null;
$client_remaining_seconds = isset($_POST['client_remaining_seconds']) ? (int)$_POST['client_remaining_seconds'] : null;

// Validate required parameters
if (!$attempt_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required parameter: attempt_id']);
    exit;
}

// Total quiz time in seconds (45 minutes = 2700 seconds)
$TOTAL_QUIZ_TIME = 2700;

try {
    // CRITICAL: Verify user is authenticated
    $user_id = $_SESSION['quiz_user_id'] ?? null;
    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'User not authenticated']);
        exit;
    }
    
    // Get attempt details with expires_at (SERVER-CONTROLLED TIMER)
    // CRITICAL: Also verify attempt belongs to current user (prevent data leakage)
    $stmt = $conn->prepare("
        SELECT 
            attempt_id,
            user_id,
            status,
            start_time,
            expires_at,
            duration_minutes,
            remaining_time_seconds
        FROM quiz_attempts 
        WHERE attempt_id = ?
    ");
    $stmt->bind_param("i", $attempt_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Quiz attempt not found']);
        $stmt->close();
        exit;
    }
    
    $attempt = $result->fetch_assoc();
    $stmt->close();
    
    // CRITICAL: Verify attempt belongs to current user (prevent data leakage)
    if ((int)$attempt['user_id'] !== (int)$user_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied: This attempt does not belong to you']);
        exit;
    }
    
    if ($attempt['status'] !== 'in_progress') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Quiz attempt is not in progress',
            'status' => $attempt['status']
        ]);
        exit;
    }
    
    // CRITICAL: Calculate remaining time from expires_at (SERVER-CONTROLLED)
    // This ensures timer NEVER resets on back/refresh/browser close
    if (!empty($attempt['expires_at']) && $attempt['expires_at'] != '0000-00-00 00:00:00') {
        $expires_datetime = new DateTime($attempt['expires_at'], new DateTimeZone('Asia/Kolkata'));
        $now_datetime = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        $server_remaining_seconds = max(0, $expires_datetime->getTimestamp() - $now_datetime->getTimestamp());
        $elapsed_seconds = $TOTAL_QUIZ_TIME - $server_remaining_seconds;
    } else {
        // Fallback: Calculate from start_time (for old records)
        $start_datetime = new DateTime($attempt['start_time'], new DateTimeZone('Asia/Kolkata'));
        $now_datetime = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        $elapsed_seconds = $now_datetime->getTimestamp() - $start_datetime->getTimestamp();
        $server_remaining_seconds = max(0, $TOTAL_QUIZ_TIME - $elapsed_seconds);
        
        // Update expires_at for this record (migration)
        $duration = $attempt['duration_minutes'] ?? 45;
        $expires_at = date('Y-m-d H:i:s', strtotime($attempt['start_time'] . " +{$duration} minutes"));
        $updateExpiresStmt = $conn->prepare("UPDATE quiz_attempts SET expires_at = ? WHERE attempt_id = ?");
        $updateExpiresStmt->bind_param("si", $expires_at, $attempt_id);
        $updateExpiresStmt->execute();
        $updateExpiresStmt->close();
    }
    
    // If time has expired, return 0
    if ($server_remaining_seconds <= 0) {
        // Mark attempt as expired
        $expireStmt = $conn->prepare("
            UPDATE quiz_attempts 
            SET status = 'expired', 
                end_time = NOW(),
                remaining_time_seconds = 0
            WHERE attempt_id = ? AND status = 'in_progress'
        ");
        $expireStmt->bind_param("i", $attempt_id);
        $expireStmt->execute();
        $expireStmt->close();
        
        echo json_encode([
            'success' => true,
            'remaining_seconds' => 0,
            'expired' => true,
            'message' => 'Time has expired'
        ]);
        exit;
    }
    
    // Check if client time differs significantly (>5 seconds difference)
    $time_difference = abs($server_remaining_seconds - (int)$attempt['remaining_time_seconds']);
    $needs_update = false;
    
    if ($client_remaining_seconds !== null) {
        $client_difference = abs($server_remaining_seconds - $client_remaining_seconds);
        if ($client_difference > 5) {
            $needs_update = true;
        }
    }
    
    // Update remaining_time_seconds in database if needed
    if ($needs_update || $time_difference > 5) {
        $updateStmt = $conn->prepare("
            UPDATE quiz_attempts 
            SET remaining_time_seconds = ?,
                last_activity_time = NOW()
            WHERE attempt_id = ? AND status = 'in_progress'
        ");
        $updateStmt->bind_param("ii", $server_remaining_seconds, $attempt_id);
        $updateStmt->execute();
        $updateStmt->close();
    } else {
        // Just update last_activity_time
        $updateStmt = $conn->prepare("
            UPDATE quiz_attempts 
            SET last_activity_time = NOW()
            WHERE attempt_id = ? AND status = 'in_progress'
        ");
        $updateStmt->bind_param("i", $attempt_id);
        $updateStmt->execute();
        $updateStmt->close();
    }
    
    // Return server-calculated remaining time (authoritative)
    echo json_encode([
        'success' => true,
        'remaining_seconds' => $server_remaining_seconds,
        'elapsed_seconds' => $elapsed_seconds,
        'server_time' => date('Y-m-d H:i:s'),
        'needs_correction' => $needs_update
    ]);
    
} catch (Exception $e) {
    error_log("sync_timer.php error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>

