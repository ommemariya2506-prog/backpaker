<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

$homepageReviews = [];
if ($pdo) {
    $stmt = $pdo->query('SELECT r.*, u.name AS reviewer_name, d.title AS destination_title FROM reviews r JOIN users u ON u.id = r.user_id JOIN destinations d ON d.id = r.destination_id ORDER BY r.created_at DESC LIMIT 3');
    $homepageReviews = $stmt->fetchAll();
    $homepagePackages = $pdo->query('SELECT * FROM destinations ORDER BY id DESC')->fetchAll();
} else {
    $homepagePackages = [];
}
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<title>Backpacker Travel - Explore the World</title>

		<link rel="stylesheet" href="Style.css" />
		<link rel="preconnect" href="https://fonts.googleapis.com" />
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
		<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

		<style>
			/* Specific styles for the new Index Page */
			body {
				background: #fafafc;
				color: #222;
			}
			/* New Header */
			.new-header {
				background: #fff;
				padding: 20px 0;
				box-shadow: 0 4px 15px rgba(0,0,0,0.03);
				position: relative; /* override absolute from old header */
			}
			.new-header nav {
				display: flex;
				justify-content: space-between;
				align-items: center;
				gap: 20px;
			}
			.new-logo {
				font-size: 26px;
				font-weight: 700;
				color: #111;
				flex-shrink: 0;
			}
			.new-logo span { color: #0066ff; }
			.new-nav-links {
				display: flex;
				gap: 30px;
				justify-content: center;
				flex: 1;
				margin: 0 auto;
				padding: 0;
				list-style: none;
			}
			.new-nav-links a { color: #555; font-size: 15px; font-weight: 500; transition: 0.3s; }
			.new-nav-links a:hover { color: #0066ff; }
			.header-actions {
				display: flex;
				align-items: center;
				gap: 10px;
				margin-left: auto;
				flex-shrink: 0;
			}
			
			.btn-primary-blue {
				background: #0066ff;
				color: #fff;
				padding: 12px 28px;
				border-radius: 8px;
				font-weight: 600;
				transition: 0.3s;
			}
			.btn-primary-blue:hover { background: #0052cc; box-shadow: 0 8px 20px rgba(0,102,255,0.3); }

			/* MMT Inspired Hero */
			.mmt-hero {
				background: linear-gradient(to bottom, #001b4d 0%, #001b4d 50%, #fafafc 50%, #fafafc 100%);
				padding: 40px 0 80px;
				position: relative;
			}
			.mmt-widget {
				background: #fff;
				border-radius: 12px;
				box-shadow: 0 4px 20px rgba(0,0,0,0.1);
				max-width: 1200px;
				margin: 0 auto;
				padding: 20px 30px 40px;
				position: relative;
			}
			.mmt-tabs {
				display: flex;
				justify-content: center;
				gap: 40px;
				margin-bottom: 25px;
				border-bottom: 1px solid #eee;
				padding-bottom: 15px;
			}
			.mmt-tab {
				display: flex;
				flex-direction: column;
				align-items: center;
				gap: 8px;
				color: #4a4a4a;
				font-size: 14px;
				font-weight: 600;
				cursor: pointer;
				text-decoration: none;
			}
			.mmt-tab i { font-size: 24px; color: #a3a3a3; transition: 0.3s; }
			.mmt-tab:hover i, .mmt-tab.active i { color: #008cff; }
			.mmt-tab.active { color: #008cff; border-bottom: 3px solid #008cff; margin-bottom: -17px; padding-bottom: 14px; }
			
			.mmt-search-grid {
				display: grid;
				grid-template-columns: 2fr 2fr 1fr 1fr 2fr;
				border: 1px solid #e7e7e7;
				border-radius: 8px;
			}
			.mmt-input-group {
				padding: 15px 20px;
				border-right: 1px solid #e7e7e7;
				display: flex;
				flex-direction: column;
				justify-content: center;
				cursor: pointer;
			}
			.mmt-input-group:last-child { border-right: none; }
			.mmt-input-group:hover { background: #eaf5ff; }
			.mmt-label {
				font-size: 14px;
				color: #4a4a4a;
				font-weight: 600;
				display: flex;
				align-items: center;
				gap: 5px;
			}
			.mmt-label i { color: #008cff; font-size: 16px; }
			.mmt-value {
				font-size: 32px;
				font-weight: 800;
				color: #000;
				margin: 5px 0;
			}
			.mmt-sub { font-size: 14px; color: #4a4a4a; font-weight: 500; }
			
			.mmt-search-btn {
				position: absolute;
				bottom: -25px;
				left: 50%;
				transform: translateX(-50%);
				background: linear-gradient(90deg, #008cff, #005ce6);
				color: #fff;
				font-size: 20px;
				font-weight: 700;
				border: none;
				border-radius: 40px;
				padding: 15px 60px;
				cursor: pointer;
				box-shadow: 0 4px 10px rgba(0, 140, 255, 0.4);
				text-transform: uppercase;
				letter-spacing: 1px;
			}
			.mmt-search-btn:hover { background: linear-gradient(90deg, #005ce6, #003db3); }

			/* Categories */
			.section-heading { margin: 80px 0 40px; }
			.section-heading h2 { font-size: 32px; font-weight: 700; color: #111; margin-bottom: 10px; }
			.section-heading p { color: #777; font-size: 15px; }
			
			.category-row {
				display: flex;
				justify-content: space-between;
				gap: 20px;
				margin-bottom: 80px;
			}
			.cat-pill {
				flex: 1;
				display: flex;
				flex-direction: column;
				align-items: center;
				cursor: pointer;
				transition: 0.3s;
				padding: 12px 8px;
				border-radius: 24px;
			}
			.cat-pill:hover { transform: translateY(-10px); }
			.cat-pill img {
				width: 100%;
				height: 250px;
				object-fit: cover;
				border-radius: 150px; /* Pill shape */
				margin-bottom: 15px;
				box-shadow: 0 10px 20px rgba(0,0,0,0.1);
			}
			.cat-pill span {
				font-weight: 700;
				color: #111;
				font-size: 18px;
			}
			.cat-pill p {
				margin: 8px 0 0;
				text-align: center;
				color: #666;
				font-size: 14px;
				line-height: 1.6;
			}

			/* Client Review */
			.review-section {
				display: flex;
				align-items: center;
				gap: 60px;
				margin: 80px 0 120px;
			}
			.review-img {
				width: 300px;
				height: 300px;
				border-radius: 50%;
				object-fit: cover;
				border: 10px solid #e8f0fe;
			}
			.review-content {
				flex: 1;
				background: #fff;
				padding: 40px;
				border-radius: 20px;
				box-shadow: 0 10px 40px rgba(0,0,0,0.05);
				position: relative;
			}
			.review-content i { font-size: 40px; color: #0066ff; margin-bottom: 20px; }
			.review-content p { font-size: 18px; color: #555; font-style: italic; line-height: 1.8; margin-bottom: 20px; }
			.reviewer-name { font-weight: 700; color: #111; font-size: 18px; }

			/* New Footer */
			.new-footer { background: #1a1a1a; color: #fff; padding: 60px 0 30px; }
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
						<li><a href="index.php" style="color:#0066ff;">Home</a></li>
						<li><a href="search.php">Destinations</a></li>
						<li><a href="packages.php">Packages</a></li>
					</ul>
					<div class="header-actions">
						<a href="search.php" class="btn-primary-blue" aria-label="Search" style="padding:10px 14px; min-width:44px; display:inline-flex; align-items:center; justify-content:center;"><i class="fa-solid fa-magnifying-glass"></i></a>
						<?php if (is_logged_in()): ?>
							<a href="<?= is_admin() ? 'admin/backpacker_admin_dashboard.php' : 'profile.php' ?>" class="btn-primary-blue" aria-label="<?= is_admin() ? 'Admin dashboard' : 'Profile' ?>" style="padding:10px 14px; min-width:44px; display:inline-flex; align-items:center; justify-content:center;"><i class="fa-regular fa-user"></i></a>
						<?php else: ?>
							<a href="register.php" class="btn-primary-blue" aria-label="Register" style="padding:10px 14px; min-width:44px; display:inline-flex; align-items:center; justify-content:center;"><i class="fa-regular fa-user"></i></a>
						<?php endif; ?>
					</div>
				</nav>
			</div>
		</header>

		<!-- MMT Inspired Hero Section -->
		<section class="mmt-hero">
			<div class="mmt-widget">
				<div class="mmt-tabs" style="display:none;">
				</div>
				
			</div>
		</section>

		<div class="container">
			<!-- Categories -->
			<div class="section-heading">
				<h2>Categories</h2>
				<p>Here are lots of interesting destinations to visit, carefully grouped by category.</p>
			</div>
			<div class="category-row">
				<div class="cat-pill">
					<img src="images/ladakh.jpg" alt="Mountain">
					<span>Mountain</span>
					<p>High-altitude trails, glacier views, and sunrise adventures in the mighty hills.</p>
				</div>
				<div class="cat-pill">
					<img src="images/spiti.jpg" alt="Desert">
					<span>Desert</span>
					<p>Golden dunes, starry nights, and unique desert camping under open skies.</p>
				</div>
				<div class="cat-pill">
					<img src="images/goa.jpg" alt="Beach">
					<span>Beaches</span>
					<p>Relax on scenic shores, enjoy coastal sunsets, and explore vibrant beach culture.</p>
				</div>
				<div class="cat-pill">
					<img src="images/hero.jpg" alt="Forest">
					<span>Forest</span>
					<p>Fresh air, thick green canopies, nature walks, and peaceful eco-travel escapes.</p>
				</div>
				<div class="cat-pill">
					<img src="images/swaziland.jpg" alt="Wildlife">
					<span>Wildlife</span>
					<p>Safari drives, nature reserves, and unforgettable encounters with exotic animals.</p>
				</div>
			</div>

			<!-- Popular Destinations -->
			<div class="section-heading">
				<h2>Popular Destinations</h2>
				<p>Brilliant reasons Backpacker should be your one-stop-shop!</p>
			</div>
			<div class="destination-grid" style="margin-bottom: 80px;">
				<?php foreach ($homepagePackages as $package): ?>
					<?php
						$destinationPages = [
							'leh ladakh' => 'leh-ladakh.php',
							'spiti valley' => 'spiti-valley.php',
							'goa' => 'goa.php',
							'girnar' => 'girnare.php',
							'swaziland' => 'swaziland.php',
						];
						$packagePage = $destinationPages[strtolower($package['title'])] ?? 'search.php';
						$packageImage = $package['image_url'] ?: ($package['hero_image'] ?: 'images/hero.jpg');
					?>
					<div class="card" style="box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #eee;">
						<img src="<?= clean($packageImage) ?>" alt="<?= clean($package['title']) ?>" style="border-radius: 20px 20px 0 0;" />
						<div class="card-content">
							<h3><?= clean($package['title']) ?></h3>
							<p><?= clean($package['duration'] ?: 'Flexible trip') ?></p>
							<div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px;">
								<h4 style="margin:0; font-size:20px; color:#111;">&#8377;<?= number_format((float) $package['price'], 0) ?></h4>
								<a href="<?= clean($packagePage) ?>" class="btn-primary-blue" style="padding: 8px 20px; font-size: 14px;">View Package</a>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- Client Review -->
			<div class="section-heading">
				<h2>Client Review</h2>
				<p>Brilliant reasons Backpacker should be your one-stop-shop!</p>
			</div>
			<?php if (!empty($homepageReviews)): ?>
				<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:24px; margin-bottom:80px;">
					<?php foreach ($homepageReviews as $review): ?>
						<div class="review-content" style="padding:24px; margin:0; min-height:100%;">
							<i class="fa-solid fa-quote-left"></i>
							<p>"<?= clean($review['comment']) ?>"</p>
							<div class="reviewer-name"><?= clean($review['reviewer_name']) ?></div>
							<div style="color:#6b7280; font-size:14px; margin-top:4px;">
								<?= clean($review['destination_title']) ?>
							</div>
							<div style="color: #ffb400; font-size: 14px; margin-top: 8px;">
								<?php for ($i = 1; $i <= 5; $i++): ?>
									<i class="fa-solid fa-star" style="opacity: <?= $i <= (int)$review['rating'] ? 1 : 0.35; ?>;"></i>
								<?php endfor; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else: ?>
				<div class="review-section" style="margin-bottom: 40px;">
					<img src="images/hero.jpg" alt="Reviewer" class="review-img">
					<div class="review-content">
						<i class="fa-solid fa-quote-left"></i>
						<p>"Booking through Backpacker was the best decision for our trip. The process was seamless, the recommendations were spot on, and we had the most beautiful experience in the mountains. Highly recommend!"</p>
						<div class="reviewer-name">Jane Cooper</div>
						<div style="color: #ffb400; font-size: 14px; margin-top: 5px;">
							<i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<footer class="new-footer">
			<div class="container">
				<div class="footer-top">
					<div class="logo">Backpacker<span>.</span></div>
					<div class="footer-links">
						<a href="index.php">Home</a>
						<a href="packages.php">Packages</a>
					</div>
				</div>
				<div class="footer-bottom">
					<div>&copy; 2026 Backpacker Travel. All Rights Reserved.</div>
					<div style="display:flex; gap:20px;">
						<span style="display:flex; gap:10px; color:#aaa;">
							<i class="fa-brands fa-facebook"></i>
							<i class="fa-brands fa-instagram"></i>
							<i class="fa-brands fa-twitter"></i>
						</span>
					</div>
				</div>
			</div>
		</footer>
	</body>
</html>
