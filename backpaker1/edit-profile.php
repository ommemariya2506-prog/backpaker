<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

require_login();

$userId = (int) $_SESSION['user_id'];
$userStmt = $pdo->prepare(
    'SELECT id, name, email, phone, mobile, city, gender FROM users WHERE id = ? LIMIT 1'
);
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

if (!$user) {
    unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_role']);
    session_regenerate_id(true);
    flash('error', 'Your account could not be found. Please sign in again.');
    redirect('login.php');
}
if (is_admin()) {
    redirect('admin/backpacker_admin_dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $gender = trim($_POST['gender'] ?? '');

    if ($name === '' || $email === '' || $phone === '' || $city === '') {
        flash('error', 'Name, email, phone, and city are required.');
        redirect('edit-profile.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Please enter a valid email address.');
        redirect('edit-profile.php');
    }

    if (strlen($phone) > 15 || !preg_match('/^\+?[0-9().\-\s]{7,15}$/', $phone)) {
        flash('error', 'Please enter a valid phone number.');
        redirect('edit-profile.php');
    }

    if (!in_array($gender, ['Male', 'Female', 'Other', 'Prefer not to say'], true)) {
        flash('error', 'Please choose a valid gender option.');
        redirect('edit-profile.php');
    }

    $duplicate = $pdo->prepare(
        'SELECT id FROM users WHERE id <> ? AND (email = ? OR phone = ? OR mobile = ?) LIMIT 1'
    );
    $duplicate->execute([$userId, $email, $phone, $phone]);
    if ($duplicate->fetch()) {
        flash('error', 'That email or phone number is already used by another account.');
        redirect('edit-profile.php');
    }

    $update = $pdo->prepare(
        'UPDATE users SET name = ?, email = ?, phone = ?, mobile = ?, city = ?, gender = ? WHERE id = ?'
    );
    $update->execute([$name, $email, $phone, $phone, $city, $gender, $userId]);

    $_SESSION['user_name'] = $name;
    flash('success', 'Your profile has been updated.');
    redirect('profile.php');
}

$error = flash('error');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Profile - Backpacker Travel</title>
    <link rel="stylesheet" href="Style.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <style>
      body { background: #f4f8ff; color: #162033; }
      .edit-profile-page { padding: 125px 20px 80px; }
      .edit-profile-card {
        max-width: 760px;
        margin: 0 auto;
        padding: 32px;
        border: 1px solid #e7edf9;
        border-radius: 24px;
        background: #fff;
        box-shadow: 0 15px 35px rgba(19, 36, 64, .06);
      }
      .edit-profile-card h1 { margin: 0 0 8px; font-size: 30px; }
      .edit-profile-card > p { margin: 0 0 24px; color: #677792; }
      .edit-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0 18px;
      }
      .edit-field { margin-bottom: 18px; }
      .edit-field.full { grid-column: 1 / -1; }
      .edit-field label {
        display: block;
        margin-bottom: 7px;
        color: #1d2942;
        font-weight: 600;
      }
      .edit-field input,
      .edit-field select {
        width: 100%;
        box-sizing: border-box;
        padding: 13px 14px;
        border: 1px solid #dfe6f3;
        border-radius: 12px;
        background: #fff;
        color: #1d1d1d;
        font: inherit;
      }
      .edit-actions { display: flex; flex-wrap: wrap; gap: 10px; }
      .edit-actions button,
      .edit-actions a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 44px;
        padding: 10px 20px;
        border: 0;
        border-radius: 999px;
        background: linear-gradient(90deg, #0072ff, #00d4ff);
        color: #fff;
        font: inherit;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
      }
      .edit-actions a { background: #eef4ff; color: #1d4ed8; }
      .alert-error {
        padding: 12px 16px;
        margin-bottom: 18px;
        border: 1px solid #ffc4c4;
        border-radius: 10px;
        background: #fff1f1;
        color: #b42318;
      }
      @media (max-width: 600px) {
        .edit-profile-card { padding: 24px 18px; }
        .edit-form-grid { grid-template-columns: 1fr; }
        .edit-field.full { grid-column: auto; }
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
          <a href="profile.php" class="login-btn">My Profile</a>
        </nav>
      </div>
    </header>

    <main class="edit-profile-page">
      <section class="edit-profile-card">
        <h1>Edit Profile</h1>
        <p>Update your account and contact details.</p>
        <?php if ($error): ?>
          <div class="alert-error"><?= clean($error) ?></div>
        <?php endif; ?>

        <form method="post" action="edit-profile.php">
          <div class="edit-form-grid">
            <div class="edit-field full">
              <label for="name">Full name</label>
              <input id="name" name="name" type="text" maxlength="255" autocomplete="name" value="<?= clean($user['name']) ?>" required />
            </div>
            <div class="edit-field full">
              <label for="email">Email</label>
              <input id="email" name="email" type="email" maxlength="255" autocomplete="email" value="<?= clean($user['email']) ?>" required />
            </div>
            <div class="edit-field">
              <label for="phone">Phone</label>
              <input id="phone" name="phone" type="tel" maxlength="15" autocomplete="tel" value="<?= clean($user['phone'] ?: $user['mobile'] ?: '') ?>" required />
            </div>
            <div class="edit-field">
              <label for="city">City</label>
              <input id="city" name="city" type="text" maxlength="100" autocomplete="address-level2" value="<?= clean($user['city'] ?? '') ?>" required />
            </div>
            <div class="edit-field full">
              <label for="gender">Gender</label>
              <select id="gender" name="gender" required>
                <?php foreach (['Male', 'Female', 'Other', 'Prefer not to say'] as $genderOption): ?>
                  <option value="<?= clean($genderOption) ?>" <?= ($user['gender'] ?? 'Prefer not to say') === $genderOption ? 'selected' : '' ?>><?= clean($genderOption) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="edit-actions">
            <button type="submit">Save Changes</button>
            <a href="profile.php">Cancel</a>
          </div>
        </form>
      </section>
    </main>
  </body>
</html>
