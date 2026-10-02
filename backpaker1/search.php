<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

// Build query
$q = trim($_GET['q'] ?? '');
$filter = trim($_GET['filter'] ?? '');
$category = trim($_GET['category'] ?? '');

if ($q !== '' || $category !== '') {
    $sql = 'SELECT * FROM destinations WHERE 1=1';
    $params = [];

    if ($q !== '') {
        $sql .= ' AND (title LIKE ? OR highlights LIKE ? OR category LIKE ?)';
        $params[] = '%' . $q . '%';
        $params[] = '%' . $q . '%';
        $params[] = '%' . $q . '%';
    }

    if ($category !== '') {
        $sql .= ' AND category = ?';
        $params[] = $category;
    }

    $sql .= ' ORDER BY title';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
} else {
    $stmt = $pdo->query('SELECT * FROM destinations ORDER BY title');
}
$destinations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Search Destinations - Backpacker Travel</title>
    <link rel="stylesheet" href="Style.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <style>
      .search-hero {
        background: url('images/hero.jpg') center/cover;
        min-height: 60vh;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        position: relative;
      }
      .search-hero::before {
        content: "";
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.4);
      }
      .search-hero .container { position: relative; z-index: 2; width: 100%; }
      .search-box {
        background: rgba(255, 255, 255, 0.95);
        padding: 20px;
        border-radius: 15px;
        display: flex;
        gap: 15px;
        max-width: 800px;
        margin: 40px auto 0;
        box-shadow: 0 15px 35px rgba(0,0,0,0.2);
      }
      .search-input {
        flex: 1;
        padding: 15px 20px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 16px;
        outline: none;
        font-family: 'Poppins', sans-serif;
      }
      .search-btn {
        padding: 15px 40px;
        background: #007bff;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.3s;
      }
      .search-btn:hover { background: #0056b3; }
      .filter-section {
        padding: 40px 0;
        background: #f9f9f9;
        border-bottom: 1px solid #eee;
      }
      .filter-tabs { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; }
      .filter-tab {
        padding: 10px 25px;
        background: white;
        border: 1px solid #ddd;
        border-radius: 50px;
        cursor: pointer;
        font-weight: 500;
        transition: 0.3s;
        text-decoration: none;
        color: #333;
      }
      .filter-tab:hover, .filter-tab.active {
        background: #007bff;
        color: white;
        border-color: #007bff;
      }
      .no-results { text-align:center; padding: 60px 0; color: #777; font-size: 18px; }
    </style>
  </head>
  <body>
    <header>
      <div class="container">
        <nav>
          <div class="logo">Backpacker<span>.</span></div>
          <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="search.php" style="color: #00c3ff;">Destinations</a></li>
            <li><a href="packages.php">Packages</a></li>
          </ul>
          <div>
            <?php if (is_logged_in()): ?>
              <a href="profile.php" class="login-btn">Profile</a>
            <?php else: ?>
              <a href="login.php" class="login-btn">Login</a>
            <?php endif; ?>
          </div>
        </nav>
      </div>
    </header>

    <section class="search-hero">
      <div class="container">
        <h1 style="color: white; font-size: 48px; margin-bottom: 10px;">Find Your Next Adventure</h1>
        <p style="color: #ddd; font-size: 18px;">Search thousands of destinations and packages across the globe.</p>
        <form class="search-box" action="search.php" method="get">
          <input type="text" class="search-input" name="q" value="<?= clean($q) ?>" placeholder="Where do you want to go? (e.g. Goa, Ladakh)">
          <button class="search-btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
        </form>
      </div>
    </section>

    <section class="filter-section">
      <div class="container">
        <div class="filter-tabs">
          <a href="search.php" class="filter-tab <?= $q === '' && $category === '' ? 'active' : '' ?>">All Destinations</a>
          <a href="search.php?category=Mountain" class="filter-tab <?= strtolower($category) === 'mountain' ? 'active' : '' ?>">Mountains</a>
          <a href="search.php?category=Beach" class="filter-tab <?= strtolower($category) === 'beach' ? 'active' : '' ?>">Beaches</a>
          <a href="search.php?category=Desert" class="filter-tab <?= strtolower($category) === 'desert' ? 'active' : '' ?>">Deserts</a>
          <a href="search.php?category=Forest" class="filter-tab <?= strtolower($category) === 'forest' ? 'active' : '' ?>">Forests</a>
          <a href="search.php?category=Wildlife" class="filter-tab <?= strtolower($category) === 'wildlife' ? 'active' : '' ?>">Wildlife</a>
        </div>
      </div>
    </section>

    <section class="container" style="padding: 60px 0;">
      <?php if ($q !== ''): ?>
        <h2 style="margin-bottom: 30px; font-size: 32px;">
          Search Results for "<em><?= clean($q) ?></em>" (<?= count($destinations) ?> found)
        </h2>
      <?php else: ?>
        <h2 style="margin-bottom: 30px; font-size: 32px;">All Destinations (<?= count($destinations) ?> found)</h2>
      <?php endif; ?>

      <?php if (empty($destinations)): ?>
        <div class="no-results">
          <i class="fa-solid fa-magnifying-glass" style="font-size:48px; color:#ddd; display:block; margin-bottom:20px;"></i>
          No destinations found for "<?= clean($q) ?>". <a href="search.php">View all</a>
        </div>
      <?php else: ?>
        <div class="destination-grid">
          <?php foreach ($destinations as $dest): ?>
            <?php
              // Map destination title to PHP page and image
              $slug_map = [
                  'leh ladakh'          => ['file' => 'leh-ladakh.php',   'img' => 'ladakh.jpg'],
                  'spiti valley'        => ['file' => 'spiti-valley.php', 'img' => 'spiti.jpg'],
                  'goa'                 => ['file' => 'goa.php',           'img' => 'goa.jpg'],
                  'girnar'              => ['file' => 'girnare.php',       'img' => 'girnar.jpg'],
                  'swaziland'           => ['file' => 'swaziland.php',     'img' => 'swaziland.jpg'],
                  'mussoorie'           => ['file' => 'search.php',        'img' => 'Dehradun.jpg'],
                  'kanyakumari temple'  => ['file' => 'search.php',        'img' => 'photo-1513786704796-b35842f0dca6.jpg'],
              ];
              $key  = strtolower($dest['title']);
              $file = $slug_map[$key]['file'] ?? 'search.php';
              $img  = $dest['image_url'] ?: ($dest['hero_image'] ?: 'images/' . ($slug_map[$key]['img'] ?? 'hero.jpg'));
            ?>
            <div class="card">
              <img src="<?= clean($img) ?>" alt="<?= clean($dest['title']) ?>" />
              <div class="card-content">
                <h3><?= clean($dest['title']) ?></h3>
                <p><?= clean($dest['duration']) ?></p>
                <h4>&#8377;<?= number_format($dest['price']) ?></h4>
                <?php if (!empty($dest['highlights'])): ?>
                  <p><?= clean($dest['highlights']) ?></p>
                <?php endif; ?>
                <a href="<?= clean($file) ?>" class="btn">View Details</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <footer>
      <div class="container">
        <p style="text-align:center;padding:40px 0;">&copy; 2026 Backpacker Travel. All Rights Reserved.</p>
      </div>
    </footer>
  </body>
</html>
