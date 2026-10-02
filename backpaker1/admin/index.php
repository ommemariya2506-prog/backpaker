<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

require_admin();

$success = flash('success');
$error = flash('error');

function remove_destination_upload(?string $imagePath): void
{
    if (!$imagePath || !preg_match('#^images/destinations/[a-f0-9]{32}\.(jpg|png|webp)$#', $imagePath)) {
        return;
    }

    $filePath = __DIR__ . '/../' . $imagePath;
    if (is_file($filePath) && !unlink($filePath)) {
        error_log('Unable to remove destination image: ' . $filePath);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete') {
        $id = (int) ($_POST['destination_id'] ?? 0);

        if ($id > 0) {
            $imageStmt = $pdo->prepare('SELECT image_url, hero_image FROM destinations WHERE id = ?');
            $imageStmt->execute([$id]);
            $images = $imageStmt->fetch();
            $stmt = $pdo->prepare('DELETE FROM destinations WHERE id = ?');
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 1) {
                if ($images) {
                    remove_destination_upload($images['image_url'] ?? null);
                    if (($images['hero_image'] ?? null) !== ($images['image_url'] ?? null)) {
                        remove_destination_upload($images['hero_image'] ?? null);
                    }
                }
                flash('success', 'Destination deleted successfully.');
            } else {
                flash('error', 'Destination not found.');
            }
        } else {
            flash('error', 'Please select a valid destination.');
        }

        redirect('index.php');
    }

    $id = (int) ($_POST['destination_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $highlights = trim($_POST['highlights'] ?? '');
    $category = trim($_POST['category'] ?? 'General');
    $price = (float) ($_POST['price'] ?? 0);
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $overview = trim($_POST['overview'] ?? '');
    $itinerary = trim($_POST['itinerary'] ?? '');
    $inclusions = trim($_POST['inclusions'] ?? '');
    $bestTime = trim($_POST['best_time'] ?? '');
    $difficulty = trim($_POST['difficulty'] ?? '');
    if ($title === '' || $price <= 0) {
        flash('error', 'Destination title and valid price are required.');
        redirect('index.php');
    }

    $existingImage = null;
    if ($id > 0) {
        $existingStmt = $pdo->prepare('SELECT image_url, hero_image FROM destinations WHERE id = ? LIMIT 1');
        $existingStmt->execute([$id]);
        $existingImage = $existingStmt->fetch();
        if (!$existingImage) {
            flash('error', 'Destination not found.');
            redirect('index.php');
        }
    }

    $hasUpload = isset($_FILES['destination_photo']) && $_FILES['destination_photo']['error'] !== UPLOAD_ERR_NO_FILE;
    $uploadedImagePath = null;
    if ($id === 0 && !$hasUpload) {
        flash('error', 'Please upload a photo for the new destination.');
        redirect('index.php');
    }

    if ($hasUpload) {
        $file = $_FILES['destination_photo'];
        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            flash('error', 'The selected image could not be uploaded. Please try again.');
            redirect('index.php' . ($id > 0 ? '?edit=' . $id : ''));
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            flash('error', 'Please choose an image smaller than 5 MB.');
            redirect('index.php' . ($id > 0 ? '?edit=' . $id : ''));
        }

        $imageInfo = getimagesize($file['tmp_name']);
        $mimeType = $imageInfo['mime'] ?? '';
        $imageWidth = (int) ($imageInfo[0] ?? 0);
        $imageHeight = (int) ($imageInfo[1] ?? 0);
        $allowedImageTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        if (!$imageInfo || !isset($allowedImageTypes[$mimeType]) || $imageWidth > 8000 || $imageHeight > 8000) {
            flash('error', 'Only valid JPG, PNG, or WebP images are allowed.');
            redirect('index.php' . ($id > 0 ? '?edit=' . $id : ''));
        }

        $uploadDir = __DIR__ . '/../images/destinations';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            flash('error', 'The destination image folder could not be created.');
            redirect('index.php' . ($id > 0 ? '?edit=' . $id : ''));
        }

        $fileName = bin2hex(random_bytes(16)) . '.' . $allowedImageTypes[$mimeType];
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $fileName)) {
            flash('error', 'The destination image could not be saved. Please try again.');
            redirect('index.php' . ($id > 0 ? '?edit=' . $id : ''));
        }
        $uploadedImagePath = 'images/destinations/' . $fileName;
    }

    $imageUrl = $uploadedImagePath
        ?? ($existingImage['image_url'] ?? 'images/ladakh.jpg');
    $heroImage = $uploadedImagePath
        ?? ($existingImage['hero_image'] ?? $imageUrl);

    if ($id > 0) {
        $stmt = $pdo->prepare(
            'UPDATE destinations SET title = ?, duration = ?, highlights = ?, category = ?, price = ?, image_url = ?, location = ?, description = ?, overview = ?, itinerary = ?, inclusions = ?, best_time = ?, difficulty = ?, hero_image = ? WHERE id = ?'
        );
        $stmt->execute([$title, $duration, $highlights, $category, $price, $imageUrl, $location, $description, $overview, $itinerary, $inclusions, $bestTime, $difficulty, $heroImage, $id]);
        if ($uploadedImagePath) {
            remove_destination_upload($existingImage['image_url'] ?? null);
            if (($existingImage['hero_image'] ?? null) !== ($existingImage['image_url'] ?? null)) {
                remove_destination_upload($existingImage['hero_image'] ?? null);
            }
        }
        flash('success', 'Destination updated successfully.');
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO destinations (title, duration, highlights, category, price, image_url, location, description, overview, itinerary, inclusions, best_time, difficulty, hero_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$title, $duration, $highlights, $category, $price, $imageUrl, $location, $description, $overview, $itinerary, $inclusions, $bestTime, $difficulty, $heroImage]);
        flash('success', 'Destination added successfully.');
    }

    redirect('index.php');
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editDestination = null;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM destinations WHERE id = ? LIMIT 1');
    $stmt->execute([$editId]);
    $editDestination = $stmt->fetch();
}

