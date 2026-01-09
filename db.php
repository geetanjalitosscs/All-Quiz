<?php
// ===============================
// File: db.php
// ===============================

if (!isset($GLOBALS['db_config'])) {
    require_once __DIR__ . '/config.php';
}

$host = $GLOBALS['db_config']['host'] ?? 'localhost';
$user = $GLOBALS['db_config']['user'] ?? 'root';
$pass = $GLOBALS['db_config']['pass'] ?? '';
$db   = $GLOBALS['db_config']['name'] ?? '';

try {
    $conn = new mysqli($host, $user, $pass, $db);
    
    if ($conn->connect_errno) {
        error_log("Database connection failed ({$conn->connect_errno}): {$conn->connect_error}");
        http_response_code(500);
        
        // User-friendly error message
        $error_msg = "Database connection error! ";
        if ($conn->connect_errno == 2002 || strpos($conn->connect_error, 'refused') !== false) {
            $error_msg .= "MySQL server is not running. Please start MySQL from XAMPP Control Panel.";
        } else {
            $error_msg .= "Please check your database configuration. Error: " . $conn->connect_error;
        }
        exit($error_msg);
    }
} catch (Exception $e) {
    error_log("Database connection exception: " . $e->getMessage());
    http_response_code(500);
    exit('Database connection error! MySQL server is not running. Please start MySQL from XAMPP Control Panel.');
}

$conn->set_charset('utf8mb4');
?> 
