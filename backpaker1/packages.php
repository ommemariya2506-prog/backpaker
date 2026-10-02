<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

$stmt = $pdo->query('SELECT * FROM destinations ORDER BY price ASC');
$packages = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Travel Packages - Backpacker Travel</title>
    <link rel="stylesheet" href="Style.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <style>
      body { background: #f5f7fb; color: #101828; }
      .packages-page { padding: 110px 0 80px; }
      .page-hero {
        background: linear-gradient(135deg, #0d1d44, #1d4ed8 45%, #4aa3ff 100%);
        color: #fff;
        padding: 80px 0 50px;
      }
      .page-hero h1 { margin: 0 0 12px; font-size: clamp(2.3rem, 4vw, 4rem); }
      .page-hero p { margin: 0; color: rgba(255,255,255,0.9); font-size: 1.1rem; }
      .package-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 28px;
        margin-top: 40px;
      }
      .package-card {
        background: #fff;
        border-radius: 22px;
        overflow: hidden;
        border: 1px solid #e8edf7;
        box-shadow: 0 18px 35px rgba(17, 24, 39, 0.06);
      }
      .package-card img {
        width: 100%;
        height: 220px;
        object-fit: cover;
        display: block;
      }
      .package-body {
        padding: 20px 20px 24px;
      }
      .package-body h3 {
        margin: 0 0 8px;
        font-size: 1.7rem;
      }
      .package-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.95rem;
        color: #4b5563;
        margin-bottom: 12px;
      }
      .package-body p {
        margin: 0 0 14px;
        color: #475467;
        line-height: 1.7;
      }
      .package-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
      }
      .package-price {
        font-size: 1.7rem;
        font-weight: 800;
        color: #111827;
      }
      .package-price span { font-size: 0.9rem; color: #64748b; }
      .btn-primary, .btn-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 11px 18px;
        font-weight: 700;
        text-decoration: none;
        transition: 0.2s ease;
      }
      .btn-primary {
        background: linear-gradient(90deg, #0072ff, #00d4ff);
        color: #fff;
      }
      .btn-secondary {
        background: #edf4ff;
        color: #1d4ed8;
      }
      .empty {
        text-align: center;
        padding: 50px 20px;
        color: #475467;
      }
      @media (max-width: 640px) {
        .package-footer { flex-direction: column; align-items: flex-start; }
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
            <li><a href="packages.php" style="color: #00c3ff;">Packages</a></li>
          </ul>
          <?php if (is_logged_in()): ?>
            <a href="profile.php" class="login-btn">Profile</a>
          <?php else: ?>
            <a href="login.php" class="login-btn">Login</a>
          <?php endif; ?>
        </nav>
      </div>
    </header>

    <section class="page-hero">
      <div class="container">
        <h1>Travel Packages</h1>
        <p>Handpicked holidays for every adventure, from mountains to beaches.</p>
      </div>
    </section>

    <main class="packages-page">
      <div class="container">
        <?php if (empty($packages)): ?>
          <div class="empty">No packages available right now. Please check back soon.</div>
        <?php else: ?>
          <div class="package-grid">
            <?php foreach ($packages as $package): ?>
              <?php $pkg_file = strtolower($package['title']) === 'leh ladakh' ? 'leh-ladakh.php' : (strtolower($package['title']) === 'spiti valley' ? 'spiti-valley.php' : (strtolower($package['title']) === 'goa' ? 'goa.php' : (strtolower($package['title']) === 'girnar' ? 'girnare.php' : (strtolower($package['title']) === 'swaziland' ? 'swaziland.php' : 'search.php')))); ?>
              <article class="package-card">
                <img src="<?= clean($package['image_url'] ?: ($package['hero_image'] ?: 'images/hero.jpg')) ?>" alt="<?= clean($package['title']) ?>" />
                <div class="package-body">
                  <h3><?= clean($package['title']) ?></h3>
                  <div class="package-meta">
                    <i class="fa-regular fa-calendar"></i>
                    <span><?= clean($package['duration'] ?: 'Flexible trip') ?></span>
                  </div>
                  <p><?= clean($package['highlights'] ?: 'Explore one of the finest experiences in India and beyond.') ?></p>
                  <div class="package-footer">
                    <div class="package-price">₹<?= number_format((float) $package['price'], 0) ?><span>/person</span></div>
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                      <a class="btn-secondary" href="<?= clean($pkg_file) ?>">View</a>
                      <a class="btn-primary" href="checkout.php?dest_id=<?= (int) $package['id'] ?>">Book Now</a>
                    </div>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </body>
</html>
