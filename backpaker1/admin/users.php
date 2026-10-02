<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

require_admin();

$success = flash('success');
$error = flash('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    $userType = strtolower(trim($_POST['usertype'] ?? 'user'));
    $city = trim($_POST['city'] ?? '');
    $gender = trim($_POST['gender'] ?? '');

    if ($name === '' || $email === '' || $phone === '' || $password === '' || $confirmPassword === '' || $city === '' || $gender === '') {
        flash('error', 'Please fill in all required fields.');
        redirect('users.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Please enter a valid email address.');
        redirect('users.php');
    }

    if (strlen($phone) > 15 || !preg_match('/^\+?[0-9().\-\s]{7,15}$/', $phone)) {
        flash('error', 'Please enter a valid phone number (up to 15 characters).');
        redirect('users.php');
    }

    if ($password !== $confirmPassword) {
        flash('error', 'Password and confirm password must match.');
        redirect('users.php');
    }

    if (
        strlen($password) < 8
        || !preg_match('/[A-Z]/', $password)
        || !preg_match('/[a-z]/', $password)
        || !preg_match('/\d/', $password)
        || !preg_match('/[^A-Za-z0-9]/', $password)
    ) {
        flash('error', 'Password must be 8+ characters and include uppercase, lowercase, number, and symbol.');
        redirect('users.php');
    }

    if (!in_array($userType, ['user', 'admin'], true)) {
        flash('error', 'Please choose a valid account type.');
        redirect('users.php');
    }

    if (!in_array($gender, ['Male', 'Female', 'Other', 'Prefer not to say'], true)) {
        flash('error', 'Please choose a valid gender option.');
        redirect('users.php');
    }

    $duplicate = $pdo->prepare('SELECT id FROM users WHERE email = ? OR phone = ? OR mobile = ? LIMIT 1');
    $duplicate->execute([$email, $phone, $phone]);
    if ($duplicate->fetch()) {
        flash('error', 'An account with that email or phone number already exists.');
        redirect('users.php');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $createUser = $pdo->prepare(
        'INSERT INTO users (name, email, password, password_hash, phone, mobile, usertype, role, city, gender)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $createUser->execute([
        $name,
        $email,
        $passwordHash,
        $passwordHash,
        $phone,
        $phone,
        $userType,
        $userType,
        $city,
        $gender,
    ]);

    $newUserId = (int) $pdo->lastInsertId();
    $setUserId = $pdo->prepare('UPDATE users SET user_id = ? WHERE id = ?');
    $setUserId->execute([$newUserId, $newUserId]);

    flash('success', 'Account created successfully.');
    redirect('users.php');
}

$users = $pdo->query(
    "SELECT id, user_id, name, email, phone, mobile, usertype, role, city, resisterdate, gender
     FROM users
     ORDER BY id DESC"
)->fetchAll();

