<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

if (is_logged_in()) {
    if (is_admin()) {
        redirect('backpacker_admin_dashboard.php');
    }
    redirect('../profile.php');
}

$error = flash('error');
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name            = trim($_POST['name'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $phone           = trim($_POST['phone'] ?? '');
    $city            = trim($_POST['city'] ?? '');
    $password        = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    $gender          = trim($_POST['gender'] ?? 'Prefer not to say');

    if ($name === '' || $email === '' || $phone === '' || $city === '' || $password === '' || $confirmPassword === '') {
        flash('error', 'Please fill in all required fields.');
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

    $chk = $pdo->prepare('SELECT id FROM users WHERE email = ? OR phone = ?');
    $chk->execute([$email, $phone]);
    if ($chk->fetch()) {
        flash('error', 'An admin account with that email or phone already exists.');
        redirect('register.php');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        'INSERT INTO users (name, email, phone, password, password_hash, usertype, role, city, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$name, $email, $phone, $hash, $hash, 'admin', 'admin', $city, $gender]);

    $newUserId = (int) $pdo->lastInsertId();
    $upd = $pdo->prepare('UPDATE users SET user_id = ? WHERE id = ?');
    $upd->execute([$newUserId, $newUserId]);

    flash('success', 'Admin account created successfully. Please sign in.');
    redirect('login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Register</title>
    <link rel="stylesheet" href="../Style.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <style>
      body {
        margin: 0;
        min-height: 100vh;
        background: linear-gradient(135deg, #071a30, #0f2e4d 40%, #1a5bb8 100%);
        color: #0f172a;
        font-family: 'Poppins', sans-serif;
      }
      .admin-register-shell {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 30px 20px;
      }
      .admin-card {
        width: 100%;
        max-width: 540px;
        background: rgba(255,255,255,0.96);
        border-radius: 28px;
        padding: 34px 28px;
        box-shadow: 0 28px 60px rgba(4, 18, 36, 0.3);
      }
      .admin-card h2 {
        margin: 0 0 10px;
        text-align: center;
        font-size: 32px;
      }
      .admin-card p {
        color: #53627f;
        text-align: center;
        margin-bottom: 24px;
      }
      .field { margin-bottom: 18px; }
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
        box-sizing: border-box;
        font-size: 15px;
        background: #fff;
      }
      .field input:focus,
      .field select:focus {
        outline: none;
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
      .btn-submit {
        width: 100%;
        border: 0;
        border-radius: 999px;
        padding: 15px 20px;
        background: linear-gradient(90deg, #0b6ef8, #23b6ff);
        color: #fff;
        font-weight: 700;
        cursor: pointer;
      }
      .alert-error,
      .alert-success {
        padding: 12px 14px;
        border-radius: 10px;
        margin-bottom: 18px;
        font-size: 14px;
      }
      .alert-error {
        background: #fff0f0;
        border: 1px solid #ffc4c4;
        color: #b42318;
      }
      .alert-success {
        background: #f0fff4;
        border: 1px solid #b3ffd0;
        color: #1e7e34;
      }
      .admin-link {
        margin-top: 18px;
        text-align: center;
        color: #49566d;
      }
      .admin-link a {
        color: #0057d9;
        font-weight: 700;
      }
    </style>
  </head>
  <body>
    <div class="admin-register-shell">
      <div class="admin-card">
        <h2>Admin Register</h2>
        <p>Create an administrator account for Backpacker Travel.</p>

        <?php if ($error): ?>
          <div class="alert-error"><?= clean($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
          <div class="alert-success"><?= clean($success) ?></div>
        <?php endif; ?>

        <form action="register.php" method="post">
          <div class="field">
            <label for="name">Full name</label>
            <input type="text" id="name" name="name" placeholder="Enter admin full name" required />
          </div>

          <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Enter admin email" required />
          </div>

          <div class="field">
            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone" placeholder="Enter admin phone" required />
          </div>

          <div class="field">
            <label for="city">City</label>
            <input type="text" id="city" name="city" placeholder="Enter city" required />
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

          <div class="field">
            <label for="password">Password</label>
            <div class="password-wrap">
              <input type="password" id="password" name="password" placeholder="Create a secure password" required />
              <button type="button" class="toggle-password" data-target="password">Show</button>
            </div>
            <small style="display:block; margin-top:6px; color:#6c7a93;">Use 8+ characters with uppercase, lowercase, number, and symbol.</small>
          </div>

          <div class="field">
            <label for="confirm_password">Confirm Password</label>
            <div class="password-wrap">
              <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required />
              <button type="button" class="toggle-password" data-target="confirm_password">Show</button>
            </div>
          </div>

          <button type="submit" class="btn-submit">Create Admin</button>
        </form>

        <p class="admin-link"><a href="login.php">Back to Admin Login</a> | <a href="../index.php">Back to site</a></p>
      </div>
    </div>
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
