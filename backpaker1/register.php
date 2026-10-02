<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

if (is_logged_in()) {
    redirect('profile.php');
}

$error   = flash('error');
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name            = trim($_POST['name']             ?? '');
    $email           = trim($_POST['email']            ?? '');
    $phone           = trim($_POST['phone']            ?? '');
    $password        = trim($_POST['password']         ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    $city            = trim($_POST['city']             ?? '');
    $gender          = trim($_POST['gender']           ?? 'Prefer not to say');

    // Validate
    if ($name === '' || $email === '' || $phone === '' || $password === '' || $confirmPassword === '' || $city === '' || $gender === '') {
        flash('error', 'All required fields are required.');
        redirect('register.php');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Please enter a valid email address.');
        redirect('register.php');
    }
    if ($password !== $confirmPassword) {
        flash('error', 'Password and confirm password must match.');
        redirect('register.php');
    }
    if (strlen($password) < 8) {
        flash('error', 'Password must be at least 8 characters long.');
        redirect('register.php');
    }
    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
        flash('error', 'Password must include at least 1 uppercase letter, 1 lowercase letter, 1 number, and 1 special character.');
        redirect('register.php');
    }
    if (!in_array($gender, ['Male', 'Female', 'Other', 'Prefer not to say'], true)) {
        $gender = 'Prefer not to say';
    }

    // Check duplicate
    $chk = $pdo->prepare('SELECT id FROM users WHERE email = ? OR phone = ?');
    $chk->execute([$email, $phone]);
    if ($chk->fetch()) {
        flash('error', 'An account with that email or phone already exists.');
        redirect('register.php');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins  = $pdo->prepare(
        'INSERT INTO users (name, email, phone, password, password_hash, usertype, role, city, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $ins->execute([$name, $email, $phone, $hash, $hash, 'user', 'user', $city, $gender]);

    $newUserId = (int) $pdo->lastInsertId();
    $userIdUpdate = $pdo->prepare('UPDATE users SET user_id = ? WHERE id = ?');
    $userIdUpdate->execute([$newUserId, $newUserId]);

    flash('success', 'Account created! Please sign in.');
    redirect('login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register - Backpacker Travel</title>
    <link rel="stylesheet" href="Style.css" />
    <style>
      body {
        min-height: 100vh;
        background: linear-gradient(135deg, rgba(7, 19, 34, 0.88), rgba(28, 52, 94, 0.78)), url('images/night-sky.jpg') center/cover no-repeat;
        position: relative;
      }
      body::before {
        content: "";
        position: fixed;
        inset: 0;
        background: radial-gradient(circle at top right, rgba(0, 212, 255, 0.18), transparent 25%);
        z-index: 0;
      }
      header, main { position: relative; z-index: 1; }
      .register-wrap {
        padding: 110px 20px 70px;
      }
      .auth-shell {
        max-width: 1120px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 360px 1fr;
        border-radius: 30px;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.12);
        box-shadow: 0 35px 80px rgba(5, 18, 33, 0.4);
        backdrop-filter: blur(8px);
      }
      .auth-panel {
        background: linear-gradient(180deg, #0c1d32 0%, #173a5a 100%);
        padding: 42px 30px;
        color: #edf6ff;
      }
      .brand-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        border-radius: 999px;
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.12);
        font-size: 12px;
        letter-spacing: 1px;
        text-transform: uppercase;
        font-weight: 700;
      }
      .auth-panel h1 {
        margin: 26px 0 12px;
        font-size: 38px;
        line-height: 1.15;
      }
      .auth-panel p {
        color: rgba(237, 246, 255, 0.75);
        font-size: 15px;
        line-height: 1.7;
        margin-bottom: 26px;
      }
      .feature-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 14px;
      }
      .feature-list li {
        display: flex;
        align-items: center;
        gap: 12px;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 14px;
        padding: 12px 14px;
        color: rgba(255,255,255,0.9);
      }
      .feature-list i {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(0, 212, 255, 0.15);
        color: #7fe7ff;
        border-radius: 10px;
      }
      .auth-card {
        background: rgba(255, 255, 255, 0.96);
        padding: 38px 34px 32px;
      }
      .auth-card h2 {
        margin: 0 0 10px;
        font-size: 34px;
        color: #111827;
      }
      .auth-card > p {
        color: #5a6880;
        margin-bottom: 24px;
      }
      .form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
      }
      .field { margin-bottom: 18px; }
      .field.full {
        grid-column: 1 / -1;
      }
      .field label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #1d2942;
      }
      .field input,
      .field select {
        width: 100%;
        padding: 13px 14px;
        border: 1px solid #dfe6f3;
        border-radius: 12px;
        font-size: 15px;
        outline: none;
        box-sizing: border-box;
        background: #fff;
        color: #1d1d1d;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
      }
      .field input:focus,
      .field select:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
      }
      .password-wrap {
        position: relative;
      }
      .password-wrap input {
        padding-right: 48px;
      }
      .toggle-password {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        border: 0;
        background: transparent;
        color: #53627f;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
      }
      .field small { display: block; margin-top: 6px; color: #6c7a93; }
      .btn-submit {
        width: 100%;
        border: none;
        border-radius: 50px;
        background: linear-gradient(90deg, #0072ff, #00d4ff);
        color: #fff;
        font-weight: 700;
        font-size: 16px;
        padding: 15px 20px;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        box-shadow: 0 12px 25px rgba(0, 114, 255, 0.25);
      }
      .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 18px 30px rgba(0, 114, 255, 0.3);
      }
      .auth-link {
        text-align: center;
        margin-top: 20px;
        color: #49566d;
      }
      .auth-link a {
        color: #007bff;
        font-weight: 600;
      }
      .alert-error {
        background: #fff0f0;
        border: 1px solid #ffb3b3;
        color: #c0392b;
        padding: 10px 15px;
        border-radius: 8px;
        margin-bottom: 16px;
        font-size: 14px;
      }
      @media (max-width: 900px) {
        .auth-shell { grid-template-columns: 1fr; }
        .auth-panel { padding: 28px 22px; }
        .auth-card { padding: 28px 22px; }
        .form-grid { grid-template-columns: 1fr; }
      }
    </style>
  </head>
  <body>
    <header>
      <div class="container">
        <nav>
          <div class="logo">Backpacker<span>.</span></div>
          <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="search.php">Destinations</a></li>
            <li><a href="packages.php">Packages</a></li>
          </ul>
          <a href="login.php" class="login-btn">Login</a>
        </nav>
      </div>
    </header>

    <main class="register-wrap">
      <div class="auth-shell">
        <aside class="auth-panel">
          <div class="brand-badge"><i class="fa-solid fa-mountain-sun"></i> Backpacker</div>
          <h1>Start your next adventure.</h1>
          <p>Create your account and unlock curated trips, smart trip planning, and unforgettable escapes.</p>

          <ul class="feature-list">
            <li><i class="fa-solid fa-plane-departure"></i> Book dream holidays</li>
            <li><i class="fa-solid fa-location-dot"></i> Explore top destinations</li>
            <li><i class="fa-solid fa-shield-heart"></i> Secure and trusted travel</li>
          </ul>
        </aside>

        <div class="auth-card">
          <h2>Create an account</h2>
          <p>Fill in your details and begin your journey.</p>

          <?php if ($error): ?>
            <div class="alert-error"><?= clean($error) ?></div>
          <?php endif; ?>

          <form action="register.php" method="post">
            <div class="form-grid">
              <div class="field full">
                <label for="name">Full name</label>
                <input type="text" id="name" name="name" placeholder="Enter your full name" required />
              </div>

              <div class="field full">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required />
              </div>

              <div class="field full">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" required />
              </div>

              <div class="field">
                <label for="city">City</label>
                <input type="text" id="city" name="city" placeholder="Enter your city" required />
              </div>

              <div class="field">
                <label for="gender">Gender</label>
                <select id="gender" name="gender" required>
                  <option value="">Select gender</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                  <option value="Other">Other</option>
                  <option value="Prefer not to say">Prefer not to say</option>
                </select>
              </div>

              <div class="field full">
                <label for="password">Password</label>
                <div class="password-wrap">
                  <input type="password" id="password" name="password" placeholder="Create a password" required />
                  <button type="button" class="toggle-password" data-target="password">Show</button>
                </div>
                <small>Use 8+ characters with uppercase, lowercase, number, and symbol.</small>
              </div>

              <div class="field full">
                <label for="confirm_password">Confirm Password</label>
                <div class="password-wrap">
                  <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required />
                  <button type="button" class="toggle-password" data-target="confirm_password">Show</button>
                </div>
              </div>
            </div>

            <button type="submit" class="btn-submit">Create account</button>
          </form>

          <p class="auth-link">Already registered? <a href="login.php">Sign in</a></p>
        </div>
      </div>
    </main>
    <script>
      document.querySelectorAll('.toggle-password').forEach(function(button) {
        button.addEventListener('click', function() {
          const target = document.getElementById(this.dataset.target);
          if (!target) return;
          const shouldShow = target.type === 'password';
          target.type = shouldShow ? 'text' : 'password';
          this.textContent = shouldShow ? 'Hide' : 'Show';
        });
      });
    </script>
  </body>
</html>
