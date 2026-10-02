<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

// Redirect if already logged in
if (is_logged_in()) {
    if (is_admin()) {
        redirect('admin/backpacker_admin_dashboard.php');
    }
    redirect('profile.php');
}

$error = flash('error');
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['email'] ?? '');
    $password   = trim($_POST['password'] ?? '');

    if ($identifier === '' || $password === '') {
        flash('error', 'Please fill in all fields.');
        redirect('login.php');
    }

    // Allow login by email or mobile
    $stmt = $pdo->prepare(
        'SELECT id, name, password_hash, password, usertype, role FROM users WHERE email = ? OR mobile = ? OR phone = ? LIMIT 1'
    );
    $stmt->execute([$identifier, $identifier, $identifier]);
    $user = $stmt->fetch();

    if ($user && (password_verify($password, $user['password_hash']) || password_verify($password, $user['password'] ?? '')) ) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['usertype'] ?? $user['role'] ?? 'user';

        flash('success', 'Welcome back, ' . clean($user['name']) . '!');

        if (($_SESSION['user_role'] ?? 'user') === 'admin') {
            redirect('admin/backpacker_admin_dashboard.php');
        }

        redirect('profile.php');
    } else {
        flash('error', 'Invalid email/mobile or password.');
        redirect('login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - Backpacker Travel</title>
    <link rel="stylesheet" href="Style.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <style>
      body {
        min-height: 100vh;
        background: url('images/night-sky.jpg') center/cover no-repeat;
        position: relative;
      }
      body::before {
        content: "";
        position: fixed;
        inset: 0;
        background: rgba(7, 19, 34, 0.55);
        z-index: 0;
      }
      header, main {
        position: relative;
        z-index: 1;
      }
      .page-shell {
        position: relative;
        z-index: 1;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
      }
      .auth-wrap {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 120px 20px 60px;
      }
      .auth-card {
        width: 100%;
        max-width: 520px;
        padding: 34px 28px;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid rgba(22, 78, 160, 0.08);
        border-radius: 26px;
        box-shadow: 0 20px 40px rgba(14, 40, 80, 0.12);
      }
      .auth-card h2 {
        margin-bottom: 8px;
        text-align: center;
        font-size: 34px;
        color: #111827;
      }
      .auth-card p {
        text-align: center;
        color: #5a6880;
        margin-bottom: 22px;
      }
      .field { margin-bottom: 16px; }
      .field label {
        display: block;
        margin-bottom: 7px;
        font-weight: 600;
        color: #1d2942;
      }
      .field input {
        width: 100%;
        padding: 13px 14px;
        border: 1px solid #dfe6f3;
        border-radius: 12px;
        background: #fff;
        outline: none;
        font-size: 15px;
        color: #1d1d1d;
        box-sizing: border-box;
      }
      .field input:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
      }
      .btn-submit {
        width: 100%;
        border: none;
        border-radius: 50px;
        background: linear-gradient(90deg, #0072ff, #00d4ff);
        color: #fff;
        font-weight: 600;
        font-size: 16px;
        padding: 15px 20px;
        cursor: pointer;
        transition: 0.3s;
      }
      .btn-submit:hover { transform: translateY(-2px); }
      .alert-error {
        background: #fff0f0;
        border: 1px solid #ffb3b3;
        color: #c0392b;
        padding: 10px 15px;
        border-radius: 8px;
        margin-bottom: 16px;
        font-size: 14px;
      }
      .alert-success {
        background: #f0fff4;
        border: 1px solid #b3ffd0;
        color: #1e7e34;
        padding: 10px 15px;
        border-radius: 8px;
        margin-bottom: 16px;
        font-size: 14px;
      }
      .auth-link { text-align: center; margin-top: 16px; color: #49566d; }
      .auth-link a { color: #007bff; font-weight: 600; }
    </style>
  </head>
  <body>
    <div class="page-shell">
      <header>
        <div class="container">
          <nav>
            <div class="logo">Backpacker<span>.</span></div>
            <ul class="nav-links">
              <li><a href="index.php">Home</a></li>
              <li><a href="search.php">Destinations</a></li>
              <li><a href="packages.php">Packages</a></li>
            </ul>
          </nav>
        </div>
      </header>

      <main class="auth-wrap">
        <div class="auth-card">
          <h2>Welcome Back</h2>
          <p>Sign in to continue your adventure.</p>

          <?php if ($error): ?>
            <div class="alert-error"><?= clean($error) ?></div>
          <?php endif; ?>
          <?php if ($success): ?>
            <div class="alert-success"><?= clean($success) ?></div>
          <?php endif; ?>

          <form action="login.php" method="post">
            <div class="field">
              <label for="email">Email or Mobile Number</label>
              <input type="text" id="email" name="email" placeholder="Enter your email or mobile" required />
            </div>
            <div class="field">
              <label for="password">Password</label>
              <input type="password" id="password" name="password" placeholder="Enter your password" required />
            </div>
            <button type="submit" class="btn-submit">Sign In</button>
          </form>
          <p class="auth-link">No account? <a href="register.php">Register here</a></p>
          <p class="auth-link"><a href="admin/login.php">Admin login</a></p>
        </div>
      </main>
    </div>
  </body>
</html>
