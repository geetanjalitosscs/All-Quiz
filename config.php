<?php
// ===============================
// File: config.php
// Environment Configuration System
// Automatically detects localhost vs server environment
// ===============================

/**
 * Environment Detection
 * Detects if running on localhost (XAMPP) or server (VPS)
 */
function detectEnvironment() {
    // Method 1: Check HTTP_HOST (most reliable for web requests)
    $httpHost = $_SERVER['HTTP_HOST'] ?? '';
    if (stripos($httpHost, 'localhost') !== false || 
        stripos($httpHost, '127.0.0.1') !== false ||
        stripos($httpHost, '::1') !== false) {
        return 'local';
    }
    
    // Method 2: Check SERVER_NAME
    $serverName = $_SERVER['SERVER_NAME'] ?? '';
    if (stripos($serverName, 'localhost') !== false || 
        stripos($serverName, '127.0.0.1') !== false) {
        return 'local';
    }
    
    // Method 3: Check if running on Windows (XAMPP indicator)
    if (PHP_OS_FAMILY === 'Windows') {
        return 'local';
    }
    
    // Method 4: Check for .env.local file (explicit local override)
    if (file_exists(__DIR__ . '/.env.local')) {
        return 'local';
    }
    
    // Method 5: Check environment variable
    $env = getenv('APP_ENV');
    if ($env === 'local' || $env === 'development') {
        return 'local';
    }
    if ($env === 'production' || $env === 'server') {
        return 'server';
    }
    
    // Default: assume server if none of the above match
    return 'server';
}

/**
 * Load .env file (optional)
 * Supports simple KEY=VALUE format
 */
function loadEnvFile($filePath) {
    if (!file_exists($filePath)) {
        return [];
    }
    
    $env = [];
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Remove quotes if present
            $value = trim($value, '"\'');
            $env[$key] = $value;
        }
    }
    
    return $env;
}

// Detect current environment
$environment = detectEnvironment();

// Load .env file if exists (takes precedence)
$envVars = [];
$envFile = $environment === 'local' ? '.env.local' : '.env';
if (file_exists(__DIR__ . '/' . $envFile)) {
    $envVars = loadEnvFile(__DIR__ . '/' . $envFile);
}

// ===============================
// Database Configuration
// ===============================

if ($environment === 'local') {
    // LOCAL DEVELOPMENT (XAMPP)
    $db_config = [
        'host' => $envVars['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost',
        'user' => $envVars['DB_USER'] ?? getenv('DB_USER') ?: 'root',
        'pass' => $envVars['DB_PASS'] ?? getenv('DB_PASS') ?: '',
        'name' => $envVars['DB_NAME'] ?? getenv('DB_NAME') ?: 'all_assessment_quiz',
        'port' => $envVars['DB_PORT'] ?? getenv('DB_PORT') ?: 3306,
        'socket' => $envVars['DB_SOCKET'] ?? getenv('DB_SOCKET') ?: null,
        'use_tcp' => false, // Use socket connection for localhost
    ];
} else {
    // PRODUCTION SERVER (VPS)
    $db_config = [
        'host' => $envVars['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1', // TCP connection
        'user' => $envVars['DB_USER'] ?? getenv('DB_USER') ?: 'quiz_user', // Non-root user
        'pass' => $envVars['DB_PASS'] ?? getenv('DB_PASS') ?: 'Quiz@123', // Must be set via .env or environment variable
        'name' => $envVars['DB_NAME'] ?? getenv('DB_NAME') ?: 'all_assessment_quiz',
        'port' => $envVars['DB_PORT'] ?? getenv('DB_PORT') ?: 3306,
        'socket' => null,
        'use_tcp' => true, // Use TCP connection for server
    ];
}

// Server Configuration (for API/router)
define('SERVER_HOST', $envVars['SERVER_HOST'] ?? getenv('SERVER_HOST') ?: '0.0.0.0');
define('SERVER_PORT', $envVars['SERVER_PORT'] ?? getenv('SERVER_PORT') ?: '8087');

// Export database config globally
$GLOBALS['db_config'] = $db_config;
$GLOBALS['app_environment'] = $environment;

// Optional: Define constants for backward compatibility
if (!defined('DB_HOST')) {
    define('DB_HOST', $db_config['host']);
    define('DB_USER', $db_config['user']);
    define('DB_PASS', $db_config['pass']);
    define('DB_NAME', $db_config['name']);
    define('DB_PORT', $db_config['port']);
}
?>


