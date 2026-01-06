<?php
// PHP Configuration Checker
// Access this file at: http://localhost/All-Assessment-Quiz/check_php.php

echo "<h2>PHP Configuration Check</h2>";
echo "<hr>";

// Check PHP version
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";

// Check if mysqli extension is loaded
echo "<p><strong>mysqli Extension:</strong> ";
if (extension_loaded('mysqli')) {
    echo "<span style='color: green;'>✓ LOADED</span></p>";
} else {
    echo "<span style='color: red;'>✗ NOT LOADED</span></p>";
    echo "<p style='color: red;'><strong>Action Required:</strong> Enable mysqli extension in php.ini</p>";
}

// Check loaded extensions
echo "<h3>Loaded Extensions:</h3>";
$extensions = get_loaded_extensions();
if (in_array('mysqli', $extensions)) {
    echo "<p style='color: green;'>mysqli is in the list of loaded extensions.</p>";
} else {
    echo "<p style='color: red;'>mysqli is NOT in the list of loaded extensions.</p>";
}

// Show php.ini location
echo "<h3>PHP Configuration File:</h3>";
$ini_path = php_ini_loaded_file();
if ($ini_path) {
    echo "<p><strong>php.ini Location:</strong> <code>" . $ini_path . "</code></p>";
    echo "<p><strong>⚠️ Edit this file to enable mysqli extension</strong></p>";
} else {
    echo "<p style='color: red;'>No php.ini file found!</p>";
}

// Show extension directory
echo "<h3>Extension Directory:</h3>";
$ext_dir = ini_get('extension_dir');
echo "<p><strong>Extension Directory:</strong> <code>" . $ext_dir . "</code></p>";

// Check if mysqli DLL exists
if ($ext_dir) {
    $mysqli_dll = rtrim($ext_dir, '\\/') . '\\php_mysqli.dll';
    if (file_exists($mysqli_dll)) {
        echo "<p style='color: green;'>✓ php_mysqli.dll found at: <code>" . $mysqli_dll . "</code></p>";
    } else {
        echo "<p style='color: orange;'>⚠ php_mysqli.dll not found at expected location.</p>";
        echo "<p>Check: <code>" . $mysqli_dll . "</code></p>";
    }
}

// Show mysqli configuration in php.ini
echo "<h3>How to Fix:</h3>";
echo "<ol>";
echo "<li>Open the php.ini file shown above</li>";
echo "<li>Search for: <code>extension=mysqli</code></li>";
echo "<li>Remove the semicolon (;) at the beginning if present</li>";
echo "<li>It should be: <code>extension=mysqli</code> (no semicolon)</li>";
echo "<li>Save the file</li>";
echo "<li>Restart Apache in XAMPP Control Panel</li>";
echo "<li>Refresh this page to verify</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='index.php'>← Back to Quiz</a></p>";
?>


