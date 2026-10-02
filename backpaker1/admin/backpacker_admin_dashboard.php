<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

require_admin();

$success = flash('success');
$error = flash('error');

$stats = $pdo->query(
    "SELECT
        (SELECT COUNT(*) FROM bookings) AS total_orders,
        (SELECT COUNT(*) FROM users) AS total_users,
        (SELECT COUNT(*) FROM destinations) AS total_packages,
        COALESCE(SUM(CASE WHEN LOWER(payment_status) = 'paid' AND COALESCE(payment_reference, '') NOT LIKE 'DEMO-PAY-%' THEN total_amount ELSE 0 END), 0) AS collected_revenue,
        COALESCE(SUM(CASE WHEN LOWER(payment_status) = 'paid' AND COALESCE(payment_reference, '') NOT LIKE 'DEMO-PAY-%' THEN 1 ELSE 0 END), 0) AS paid_orders,
        COALESCE(SUM(CASE WHEN LOWER(payment_status) = 'pending' THEN 1 ELSE 0 END), 0) AS pending_orders
     FROM bookings"
)->fetch() ?: [];

$orders = $pdo->query(
    'SELECT b.id, b.booking_reference, b.travel_date, b.guests, b.total_amount, b.payment_status, b.payment_method, b.payment_reference, b.payment_detail, b.created_at,
            u.name AS customer_name, u.email AS customer_email, d.title AS destination_title
     FROM bookings b
     LEFT JOIN users u ON u.id = b.user_id
     LEFT JOIN destinations d ON d.id = b.destination_id
     ORDER BY b.created_at DESC, b.id DESC
     LIMIT 10'
)->fetchAll();

$users = $pdo->query(
    'SELECT id, user_id, name, email, phone, mobile, usertype, role, city, resisterdate
     FROM users
     ORDER BY id DESC
     LIMIT 10'
)->fetchAll();

