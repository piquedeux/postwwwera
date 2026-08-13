<?php
require_once __DIR__ . '/includes/functions.php';

function sort_items_by_order(array $items): array
{
    usort($items, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
    return $items;
}

function next_order(array $items): int
{
    $max = 0;
    foreach ($items as $item) {
        $max = max($max, (int)($item['order'] ?? 0));
    }
    return $max + 1;
}

function upload_file_for_kind(array $file, array $allowedKinds): array
{
    if (empty($file['name'])) {
        return ['ok' => false, 'error' => 'Bitte eine Datei auswählen.'];
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload fehlgeschlagen (Fehlercode ' . (int)$file['error'] . ').'];
    }
    if (($file['size'] ?? 0) > MAX_FILE_SIZE) {
        return ['ok' => false, 'error' => 'Datei ist zu groß (max. ' . (int)(MAX_FILE_SIZE / 1024 / 1024) . ' MB).'];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $type = media_type_for_ext($ext);
    if (!$type || !in_array($type, $allowedKinds, true)) {
        $allowedExt = [];
        if (in_array('image', $allowedKinds, true)) {
            $allowedExt = array_merge($allowedExt, ALLOWED_IMAGE_EXT);
        }
        if (in_array('video', $allowedKinds, true)) {
            $allowedExt = array_merge($allowedExt, ALLOWED_VIDEO_EXT);
        }
        if (in_array('audio', $allowedKinds, true)) {
            $allowedExt = array_merge($allowedExt, ALLOWED_AUDIO_EXT);
        }
        return ['ok' => false, 'error' => 'Dateityp nicht erlaubt. Erlaubt: ' . implode(', ', $allowedExt) . '.'];
    }
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    $id = generate_id();
    $newName = $id . '.' . $ext;
    $dest = UPLOAD_DIR . '/' . $newName;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['ok' => false, 'error' => 'Datei konnte nicht gespeichert werden. Bitte Schreibrechte für den uploads-Ordner prüfen.'];
    }
    return ['ok' => true, 'file' => $newName, 'type' => $type];
}

function delete_uploaded_file(?string $file): void
{
    if (!$file) {
        return;
    }
    $path = UPLOAD_DIR . '/' . $file;
    if (is_file($path)) {
        unlink($path);
    }
}

function find_item_index(array $items, string $id): ?int
{
    foreach ($items as $index => $item) {
        if (($item['id'] ?? '') === $id) {
            return $index;
        }
    }
    return null;
}

$error = '';
$success = '';
$content = load_content();
$messages = load_messages();

if (!is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    if (!csrf_check()) {
        $error = 'Sitzung abgelaufen. Bitte Seite neu laden und erneut versuchen.';
    } elseif (password_verify($_POST['password'] ?? '', ADMIN_PASSWORD_HASH)) {
        session_regenerate_id(true);
        $_SESSION['sz_admin'] = true;
    } else {
        $error = 'Falsches Passwort.';
    }
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] !== 'login') {
    if (!csrf_check()) {
        $error = 'Sitzung abgelaufen. Bitte Seite neu laden und erneut versuchen.';
    } else {
        $action = $_POST['action'];
        $content = load_content();

if ($action === 'save_settings') {
            $content['site_title'] = trim($_POST['site_title'] ?? '') ?: 'Olafur Mowa';
            $content['instagram_url'] = trim($_POST['instagram_url'] ?? '');
            $content['hero'] = [
                'kicker' => trim($_POST['hero_kicker'] ?? ''),
                'headline' => trim($_POST['hero_headline'] ?? ''),
                'intro' => trim($_POST['hero_intro'] ?? ''),
            ];
            $content['nav'] = [
                'music' => trim($_POST['nav_music'] ?? '') ?: 'Music',
                'gallery' => trim($_POST['nav_gallery'] ?? '') ?: 'Picture & Film',
                'demos' => trim($_POST['nav_demos'] ?? '') ?: 'Demos',
                'concerts' => trim($_POST['nav_concerts'] ?? '') ?: 'Concerts',
                'shop' => trim($_POST['nav_shop'] ?? '') ?: 'Shop',
                'message' => trim($_POST['nav_message'] ?? '') ?: 'Message',
            ];

            // Handle Navigation Icons
            $navKeys = ['music', 'gallery', 'demos', 'concerts', 'shop', 'message'];
            $navIcons = $content['nav_icons'] ?? [];

            foreach ($navKeys as $navKey) {
                // Delete icon if requested
                if (!empty($_POST['delete_nav_icon_' . $navKey])) {
                    delete_uploaded_file($navIcons[$navKey] ?? null);
                    $navIcons[$navKey] = '';
                }

                // Upload new icon if provided
                if (!empty($_FILES['nav_icon_' . $navKey]['name'])) {
                    $upload = upload_file_for_kind($_FILES['nav_icon_' . $navKey], ['image']);
                    if ($upload['ok']) {
                        delete_uploaded_file($navIcons[$navKey] ?? null);
                        $navIcons[$navKey] = $upload['file'];
                    } else {
                        $error = $upload['error'];
                    }
                }
            }
            $content['nav_icons'] = $navIcons;

            $content['shop'] = [
                'heading' => trim($_POST['shop_heading'] ?? '') ?: 'Shop',
                'iframe_url' => trim($_POST['shop_iframe_url'] ?? 'https://r2s.bigcartel.com'),
                'note' => trim($_POST['shop_note'] ?? ''),
            ];
            $content['contact'] = [
                'heading' => trim($_POST['contact_heading'] ?? '') ?: 'Message',
                'intro' => trim($_POST['contact_intro'] ?? ''),
                'button_label' => trim($_POST['contact_button_label'] ?? '') ?: 'Send message',
                'thank_you' => trim($_POST['contact_thank_you'] ?? '') ?: 'Message sent. I will get back to you soon.',
                'recipient_email' => trim($_POST['contact_recipient_email'] ?? ''),
            ];
            
            if (!$error) {
                save_content($content);
                $success = 'Einstellungen gespeichert.';
            }
        } elseif ($action === 'add_music_release') {
            $upload = upload_file_for_kind($_FILES['thumbnail'] ?? [], ['image']);
            if (!$upload['ok']) {
                $error = $upload['error'];
            } else {
                $releases = $content['music_releases'] ?? [];
            $order = (int)($_POST['order'] ?? 0);
            if ($order <= 0) {
              $order = next_order($releases);
            }
                $releases[] = [
                    'id' => generate_id(),
              'order' => $order,
                    'title' => trim($_POST['title'] ?? ''),
                    'subtitle' => trim($_POST['subtitle'] ?? ''),
                    'thumbnail' => $upload['file'],
                    'spotify_url' => trim($_POST['spotify_url'] ?? ''),
                    'apple_url' => trim($_POST['apple_url'] ?? ''),
                    'youtube_url' => trim($_POST['youtube_url'] ?? ''),
                    'bandcamp_url' => trim($_POST['bandcamp_url'] ?? ''),
                    'other_url_label' => trim($_POST['other_url_label'] ?? ''),
                    'other_url' => trim($_POST['other_url'] ?? ''),
                ];
                $content['music_releases'] = sort_items_by_order($releases);
                save_content($content);
                $success = 'Release hinzugefügt.';
            }
        } elseif ($action === 'save_music_release' && !empty($_POST['id'])) {
            $releases = $content['music_releases'] ?? [];
            $index = find_item_index($releases, (string)$_POST['id']);
            if ($index === null) {
                $error = 'Release nicht gefunden.';
            } else {
                $oldFile = $releases[$index]['thumbnail'] ?? null;
                $upload = upload_file_for_kind($_FILES['thumbnail'] ?? [], ['image']);
                if (!empty($_FILES['thumbnail']['name']) && !$upload['ok']) {
                    $error = $upload['error'];
                } else {
                    if ($upload['ok']) {
                        delete_uploaded_file($oldFile);
                        $releases[$index]['thumbnail'] = $upload['file'];
                    }
                    $releases[$index]['title'] = trim($_POST['title'] ?? '');
                    $releases[$index]['subtitle'] = trim($_POST['subtitle'] ?? '');
                    $releases[$index]['order'] = (int)($_POST['order'] ?? 0);
                    $releases[$index]['spotify_url'] = trim($_POST['spotify_url'] ?? '');
                    $releases[$index]['apple_url'] = trim($_POST['apple_url'] ?? '');
                    $releases[$index]['youtube_url'] = trim($_POST['youtube_url'] ?? '');
                    $releases[$index]['bandcamp_url'] = trim($_POST['bandcamp_url'] ?? '');
                    $releases[$index]['other_url_label'] = trim($_POST['other_url_label'] ?? '');
                    $releases[$index]['other_url'] = trim($_POST['other_url'] ?? '');
                    $content['music_releases'] = sort_items_by_order($releases);
                    save_content($content);
                    $success = 'Release gespeichert.';
                }
            }
        } elseif ($action === 'delete_music_release' && !empty($_POST['id'])) {
            $releases = $content['music_releases'] ?? [];
            $index = find_item_index($releases, (string)$_POST['id']);
            if ($index !== null) {
                delete_uploaded_file($releases[$index]['thumbnail'] ?? null);
                array_splice($releases, $index, 1);
                $content['music_releases'] = sort_items_by_order($releases);
                save_content($content);
            }
            $success = 'Release gelöscht.';
        } elseif ($action === 'add_gallery_item') {
            $upload = upload_file_for_kind($_FILES['file'] ?? [], ['image', 'video']);
            if (!$upload['ok']) {
                $error = $upload['error'];
            } else {
                $items = $content['gallery_items'] ?? [];
            $order = (int)($_POST['order'] ?? 0);
            if ($order <= 0) {
              $order = next_order($items);
            }
                $items[] = [
                    'id' => generate_id(),
              'order' => $order,
                    'type' => $upload['type'],
                    'file' => $upload['file'],
                    'title' => trim($_POST['title'] ?? ''),
                    'caption' => trim($_POST['caption'] ?? ''),
                ];
                $content['gallery_items'] = sort_items_by_order($items);
                save_content($content);
                $success = 'Galerieeintrag hinzugefügt.';
            }
        } elseif ($action === 'save_gallery_item' && !empty($_POST['id'])) {
            $items = $content['gallery_items'] ?? [];
            $index = find_item_index($items, (string)$_POST['id']);
            if ($index === null) {
                $error = 'Galerieeintrag nicht gefunden.';
            } else {
                $oldFile = $items[$index]['file'] ?? null;
                $upload = upload_file_for_kind($_FILES['file'] ?? [], ['image', 'video']);
                if (!empty($_FILES['file']['name']) && !$upload['ok']) {
                    $error = $upload['error'];
                } else {
                    if ($upload['ok']) {
                        delete_uploaded_file($oldFile);
                        $items[$index]['file'] = $upload['file'];
                        $items[$index]['type'] = $upload['type'];
                    }
                    $items[$index]['title'] = trim($_POST['title'] ?? '');
                    $items[$index]['caption'] = trim($_POST['caption'] ?? '');
                    $items[$index]['order'] = (int)($_POST['order'] ?? 0);
                    $content['gallery_items'] = sort_items_by_order($items);
                    save_content($content);
                    $success = 'Galerieeintrag gespeichert.';
                }
            }
        } elseif ($action === 'delete_gallery_item' && !empty($_POST['id'])) {
            $items = $content['gallery_items'] ?? [];
            $index = find_item_index($items, (string)$_POST['id']);
            if ($index !== null) {
                delete_uploaded_file($items[$index]['file'] ?? null);
                array_splice($items, $index, 1);
                $content['gallery_items'] = sort_items_by_order($items);
                save_content($content);
            }
            $success = 'Galerieeintrag gelöscht.';
        } elseif ($action === 'add_demo') {
            $upload = upload_file_for_kind($_FILES['file'] ?? [], ['audio']);
            if (!$upload['ok']) {
                $error = $upload['error'];
            } else {
            $thumbUpload = null;
            if (!empty($_FILES['thumbnail']['name'])) {
              $thumbUpload = upload_file_for_kind($_FILES['thumbnail'], ['image']);
              if (!$thumbUpload['ok']) {
                $error = $thumbUpload['error'];
              }
            }
            if (!$error) {
                $demos = $content['demos'] ?? [];
            $order = (int)($_POST['order'] ?? 0);
            if ($order <= 0) {
              $order = next_order($demos);
            }
              $demos[] = [
                'id' => generate_id(),
                'order' => $order,
                'title' => trim($_POST['title'] ?? ''),
                'note' => trim($_POST['note'] ?? ''),
                'file' => $upload['file'],
                'thumbnail' => $thumbUpload['file'] ?? '',
              ];
              $content['demos'] = sort_items_by_order($demos);
              save_content($content);
              $success = 'Demo hinzugefügt.';
            }
            }
        } elseif ($action === 'save_demo' && !empty($_POST['id'])) {
            $demos = $content['demos'] ?? [];
            $index = find_item_index($demos, (string)$_POST['id']);
            if ($index === null) {
                $error = 'Demo nicht gefunden.';
            } else {
                $oldFile = $demos[$index]['file'] ?? null;
            $oldThumbnail = $demos[$index]['thumbnail'] ?? null;
                $upload = upload_file_for_kind($_FILES['file'] ?? [], ['audio']);
            $thumbUpload = null;
                if (!empty($_FILES['file']['name']) && !$upload['ok']) {
                    $error = $upload['error'];
                } else {
              if (!empty($_FILES['thumbnail']['name'])) {
                $thumbUpload = upload_file_for_kind($_FILES['thumbnail'], ['image']);
                if (!$thumbUpload['ok']) {
                  $error = $thumbUpload['error'];
                }
              }
            }
            if (!$error) {
                    if ($upload['ok']) {
                        delete_uploaded_file($oldFile);
                        $demos[$index]['file'] = $upload['file'];
                    }
              if ($thumbUpload && $thumbUpload['ok']) {
                delete_uploaded_file($oldThumbnail);
                $demos[$index]['thumbnail'] = $thumbUpload['file'];
              }
                    $demos[$index]['title'] = trim($_POST['title'] ?? '');
                    $demos[$index]['note'] = trim($_POST['note'] ?? '');
                    $demos[$index]['order'] = (int)($_POST['order'] ?? 0);
                    $content['demos'] = sort_items_by_order($demos);
                    save_content($content);
                    $success = 'Demo gespeichert.';
                }
            }
        } elseif ($action === 'delete_demo' && !empty($_POST['id'])) {
            $demos = $content['demos'] ?? [];
            $index = find_item_index($demos, (string)$_POST['id']);
            if ($index !== null) {
                delete_uploaded_file($demos[$index]['file'] ?? null);
            delete_uploaded_file($demos[$index]['thumbnail'] ?? null);
                array_splice($demos, $index, 1);
                $content['demos'] = sort_items_by_order($demos);
                save_content($content);
            }
            $success = 'Demo gelöscht.';
        } elseif ($action === 'add_concert') {
            $concerts = $content['concerts'] ?? [];
          $order = (int)($_POST['order'] ?? 0);
          if ($order <= 0) {
            $order = next_order($concerts);
          }
            $concerts[] = [
                'id' => generate_id(),
            'order' => $order,
                'date' => trim($_POST['date'] ?? ''),
                'venue' => trim($_POST['venue'] ?? ''),
                'city' => trim($_POST['city'] ?? ''),
                'country' => trim($_POST['country'] ?? ''),
                'ticket_url' => trim($_POST['ticket_url'] ?? ''),
                'note' => trim($_POST['note'] ?? ''),
            ];
            $content['concerts'] = sort_items_by_order($concerts);
            save_content($content);
            $success = 'Show hinzugefügt.';
        } elseif ($action === 'save_concert' && !empty($_POST['id'])) {
            $concerts = $content['concerts'] ?? [];
            $index = find_item_index($concerts, (string)$_POST['id']);
            if ($index === null) {
                $error = 'Show nicht gefunden.';
            } else {
                $concerts[$index]['date'] = trim($_POST['date'] ?? '');
                $concerts[$index]['venue'] = trim($_POST['venue'] ?? '');
                $concerts[$index]['city'] = trim($_POST['city'] ?? '');
                $concerts[$index]['country'] = trim($_POST['country'] ?? '');
                $concerts[$index]['ticket_url'] = trim($_POST['ticket_url'] ?? '');
                $concerts[$index]['note'] = trim($_POST['note'] ?? '');
                $concerts[$index]['order'] = (int)($_POST['order'] ?? 0);
                $content['concerts'] = sort_items_by_order($concerts);
                save_content($content);
                $success = 'Show gespeichert.';
            }
        } elseif ($action === 'delete_concert' && !empty($_POST['id'])) {
            $concerts = $content['concerts'] ?? [];
            $index = find_item_index($concerts, (string)$_POST['id']);
            if ($index !== null) {
                array_splice($concerts, $index, 1);
                $content['concerts'] = sort_items_by_order($concerts);
                save_content($content);
            }
            $success = 'Show gelöscht.';
        }

        $content = load_content();
        $messages = load_messages();
    }
}

