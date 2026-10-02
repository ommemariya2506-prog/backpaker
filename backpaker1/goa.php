<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

// Find destination in DB
$stmt = $pdo->prepare("SELECT * FROM destinations WHERE LOWER(title) = 'goa' LIMIT 1");
$stmt->execute();
$dest = $stmt->fetch();
$dest_id = $dest['id'] ?? 0;

// Handle review submission
$review_error   = flash('review_error');
$review_success = flash('review_success');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $dest_id > 0) {
    if (!is_logged_in()) {
        flash('review_error', 'You must be logged in to leave a review.');
        redirect('goa.php');
    }
    $rating  = min(5, max(1, (int)($_POST['rating'] ?? 5)));
    $comment = trim($_POST['comment'] ?? '');
    if ($comment === '') {
        flash('review_error', 'Please write a comment.');
        redirect('goa.php');
    }
    $ins = $pdo->prepare(
        'INSERT INTO reviews (user_id, destination_id, rating, comment) VALUES (?, ?, ?, ?)'
    );
    $ins->execute([$_SESSION['user_id'], $dest_id, $rating, $comment]);
    flash('review_success', 'Thank you! Your review has been posted.');
    redirect('goa.php');
}

// Fetch reviews from DB
$reviews = [];
if ($dest_id > 0) {
    $rs = $pdo->prepare(
        'SELECT r.*, u.name FROM reviews r JOIN users u ON r.user_id = u.id
         WHERE r.destination_id = ? ORDER BY r.created_at DESC'
    );
    $rs->execute([$dest_id]);
    $reviews = $rs->fetchAll();
}
$avg_rating = count($reviews) ? round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1) : 4.7;