$currentAdminName = $_SESSION['user_name'] ?? 'Admin';
$totalOrders = (int) ($stats['total_orders'] ?? 0);
$totalUsers = (int) ($stats['total_users'] ?? 0);
$totalPackages = (int) ($stats['total_packages'] ?? 0);
$paidOrders = (int) ($stats['paid_orders'] ?? 0);
$pendingOrders = (int) ($stats['pending_orders'] ?? 0);
$collectedRevenue = (float) ($stats['collected_revenue'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard - Backpacker</title>
    <link rel="stylesheet" href="style.css" />
    <style>
      .dashboard-stat-link {
        display: block;
        transition: transform .2s ease, box-shadow .2s ease;
      }
      .dashboard-stat-link:hover {
        transform: translateY(-2px);
        box-shadow: 0 18px 34px rgba(18, 36, 62, .09);
      }
      .dashboard-stat-link .stat-value { display: block; }
      .dashboard-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.45fr) minmax(280px, .85fr);
        gap: 24px;
      }
      .card-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 16px;
      }
      .card-heading h2 { margin: 0; }
      .card-heading .btn-muted { white-space: nowrap; }
      .dashboard-table { min-width: 680px; }
      .dashboard-users-table { min-width: 520px; }
      .dashboard-table th,
      .dashboard-table td,
      .dashboard-users-table th,
      .dashboard-users-table td { padding: 11px 9px; }
      .status-pill {
        display: inline-flex;
        padding: 5px 9px;
        border-radius: 999px;
        background: #f1f5f9;
        color: #475569;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
      }
      .status-pill.paid { background: #ecfff4; color: #166534; }
      .status-pill.pending { background: #fff7eb; color: #b45309; }
      .status-pill.failed { background: #fff1f2; color: #b42318; }
      .status-pill.cancelled { background: #f1f5f9; color: #475569; }
      .dashboard-user-type {
        display: inline-flex;
        padding: 5px 8px;
        border-radius: 999px;
        background: #eef5ff;
        color: #1d4ed8;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
      }
      .dashboard-user-type.admin { background: #f3edff; color: #6941c6; }
      @media (max-width: 1100px) {
        .dashboard-grid { grid-template-columns: 1fr; }
      }
    </style>
  </head>
  <body>
    <div class="admin-shell">
      <div class="app-shell">
        <aside class="sidebar">
          <div class="sidebar-brand"><span class="brand-mark">B</span>Backpacker</div>
          <nav class="nav-menu" aria-label="Admin navigation">
            <a class="nav-link active" href="backpacker_admin_dashboard.php" aria-current="page">Dashboard</a>
            <a class="nav-link" href="orders.php">All Orders</a>
            <a class="nav-link" href="revenue.php">Revenue</a>
            <a class="nav-link" href="users.php">Users</a>
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
              <div class="page-badge">Backpacker admin</div>
              <h1>Dashboard</h1>
            </div>
            <div class="topbar-actions">
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

          <section class="stats-grid" aria-label="Business overview">
            <a class="stat-box dashboard-stat-link" href="orders.php">
              <span class="stat-label">All Orders</span>
              <div class="stat-header">
                <span class="stat-value"><?= number_format($totalOrders) ?></span>
                <span class="stat-icon">▣</span>
              </div>
            </a>
            <a class="stat-box dashboard-stat-link" href="revenue.php">
              <span class="stat-label">Collected Revenue</span>
              <div class="stat-header">
                <span class="stat-value">₹<?= number_format($collectedRevenue, 2) ?></span>
                <span class="stat-icon">₹</span>
              </div>
            </a>
            <a class="stat-box dashboard-stat-link" href="users.php">
              <span class="stat-label">Registered Users</span>
              <div class="stat-header">
                <span class="stat-value"><?= number_format($totalUsers) ?></span>
                <span class="stat-icon">♙</span>
              </div>
            </a>
            <a class="stat-box dashboard-stat-link" href="index.php">
              <span class="stat-label">Travel Packages</span>
              <div class="stat-header">
                <span class="stat-value"><?= number_format($totalPackages) ?></span>
                <span class="stat-icon">◈</span>
              </div>
            </a>
          </section>

          <div class="dashboard-grid">
            <section class="card">
              <div class="card-heading">
                <h2>Recent Orders</h2>
                <a href="orders.php" class="btn-muted">View all</a>
              </div>
              <div class="table-wrap">
                <table class="dashboard-table">
                  <thead>
                    <tr>
                      <th>Order</th>
                      <th>Customer</th>
                      <th>Package</th>
                      <th>Travel Date</th>
                      <th>Amount</th>
                      <th>Payment Method</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if ($orders): ?>
                      <?php foreach ($orders as $order): ?>
                        <?php
                          $orderStatus = strtolower((string) ($order['payment_status'] ?? 'pending'));
                          $isDemoPayment = strpos((string) ($order['payment_reference'] ?? ''), 'DEMO-PAY-') === 0;
                        ?>
                        <tr>
                          <td>
                            <strong><?= clean($order['booking_reference'] ?: 'Order #' . $order['id']) ?></strong>
                            <div class="meta">ID: <?= (int) $order['id'] ?></div>
                          </td>
                          <td>
                            <?= clean($order['customer_name'] ?? 'Deleted user') ?>
                            <div class="meta"><?= clean($order['customer_email'] ?? '-') ?></div>
                          </td>
                          <td><?= clean($order['destination_title'] ?? 'Deleted package') ?></td>
                          <td><?= clean($order['travel_date'] ?? '-') ?></td>
                          <td>₹<?= number_format((float) ($order['total_amount'] ?? 0), 2) ?></td>
                          <td>
                            <?= clean($order['payment_method'] ?? 'Not selected') ?>
                            <div class="meta"><?= clean($order['payment_detail'] ?? '-') ?></div>
                            <div class="meta"><?= clean($order['payment_reference'] ?? '-') ?></div>
                          </td>
                          <td><span class="status-pill <?= clean($orderStatus) ?>"><?= $isDemoPayment ? 'Paid (Demo)' : clean($order['payment_status'] ?? 'Pending') ?></span></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr><td colspan="7" class="meta">No orders found yet.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </section>

            <section class="card">
              <div class="card-heading">
                <h2>Recent Users</h2>
                <a href="users.php" class="btn-muted">Manage users</a>
              </div>
              <div class="table-wrap">
                <table class="dashboard-users-table">
                  <thead>
                    <tr><th>User</th><th>City</th><th>Type</th></tr>
                  </thead>
                  <tbody>
                    <?php if ($users): ?>
                      <?php foreach ($users as $user): ?>
                        <?php $userType = strtolower((string) ($user['usertype'] ?: $user['role'] ?: 'user')); ?>
                        <tr>
                          <td>
                            <strong><?= clean($user['name']) ?></strong>
                            <div class="meta"><?= clean($user['email']) ?></div>
                          </td>
                          <td><?= clean($user['city'] ?? '-') ?></td>
                          <td><span class="dashboard-user-type <?= $userType === 'admin' ? 'admin' : '' ?>"><?= clean($userType) ?></span></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr><td colspan="3" class="meta">No users found yet.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </section>
          </div>

          <section class="card">
            <div class="card-heading">
              <h2>Order Status</h2>
              <a href="revenue.php" class="btn-muted">Revenue details</a>
            </div>
            <div class="quick-actions">
              <a class="quick-link" href="orders.php?status=paid">
                <span class="quick-icon">✓</span>
                <span><?= number_format($paidOrders) ?> Paid Orders</span>
              </a>
              <a class="quick-link" href="orders.php?status=pending">
                <span class="quick-icon">◷</span>
                <span><?= number_format($pendingOrders) ?> Pending Orders</span>
              </a>
              <a class="quick-link" href="index.php">
                <span class="quick-icon">＋</span>
                <span>Manage Packages</span>
              </a>
            </div>
          </section>
        </main>
      </div>
    </div>
  </body>
</html>
