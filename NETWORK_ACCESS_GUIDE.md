# Network Access Guide - Access Quiz from Other Devices

## Same WiFi Network se Access Kaise Karein

### Step 1: Apne System ka IP Address Find Karein

**Windows me:**
1. `Win + R` press karein
2. Type karein: `cmd` aur Enter press karein
3. Command prompt me type karein:
   ```
   ipconfig
   ```
4. `IPv4 Address` dekhein (example: `192.168.1.100`)

**Ya PowerShell me:**
```powershell
ipconfig | findstr IPv4
```

### Step 2: XAMPP Apache ko Network Access Allow Karein

**Option A: XAMPP Control Panel se (Easiest)**
1. XAMPP Control Panel open karein
2. Apache ke saamne **"Config"** button click karein
3. **"httpd.conf"** select karein
4. File me search karein: `Listen 80`
5. Change karein:
   ```
   Listen 0.0.0.0:80
   ```
   (Ye sabhi network interfaces par listen karega)

6. Search karein: `<Directory "C:/xampp/htdocs">`
7. Iske neeche find karein:
   ```
   Require all denied
   ```
8. Change karein:
   ```
   Require all granted
   ```

9. File save karein (Ctrl+S)
10. Apache ko restart karein (Stop, phir Start)

**Option B: Manual Edit (Advanced)**
1. File open karein: `C:\xampp\apache\conf\httpd.conf`
2. Line 46 pe find karein:
   ```
   Listen 80
   ```
3. Change karein:
   ```
   Listen 0.0.0.0:80
   ```

4. Line 240 ke aas-paas find karein:
   ```
   <Directory "C:/xampp/htdocs">
       ...
       Require all denied
   ```
5. Change karein:
   ```
   Require all granted
   ```

6. File save karein
7. Apache restart karein

### Step 3: Windows Firewall me Allow Karein

1. Windows Search me type karein: `Windows Defender Firewall`
2. **"Allow an app through firewall"** click karein
3. **"Change Settings"** click karein (admin permission chahiye)
4. **"Apache HTTP Server"** dhoondh kar check karein
5. Agar nahi mila, to:
   - **"Allow another app"** click karein
   - **"Browse"** click karein
   - Navigate karein: `C:\xampp\apache\bin\httpd.exe`
   - Add karein aur **"Private"** aur **"Public"** dono check karein

### Step 4: Dusre Device se Access Karein

**Mobile/Tablet/Another Computer se:**
1. Same WiFi network se connect karein
2. Browser open karein
3. URL me type karein:
   ```
   http://YOUR_IP_ADDRESS/All-Assessment-Quiz/
   ```
   Example: `http://192.168.1.100/All-Assessment-Quiz/`

### Step 5: IP Address Quick Find Script

Agar aapko har baar IP address check karna padta hai, to yeh script use karein:

**File create karein:** `get_ip.php` (project root me)
```php
<?php
$ip = $_SERVER['SERVER_ADDR'] ?? 'Not found';
$host = $_SERVER['HTTP_HOST'] ?? 'Not found';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Network IP Address</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .ip { font-size: 24px; color: #007bff; font-weight: bold; margin: 10px 0; }
        .url { font-size: 18px; color: #28a745; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Network Access Information</h2>
        <p><strong>Server IP:</strong></p>
        <div class="ip"><?php echo htmlspecialchars($ip); ?></div>
        <p><strong>Access URL (from other devices):</strong></p>
        <div class="url">http://<?php echo htmlspecialchars($ip); ?>/All-Assessment-Quiz/</div>
        <p><strong>Current Host:</strong> <?php echo htmlspecialchars($host); ?></p>
        <hr>
        <p><small>Note: Make sure both devices are on the same WiFi network</small></p>
    </div>
</body>
</html>
```

Is file ko browser me open karein: `http://localhost/All-Assessment-Quiz/get_ip.php`

## Troubleshooting

### Issue: "Connection Refused" ya "Can't Connect"

**Solutions:**
1. ✅ Apache running hai ya nahi check karein (XAMPP Control Panel)
2. ✅ Windows Firewall me Apache allow hai ya nahi check karein
3. ✅ Dono devices same WiFi par hain ya nahi verify karein
4. ✅ IP address sahi hai ya nahi check karein (`ipconfig` se)

### Issue: "403 Forbidden" Error

**Solution:**
- `httpd.conf` me `Require all granted` set kiya hai ya nahi check karein
- Apache restart karein

### Issue: IP Address Change Ho Raha Hai

**Solution:**
- Router me static IP assign karein (advanced)
- Ya har baar `ipconfig` se current IP check karein

### Issue: Mobile se Access Nahi Ho Raha

**Solutions:**
1. Mobile me browser me exact URL type karein (autocomplete avoid karein)
2. Mobile WiFi settings me check karein ki same network par hai
3. Mobile me airplane mode ON/OFF karein (network refresh ke liye)

## Security Note

⚠️ **Important:** Ye setup sirf local network (same WiFi) ke liye hai. Internet par expose mat karein without proper security (HTTPS, authentication, etc.).

## Quick Test

1. Apne system me: `http://localhost/All-Assessment-Quiz/` open karein
2. Dusre device me: `http://YOUR_IP/All-Assessment-Quiz/` open karein
3. Dono me same page dikhna chahiye

## Multiple Devices Support

✅ System 1000+ candidates ko simultaneously handle kar sakta hai
✅ Har device se independent access possible hai
✅ Har candidate ka data isolated rahega (session-based)

---

**Need Help?**
- Check XAMPP error logs: `C:\xampp\apache\logs\error.log`
- Check Windows Firewall settings
- Verify both devices on same network