$content = load_content();
$messages = is_logged_in() ? load_messages() : [];
$releases = sort_items_by_order($content['music_releases'] ?? []);
$galleryItems = sort_items_by_order($content['gallery_items'] ?? []);
$demos = sort_items_by_order($content['demos'] ?? []);
$concerts = sort_items_by_order($content['concerts'] ?? []);
$token = csrf_token();
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin – <?= e($content['site_title'] ?? 'Olafur Mowa') ?></title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="admin.css">
</head>
<body class="admin">

<?php if (!is_logged_in()): ?>

<main class="login">
  <form method="post" class="login-card">
    <input type="hidden" name="action" value="login">
    <input type="hidden" name="csrf" value="<?= e($token) ?>">
    <label for="password">Passwort</label>
    <input type="password" id="password" name="password" autofocus required>
    <button type="submit">Anmelden</button>
    <?php if ($error): ?><p class="msg error"><?= e($error) ?></p><?php endif; ?>
  </form>
</main>

<?php else: ?>

<header class="site-header admin-header">
  <a href="index.php" target="_blank" class="site-title"><?= e($content['site_title'] ?? 'Olafur Mowa') ?></a>
  <a href="logout.php" class="logout">Abmelden</a>
</header>

<?php if ($error || $success): ?>
<div class="admin-notice <?= $error ? 'is-error' : 'is-success' ?>">
  <?= e($error ?: $success) ?>