$destinationTitle = $dest['title'] ?? 'Goa';
$destinationSubtitle = $dest['description'] ?: 'Sun, sand, nightlife and scenic beaches — the perfect coastal getaway.';
$destinationOverview = $dest['overview'] ?: 'Relax on golden beaches, try thrilling water sports, and enjoy vibrant local cuisine and nightlife. Goa offers the perfect balance of relaxation and adventure. Experience the breathtaking sunsets at Palolem beach, explore the historic churches of Old Goa, and dance the night away in Baga. Our 4-day escape is designed to give you the ultimate coastal retreat.';
$destinationLocation = $dest['location'] ?: 'North and South Goa';
$destinationBestTime = $dest['best_time'] ?: 'November to March';
$destinationDifficulty = $dest['difficulty'] ?: 'Easy';
$destinationCategory = $dest['category'] ?: 'Beach';
$destinationItinerary = $dest['itinerary'] ?: "Day 1: Arrival and beach walk\nDay 2: Water sports and nightlife\nDay 3: Old Goa sightseeing\nDay 4: Sunset cruise and departure";
$destinationInclusions = $dest['inclusions'] ?: 'Hotel, breakfast, transfers, beach activity, guided city tour';
$destinationHeroImage = !empty($dest['hero_image']) ? $dest['hero_image'] : (!empty($dest['image_url']) ? $dest['image_url'] : 'images/goa.jpg');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Goa - Backpacker Travel</title>
    <link rel="stylesheet" href="Style.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <style>
      .destination-hero { background: url('<?= clean($destinationHeroImage) ?>') center/cover; }
      .trip-details { padding: 80px 0; background: #fff; }
      .trip-details h2 { font-size: 36px; color: #222; margin-bottom: 20px; }
      .trip-details p { font-size: 18px; color: #555; line-height: 1.8; margin-bottom: 30px; }
      .trip-info-box { display: flex; gap: 30px; margin-bottom: 40px; }
      .info-item { background: #f9f9f9; padding: 20px 30px; border-radius: 15px; flex: 1; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
      .info-item h4 { font-size: 20px; color: #007bff; margin-bottom: 5px; }
      .info-item p { margin: 0; font-size: 16px; }
      .alert-success { background: #f0fff4; border: 1px solid #b3ffd0; color: #1e7e34; padding: 10px 15px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
      .alert-error { background: #fff0f0; border: 1px solid #ffb3b3; color: #c0392b; padding: 10px 15px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
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
          <div style="display:flex; align-items:center; gap:20px;">
            <?php if (is_logged_in()): ?>
              <a href="profile.php" class="login-btn"><i class="fa-regular fa-user" style="margin-right:8px;"></i>Profile</a>
            <?php else: ?>
              <a href="login.php" class="login-btn"><i class="fa-regular fa-user" style="margin-right:8px;"></i>Login</a>
            <?php endif; ?>
          </div>
        </nav>
      </div>
    </header>

    <section class="hero destination-hero">
      <div class="container">
        <div class="hero-content">
          <h1><?= clean($destinationTitle) ?></h1>
          <p><?= clean($destinationSubtitle) ?></p>
          <a href="#booking" class="btn">Book This Trip</a>
        </div>
      </div>
      <div class="booking-card glass">
        <p>Featured Package</p>
        <h2><?= clean($destinationTitle) ?> Weekend Escape</h2>
        <p><?= clean($dest['duration'] ?: '4 Days / 3 Nights') ?></p>
        <div class="price">&#8377;<?= number_format((float) ($dest['price'] ?? 9999), 0) ?></div>
        <button onclick="document.getElementById('booking').scrollIntoView();">Book Now</button>
      </div>
    </section>

    <section class="trip-details" id="booking">
      <div class="container">
        <h2>Trip Overview</h2>
        <div class="trip-info-box">
          <div class="info-item">
            <h4><i class="fa-regular fa-clock"></i> Duration</h4>
            <p><?= clean($dest['duration'] ?: '4 Days / 3 Nights') ?></p>
          </div>
          <div class="info-item">
            <h4><i class="fa-solid fa-location-dot"></i> Location</h4>
            <p><?= clean($destinationLocation) ?></p>
          </div>
          <div class="info-item">
            <h4><i class="fa-solid fa-indian-rupee-sign"></i> Price</h4>
            <p>&#8377;<?= number_format((float) ($dest['price'] ?? 9999), 0) ?></p>
          </div>
          <div class="info-item">
            <h4><i class="fa-solid fa-signal"></i> Difficulty</h4>
            <p><?= clean($destinationDifficulty) ?></p>
          </div>
        </div>
        <p><?= clean($destinationOverview) ?></p>
        <div style="margin: 24px 0 30px; background: #f7faff; padding: 20px 24px; border-radius: 16px; border: 1px solid #e4edff;">
          <strong style="display: block; margin-bottom: 8px; color: #0f172a;">Category:</strong>
          <span style="color:#475467;"><?= clean($destinationCategory) ?></span>
        </div>
        <div style="margin: 24px 0 30px; background: #f7faff; padding: 20px 24px; border-radius: 16px; border: 1px solid #e4edff;">
          <strong style="display: block; margin-bottom: 8px; color: #0f172a;">Best Time:</strong>
          <span style="color:#475467;"><?= clean($destinationBestTime) ?></span>
        </div>
        <div style="margin: 24px 0 30px; display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
          <div style="background:#fff; border:1px solid #e7edf9; border-radius:16px; padding:24px;">
            <h3 style="margin-top:0; margin-bottom:12px;">Itinerary</h3>
            <div style="white-space:pre-line; color:#475467; line-height:1.8;">
              <?= clean($destinationItinerary) ?>
            </div>
          </div>
          <div style="background:#fff; border:1px solid #e7edf9; border-radius:16px; padding:24px;">
            <h3 style="margin-top:0; margin-bottom:12px;">Inclusions</h3>
            <div style="white-space:pre-line; color:#475467; line-height:1.8;">
              <?= clean($destinationInclusions) ?>
            </div>
          </div>
        </div>
        <a href="index.php" class="btn">Back to Home</a>
        <a href="checkout.php<?= $dest_id ? '?dest_id=' . $dest_id : '' ?>" class="btn btn-outline" style="color: #007bff; border-color: #007bff;">Proceed to Checkout</a>
      </div>
    </section>

    <section class="trip-details" style="background: #f9f9f9;">
      <div class="container">
        <h2>Reviews &amp; Ratings</h2>
        <div style="display:flex; align-items:center; gap: 20px; margin-bottom: 30px;">
          <h1 style="font-size:48px; margin:0; color:#111;"><?= $avg_rating ?></h1>
          <div>
            <div style="color:#ffb400; font-size:18px;">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="fa-solid fa-star<?= $i > $avg_rating ? ($i - $avg_rating < 1 ? '-half-stroke' : '') : '' ?>"></i>
              <?php endfor; ?>
            </div>
            <p style="margin:0; color:#777;">Based on <?= count($reviews) ?: 342 ?> reviews</p>
          </div>
        </div>

        <?php if (empty($reviews)): ?>
          <!-- Fallback static reviews -->
          <div style="background:#fff; padding:20px; border-radius:12px; margin-bottom:15px; box-shadow:0 2px 10px rgba(0,0,0,0.03);">
            <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
              <strong style="font-size:16px;">Priya Sharma</strong>
              <div style="color:#ffb400;"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
            </div>
            <p style="margin:0; color:#555; font-size:15px; line-height:1.6;">Amazing trip! The beaches were beautiful and the nightlife in Baga was so much fun. Everything was perfectly organized.</p>
          </div>
          <div style="background:#fff; padding:20px; border-radius:12px; margin-bottom:30px; box-shadow:0 2px 10px rgba(0,0,0,0.03);">
            <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
              <strong style="font-size:16px;">Rahul Verma</strong>
              <div style="color:#ffb400;"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-regular fa-star"></i></div>
            </div>
            <p style="margin:0; color:#555; font-size:15px; line-height:1.6;">Great experience overall. The water sports were a highlight. The hotel was good but slightly far from the beach.</p>
          </div>
        <?php else: ?>
          <?php foreach ($reviews as $rev): ?>
            <div style="background:#fff; padding:20px; border-radius:12px; margin-bottom:15px; box-shadow:0 2px 10px rgba(0,0,0,0.03);">
              <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                <strong style="font-size:16px;"><?= clean($rev['name']) ?></strong>
                <div style="color:#ffb400;">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fa-<?= $i <= $rev['rating'] ? 'solid' : 'regular' ?> fa-star"></i>
                  <?php endfor; ?>
                </div>
              </div>
              <p style="margin:0; color:#555; font-size:15px; line-height:1.6;"><?= clean($rev['comment']) ?></p>
              <small style="color:#aaa;"><?= date('M j, Y', strtotime($rev['created_at'])) ?></small>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <h3 style="margin-bottom:15px;">Leave a Review</h3>
        <?php if ($review_error): ?><div class="alert-error"><?= clean($review_error) ?></div><?php endif; ?>
        <?php if ($review_success): ?><div class="alert-success"><?= clean($review_success) ?></div><?php endif; ?>

        <?php if (is_logged_in()): ?>
          <form method="post" action="goa.php" style="background:#fff; padding:30px; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.03);">
            <div style="margin-bottom:15px;">
              <label style="display:block; margin-bottom:5px; font-weight:600;">Your Rating</label>
              <div style="display:flex; gap:8px; font-size:24px; cursor:pointer;" id="star-row">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <i class="fa-solid fa-star" data-val="<?= $i ?>" style="color:#ddd;" onclick="setRating(<?= $i ?>)"></i>
                <?php endfor; ?>
              </div>
              <input type="hidden" name="rating" id="rating_val" value="5">
            </div>
            <div style="margin-bottom:15px;">
              <label style="display:block; margin-bottom:5px; font-weight:600;">Comment</label>
              <textarea name="comment" style="width:100%; padding:15px; border:1px solid #ddd; border-radius:8px; font-family:inherit; box-sizing:border-box;" rows="4" placeholder="Share your experience..." required></textarea>
            </div>
            <button type="submit" class="btn" style="border:none; cursor:pointer;">Submit Review</button>
          </form>
        <?php else: ?>
          <p style="background:#fff; padding:20px; border-radius:12px;">
            <a href="login.php">Login</a> to leave a review.
          </p>
        <?php endif; ?>
      </div>
    </section>

    <footer>
      <div class="container">
        <p style="text-align:center;padding:40px 0;">&copy; 2026 Backpacker Travel. All Rights Reserved.</p>
      </div>
    </footer>
    <script>
      function setRating(val) {
        document.getElementById('rating_val').value = val;
        document.querySelectorAll('#star-row i').forEach((el, i) => {
          el.style.color = i < val ? '#ffb400' : '#ddd';
        });
      }
      setRating(5);
    </script>
  </body>
</html>
