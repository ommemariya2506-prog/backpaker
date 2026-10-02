<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

require_login();

$user_id = $_SESSION['user_id'];

// Fetch full user row
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user) {
    unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_role']);
    session_regenerate_id(true);
    flash('error', 'Your account could not be found. Please sign in again.');
    redirect('login.php');
}
if (is_admin()) {
    redirect('admin/backpacker_admin_dashboard.php');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        flash('error', 'Your request could not be verified. Please try again.');
        redirect('profile.php');
    }

    $bookingId = filter_input(INPUT_POST, 'cancel_booking', FILTER_VALIDATE_INT);
    if (!$bookingId || $bookingId < 1) {
        flash('error', 'Please choose a valid booking to cancel.');
        redirect('profile.php');
    }

    $cancel = $pdo->prepare(
        "UPDATE bookings
         SET payment_status = 'Cancelled'
         WHERE id = ?
           AND user_id = ?
           AND travel_date >= CURDATE()
           AND (
               payment_status = 'Pending'
               OR (payment_status = 'Paid' AND payment_reference LIKE 'DEMO-PAY-%')
           )"
    );
    $cancel->execute([$bookingId, $user_id]);

    if ($cancel->rowCount() !== 1) {
        flash('error', 'This booking cannot be cancelled. Only upcoming pending or demo-paid bookings can be cancelled.');
    } else {
        flash('success', 'Your booking has been cancelled.');
    }
    redirect('profile.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_profile_photo'])) {
    if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
        flash('error', 'Please select a valid image to upload.');
        redirect('profile.php');
    }

    $file = $_FILES['profile_photo'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedTypes, true)) {
        flash('error', 'Only JPG, PNG, WebP, and GIF images are allowed.');
        redirect('profile.php');
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        flash('error', 'Please upload an image smaller than 2MB.');
        redirect('profile.php');
    }

    $uploadDir = __DIR__ . '/images/avatars';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
        flash('error', 'The photo upload directory could not be created.');
        redirect('profile.php');
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $newFileName = 'user-' . $user_id . '-' . time() . '.' . $extension;
    $targetPath = $uploadDir . '/' . $newFileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        flash('error', 'The profile photo could not be saved. Please try again.');
        redirect('profile.php');
    }

    $oldPhoto = $user['profile_photo'] ?? '';
    if (!empty($oldPhoto) && $oldPhoto !== 'images/avatar-placeholder.svg' && strpos($oldPhoto, 'images/avatars/') === 0 && file_exists(__DIR__ . '/' . $oldPhoto)) {
        @unlink(__DIR__ . '/' . $oldPhoto);
    }

    $newPhotoPath = 'images/avatars/' . $newFileName;
    $update = $pdo->prepare('UPDATE users SET profile_photo = ? WHERE id = ?');
    $update->execute([$newPhotoPath, $user_id]);
    $user['profile_photo'] = $newPhotoPath;

    flash('success', 'Your profile photo has been updated.');
    redirect('profile.php');
}

$statusFilter = strtolower(trim($_GET['status'] ?? 'all'));
$allowedFilters = ['all', 'pending', 'paid', 'failed', 'cancelled'];
if (!in_array($statusFilter, $allowedFilters, true)) {
    $statusFilter = 'all';
}

// Fetch bookings
$sql = 'SELECT b.*, d.title, d.duration
        FROM bookings b
        JOIN destinations d ON b.destination_id = d.id
        WHERE b.user_id = ?';
