<?php
// File: index.php
?>
<!DOCTYPE html>
<html>
<head>
  <title>Quiz Registration - Toss Consultancy Services</title>
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
      max-width: 550px;
      margin: 40px auto;
      background: white;
      padding: 40px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.2);
      border-radius: 12px;
    }

    h2 {
      text-align: center;
      color: #004080;
      margin-bottom: 30px;
      font-size: 26px;
      font-weight: 600;
    }

    .form-group {
      margin-bottom: 25px;
    }

    label {
      font-weight: 600;
      color: #333;
      display: block;
      margin-bottom: 8px;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    input[type="text"],
    input[type="email"],
    input[type="tel"] {
      width: 100%;
      padding: 14px 16px;
      margin-top: 5px;
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      font-size: 15px;
      transition: all 0.3s ease;
      background: #fafafa;
    }

    input[type="text"]:focus,
    input[type="email"]:focus,
    input[type="tel"]:focus {
      outline: none;
      border-color: #004080;
      background: white;
      box-shadow: 0 0 0 3px rgba(0, 64, 128, 0.1);
    }

    input[readonly] {
      background-color: #f5f7fa;
      cursor: not-allowed;
      color: #666;
      border-color: #d0d0d0;
    }

    input[type="submit"] {
      width: 100%;
      padding: 16px;
      background: linear-gradient(135deg, #004080 0%, #0056b3 100%);
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-top: 10px;
      box-shadow: 0 4px 15px rgba(0, 64, 128, 0.3);
    }

    input[type="submit"]:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0, 64, 128, 0.4);
    }

    input[type="submit"]:active {
      transform: translateY(0);
    }

    select {
      width: 100%;
      padding: 14px 16px;
      margin-top: 5px;
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      font-size: 15px;
      transition: all 0.3s ease;
      background: #fafafa;
      cursor: pointer;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 16px center;
      padding-right: 40px;
    }

    select:focus {
      outline: none;
      border-color: #004080;
      background: white;
      box-shadow: 0 0 0 3px rgba(0, 64, 128, 0.1);
    }

    input:invalid {
      border-color: #ff6b6b;
    }

    input:valid:not([readonly]) {
      border-color: #51cf66;
    }

    .error-message {
      color: #ff6b6b;
      font-size: 12px;
      margin-top: 5px;
      margin-bottom: 15px;
      display: none;
      font-weight: 500;
    }

    input:invalid + .error-message {
      display: block;
    }
  </style>
</head>
<body>

  <header>
    Toss Consultancy Services
  </header>

  <div class="container">
    <h2>Enter Your Details to Start the Quiz</h2>
    <form action="quiz.php" method="POST">
      <div class="form-group">
        <label>Name:</label>
        <input type="text" name="name" required placeholder="Enter your full name">
      </div>

      <div class="form-group">
        <label>Role:</label>
        <select name="role" id="role" required>
          <option value="">Select your role</option>
          <option value="Backend Developer">Backend Developer</option>
          <option value="Python Developer">Python Developer</option>
          <option value="Flutter Developer">Flutter Developer</option>
          <option value="Mern Developer">Mern Developer</option>
          <option value="Full Stack Developer">Full Stack Developer</option>
        </select>
      </div>

      <div class="form-group">
        <label>Level:</label>
        <select name="level" id="level" required>
          <option value="">Select your level</option>
          <option value="Beginner">Beginner</option>
          <option value="Intermediate">Intermediate</option>
          <option value="Advanced">Advanced</option>
        </select>
      </div>

      <div class="form-group">
        <label>Place:</label>
        <input type="text" name="place" required placeholder="Enter your location">
      </div>

      <div class="form-group">
        <label>Phone Number:</label>
        <input type="tel" name="mobile" id="mobile" pattern="^[6789]\d{9}$" inputmode="numeric" maxlength="10" required placeholder="Enter 10 digit number starting with 6, 7, 8, or 9">
        <div class="error-message">Phone number must be exactly 10 digits starting with 6, 7, 8, or 9</div>
      </div>

      <div class="form-group">
        <label>Email:</label>
        <input type="email" name="email" required placeholder="Enter your email address">
        <div class="error-message" id="duplicateMsg" style="display:none;">User already attempt. Use different phone number and email.</div>
      </div>

      <input type="submit" id="startBtn" value="Start Quiz">
    </form>
  </div>

  <script>
    // Client-side duplicate attempt check on Start Quiz button click (AJAX before submit)
    document.getElementById('startBtn').addEventListener('click', async function(e) {
      e.preventDefault();
      const form = this.form;
      // Trigger HTML5 validation first
      if (!form.reportValidity()) return;

      const mobile = document.getElementById('mobile').value;
      const email = document.querySelector('input[name="email"]').value.trim();
      try {
        const resp = await fetch('check_user_attempt.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ email, mobile })
        });
        const data = await resp.json();
        if (data && data.attempted) {
          // Show inline message instead of popup
          const dup = document.getElementById('duplicateMsg');
          dup.style.display = 'block';
          return;
        }
        // Not attempted, proceed with real submit
        form.submit();
      } catch (err) {
        // On error, let the form submit
        form.submit();
      }
    });
    // Additional validation for phone number
    document.getElementById('mobile').addEventListener('input', function(e) {
      // Remove any non-numeric characters
      this.value = this.value.replace(/[^0-9]/g, '');
      
      // Limit to 10 digits
      if (this.value.length > 10) {
        this.value = this.value.substring(0, 10);
      }
      
      // Ensure it starts with 6, 7, 8, or 9
      if (this.value.length > 0 && !/^[6789]/.test(this.value)) {
        // If first digit is not 6, 7, 8, or 9, clear it
        if (this.value.length === 1) {
          this.value = '';
        } else {
          // Keep only if it starts with valid digit
          this.value = this.value.replace(/^[^6789]+/, '');
          // Limit again after replacement
          if (this.value.length > 10) {
            this.value = this.value.substring(0, 10);
          }
        }
      }
    });

    // (Moved submit validation above into unified handler)
  </script>

</body>
</html>
