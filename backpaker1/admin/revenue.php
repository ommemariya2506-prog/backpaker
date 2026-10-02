<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

require_admin();

$revenueStats = $pdo->query(
    "SELECT
        COUNT(*) AS order_count,
        COALESCE(SUM(CASE WHEN LOWER(payment_status) = 'paid' AND COALESCE(payment_reference, '') NOT LIKE 'DEMO-PAY-%' THEN total_amount ELSE 0 END), 0) AS collected_revenue,
        COALESCE(SUM(CASE WHEN LOWER(payment_status) = 'pending' THEN total_amount ELSE 0 END), 0) AS pending_revenue,
        COALESCE(SUM(CASE WHEN LOWER(payment_status) = 'paid' AND COALESCE(payment_reference, '') NOT LIKE 'DEMO-PAY-%' THEN 1 ELSE 0 END), 0) AS paid_orders,
        COALESCE(SUM(CASE WHEN LOWER(payment_status) = 'pending' THEN 1 ELSE 0 END), 0) AS pending_orders
     FROM bookings"
)->fetch() ?: [];

$paidOrders = $pdo->query(
    "SELECT b.id, b.booking_reference, b.total_amount, b.payment_status, b.created_at,
            u.name AS customer_name, d.title AS destination_title
     FROM bookings b
     LEFT JOIN users u ON u.id = b.user_id
     LEFT JOIN destinations d ON d.id = b.destination_id
     WHERE LOWER(b.payment_status) = 'paid'
       AND COALESCE(b.payment_reference, '') NOT LIKE 'DEMO-PAY-%'
     ORDER BY b.created_at DESC, b.id DESC"
)->fetchAll();

$currentAdminName = $_SESSION['user_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Revenue - Backpacker Admin</title>
    <link rel="stylesheet" href="style.css" />
    <style>
      .revenue-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
      }
      .revenue-card {
        padding: 20px;
        border: 1px solid var(--line);
        border-radius: 18px;
        background: linear-gradient(135deg, #fff, #f4f8ff);
        box-shadow: var(--shadow);
      }
      .revenue-card span {
        display: block;
        color: var(--muted);
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .05em;
        text-transform: uppercase;
      }
      .revenue-card strong {
        display: block;
        margin-top: 12px;
        color: var(--text);
        font-size: clamp(22px, 3vw, 30px);
      }
      .revenue-table { min-width: 650px; }
      @media (max-width: 700px) {
        .revenue-grid { grid-template-columns: 1fr; }
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
            <a class="nav-link active" href="revenue.php" aria-current="page">Revenue</a>
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
              <div class="page-badge">Finance overview</div>
              <h1>Revenue</h1>
            </div>
            <div class="topbar-actions">
              <a href="orders.php" class="btn-muted">All Orders</a>
              <a href="../logout.php" class="btn-danger">Logout</a>
            </div>
          </header>

          <section class="revenue-grid" aria-label="Revenue summary">
            <article class="revenue-card">
              <span>Collected revenue</span>
              <strong>₹<?= number_format((float) ($revenueStats['collected_revenue'] ?? 0), 2) ?></strong>
            </article>
            <article class="revenue-card">
              <span>Pending amount</span>
              <strong>₹<?= number_format((float) ($revenueStats['pending_revenue'] ?? 0), 2) ?></strong>
            </article>
            <article class="revenue-card">
              <span>Total orders</span>
              <strong><?= number_format((int) ($revenueStats['order_count'] ?? 0)) ?></strong>
            </article>
          </section>

          <section class="card">
            <div class="section-heading">
              <h2>Paid Orders</h2>
              <span class="section-count">
                <?= number_format((int) ($revenueStats['paid_orders'] ?? 0)) ?> paid ·
                <?= number_format((int) ($revenueStats['pending_orders'] ?? 0)) ?> pending
              </span>
            </div>
            <div class="table-wrap">
              <table class="revenue-table">
                <thead>
                  <tr><th>Order</th><th>Customer</th><th>Package</th><th>Paid on</th><th>Amount</th></tr>
                </thead>
                <tbody>
                  <?php if ($paidOrders): ?>
                    <?php foreach ($paidOrders as $order): ?>
                      <tr>
                        <td><?= clean($order['booking_reference'] ?: 'Order #' . $order['id']) ?></td>
                        <td><?= clean($order['customer_name'] ?? 'Deleted user') ?></td>
                        <td><?= clean($order['destination_title'] ?? 'Deleted package') ?></td>
                        <td><?= clean($order['created_at'] ?? '-') ?></td>
                        <td><strong>₹<?= number_format((float) ($order['total_amount'] ?? 0), 2) ?></strong></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr><td colspan="5" class="meta">No paid orders have been recorded yet.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </section>
        </main>
      </div>
    </div>
  </body>
</html>