$currentAdminName = $_SESSION['user_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manage Users - Backpacker Admin</title>
    <link rel="stylesheet" href="style.css" />
    <style>
      .users-layout {
        display: grid;
        grid-template-columns: minmax(280px, 0.75fr) minmax(0, 1.6fr);
        gap: 24px;
      }
      .user-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0 16px;
      }
      .user-field {
        margin-bottom: 16px;
      }
      .user-field.full {
        grid-column: 1 / -1;
      }
      .user-field label {
        display: block;
        margin-bottom: 7px;
        color: var(--text);
        font-size: 13px;
        font-weight: 700;
      }
      .user-field input,
      .user-field select {
        width: 100%;
        min-height: 44px;
        padding: 10px 12px;
        border: 1px solid var(--line);
        border-radius: 10px;
        background: #fff;
        color: var(--text);
      }
      .user-field input:focus,
      .user-field select:focus {
        outline: 3px solid rgba(11, 110, 248, 0.13);
        border-color: var(--primary);
      }
      .form-note {
        margin: -4px 0 18px;
        color: var(--muted);
        font-size: 12px;
        line-height: 1.5;
      }
      .users-table {
        min-width: 900px;
      }
      .users-table th,
      .users-table td {
        padding: 12px 10px;
      }
      .account-type {
        display: inline-block;
        padding: 5px 9px;
        border-radius: 999px;
        background: #eef5ff;
        color: #1d4ed8;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
      }
      .account-type.admin {
        background: #f3edff;
        color: #6941c6;
      }
      .section-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 18px;
      }
      .section-heading h2 {
        margin: 0;
      }
      .section-count {
        color: var(--muted);
        font-size: 13px;
      }
      @media (max-width: 980px) {
        .users-layout { grid-template-columns: 1fr; }
      }
      @media (max-width: 600px) {
        .user-form-grid { grid-template-columns: 1fr; }
        .user-field.full { grid-column: auto; }
      }
    </style>
  </head>
  <body>
    <div class="admin-shell">
      <div class="app-shell">
        <aside class="sidebar">
          <div class="sidebar-brand"><span class="brand-mark">B</span>Backpacker</div>
          <nav class="nav-menu" aria-label="Admin navigation">
            <a class="nav-link" href="backpacker_admin_dashboard.php">Dashboard</a>
            <a class="nav-link" href="orders.php">All Orders</a>
            <a class="nav-link" href="revenue.php">Revenue</a>
            <a class="nav-link active" href="users.php" aria-current="page">Users</a>
            <a class="nav-link" href="index.php">Packages</a>
          </nav>
          <div class="sidebar-footer">
            <div class="admin-mini">
              <div class="brand-mark"><?= clean(strtoupper(substr((string) $currentAdminName, 0, 1))) ?></div>
              <div><strong><?= clean($currentAdminName) ?></strong><span>Admin</span></div>
            </div>
          </div>
        </aside>

        <main class="workspace">
          <header class="workspace-header">
            <div>
              <div class="page-badge">Account management</div>
              <h1>Users</h1>
            </div>
            <div class="topbar-actions">
              <a href="backpacker_admin_dashboard.php" class="btn-muted">Dashboard</a>
              <a href="index.php" class="btn-muted">Packages</a>
              <a href="../index.php" class="btn-muted">View Site</a>
              <a href="../logout.php" class="btn-danger">Logout</a>
            </div>
          </header>

          <?php if ($success): ?>
            <div class="alert-success"><?= clean($success) ?></div>
          <?php endif; ?>
          <?php if ($error): ?>
            <div class="alert-error"><?= clean($error) ?></div>
          <?php endif; ?>

          <div class="users-layout">
            <section class="card">
              <h2>Add Account</h2>
              <p class="form-note">User ID and registration date are generated automatically. Passwords are stored securely and never shown in the user list.</p>
              <form method="post" action="users.php">
                <div class="user-form-grid">
                  <div class="user-field full">
                    <label for="name">Full name</label>
                    <input id="name" name="name" type="text" maxlength="255" autocomplete="name" required />
                  </div>
                  <div class="user-field full">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" maxlength="255" autocomplete="email" required />
                  </div>
                  <div class="user-field full">
                    <label for="phone">Phone number</label>
                    <input id="phone" name="phone" type="tel" maxlength="15" autocomplete="tel" required />
                  </div>
                  <div class="user-field">
                    <label for="usertype">User type</label>
                    <select id="usertype" name="usertype" required>
                      <option value="user">User</option>
                      <option value="admin">Admin</option>
                    </select>
                  </div>
                  <div class="user-field">
                    <label for="city">City</label>
                    <input id="city" name="city" type="text" maxlength="100" autocomplete="address-level2" required />
                  </div>
                  <div class="user-field full">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender" required>
                      <option value="">Choose gender</option>
                      <option value="Male">Male</option>
                      <option value="Female">Female</option>
                      <option value="Other">Other</option>
                      <option value="Prefer not to say">Prefer not to say</option>
                    </select>
                  </div>
                  <div class="user-field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required />
                  </div>
                  <div class="user-field">
                    <label for="confirm_password">Confirm password</label>
                    <input id="confirm_password" name="confirm_password" type="password" minlength="8" autocomplete="new-password" required />
                  </div>
                </div>
                <p class="form-note">Use at least 8 characters with uppercase, lowercase, number, and symbol.</p>
                <button type="submit" class="btn">Add Account</button>
              </form>
            </section>

            <section class="card">
              <div class="section-heading">
                <h2>All Accounts</h2>
                <span class="section-count"><?= number_format(count($users)) ?> total</span>
              </div>
              <div class="table-wrap">
                <table class="users-table">
                  <thead>
                    <tr>
                      <th>User ID</th>
                      <th>Name</th>
                      <th>Email</th>
                      <th>Phone</th>
                      <th>Type</th>
                      <th>City</th>
                      <th>Registered</th>
                      <th>Gender</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if ($users): ?>
                      <?php foreach ($users as $user): ?>
                        <?php $accountType = strtolower((string) ($user['usertype'] ?: $user['role'] ?: 'user')); ?>
                        <tr>
                          <td><?= (int) ($user['user_id'] ?: $user['id']) ?></td>
                          <td><?= clean($user['name']) ?></td>
                          <td><?= clean($user['email']) ?></td>
                          <td><?= clean($user['phone'] ?: $user['mobile'] ?: '-') ?></td>
                          <td><span class="account-type <?= $accountType === 'admin' ? 'admin' : '' ?>"><?= clean($accountType) ?></span></td>
                          <td><?= clean($user['city'] ?? '-') ?></td>
                          <td><?= clean($user['resisterdate'] ?? '-') ?></td>
                          <td><?= clean($user['gender'] ?? '-') ?></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr><td colspan="8" class="meta">No accounts have been created yet.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </section>
          </div>
        </main>
      </div>
    </div>
  </body>
</html>