$params = [$user_id];
if ($statusFilter !== 'all') {
    $sql .= ' AND LOWER(COALESCE(b.payment_status, "")) = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY b.created_at DESC';
$bstmt = $pdo->prepare($sql);
$bstmt->execute($params);
$bookings = $bstmt->fetchAll();

$success = flash('success');
$error = flash('error');

$profilePhoto = $user['profile_photo'] ?? '';
if (empty($profilePhoto) || !file_exists(__DIR__ . '/' . $profilePhoto)) {
    $profilePhoto = 'images/avatar-placeholder.svg';
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Profile - Backpacker Travel</title>
    <link rel="stylesheet" href="Style.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <style>
      body { background: #f4f8ff; color: #162033; }
      .profile-page { padding: 120px 20px 80px; }
      .profile-layout {
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 30px;
      }
      .profile-card, .info-card {
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid #e7edf9;
        border-radius: 24px;
        box-shadow: 0 15px 35px rgba(19, 36, 64, 0.06);
      }
      .profile-card { padding: 30px 20px; text-align: center; }
      .avatar { width: 140px; height: 140px; border-radius: 50%; object-fit: cover; margin: 0 auto 16px; border: 4px solid #e4eeff; }
      .profile-card h3 { font-size: 26px; margin-bottom: 8px; }
      .profile-card p { color: #677792; margin-bottom: 20px; }
      .btn-primary { display: inline-block; padding: 12px 20px; border-radius: 50px; background: linear-gradient(90deg, #0072ff, #00d4ff); color: #fff; font-weight: 600; }
      .btn-logout { display: inline-block; padding: 10px 20px; border-radius: 50px; background: #fff; border: 1px solid #e0e0e0; color: #c0392b; font-weight: 600; margin-top: 10px; }
      .photo-upload { margin-top: 18px; }
      .photo-upload label {
        display: inline-block; width: 100%; cursor: pointer; background: #f5f9ff; border: 1px dashed #9ac4ff; border-radius: 12px; padding: 12px 14px; color: #1d4ed8; font-weight: 600;
      }
      .photo-upload input[type="file"] { display: none; }
      .photo-upload button {
        width: 100%; margin-top: 10px; border: none; border-radius: 50px; padding: 12px 18px; background: #0f172a; color: #fff; font-weight: 600; cursor: pointer;
      }
      .alert-error {
        background: #fff1f1; border: 1px solid #ffc4c4; color: #b42318;
        padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;
      }
      .info-stack { display: flex; flex-direction: column; gap: 24px; }
      .info-card { padding: 28px 24px; }
      .info-card h3 { font-size: 26px; margin-bottom: 18px; }
      .booking-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 18px;
      }
      .booking-filter {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 8px 14px;
        border-radius: 999px;
        border: 1px solid #dfe8fb;
        background: #f7faff;
        color: #42526e;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
      }
      .booking-filter.active {
        background: #0f172a;
        border-color: #0f172a;
        color: #fff;
      }
      .booking-list { list-style: none; padding: 0; margin: 0; }
      .booking-list li { display: flex; justify-content: space-between; gap: 20px; padding: 18px 0; border-bottom: 1px solid #edf1f7; color: #2a3550; }
      .booking-list li:last-child { border-bottom: none; }
      .meta { color: #53627f; margin-top: 12px; line-height: 1.8; }
      .badge-paid { color: #28a745; font-size: 13px; font-weight: 600; }
      .badge-pending { color: #ffc107; font-size: 13px; font-weight: 600; }
      .badge-failed { color: #dc3545; font-size: 13px; font-weight: 600; }
      .badge-cancelled { color: #64748b; font-size: 13px; font-weight: 600; }
      .btn-cancel-booking {
        margin-top: 10px; padding: 7px 12px; border: 1px solid #fecaca; border-radius: 8px;
        background: #fff; color: #b42318; font: inherit; font-size: 12px; font-weight: 600; cursor: pointer;
      }
      .btn-cancel-booking:hover { background: #fff1f1; }
      .alert-success {
        background: #f0fff4; border: 1px solid #b3ffd0; color: #1e7e34;
        padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;
      }
      @media (max-width: 900px) { .profile-layout { grid-template-columns: 1fr; } }
    </style>
  </head>
  <body>
    <header>
      <div class="container">
        <nav>
          <div class="logo" style="color: #0f172a;">Backpacker<span>.</span></div>
          <ul class="nav-links">
            <li><a href="index.php" style="color: #0f172a;">Home</a></li>
            <li><a href="search.php" style="color: #0f172a;">Destinations</a></li>
            <li><a href="packages.php" style="color: #0f172a;">Packages</a></li>
          </ul>
          <a href="logout.php" class="login-btn">Logout</a>
        </nav>
      </div>
    </header>

    <main class="profile-page">
      <?php if ($success): ?>
        <div class="container"><div class="alert-success"><?= clean($success) ?></div></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="container"><div class="alert-error"><?= clean($error) ?></div></div>
      <?php endif; ?>

      <div class="profile-layout">
        <aside class="profile-card">
          <img src="<?= clean($profilePhoto) ?>" alt="User avatar" class="avatar" />
          <h3><?= clean($user['name']) ?></h3>
          <p><?= clean($user['email']) ?></p>
          <?php if (!empty($user['mobile'])): ?>
            <p style="font-size:13px; color:#8899b0; margin-top:-12px; margin-bottom:20px; "><?= clean($user['mobile']) ?></p>
          <?php endif; ?>

          <form method="post" enctype="multipart/form-data" class="photo-upload">
            <label for="profile_photo"><i class="fa-solid fa-camera"></i> Choose Photo</label>
            <input type="file" id="profile_photo" name="profile_photo" accept="image/*" />
            <button type="submit" name="upload_profile_photo" value="1">Upload Photo</button>
          </form>

          <a href="edit-profile.php" class="btn-primary" style="display:block; width:100%; box-sizing:border-box; margin-top:20px;">Edit Profile</a>
          <a href="logout.php" class="btn-logout" style="display:block; width:100%; box-sizing:border-box;">Logout</a>
        </aside>

        <section class="info-stack">
          <!-- Booking History -->
          <div class="info-card bookings">
            <h3>Booking History</h3>
            <div class="booking-filters">
              <a class="booking-filter <?= $statusFilter === 'all' ? 'active' : '' ?>" href="profile.php?status=all">All</a>
              <a class="booking-filter <?= $statusFilter === 'pending' ? 'active' : '' ?>" href="profile.php?status=pending">Pending</a>
              <a class="booking-filter <?= $statusFilter === 'paid' ? 'active' : '' ?>" href="profile.php?status=paid">Paid</a>
              <a class="booking-filter <?= $statusFilter === 'failed' ? 'active' : '' ?>" href="profile.php?status=failed">Failed</a>
              <a class="booking-filter <?= $statusFilter === 'cancelled' ? 'active' : '' ?>" href="profile.php?status=cancelled">Cancelled</a>
            </div>
            <?php if (empty($bookings)): ?>
              <p style="color:#777;">No <?= $statusFilter === 'all' ? '' : strtolower($statusFilter) ?> bookings found. <a href="search.php">Browse destinations</a></p>
            <?php else: ?>
              <ul class="booking-list">
                <?php foreach ($bookings as $b): ?>
                  <?php $status = strtoupper((string)($b['payment_status'] ?? 'pending')); ?>
                  <li>
                    <div>
                      <span style="font-weight: 600; display: block;">
                        <?= clean($b['title']) ?>
                      </span>
                      <small style="color: #677792;">Booking ID: #<?= clean($b['booking_reference']) ?> &bull; <?= clean($b['duration']) ?></small>
                      <small style="color: #677792; display:block;">Travel: <?= clean($b['travel_date']) ?> &bull; Guests: <?= (int)$b['guests'] ?></small>
                      <small style="color: #677792; display:block;">Payment method: <?= clean($b['payment_method'] ?? 'Not selected') ?></small>
                      <?php if (!empty($b['payment_detail'])): ?>
                        <small style="color: #677792; display:block;">Payment detail: <?= clean($b['payment_detail']) ?></small>
                      <?php endif; ?>
                      <?php if (!empty($b['payment_reference'])): ?>
                        <small style="color: #677792; display:block;">Payment ID: <?= clean($b['payment_reference']) ?></small>
                      <?php endif; ?>
                    </div>
                    <div style="text-align: right;">
                      <strong>&#8377;<?= number_format($b['total_amount']) ?></strong>
                      <?php if ($status === 'PAID'): ?>
                        <?php $isDemoPayment = strpos((string) ($b['payment_reference'] ?? ''), 'DEMO-PAY-') === 0; ?>
                        <div class="badge-paid"><i class="fa-solid fa-check-circle"></i> <?= $isDemoPayment ? 'Paid (Demo)' : 'Paid' ?></div>
                      <?php elseif ($status === 'PENDING'): ?>
                        <div class="badge-pending"><i class="fa-solid fa-clock"></i> Pending Payment</div>
                      <?php elseif ($status === 'CANCELLED'): ?>
                        <div class="badge-cancelled"><i class="fa-solid fa-ban"></i> Cancelled</div>
                      <?php else: ?>
                        <div class="badge-failed"><i class="fa-solid fa-times-circle"></i> Failed</div>
                      <?php endif; ?>
                      <?php if (
                          $b['travel_date'] >= date('Y-m-d')
                          && ($status === 'PENDING' || ($status === 'PAID' && strpos((string) ($b['payment_reference'] ?? ''), 'DEMO-PAY-') === 0))
                      ): ?>
                        <form method="post" onsubmit="return confirm('Cancel this booking?');">
                          <input type="hidden" name="csrf_token" value="<?= clean($_SESSION['csrf_token']) ?>">
                          <button type="submit" name="cancel_booking" value="<?= (int) $b['id'] ?>" class="btn-cancel-booking">Cancel booking</button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>

          <!-- Notifications -->
          <div class="info-card">
            <h3>Notifications</h3>
            <?php foreach ($bookings as $b): ?>
              <?php if ($b['payment_status'] === 'Paid'): ?>
                <div style="background: #f8fbff; padding: 15px; border-left: 4px solid #0072ff; margin-bottom: 15px; border-radius: 4px;">
                  <strong style="display:block; margin-bottom: 5px;"><?= strpos((string) ($b['payment_reference'] ?? ''), 'DEMO-PAY-') === 0 ? '[Demo] Booking marked paid' : '[Email] Booking Confirmed!' ?></strong>
                  <p style="margin:0; font-size: 14px; color: #555;">
                    Your trip to <?= clean($b['title']) ?> (#<?= clean($b['booking_reference']) ?>) has been confirmed. Get ready for an adventure!
                  </p>
                </div>
              <?php elseif ($b['payment_status'] === 'Pending'): ?>
                <div style="background: #fff8f8; padding: 15px; border-left: 4px solid #ff4757; border-radius: 4px; margin-bottom: 15px;">
                  <strong style="display:block; margin-bottom: 5px;">[SMS] Payment Reminder</strong>
                  <p style="margin:0; font-size: 14px; color: #555;">
                    Please complete your payment for <?= clean($b['title']) ?> (#<?= clean($b['booking_reference']) ?>) within 24 hours.
                  </p>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
            <?php if (empty($bookings)): ?>
              <p style="color:#777; font-size:14px;">No notifications.</p>
            <?php endif; ?>
          </div>
        </section>
      </div>
    </main>
  </body>
</html>