$stmt = $pdo->query('SELECT * FROM destinations ORDER BY id DESC');
$destinations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manage Packages - Backpacker Admin</title>
    <link rel="stylesheet" href="style.css" />
    <style>
      body {
        margin: 0;
        background: #f4f8ff;
        color: #142033;
        font-family: 'Poppins', sans-serif;
      }
      .grid {
        display: grid;
        grid-template-columns: minmax(300px, 0.8fr) minmax(0, 1.5fr);
        gap: 24px;
      }
      .card {
        background: #fff;
        border: 1px solid #e5edf9;
        border-radius: 24px;
        padding: 24px;
        box-shadow: 0 15px 30px rgba(18, 36, 62, 0.04);
      }
      .card h2 {
        margin-top: 0;
        margin-bottom: 18px;
        font-size: 22px;
      }
      .field { margin-bottom: 16px; }
      .field label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #1d2942;
      }
      .field input,
      .field textarea,
      .field select {
        width: 100%;
        padding: 12px 13px;
        border: 1px solid #dfe6f3;
        border-radius: 12px;
        box-sizing: border-box;
        font: inherit;
      }
      .field textarea {
        min-height: 92px;
        resize: vertical;
      }
      .table-wrap {
        overflow-x: auto;
      }
      table {
        width: 100%;
        border-collapse: collapse;
      }
      th, td {
        padding: 14px 12px;
        border-bottom: 1px solid #edf1f7;
        text-align: left;
        vertical-align: top;
      }
      th {
        color: #53627f;
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
      }
      .destination-title {
        font-weight: 700;
        color: #0f172a;
      }
      .meta {
        color: #53627f;
        font-size: 13px;
      }
      .actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
      }
      .alert-error,
      .alert-success {
        padding: 12px 14px;
        border-radius: 10px;
        margin-bottom: 18px;
        font-size: 14px;
      }
      .alert-error {
        background: #fff0f0;
        border: 1px solid #ffc4c4;
        color: #b42318;
      }
      .alert-success {
        background: #f0fff4;
        border: 1px solid #b3ffd0;
        color: #1e7e34;
      }
      @media (max-width: 1100px) {
        .grid { grid-template-columns: 1fr; }
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
            <a class="nav-link" href="revenue.php">Revenue</a>
            <a class="nav-link" href="users.php">Users</a>
            <a class="nav-link active" href="index.php" aria-current="page">Packages</a>
          </nav>
          <div class="sidebar-footer">
            <div class="admin-mini">
              <div class="brand-mark"><?= clean(strtoupper(substr((string) ($_SESSION['user_name'] ?? 'Admin'), 0, 1))) ?></div>
              <div><strong><?= clean($_SESSION['user_name'] ?? 'Admin') ?></strong><span>Admin</span></div>
            </div>
          </div>
        </aside>

        <main class="workspace">
          <header class="workspace-header">
            <div>
              <div class="page-badge">Package management</div>
              <h1>Travel Packages</h1>
            </div>
            <div class="topbar-actions">
              <a href="backpacker_admin_dashboard.php" class="btn-muted">Dashboard</a>
              <a href="#package-form" class="btn">Add Package</a>
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

      <div class="grid">
        <aside class="card">
          <h2><?= $editDestination ? 'Edit Package' : 'Add Package' ?></h2>
          <form id="package-form" method="post" action="index.php" enctype="multipart/form-data">
            <input type="hidden" name="destination_id" value="<?= (int) ($editDestination['id'] ?? 0) ?>" />
            <div class="field">
              <label for="title">Title</label>
              <input type="text" id="title" name="title" value="<?= clean($editDestination['title'] ?? '') ?>" required />
            </div>
            <div class="field">
              <label for="duration">Duration</label>
              <input type="text" id="duration" name="duration" value="<?= clean($editDestination['duration'] ?? '') ?>" placeholder="7 Days / 6 Nights" />
            </div>
            <div class="field">
              <label for="highlights">Highlights</label>
              <textarea id="highlights" name="highlights" placeholder="Mountain views, lake, monasteries"><?= clean($editDestination['highlights'] ?? '') ?></textarea>
            </div>
            <div class="field">
              <label for="category">Category</label>
              <select id="category" name="category">
                <?php $selectedCategory = clean($editDestination['category'] ?? 'General'); ?>
                <?php foreach (['General', 'Mountain', 'Beach', 'Desert', 'Forest', 'Wildlife', 'Heritage', 'Adventure'] as $cat): ?>
                  <option value="<?= clean($cat) ?>" <?= strtolower($selectedCategory) === strtolower($cat) ? 'selected' : '' ?>><?= clean($cat) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label for="location">Location</label>
              <input type="text" id="location" name="location" value="<?= clean($editDestination['location'] ?? '') ?>" placeholder="Leh, Ladakh" />
            </div>
            <div class="field">
              <label for="description">Short Description</label>
              <textarea id="description" name="description" placeholder="Short description for the destination page"><?= clean($editDestination['description'] ?? '') ?></textarea>
            </div>
            <div class="field">
              <label for="overview">Trip Overview</label>
              <textarea id="overview" name="overview" placeholder="Full destination overview text"><?= clean($editDestination['overview'] ?? '') ?></textarea>
            </div>
            <div class="field">
              <label for="itinerary">Itinerary</label>
              <textarea id="itinerary" name="itinerary" placeholder="Day-wise itinerary details"><?= clean($editDestination['itinerary'] ?? '') ?></textarea>
            </div>
            <div class="field">
              <label for="inclusions">Inclusions</label>
              <textarea id="inclusions" name="inclusions" placeholder="Stay, meals, transport, sightseeing"><?= clean($editDestination['inclusions'] ?? '') ?></textarea>
            </div>
            <div class="field">
              <label for="best_time">Best Time</label>
              <input type="text" id="best_time" name="best_time" value="<?= clean($editDestination['best_time'] ?? '') ?>" placeholder="May to September" />
            </div>
            <div class="field">
              <label for="difficulty">Difficulty</label>
              <input type="text" id="difficulty" name="difficulty" value="<?= clean($editDestination['difficulty'] ?? '') ?>" placeholder="Moderate" />
            </div>
            <div class="field">
              <label for="price">Price</label>
              <input type="number" id="price" name="price" step="0.01" min="0" value="<?= clean($editDestination['price'] ?? '0') ?>" required />
            </div>
            <div class="field">
              <label for="destination_photo">Destination Photo</label>
              <?php if (!empty($editDestination['image_url'])): ?>
                <img src="../<?= clean($editDestination['image_url']) ?>" alt="Current destination photo" style="display:block; width:100%; max-height:180px; object-fit:cover; border-radius:12px; margin-bottom:10px;">
              <?php endif; ?>
              <input type="file" id="destination_photo" name="destination_photo" accept="image/jpeg,image/png,image/webp" <?= $editDestination ? '' : 'required' ?> />
              <small class="meta">JPG, PNG, or WebP. Maximum file size: 5 MB. Leave empty when editing to keep the current photo.</small>
            </div>
            <button type="submit" class="btn"><?= $editDestination ? 'Update Destination' : 'Add Destination' ?></button>
            <?php if ($editDestination): ?>
              <a href="index.php" class="btn-muted" style="margin-left: 8px;">Cancel</a>
            <?php endif; ?>
          </form>
        </aside>

        <section class="card">
          <h2>Packages</h2>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Title</th>
                  <th>Duration</th>
                  <th>Highlights</th>
                  <th>Price</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($destinations as $destination): ?>
                  <tr>
                    <td>
                      <div class="destination-title"><?= clean($destination['title']) ?></div>
                      <?php if (!empty($destination['image_url'])): ?>
                        <img src="../<?= clean($destination['image_url']) ?>" alt="<?= clean($destination['title']) ?>" style="width:100px; height:64px; object-fit:cover; border-radius:8px; margin-top:6px;">
                      <?php endif; ?>
                    </td>
                    <td><?= clean($destination['duration'] ?? '-') ?></td>
                    <td><?= clean($destination['highlights'] ?? '-') ?></td>
                    <td>₹<?= number_format((float) $destination['price'], 2) ?></td>
                    <td>
                      <div class="actions">
                        <a href="index.php?edit=<?= (int) $destination['id'] ?>" class="btn-muted">Edit</a>
                        <form method="post" action="index.php" onsubmit="return confirm('Delete this destination?');" style="display:inline;">
                          <input type="hidden" name="action" value="delete" />
                          <input type="hidden" name="destination_id" value="<?= (int) $destination['id'] ?>" />
                          <button type="submit" class="btn-danger">Delete</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>
      </div>
        </main>
      </div>
    </div>
  </body>
</html>
