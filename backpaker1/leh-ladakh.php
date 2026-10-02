<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

$stmt = $pdo->prepare("SELECT * FROM destinations WHERE LOWER(title) = 'leh ladakh' LIMIT 1");
$stmt->execute();
$dest = $stmt->fetch();
$dest_id = $dest['id'] ?? 0;

$review_error   = flash('review_error');
$review_success = flash('review_success');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $dest_id > 0) {
    if (!is_logged_in()) {
        flash('review_error', 'You must be logged in to leave a review.');
        redirect('leh-ladakh.php');
    }
    $rating  = min(5, max(1, (int)($_POST['rating'] ?? 5)));
    $comment = trim($_POST['comment'] ?? '');
    if ($comment === '') { flash('review_error', 'Please write a comment.'); redirect('leh-ladakh.php'); }
    $pdo->prepare('INSERT INTO reviews (user_id, destination_id, rating, comment) VALUES (?, ?, ?, ?)')
        ->execute([$_SESSION['user_id'], $dest_id, $rating, $comment]);
    flash('review_success', 'Thank you! Your review has been posted.');
    redirect('leh-ladakh.php');
}

$reviews = [];
if ($dest_id > 0) {
    $rs = $pdo->prepare('SELECT r.*, u.name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.destination_id = ? ORDER BY r.created_at DESC');
    $rs->execute([$dest_id]);
    $reviews = $rs->fetchAll();
}
$avg_rating = count($reviews) ? round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1) : 4.9;

$destinationTitle = $dest['title'] ?? 'Leh Ladakh';
$destinationSubtitle = $dest['description'] ?: 'Experience majestic mountains, high-altitude lakes, and unforgettable roads in the Land of High Passes.';
$destinationOverview = $dest['overview'] ?: 'Embark on a thrilling journey to Leh Ladakh, a dream destination for adventurers and nature lovers. Traverse through rugged mountain passes, witness the stunning beauty of the Pangong Tso lake changing colors, and find peace in ancient Buddhist monasteries. This 8-day package covers all the major highlights including Nubra Valley, Khardung La Pass, and Magnetic Hill, providing you with a seamless and unforgettable experience.';
$destinationLocation = $dest['location'] ?: 'Leh, Ladakh';
$destinationBestTime = $dest['best_time'] ?: 'May to September';
$destinationDifficulty = $dest['difficulty'] ?: 'Moderate';
$destinationCategory = $dest['category'] ?: 'Mountain';
$destinationItinerary = $dest['itinerary'] ?: "Day 1: Arrival and acclimatization\nDay 2: Leh sightseeing\nDay 3: Nubra Valley\nDay 4: Khardung La pass\nDay 5: Pangong Lake\nDay 6: Monastery visit\nDay 7: Local exploration\nDay 8: Departure";
$destinationInclusions = $dest['inclusions'] ?: 'Hotel stay, breakfast, local transfers, sightseeing, permits, support assistance';
$destinationHeroImage = !empty($dest['hero_image']) ? $dest['hero_image'] : (!empty($dest['image_url']) ? $dest['image_url'] : 'images/ladakh.jpg');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Leh Ladakh - Backpacker Travel</title>
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
      .alert-success { background: #f0fff4; border: 1px solid #b3ffd0; color: #1e7e34; padding: 10px 15px; border-radius: 8px; margin-bottom: 16px; }
      .alert-error { background: #fff0f0; border: 1px solid #ffb3b3; color: #c0392b; padding: 10px 15px; border-radius: 8px; margin-bottom: 16px; }
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
        <h2><?= clean($destinationTitle) ?> Adventure</h2>
        <p><?= clean($dest['duration'] ?: '8 Days / 7 Nights') ?></p>
        <div class="price">&#8377;<?= number_format((float) ($dest['price'] ?? 24999), 0) ?></div>
        <button onclick="document.getElementById('booking').scrollIntoView();">Book Now</button>
      </div>
    </section>

    <section class="trip-details" id="booking">
      <div class="container">
        <h2>Trip Overview</h2>
        <div class="trip-info-box">
          <div class="info-item"><h4><i class="fa-regular fa-clock"></i> Duration</h4><p><?= clean($dest['duration'] ?: '8 Days / 7 Nights') ?></p></div>
          <div class="info-item"><h4><i class="fa-solid fa-location-dot"></i> Location</h4><p><?= clean($destinationLocation) ?></p></div>
          <div class="info-item"><h4><i class="fa-solid fa-mountain"></i> Difficulty</h4><p><?= clean($destinationDifficulty) ?></p></div>
          <div class="info-item"><h4><i class="fa-solid fa-indian-rupee-sign"></i> Price</h4><p>&#8377;<?= number_format((float) ($dest['price'] ?? 24999), 0) ?></p></div>
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
            <div style="color:#ffb400; font-size:18px;"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
            <p style="margin:0; color:#777;">Based on <?= count($reviews) ?: 412 ?> reviews</p>
          </div>
        </div>

        <?php if (empty($reviews)): ?>
          <div style="background:#fff; padding:20px; border-radius:12px; margin-bottom:15px; box-shadow:0 2px 10px rgba(0,0,0,0.03);">
            <div style="display:flex; justify-content:space-between; margin-bottom:10px;"><strong>Neha Gupta</strong><div style="color:#ffb400;"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div></div>
            <p style="margin:0; color:#555; font-size:15px; line-height:1.6;">Pangong Lake was mesmerizing! The tour was very well-organized. A trip of a lifetime.</p>
          </div>
          <div style="background:#fff; padding:20px; border-radius:12px; margin-bottom:30px; box-shadow:0 2px 10px rgba(0,0,0,0.03);">
            <div style="display:flex; justify-content:space-between; margin-bottom:10px;"><strong>Siddharth M.</strong><div style="color:#ffb400;"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star-half-stroke"></i></div></div>
            <p style="margin:0; color:#555; font-size:15px; line-height:1.6;">Stunning views. Acclimatization was a bit tough the first day, but the guides were very helpful.</p>
          </div>
        <?php else: ?>
          <?php foreach ($reviews as $rev): ?>
            <div style="background:#fff; padding:20px; border-radius:12px; margin-bottom:15px; box-shadow:0 2px 10px rgba(0,0,0,0.03);">
              <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                <strong><?= clean($rev['name']) ?></strong>
                <div style="color:#ffb400;"><?php for ($i=1;$i<=5;$i++) echo '<i class="fa-' . ($i<=$rev['rating']?'solid':'regular') . ' fa-star"></i>'; ?></div>
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
          <form method="post" action="leh-ladakh.php" style="background:#fff; padding:30px; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.03);">
            <div style="margin-bottom:15px;">
              <label style="display:block; margin-bottom:5px; font-weight:600;">Your Rating</label>
              <div style="display:flex; gap:8px; font-size:24px;" id="star-row">
                <?php for ($i=1;$i<=5;$i++): ?><i class="fa-solid fa-star" data-val="<?= $i ?>" style="color:#ddd; cursor:pointer;" onclick="setRating(<?= $i ?>)"></i><?php endfor; ?>
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
          <p style="background:#fff; padding:20px; border-radius:12px;"><a href="login.php">Login</a> to leave a review.</p>
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
        document.querySelectorAll('#star-row i').forEach((el,i) => { el.style.color = i < val ? '#ffb400' : '#ddd'; });
      }
      setRating(5);
    </script>
  </body>
</html>