</div>
<?php endif; ?>

<nav class="admin-nav">
  <a href="#site">Site</a>
  <a href="#music">Music</a>
  <a href="#gallery">Picture & Film</a>
  <a href="#demos">Demos</a>
  <a href="#concerts">Concerts</a>
  <a href="#shop">Shop</a>
  <a href="#messages">Messages</a>
</nav>

<main class="admin-main">

<section id="site" class="admin-section">
  <h2>Site settings</h2>
  <form method="post" enctype="multipart/form-data" class="admin-form admin-form-wide">
    <input type="hidden" name="action" value="save_settings">
    <input type="hidden" name="csrf" value="<?= e($token) ?>">
    
    <div class="form-grid">
      <label>Site title<input type="text" name="site_title" value="<?= e($content['site_title'] ?? '') ?>"></label>
      <label>Instagram URL<input type="url" name="instagram_url" value="<?= e($content['instagram_url'] ?? '') ?>"></label>

      <!-- Navigation Labels & Icons -->
      <?php 
      $navFields = [
        'music' => 'Music', 
        'gallery' => 'Picture & Film', 
        'demos' => 'Demos', 
        'concerts' => 'Concerts', 
        'shop' => 'Shop', 
        'message' => 'Message'
      ];
      foreach ($navFields as $key => $defaultLabel): 
        $currentIcon = $content['nav_icons'][$key] ?? '';
      ?>
        <label><?= $defaultLabel ?> label
          <input type="text" name="nav_<?= $key ?>" value="<?= e($content['nav'][$key] ?? '') ?>">
        </label>
        
        <label><?= $defaultLabel ?> Icon
          <input type="file" name="nav_icon_<?= $key ?>" accept="image/*">
          <?php if (!empty($currentIcon)): ?>
            <div class="icon-preview" style="display:flex; align-items:center; gap:8px; margin-top:4px;">
              <img src="<?= e(upload_url_for_file($currentIcon)) ?>" alt="" style="height: 24px; width: auto; object-fit: contain;">
              <label style="font-size: 0.85em; font-weight: normal;">
                <input type="checkbox" name="delete_nav_icon_<?= $key ?>" value="1"> Icon entfernen
              </label>
            </div>
          <?php endif; ?>
        </label>
      <?php endforeach; ?>

      <label>Hero kicker<input type="text" name="hero_kicker" value="<?= e($content['hero']['kicker'] ?? '') ?>"></label>
      <label>Hero headline<input type="text" name="hero_headline" value="<?= e($content['hero']['headline'] ?? '') ?>"></label>
      <label class="span-2">Hero intro<textarea name="hero_intro" rows="4"><?= e($content['hero']['intro'] ?? '') ?></textarea></label>
      <label>Shop heading<input type="text" name="shop_heading" value="<?= e($content['shop']['heading'] ?? '') ?>"></label>
      <label>Shop iframe URL<input type="url" name="shop_iframe_url" value="<?= e($content['shop']['iframe_url'] ?? '') ?>"></label>
      <label class="span-2">Shop note<textarea name="shop_note" rows="3"><?= e($content['shop']['note'] ?? '') ?></textarea></label>
      <label>Contact heading<input type="text" name="contact_heading" value="<?= e($content['contact']['heading'] ?? '') ?>"></label>
      <label>Contact button label<input type="text" name="contact_button_label" value="<?= e($content['contact']['button_label'] ?? '') ?>"></label>
      <label>Contact recipient email<input type="email" name="contact_recipient_email" value="<?= e($content['contact']['recipient_email'] ?? '') ?>"></label>
      <label class="span-2">Contact intro<textarea name="contact_intro" rows="3"><?= e($content['contact']['intro'] ?? '') ?></textarea></label>
      <label class="span-2">Contact thank-you text<textarea name="contact_thank_you" rows="3"><?= e($content['contact']['thank_you'] ?? '') ?></textarea></label>
    </div>
    <button type="submit">Save settings</button>
  </form>
