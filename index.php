<?php
// File: index.php
?>
<!DOCTYPE html>
<html>
<head>
  <title>Quiz Registration - Toss Consultancy Services</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="assets/app.css">
</head>
<body class="app-shell app-shell-registration">

  <header class="app-header">
    <div class="app-header-inner">
      <div class="brand-lockup">
        <span class="brand-pill">Toss Consultancy Services</span>
        <div class="brand-text">
          <span class="brand-title">Assessment Platform</span>
          <span class="brand-subtitle">Standardized multi-role technical evaluation</span>
        </div>
      </div>
      <div class="header-meta">
        <span class="header-meta-pill">Secure Proctored Session</span>
        <span>Approx. duration: 45 min</span>
      </div>
    </div>
  </header>

  <main class="app-main">
    <div class="app-main-inner">
      <section class="card">
        <div class="card-header">
          <div class="badge badge-neutral">Candidate onboarding</div>
          <h1 class="card-title">Enter your details to start the assessment</h1>
          <p class="card-subtitle">
            Please provide accurate information. Your details will be used for certification and reporting.
          </p>
  </div>

        <hr class="card-divider">

        <form action="quiz.php" method="POST" novalidate>
          <div class="form-grid">
      <div class="form-group">
              <label class="form-label">Full name</label>
              <input
                type="text"
                name="name"
                id="name"
                required
                pattern="^[A-Za-z ]+$"
                placeholder="Enter your full name"
                class="form-control"
                value="<?php echo htmlspecialchars($_GET['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              >
              <p class="form-hint">Use the same name as on official records.</p>
      </div>

      <div class="form-group">
              <label class="form-label">Role</label>
              <select name="role" id="role" required class="form-select">
          <option value="">Select your role</option>
          <option value="Backend Developer" <?php echo (isset($_GET['role']) && $_GET['role'] === 'Backend Developer') ? 'selected' : ''; ?>>Backend Developer</option>
          <option value="Python Developer" <?php echo (isset($_GET['role']) && $_GET['role'] === 'Python Developer') ? 'selected' : ''; ?>>Python Developer</option>
          <option value="Flutter Developer" <?php echo (isset($_GET['role']) && $_GET['role'] === 'Flutter Developer') ? 'selected' : ''; ?>>Flutter Developer</option>
          <option value="Mern Developer" <?php echo (isset($_GET['role']) && $_GET['role'] === 'Mern Developer') ? 'selected' : ''; ?>>Mern Developer</option>
          <option value="Full Stack Developer" <?php echo (isset($_GET['role']) && $_GET['role'] === 'Full Stack Developer') ? 'selected' : ''; ?>>Full Stack Developer</option>
          <option value="Data Analytics" <?php echo (isset($_GET['role']) && $_GET['role'] === 'Data Analytics') ? 'selected' : ''; ?>>Data Analytics</option>
        </select>
      </div>

      <div class="form-group">
              <label class="form-label">Level</label>
              <select name="level" id="level" required class="form-select">
          <option value="">Select your level</option>
          <option value="Beginner" <?php echo (isset($_GET['level']) && $_GET['level'] === 'Beginner') ? 'selected' : ''; ?>>Beginner</option>
          <option value="Intermediate" <?php echo (isset($_GET['level']) && $_GET['level'] === 'Intermediate') ? 'selected' : ''; ?>>Intermediate</option>
          <option value="Advanced" <?php echo (isset($_GET['level']) && $_GET['level'] === 'Advanced') ? 'selected' : ''; ?>>Advanced</option>
        </select>
      </div>

      <div class="form-group">
              <label class="form-label">Location</label>
              <input
                type="text"
                name="place"
                required
                placeholder="City / region"
                class="form-control"
              >
      </div>

      <div class="form-group">
              <label class="form-label">Phone number</label>
              <input
                type="tel"
                name="mobile"
                id="mobile"
                pattern="^[6789]\d{9}$"
                inputmode="numeric"
                maxlength="10"
                required
                placeholder="10-digit number starting with 6, 7, 8, or 9"
                class="form-control"
                value="<?php echo htmlspecialchars($_GET['mobile'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              >
              <p class="form-error" id="mobileError" style="display:none;">
                Phone number must be exactly 10 digits starting with 6, 7, 8, or 9.
              </p>
      </div>

      <div class="form-group">
              <label class="form-label">Email</label>
              <input
                type="email"
                name="email"
                id="email"
                required
                placeholder="name@company.com"
                class="form-control"
                value="<?php echo htmlspecialchars($_GET['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              >
              <p class="form-error" id="duplicateMsg" style="display:none;">
                User already attempted this assessment. Use a different phone number and email.
              </p>
            </div>
      </div>

          <div class="form-footer">
            <button type="submit" id="startBtn" class="btn btn-primary">
              Start assessment
            </button>
          </div>
    </form>
      </section>
  </div>
  </main>

  <script>
    // Basic deterrent: disable context menu and common shortcuts
    document.addEventListener('contextmenu', event => event.preventDefault());
    document.onkeydown = function(e) {
      if (e.keyCode === 123) return false;
      if (e.ctrlKey && e.shiftKey && (e.keyCode === 73 || e.keyCode === 74)) return false;
      if (e.ctrlKey && (e.keyCode === 85 || e.keyCode === 83)) return false;
    };

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

    // Inline error visibility for mobile pattern (purely visual)
    const mobileInput = document.getElementById('mobile');
    const mobileError = document.getElementById('mobileError');
    mobileInput.addEventListener('blur', function () {
      if (this.value && !this.checkValidity()) {
        mobileError.style.display = 'block';
      } else {
        mobileError.style.display = 'none';
      }
    });

    // (Moved submit validation above into unified handler) 
  </script>

</body>
</html>
