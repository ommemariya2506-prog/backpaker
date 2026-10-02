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
    $identifier = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($identifier === '' || $password === '') {
        flash('error', 'Please enter your email and password.');
        redirect('login.php');
    }

    $stmt = $pdo->prepare(
        'SELECT id, name, password_hash, usertype, role FROM users WHERE (usertype = ? OR role = ?) AND (email = ? OR phone = ? OR mobile = ?) LIMIT 1'
    );
    $stmt->execute(['admin', 'admin', $identifier, $identifier, $identifier]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = 'admin';

        flash('success', 'Welcome back, ' . clean($user['name']) . '!');
        redirect('backpacker_admin_dashboard.php');
    }

    flash('error', 'Invalid admin credentials.');
    redirect('login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login</title>
    <link rel="stylesheet" href="../Style.css" />
    <style>
      body {
        margin: 0;
        min-height: 100vh;
        background:
          radial-gradient(circle at 15% 20%, rgba(35, 182, 255, 0.3), transparent 30%),
          linear-gradient(135deg, #061b3d, #0c2a5d 50%, #1a5bb8 100%);
        color: #0f172a;
        font-family: 'Poppins', sans-serif;
      }
      .admin-login-shell {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 30px 20px;
      }
      .admin-login-layout {
        width: 100%;
        max-width: 980px;
        display: grid;
        grid-template-columns: 1fr 440px;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,.2);
        border-radius: 28px;
        box-shadow: 0 30px 70px rgba(2, 12, 30, 0.35);
      }
      .admin-welcome {
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 52px;
        color: #fff;
        background: linear-gradient(145deg, rgba(10, 31, 65, .95), rgba(18, 76, 135, .9));
      }
      .admin-welcome-mark {
        width: 48px;
        height: 48px;
        display: grid;
        place-items: center;
        margin-bottom: 28px;
        border-radius: 14px;
        background: linear-gradient(135deg, #0b6ef8, #23b6ff);
        font-size: 23px;
        font-weight: 800;
      }
      .admin-welcome h1 {
        margin: 0 0 14px;
        font-size: clamp(30px, 4vw, 44px);
        line-height: 1.12;
      }
      .admin-welcome p {
        max-width: 360px;
        margin: 0;
        color: #c7d8f3;
        line-height: 1.7;
      }
      .admin-card {
        width: 100%;
        background: rgba(255, 255, 255, 0.97);
        padding: 42px 34px;
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
      .field input {
        width: 100%;
        padding: 14px 14px;
        border: 1px solid #dfe6f3;
        border-radius: 12px;
        box-sizing: border-box;
        font-size: 15px;
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
      @media (max-width: 760px) {
        .admin-login-layout { max-width: 480px; grid-template-columns: 1fr; }
        .admin-welcome { padding: 30px 28px; }
        .admin-welcome-mark { margin-bottom: 18px; }
        .admin-card { padding: 32px 26px; }
      }
    </style>
  </head>
  <body>
    <div class="admin-login-shell">
      <div class="admin-login-layout">
        <section class="admin-welcome">
          <div class="admin-welcome-mark">B</div>
          <h1>Backpacker<br>Admin Portal</h1>
          <p>Manage customers, travel orders, packages, and revenue from one secure workspace.</p>
        </section>
        <section class="admin-card">
          <h2>Admin Login</h2>
          <p>Sign in with your administrator account.</p>

          <?php if ($error): ?>
            <div class="alert-error"><?= clean($error) ?></div>
          <?php endif; ?>
          <?php if ($success): ?>
            <div class="alert-success"><?= clean($success) ?></div>
          <?php endif; ?>

          <form action="login.php" method="post">
            <div class="field">
              <label for="email">Admin Email or Mobile</label>
              <input type="text" id="email" name="email" autocomplete="username" placeholder="admin@backpacker.com" required />
            </div>
            <div class="field">
              <label for="password">Password</label>
              <input type="password" id="password" name="password" autocomplete="current-password" placeholder="Enter admin password" required />
            </div>
            <button type="submit" class="btn-submit">Sign In</button>
          </form>

          <p class="admin-link"><a href="../index.php">Back to site</a></p>
        </section>
      </div>
    </div>
  </body>
</html>