</section>

<section id="music" class="admin-section">
  <h2>Music releases</h2>
  <form method="post" enctype="multipart/form-data" class="admin-form add-form">
    <input type="hidden" name="action" value="add_music_release">
    <input type="hidden" name="csrf" value="<?= e($token) ?>">
    <label>Title<input type="text" name="title" required></label>
    <label>Subtitle<input type="text" name="subtitle"></label>
    <label>Thumbnail<input type="file" name="thumbnail" accept="image/*" required></label>
    <label>Spotify URL<input type="url" name="spotify_url"></label>
    <label>Apple Music URL<input type="url" name="apple_url"></label>
    <label>YouTube URL<input type="url" name="youtube_url"></label>
    <label>Bandcamp URL<input type="url" name="bandcamp_url"></label>
    <label>Other label<input type="text" name="other_url_label"></label>
    <label>Other URL<input type="url" name="other_url"></label>
    <label>Order<input type="number" name="order" min="1"></label>
    <button type="submit">Add release</button>
  </form>

  <div class="admin-list">
    <?php foreach ($releases as $release): ?>
    <form method="post" enctype="multipart/form-data" class="admin-card">
      <input type="hidden" name="action" value="save_music_release">
      <input type="hidden" name="csrf" value="<?= e($token) ?>">
      <input type="hidden" name="id" value="<?= e($release['id'] ?? '') ?>">
      <div class="preview-box">
        <?php if (!empty($release['thumbnail'])): ?>
        <img src="<?= e(upload_url_for_file($release['thumbnail'])) ?>" alt="">
        <?php endif; ?>
      </div>
      <div class="form-grid">
        <label>Title<input type="text" name="title" value="<?= e($release['title'] ?? '') ?>"></label>
        <label>Subtitle<input type="text" name="subtitle" value="<?= e($release['subtitle'] ?? '') ?>"></label>
        <label>Replace thumbnail<input type="file" name="thumbnail" accept="image/*"></label>
        <label>Spotify URL<input type="url" name="spotify_url" value="<?= e($release['spotify_url'] ?? '') ?>"></label>
        <label>Apple Music URL<input type="url" name="apple_url" value="<?= e($release['apple_url'] ?? '') ?>"></label>
        <label>YouTube URL<input type="url" name="youtube_url" value="<?= e($release['youtube_url'] ?? '') ?>"></label>
        <label>Bandcamp URL<input type="url" name="bandcamp_url" value="<?= e($release['bandcamp_url'] ?? '') ?>"></label>
        <label>Other label<input type="text" name="other_url_label" value="<?= e($release['other_url_label'] ?? '') ?>"></label>
        <label>Other URL<input type="url" name="other_url" value="<?= e($release['other_url'] ?? '') ?>"></label>
        <label>Order<input type="number" name="order" min="1" value="<?= e((string)($release['order'] ?? '')) ?>"></label>
      </div>
      <div class="inline-actions">
        <button type="submit">Save</button>
      </div>
    </form>
    <form method="post" class="inline-delete-form">
      <input type="hidden" name="action" value="delete_music_release">
      <input type="hidden" name="csrf" value="<?= e($token) ?>">
      <input type="hidden" name="id" value="<?= e($release['id'] ?? '') ?>">
      <button type="submit" formnovalidate>Delete</button>
    </form>
    <?php endforeach; ?>
    <?php if (empty($releases)): ?><p class="empty-state">No releases yet.</p><?php endif; ?>
  </div>
