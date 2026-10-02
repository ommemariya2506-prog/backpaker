<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

require_admin();

$statusOptions = ['all', 'pending', 'paid', 'failed', 'cancelled'];
$statusFilter = strtolower(trim($_GET['status'] ?? 'all'));
if (!in_array($statusFilter, $statusOptions, true)) {
    $statusFilter = 'all';
}

$sql = "SELECT b.id, b.booking_reference, b.travel_date, b.guests, b.total_amount, b.payment_status, b.payment_method, b.payment_reference, b.payment_detail, b.created_at,
               u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone, u.mobile AS customer_mobile,
               d.title AS destination_title
        FROM bookings b
        LEFT JOIN users u ON u.id = b.user_id
        LEFT JOIN destinations d ON d.id = b.destination_id";
$params = [];
if ($statusFilter !== 'all') {
    $sql .= ' WHERE LOWER(b.payment_status) = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY b.created_at DESC, b.id DESC';

$ordersStmt = $pdo->prepare($sql);
$ordersStmt->execute($params);
$orders = $ordersStmt->fetchAll();

$orderCounts = $pdo->query(
    "SELECT
        COUNT(*) AS all_orders,
        COALESCE(SUM(CASE WHEN LOWER(payment_status) = 'pending' THEN 1 ELSE 0 END), 0) AS pending_orders,
        COALESCE(SUM(CASE WHEN LOWER(payment_status) = 'paid' THEN 1 ELSE 0 END), 0) AS paid_orders
     FROM bookings"
)->fetch() ?: [];

$currentAdminName = $_SESSION['user_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>All Orders - Backpacker Admin</title>
    <link rel="stylesheet" href="style.css" />
    <style>
      .orders-table { min-width: 1400px; }
      .orders-table th, .orders-table td { padding: 12px 10px; }
      .order-filters { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 18px; }
      .filter-link {
        display: inline-flex;
        padding: 8px 12px;
        border-radius: 999px;
        background: #f3f6fb;
        color: var(--muted);
        font-size: 12px;
        font-weight: 700;
      }
      .filter-link.active { background: #eaf2ff; color: var(--primary); }
      .order-status {
        display: inline-flex;
        padding: 5px 9px;
        border-radius: 999px;
        background: #f1f5f9;
        color: #475569;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
      }
      .order-status.paid { background: #ecfff4; color: #166534; }
      .order-status.pending { background: #fff7eb; color: #b45309; }
      .order-status.failed { background: #fff1f2; color: #b42318; }
      .order-summary { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 18px; }
      .summary-pill { padding: 10px 14px; border: 1px solid var(--line); border-radius: 12px; background: #fff; font-size: 13px; }
      .summary-pill strong { margin-left: 5px; color: var(--text); }
    </style>
  </head>
  <body>
    <div class="admin-shell">
      <div class="app-shell">
        <aside class="sidebar">
          <div class="sidebar-brand"><span class="brand-mark">B</span>Backpacker</div>
          <nav class="nav-menu" aria-label="Admin navigation">
            <a class="nav-link" href="backpacker_admin_dashboard.php">Dashboard</a>
            <a class="nav-link active" href="orders.php" aria-current="page">All Orders</a>
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
              <div class="page-badge">Booking management</div>
              <h1>All Orders</h1>
            </div>
            <div class="topbar-actions">
              <a href="revenue.php" class="btn-muted">Revenue</a>
              <a href="../logout.php" class="btn-danger">Logout</a>
            </div>
          </header>

          <div class="order-summary">
            <div class="summary-pill">All orders <strong><?= number_format((int) ($orderCounts['all_orders'] ?? 0)) ?></strong></div>
            <div class="summary-pill">Paid <strong><?= number_format((int) ($orderCounts['paid_orders'] ?? 0)) ?></strong></div>
            <div class="summary-pill">Pending <strong><?= number_format((int) ($orderCounts['pending_orders'] ?? 0)) ?></strong></div>
          </div>

          <section class="card">
            <div class="section-heading">
              <h2>Order Records</h2>
              <span class="section-count"><?= number_format(count($orders)) ?> shown</span>
            </div>
            <div class="order-filters" aria-label="Filter orders by payment status">
              <?php foreach ($statusOptions as $status): ?>
                <a class="filter-link <?= $statusFilter === $status ? 'active' : '' ?>" href="orders.php?status=<?= urlencode($status) ?>">
                  <?= clean(ucfirst($status)) ?>
                </a>
              <?php endforeach; ?>
            </div>
            <div class="table-wrap">
              <table class="orders-table">
                <thead>
                  <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Package</th>
                    <th>Travel Date</th>
                    <th>Guests</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Payment Detail</th>
                    <th>Payment ID</th>
                    <th>Status</th>
                    <th>Created</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($orders): ?>
                    <?php foreach ($orders as $order): ?>
                      <?php
                        $paymentStatus = strtolower((string) ($order['payment_status'] ?? 'pending'));
                        $isDemoPayment = strpos((string) ($order['payment_reference'] ?? ''), 'DEMO-PAY-') === 0;
                      ?>
                      <tr>
                        <td>
                          <strong><?= clean($order['booking_reference'] ?: 'Order #' . $order['id']) ?></strong>
                          <div class="meta">ID: <?= (int) $order['id'] ?></div>
                        </td>
                        <td>
                          <strong><?= clean($order['customer_name'] ?? 'Deleted user') ?></strong>
                          <div class="meta"><?= clean($order['customer_email'] ?? '-') ?></div>
                          <div class="meta"><?= clean($order['customer_phone'] ?: $order['customer_mobile'] ?: '-') ?></div>
                        </td>
                        <td><?= clean($order['destination_title'] ?? 'Deleted package') ?></td>
                        <td><?= clean($order['travel_date'] ?? '-') ?></td>
                        <td><?= (int) ($order['guests'] ?? 0) ?></td>
                        <td>₹<?= number_format((float) ($order['total_amount'] ?? 0), 2) ?></td>
                        <td><?= clean($order['payment_method'] ?? 'Not selected') ?></td>
                        <td><?= clean($order['payment_detail'] ?? '-') ?></td>
                        <td><?= clean($order['payment_reference'] ?? '-') ?></td>
                        <td><span class="order-status <?= clean($paymentStatus) ?>"><?= $isDemoPayment ? 'Paid (Demo)' : clean($order['payment_status'] ?? 'Pending') ?></span></td>
                        <td><?= clean($order['created_at'] ?? '-') ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr><td colspan="11" class="meta">No orders found for this status.</td></tr>
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
