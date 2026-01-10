<?php
// Quick IP Address Finder - Network Access Helper
// Access this file to see your system's IP address for network access

// Get server IP
$server_ip = $_SERVER['SERVER_ADDR'] ?? 'Not detected';
$http_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$remote_addr = $_SERVER['REMOTE_ADDR'] ?? 'Not detected';

// Try to get actual network IP (Windows)
$network_ip = $server_ip;
if ($server_ip === '::1' || $server_ip === '127.0.0.1') {
    // If localhost, try to get actual network IP
    $output = [];
    if (PHP_OS_FAMILY === 'Windows') {
        exec('ipconfig | findstr /i "IPv4"', $output);
        if (!empty($output)) {
            preg_match('/\d+\.\d+\.\d+\.\d+/', $output[0], $matches);
            if (!empty($matches)) {
                $network_ip = $matches[0];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Network IP Address - Quiz System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        .value {
            font-size: 24px;
            color: #667eea;
            font-weight: bold;
            word-break: break-all;
            font-family: 'Courier New', monospace;
        }
        .url-box {
            background: #e8f5e9;
            border: 2px solid #4caf50;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .url-box .label {
            color: #2e7d32;
        }
        .url-box .value {
            color: #2e7d32;
            font-size: 20px;
        }
        .copy-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
            transition: background 0.3s;
        }
        .copy-btn:hover {
            background: #5568d3;
        }
        .copy-btn:active {
            transform: scale(0.98);
        }
        .instructions {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .instructions h3 {
            color: #856404;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .instructions ol {
            margin-left: 20px;
            color: #856404;
        }
        .instructions li {
            margin: 5px 0;
        }
        .warning {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            color: #721c24;
            font-size: 14px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌐 Network Access Information</h1>
        <p class="subtitle">Use this IP address to access the quiz from other devices on the same WiFi network</p>
        
        <div class="info-box">
            <div class="label">Your System IP Address</div>
            <div class="value" id="ipAddress"><?php echo htmlspecialchars($network_ip); ?></div>
        </div>
        
        <div class="url-box">
            <div class="label">Access URL (from other devices)</div>
            <div class="value" id="accessUrl">http://<?php echo htmlspecialchars($network_ip); ?>/All-Assessment-Quiz/</div>
            <button class="copy-btn" onclick="copyToClipboard()">📋 Copy URL</button>
        </div>
        
        <div class="info-box">
            <div class="label">Current Access</div>
            <div class="value" style="font-size: 16px;"><?php echo htmlspecialchars($http_host); ?></div>
        </div>
        
        <div class="instructions">
            <h3>📱 How to Access from Another Device:</h3>
            <ol>
                <li>Make sure both devices are on the <strong>same WiFi network</strong></li>
                <li>On the other device, open a web browser</li>
                <li>Type the URL shown above in the address bar</li>
                <li>Press Enter to access the quiz system</li>
            </ol>
        </div>
        
        <div class="warning">
            ⚠️ <strong>Security Note:</strong> This setup is for local network access only. Do not expose this to the internet without proper security measures (HTTPS, authentication, firewall rules).
        </div>
        
        <a href="index.php" class="back-link">← Back to Quiz Registration</a>
    </div>
    
    <script>
        function copyToClipboard() {
            const url = document.getElementById('accessUrl').textContent;
            navigator.clipboard.writeText(url).then(function() {
                const btn = event.target;
                const originalText = btn.textContent;
                btn.textContent = '✅ Copied!';
                btn.style.background = '#4caf50';
                setTimeout(function() {
                    btn.textContent = originalText;
                    btn.style.background = '#667eea';
                }, 2000);
            }).catch(function(err) {
                alert('Failed to copy. Please copy manually: ' + url);
            });
        }
    </script>
</body>
</html>