</section>

<section id="gallery" class="admin-section">
  <h2>Picture & Film</h2>
  <form method="post" enctype="multipart/form-data" class="admin-form add-form">
    <input type="hidden" name="action" value="add_gallery_item">
    <input type="hidden" name="csrf" value="<?= e($token) ?>">
    <label>File<input type="file" name="file" accept="image/*,video/*" required></label>
    <label>Title<input type="text" name="title"></label>
    <label>Caption<input type="text" name="caption"></label>
    <label>Order<input type="number" name="order" min="1"></label>
    <button type="submit">Add media</button>
  </form>

  <div class="admin-list">
    <?php foreach ($galleryItems as $item): ?>
    <form method="post" enctype="multipart/form-data" class="admin-card">
      <input type="hidden" name="action" value="save_gallery_item">
      <input type="hidden" name="csrf" value="<?= e($token) ?>">
      <input type="hidden" name="id" value="<?= e($item['id'] ?? '') ?>">
      <div class="preview-box">
        <?php if (($item['type'] ?? '') === 'video'): ?>
        <video src="<?= e(upload_url_for_file($item['file'] ?? '')) ?>" muted loop playsinline></video>
        <?php else: ?>
        <img src="<?= e(upload_url_for_file($item['file'] ?? '')) ?>" alt="">
        <?php endif; ?>
      </div>
      <div class="form-grid">
        <label>Title<input type="text" name="title" value="<?= e($item['title'] ?? '') ?>"></label>
        <label>Caption<input type="text" name="caption" value="<?= e($item['caption'] ?? '') ?>"></label>
        <label>Replace file<input type="file" name="file" accept="image/*,video/*"></label>
        <label>Order<input type="number" name="order" min="1" value="<?= e((string)($item['order'] ?? '')) ?>"></label>
      </div>
      <div class="inline-actions">
        <button type="submit">Save</button>
      </div>
    </form>
    <form method="post" class="inline-delete-form">
      <input type="hidden" name="action" value="delete_gallery_item">
      <input type="hidden" name="csrf" value="<?= e($token) ?>">
      <input type="hidden" name="id" value="<?= e($item['id'] ?? '') ?>">
      <button type="submit" formnovalidate>Delete</button>
    </form>
    <?php endforeach; ?>
    <?php if (empty($galleryItems)): ?><p class="empty-state">No gallery items yet.</p><?php endif; ?>
  </div>
