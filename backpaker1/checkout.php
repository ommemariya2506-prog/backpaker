<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

require_login();

$user_id = $_SESSION['user_id'];

// Fetch user
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

// Get destination_id from query param (passed from destination pages)
$dest_id = isset($_GET['dest_id']) ? (int)$_GET['dest_id'] : 0;
$destination = null;
if ($dest_id > 0) {
    $dstmt = $pdo->prepare('SELECT * FROM destinations WHERE id = ?');
    $dstmt->execute([$dest_id]);
    $destination = $dstmt->fetch();
}

$error = flash('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $travel_date  = trim($_POST['travel_date'] ?? '');
    $guests       = (int) ($_POST['guests'] ?? 0);
    $p_dest_id    = (int)($_POST['dest_id'] ?? 0);
    $paymentMethod = trim($_POST['payment_method'] ?? '');
    $paymentAction = trim($_POST['payment_action'] ?? '');
    $allowedPaymentMethods = ['Card', 'UPI', 'Net Banking', 'Wallet'];

    $travelTimestamp = strtotime($travel_date);
    if ($travelTimestamp === false || date('Y-m-d', $travelTimestamp) !== $travel_date || $travel_date < date('Y-m-d')) {
        flash('error', 'Please select a valid travel date that is not in the past.');
        redirect('checkout.php' . ($p_dest_id > 0 ? '?dest_id=' . $p_dest_id : ''));
    }

    if ($guests < 1 || $guests > 20) {
        flash('error', 'Please choose between 1 and 20 guests.');
        redirect('checkout.php' . ($p_dest_id > 0 ? '?dest_id=' . $p_dest_id : ''));
    }

    if (!in_array($paymentMethod, $allowedPaymentMethods, true)) {
        flash('error', 'Please select a valid payment method.');
        redirect('checkout.php' . ($p_dest_id > 0 ? '?dest_id=' . $p_dest_id : ''));
    }

    if (!in_array($paymentAction, ['simulate_payment', 'pay_later'], true)) {
        flash('error', 'Please choose a payment action.');
        redirect('checkout.php' . ($p_dest_id > 0 ? '?dest_id=' . $p_dest_id : ''));
    }

    $isDemoPayment = $paymentAction === 'simulate_payment';
    $paymentDetail = '';
    if ($isDemoPayment && $paymentMethod === 'Card') {
        $cardLastFour = trim($_POST['card_last_four'] ?? '');
        if (!preg_match('/^[0-9]{4}$/', $cardLastFour)) {
            flash('error', 'Enter only the last four digits of the card.');
            redirect('checkout.php' . ($p_dest_id > 0 ? '?dest_id=' . $p_dest_id : ''));
        }
        $paymentDetail = 'Card ending in ' . $cardLastFour;
    } elseif ($isDemoPayment && $paymentMethod === 'UPI') {
        $upiId = trim($_POST['upi_id'] ?? '');
        if (!preg_match('/^[A-Za-z0-9._-]{2,256}@[A-Za-z0-9.-]{2,64}$/', $upiId)) {
            flash('error', 'Enter a valid UPI ID, for example name@upi.');
            redirect('checkout.php' . ($p_dest_id > 0 ? '?dest_id=' . $p_dest_id : ''));
        }
        $paymentDetail = $upiId;
    } elseif ($isDemoPayment && $paymentMethod === 'Net Banking') {
        $bankName = trim($_POST['bank_name'] ?? '');
        if (!in_array($bankName, ['SBI', 'HDFC', 'ICICI', 'Axis', 'Bank of Baroda'], true)) {
            flash('error', 'Please select a supported bank.');
            redirect('checkout.php' . ($p_dest_id > 0 ? '?dest_id=' . $p_dest_id : ''));
        }
        $paymentDetail = $bankName;
    } elseif ($isDemoPayment) {
        $walletNumber = trim($_POST['wallet_number'] ?? '');
        $digitsOnly = preg_replace('/\D/', '', $walletNumber);
        if (!is_string($digitsOnly) || strlen($digitsOnly) < 7 || strlen($digitsOnly) > 15) {
            flash('error', 'Enter a valid wallet mobile number.');
            redirect('checkout.php' . ($p_dest_id > 0 ? '?dest_id=' . $p_dest_id : ''));
        }
        $paymentDetail = 'Wallet ending in ' . substr($digitsOnly, -4);
    }

    if ($p_dest_id <= 0) {
        flash('error', 'Please select a destination.');
        redirect('checkout.php');
    }

    // Fetch destination price
    $ps = $pdo->prepare('SELECT * FROM destinations WHERE id = ?');
    $ps->execute([$p_dest_id]);
    $pdest = $ps->fetch();
    if (!$pdest) {
        flash('error', 'Invalid destination.');
        redirect('checkout.php');
    }

    $base    = $pdest['price'] * $guests;
    $tax     = round($base * 0.05);
    $total   = $base + $tax;
    $ref     = 'BP-' . strtoupper(substr(uniqid(), -5));
    $paymentStatus = $isDemoPayment ? 'Paid' : 'Pending';
    $paymentReference = ($isDemoPayment ? 'DEMO-PAY-' : 'BP-PAY-') . strtoupper(bin2hex(random_bytes(6)));

    $pdo->beginTransaction();
    $ins = $pdo->prepare(
        'INSERT INTO bookings (booking_reference, user_id, destination_id, travel_date, guests, total_amount, payment_status, payment_method, payment_reference, payment_detail)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $ins->execute([$ref, $user_id, $p_dest_id, $travel_date, $guests, $total, $paymentStatus, $paymentMethod, $paymentReference, $paymentDetail]);
    $bookingId = (int) $pdo->lastInsertId();

    if ($isDemoPayment) {
        $paymentInsert = $pdo->prepare(
            'INSERT INTO payments (booking_id, payment_method, transaction_id, amount) VALUES (?, ?, ?, ?)'
        );
        $paymentInsert->execute([$bookingId, $paymentMethod, $paymentReference, $total]);
    }
    $pdo->commit();

    $resultMessage = $isDemoPayment
        ? 'Demo payment successful. Booking #' . $ref . ' is marked Paid (Demo).'
        : 'Booking #' . $ref . ' created. Payment is Pending.';
    flash('success', $resultMessage . ' Payment ID: ' . $paymentReference . '.');
    redirect('profile.php');
}

// Fetch all destinations for dropdown
$all_dest = $pdo->query('SELECT * FROM destinations ORDER BY title')->fetchAll();
$selectedDestination = $destination;
if (!$selectedDestination && !empty($all_dest)) {
    $selectedDestination = $all_dest[0];
}
$checkoutImage = !empty($selectedDestination['image_url'])
    ? $selectedDestination['image_url']
    : (!empty($selectedDestination['hero_image']) ? $selectedDestination['hero_image'] : 'images/hero.jpg');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Checkout - Backpacker Travel</title>
    <link rel="stylesheet" href="Style.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <style>
      body { background: #fafafc; color: #222; }
      .new-header { background: #fff; padding: 20px 0; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
      .new-header nav { display: flex; justify-content: space-between; align-items: center; }
      .new-logo { font-size: 26px; font-weight: 700; color: #111; }
      .new-logo span { color: #0066ff; }
      .new-nav-links { display: flex; gap: 30px; }
      .new-nav-links a { color: #555; font-size: 15px; font-weight: 500; transition: 0.3s; }
      .new-nav-links a:hover { color: #0066ff; }
      .checkout-container { padding: 60px 0; display: grid; grid-template-columns: 2fr 1fr; gap: 40px; }
      .checkout-form, .order-summary { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
      .checkout-form h2, .order-summary h2 { font-size: 24px; margin-bottom: 20px; color: #111; border-bottom: 1px solid #eee; padding-bottom: 10px; }
      .form-group { margin-bottom: 20px; }
      .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
      .form-group input, .form-group select { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; font-size: 15px; box-sizing: border-box; }
      .payment-methods { display: flex; gap: 15px; margin-top: 10px; }
      .payment-method { flex: 1; padding: 15px; border: 1px solid #ddd; border-radius: 8px; text-align: center; cursor: pointer; font-weight: 500; }
      .payment-method.active { border-color: #0066ff; background: #f0f7ff; color: #0066ff; }
      .payment-method input { position: absolute; opacity: 0; }
      .payment-method:focus-within { outline: 2px solid #0066ff; outline-offset: 2px; }
      .payment-methods { flex-wrap: wrap; }
      .payment-method { min-width: 110px; }
      .payment-details { margin-top: 18px; }
      .payment-details[hidden] { display: none; }
      .summary-item { display: flex; justify-content: space-between; margin-bottom: 15px; color: #555; }
      .summary-total { display: flex; justify-content: space-between; margin-top: 20px; padding-top: 20px; border-top: 2px dashed #eee; font-size: 20px; font-weight: 700; color: #111; }
      .btn-pay { display: block; width: 100%; background: #0066ff; color: #fff; text-align: center; padding: 15px; border: none; border-radius: 8px; font-size: 18px; font-weight: 600; margin-top: 30px; cursor: pointer; transition: 0.3s; }
      .btn-pay:hover { background: #0052cc; }
      .btn-later { display: block; width: 100%; background: #fff; color: #334155; text-align: center; padding: 13px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 16px; font-weight: 600; margin-top: 12px; cursor: pointer; }
      .btn-later:hover { background: #f8fafc; }
      .alert-error { background: #fff0f0; border: 1px solid #ffb3b3; color: #c0392b; padding: 12px; border-radius: 8px; margin-bottom: 16px; }
      .new-footer { background: #1a1a1a; color: #fff; padding: 60px 0 30px; margin-top: 60px; }
      .footer-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; border-bottom: 1px solid #333; padding-bottom: 40px; }
      .footer-top .logo { font-size: 26px; font-weight: 700; color: #fff; }
      .footer-top .logo span { color: #0066ff; }
      .footer-links { display: flex; gap: 30px; }
      .footer-links a { color: #aaa; transition: 0.3s; }
      .footer-links a:hover { color: #fff; }
      .footer-bottom { display: flex; justify-content: space-between; color: #777; font-size: 14px; }
    </style>
  </head>

  <body>
    <header class="new-header">
      <div class="container">
        <nav>
          <div class="new-logo">Backpacker<span>.</span></div>
          <ul class="new-nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="search.php">Destinations</a></li>
            <li><a href="packages.php">Packages</a></li>
          </ul>
          <a href="profile.php" style="color:#333;"><i class="fa-regular fa-user"></i> Profile</a>
        </nav>
      </div>
    </header>

    <div class="container checkout-container">
      <div class="checkout-form">
        <?php if ($error): ?>
          <div class="alert-error"><?= clean($error) ?></div>
        <?php endif; ?>

        <form action="checkout.php" method="post">
          <input type="hidden" name="dest_id" value="<?= $dest_id ?: (count($all_dest) ? $all_dest[0]['id'] : '') ?>">

          <h2>Traveller Details</h2>
          <div class="form-group">
            <label>Email Address</label>
            <input type="email" value="<?= clean($user['email']) ?>" readonly>
          </div>
          <div class="form-group">
            <label>Destination</label>
            <select name="dest_id" id="dest_select" onchange="updateSummary(this)">
              <?php foreach ($all_dest as $d): ?>
                <option value="<?= $d['id'] ?>"
                  data-price="<?= $d['price'] ?>"
                  data-title="<?= clean($d['title']) ?>"
                  data-duration="<?= clean($d['duration']) ?>"
                    data-image="<?= clean($d['image_url'] ?: ($d['hero_image'] ?: 'images/hero.jpg')) ?>"
                    <?= ($dest_id === (int)$d['id']) ? 'selected' : '' ?>>
                  <?= clean($d['title']) ?> — &#8377;<?= number_format($d['price']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
            <div class="form-group">
              <label>Travel Date</label>
              <input type="date" name="travel_date" required min="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
              <label>Number of Guests</label>
              <input type="number" name="guests" id="guests_input" value="2" min="1" max="20" oninput="updateSummary()">
            </div>
          </div>

          <h2 style="margin-top:30px;">Payment Method</h2>
          <div class="payment-methods" role="radiogroup" aria-label="Choose a payment method">
            <label class="payment-method active">
              <input type="radio" name="payment_method" value="Card" checked required>
              <i class="fa-solid fa-credit-card" aria-hidden="true"></i> Card
            </label>
            <label class="payment-method">
              <input type="radio" name="payment_method" value="UPI" required>
              <i class="fa-solid fa-mobile-screen" aria-hidden="true"></i> UPI
            </label>
            <label class="payment-method">
              <input type="radio" name="payment_method" value="Net Banking" required>
              <i class="fa-solid fa-building-columns" aria-hidden="true"></i> Net Banking
            </label>
            <label class="payment-method">
              <input type="radio" name="payment_method" value="Wallet" required>
              <i class="fa-solid fa-wallet" aria-hidden="true"></i> Wallet
            </label>
          </div>
          <div class="payment-details" data-payment-details="Card">
            <div class="form-group">
              <label for="card_last_four">Card last 4 digits</label>
              <input type="text" id="card_last_four" name="card_last_four" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" autocomplete="off" placeholder="1234" required>
            </div>
          </div>
          <div class="payment-details" data-payment-details="UPI" hidden>
            <div class="form-group">
              <label for="upi_id">UPI ID</label>
              <input type="text" id="upi_id" name="upi_id" autocomplete="off" placeholder="name@upi" disabled>
            </div>
          </div>
          <div class="payment-details" data-payment-details="Net Banking" hidden>
            <div class="form-group">
              <label for="bank_name">Select your bank</label>
              <select id="bank_name" name="bank_name" disabled>
                <option value="">Select bank</option>
                <option value="SBI">SBI</option>
                <option value="HDFC">HDFC</option>
                <option value="ICICI">ICICI</option>
                <option value="Axis">Axis</option>
                <option value="Bank of Baroda">Bank of Baroda</option>
              </select>
            </div>
          </div>
          <div class="payment-details" data-payment-details="Wallet" hidden>
            <div class="form-group">
              <label for="wallet_number">Wallet mobile number</label>
              <input type="tel" id="wallet_number" name="wallet_number" inputmode="tel" autocomplete="tel" placeholder="Enter registered mobile number" disabled>
            </div>
          </div>
          <button type="submit" name="payment_action" value="simulate_payment" class="btn-pay" <?= count($all_dest) === 0 ? 'disabled' : '' ?>>Simulate Payment &amp; Book</button>
          <button type="submit" name="payment_action" value="pay_later" class="btn-later" <?= count($all_dest) === 0 ? 'disabled' : '' ?>>Create Booking &amp; Pay Later</button>
        </form>
      </div>

      <div class="order-summary">
        <h2>Booking Summary</h2>
        <div style="margin-bottom: 20px;">
          <img src="<?= clean($checkoutImage) ?>" id="sum_image" alt="Destination" style="width:100%; border-radius: 8px; margin-bottom: 10px;">
          <h3 style="margin:0; font-size: 18px;" id="sum_title">
            <?= $destination ? clean($destination['title']) : (count($all_dest) ? clean($all_dest[0]['title']) : 'Select a destination') ?>
          </h3>
          <p style="color:#777; margin:5px 0;" id="sum_duration">
            <?= $destination ? clean($destination['duration']) : (count($all_dest) ? clean($all_dest[0]['duration']) : '') ?>
          </p>
        </div>
        <div class="summary-item">
          <span>Base Price (<span id="sum_guests">2</span> Guests)</span>
          <span id="sum_base">&#8377;<?= $destination ? number_format($destination['price'] * 2) : (count($all_dest) ? number_format($all_dest[0]['price'] * 2) : '0') ?></span>
        </div>
        <div class="summary-item">
          <span>Taxes &amp; Fees (5%)</span>
          <span id="sum_tax">&#8377;<?= $destination ? number_format(round($destination['price'] * 2 * 0.05)) : '0' ?></span>
        </div>
        <div class="summary-total">
          <span>Total</span>
          <span id="sum_total">&#8377;<?= $destination ? number_format(round($destination['price'] * 2 * 1.05)) : '0' ?></span>
        </div>
      </div>
    </div>

    <footer class="new-footer">
      <div class="container">
        <div class="footer-top">
          <div class="logo">Backpacker<span>.</span></div>
          <div class="footer-links">
            <a href="index.php">Home</a><a href="packages.php">Packages</a>
          </div>
        </div>
        <div class="footer-bottom">
          <div>&copy; 2026 Backpacker Travel. All Rights Reserved.</div>
        </div>
      </div>
    </footer>

    <script>
      let paymentActionChoice = 'simulate_payment';

      function updatePaymentFields() {
        const selectedMethod = document.querySelector('input[name="payment_method"]:checked').value;
        document.querySelectorAll('.payment-method').forEach(label => {
          label.classList.toggle('active', label.querySelector('input').checked);
        });
        document.querySelectorAll('.payment-details').forEach(section => {
          const active = section.dataset.paymentDetails === selectedMethod;
          section.hidden = !active;
          section.querySelectorAll('input, select').forEach(field => {
            field.disabled = !active;
            field.required = active && paymentActionChoice === 'simulate_payment';
          });
        });
      }

      document.querySelectorAll('.payment-method input[name="payment_method"]').forEach(input => {
        input.addEventListener('change', updatePaymentFields);
      });
      updatePaymentFields();

      document.querySelectorAll('button[name="payment_action"]').forEach(button => {
        button.addEventListener('click', () => {
          paymentActionChoice = button.value;
          updatePaymentFields();
        });
      });

      // Live summary update
      function updateSummary(sel) {
        const select  = document.getElementById('dest_select');
        const opt     = select.options[select.selectedIndex];
        const price   = parseFloat(opt.dataset.price) || 0;
        const guests  = parseInt(document.getElementById('guests_input').value) || 1;
        const base    = price * guests;
        const tax     = Math.round(base * 0.05);
        const total   = base + tax;
        const fmt     = n => '\u20B9' + n.toLocaleString('en-IN');
        document.getElementById('sum_title').textContent    = opt.dataset.title;
        document.getElementById('sum_duration').textContent = opt.dataset.duration;
        document.getElementById('sum_image').src = opt.dataset.image;
        document.getElementById('sum_image').alt = opt.dataset.title;
        document.getElementById('sum_guests').textContent   = guests;
        document.getElementById('sum_base').innerHTML       = fmt(base);
        document.getElementById('sum_tax').innerHTML        = fmt(tax);
        document.getElementById('sum_total').innerHTML      = fmt(total);
        // also update hidden dest_id in form
        document.querySelector('input[name="dest_id"]').value = opt.value;
      }
    </script>
  </body>
</html>
