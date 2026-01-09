<?php
// ===============================
// File: db.php
// Database Connection Handler
// Handles connection logic only - configuration comes from config.php
// ===============================

// Load configuration if not already loaded
if (!isset($GLOBALS['db_config'])) {
    require_once __DIR__ . '/config.php';
}

$config = $GLOBALS['db_config'] ?? [];

// Extract connection parameters
$host = $config['host'] ?? 'localhost';
$user = $config['user'] ?? 'root';
$pass = $config['pass'] ?? '';
$db   = $config['name'] ?? '';
$port = $config['port'] ?? 3306;
$socket = $config['socket'] ?? null;
$use_tcp = $config['use_tcp'] ?? false;

// Validate required configuration
if (empty($db)) {
    error_log("Database configuration error: Database name is not set");
    http_response_code(500);
    exit('Database configuration error! Please check your configuration file.');
}

try {
    // Build connection parameters
    // For TCP connection (server): use host:port
    // For socket connection (local): use host with socket parameter
    if ($use_tcp && $socket === null) {
        // TCP connection for server (explicit port)
        $conn = new mysqli($host, $user, $pass, $db, $port);
    } elseif ($socket !== null) {
        // Socket connection (local XAMPP)
        $conn = new mysqli($host, $user, $pass, $db, $port, $socket);
    } else {
        // Default: let mysqli decide (usually socket on localhost, TCP on server)
        $conn = new mysqli($host, $user, $pass, $db, $port);
    }
    
    // Check connection errors
    if ($conn->connect_errno) {
        $error_code = $conn->connect_errno;
        $error_message = $conn->connect_error;
        
        error_log("Database connection failed ({$error_code}): {$error_message}");
        error_log("Connection attempt: host={$host}, user={$user}, db={$db}, port={$port}, socket=" . ($socket ?? 'null'));
        
        http_response_code(500);
        
        // User-friendly error messages based on environment
        $environment = $GLOBALS['app_environment'] ?? 'unknown';
        
        if ($environment === 'local') {
            // Local development error messages
            if ($error_code == 2002 || strpos($error_message, 'refused') !== false || strpos($error_message, 'No connection') !== false) {
                $error_msg = "Database connection error! MySQL server is not running. Please start MySQL from XAMPP Control Panel.";
            } elseif ($error_code == 1045) {
                $error_msg = "Database authentication failed! Please check your database credentials in config.php";
            } elseif ($error_code == 1049) {
                $error_msg = "Database '{$db}' not found! Please create the database first.";
            } else {
                $error_msg = "Database connection error! Please check your database configuration. Error: {$error_message}";
            }
        } else {
            // Production server error messages (less detailed for security)
            if ($error_code == 2002 || strpos($error_message, 'refused') !== false) {
                $error_msg = "Database connection error! Unable to connect to database server.";
            } elseif ($error_code == 1045) {
                $error_msg = "Database authentication failed! Please check your database credentials.";
            } elseif ($error_code == 1049) {
                $error_msg = "Database '{$db}' not found! Please check your configuration.";
            } else {
                $error_msg = "Database connection error! Please contact the administrator.";
            }
        }
        
        exit($error_msg);
    }
    
    // Set charset for proper encoding
    $conn->set_charset('utf8mb4');
    
    // Optional: Set timezone (adjust as needed)
    // $conn->query("SET time_zone = '+00:00'");
    
} catch (Exception $e) {
    error_log("Database connection exception: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    
    $environment = $GLOBALS['app_environment'] ?? 'unknown';
    if ($environment === 'local') {
        exit('Database connection error! MySQL server is not running. Please start MySQL from XAMPP Control Panel.');
    } else {
        exit('Database connection error! Please contact the administrator.');
    }
}
?> 