</section>

<section id="demos" class="admin-section">
  <h2>Demos</h2>
  <form method="post" enctype="multipart/form-data" class="admin-form add-form">
    <input type="hidden" name="action" value="add_demo">
    <input type="hidden" name="csrf" value="<?= e($token) ?>">
    <label>Title<input type="text" name="title" required></label>
    <label>Thumbnail<input type="file" name="thumbnail" accept="image/*"></label>
    <label>Audio file<input type="file" name="file" accept="audio/*" required></label>
    <label>Note<input type="text" name="note"></label>
    <label>Order<input type="number" name="order" min="1"></label>
    <button type="submit">Add demo</button>
  </form>

  <div class="admin-list">
    <?php foreach ($demos as $demo): ?>
    <form method="post" enctype="multipart/form-data" class="admin-card">
      <input type="hidden" name="action" value="save_demo">
      <input type="hidden" name="csrf" value="<?= e($token) ?>">
      <input type="hidden" name="id" value="<?= e($demo['id'] ?? '') ?>">
      <div class="form-grid">
        <label>Title<input type="text" name="title" value="<?= e($demo['title'] ?? '') ?>"></label>
        <label>Note<input type="text" name="note" value="<?= e($demo['note'] ?? '') ?>"></label>
        <label>Replace thumbnail<input type="file" name="thumbnail" accept="image/*"></label>
        <label>Replace audio<input type="file" name="file" accept="audio/*"></label>
        <label>Order<input type="number" name="order" min="1" value="<?= e((string)($demo['order'] ?? '')) ?>"></label>
      </div>
      <div class="inline-actions">
        <button type="submit">Save</button>
      </div>
    </form>
    <form method="post" class="inline-delete-form">
      <input type="hidden" name="action" value="delete_demo">
      <input type="hidden" name="csrf" value="<?= e($token) ?>">
      <input type="hidden" name="id" value="<?= e($demo['id'] ?? '') ?>">
      <button type="submit" formnovalidate>Delete</button>
    </form>
    <?php endforeach; ?>
    <?php if (empty($demos)): ?><p class="empty-state">No demos yet.</p><?php endif; ?>
  </div>
</section>

<section id="concerts" class="admin-section">
  <h2>Concerts</h2>
  <form method="post" class="admin-form add-form">
    <input type="hidden" name="action" value="add_concert">
    <input type="hidden" name="csrf" value="<?= e($token) ?>">
    <label>Date<input type="text" name="date" placeholder="2026-10-10"></label>
    <label>Venue<input type="text" name="venue" required></label>
    <label>City<input type="text" name="city"></label>
    <label>Country<input type="text" name="country"></label>
    <label>Ticket URL<input type="url" name="ticket_url"></label>
    <label>Note<input type="text" name="note"></label>
    <label>Order<input type="number" name="order" min="1"></label>
    <button type="submit">Add show</button>
  </form>

  <div class="admin-list">
    <?php foreach ($concerts as $concert): ?>
    <form method="post" class="admin-card">
      <input type="hidden" name="action" value="save_concert">
      <input type="hidden" name="csrf" value="<?= e($token) ?>">
      <input type="hidden" name="id" value="<?= e($concert['id'] ?? '') ?>">
      <div class="form-grid">
        <label>Date<input type="text" name="date" value="<?= e($concert['date'] ?? '') ?>"></label>
        <label>Venue<input type="text" name="venue" value="<?= e($concert['venue'] ?? '') ?>"></label>
        <label>City<input type="text" name="city" value="<?= e($concert['city'] ?? '') ?>"></label>
        <label>Country<input type="text" name="country" value="<?= e($concert['country'] ?? '') ?>"></label>
        <label>Ticket URL<input type="url" name="ticket_url" value="<?= e($concert['ticket_url'] ?? '') ?>"></label>
        <label>Note<input type="text" name="note" value="<?= e($concert['note'] ?? '') ?>"></label>
        <label>Order<input type="number" name="order" min="1" value="<?= e((string)($concert['order'] ?? '')) ?>"></label>
      </div>
      <div class="inline-actions">
        <button type="submit">Save</button>
      </div>
    </form>
    <form method="post" class="inline-delete-form">
      <input type="hidden" name="action" value="delete_concert">
      <input type="hidden" name="csrf" value="<?= e($token) ?>">
      <input type="hidden" name="id" value="<?= e($concert['id'] ?? '') ?>">
      <button type="submit" formnovalidate>Delete</button>
    </form>
    <?php endforeach; ?>
    <?php if (empty($concerts)): ?><p class="empty-state">No concerts yet.</p><?php endif; ?>
  </div>
</section>

<section id="shop" class="admin-section">
  <h2>Shop</h2>
  <p class="hint">Edit the shop iframe URL and the short note in the Site section above.</p>
</section>

<section id="messages" class="admin-section">
  <h2>Messages</h2>
  <?php if (empty($messages)): ?>
    <p class="empty-state">No messages yet.</p>
  <?php else: ?>
    <div class="message-list">
      <?php foreach (array_reverse($messages) as $message): ?>
      <article class="message-card">
        <div class="message-meta">
          <strong><?= e($message['name'] ?? '') ?></strong>
          <span><?= e($message['email'] ?? '') ?></span>
          <time><?= e($message['created_at'] ?? '') ?></time>
        </div>
        <p><?= nl2br(e($message['message'] ?? '')) ?></p>
      </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

</main>

  <div class="site-copyright"><a href="https://piquedeux.de" target="_blank" rel="noopener">Click for help ©mg</a></div>

<?php endif; ?>

</body>
</html>
