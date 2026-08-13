<?php
require_once __DIR__ . '/includes/functions.php';
$content = load_content();
$active = 'gallery';
$media = $content['gallery_items'] ?? [];
usort($media, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
$tileSizes = [
  ['cols' => 5, 'rows' => 2],
  ['cols' => 3, 'rows' => 3],
  ['cols' => 4, 'rows' => 2],
  ['cols' => 6, 'rows' => 3],
  ['cols' => 2, 'rows' => 2],
  ['cols' => 4, 'rows' => 2],
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($content['nav']['gallery'] ?? 'Picture & Film') . ' – ' . ($content['site_title'] ?? 'Olafur Mowa')) ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body class="page-gallery">

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="section-page">

  <?php if (empty($media)): ?>
    <p class="empty-state">No picture or film entries yet.</p>
  <?php else: ?>
  <div class="gallery-grid">
    <?php foreach ($media as $index => $item):
        $url = upload_url_for_file($item['file'] ?? '');
        $type = $item['type'] ?? 'image';
        $size = $tileSizes[$index % count($tileSizes)];
    ?>
    <button type="button" class="gallery-tile" data-gallery-tile data-type="<?= e($type) ?>" data-src="<?= e($url) ?>" style="--col-span: <?= (int)$size['cols'] ?>; --row-span: <?= (int)$size['rows'] ?>;">
      <?php if ($type === 'video'): ?>
      <video class="gallery-preview" data-gallery-preview-video src="<?= e($url) ?>" muted autoplay loop playsinline preload="auto"></video>
      <?php else: ?>
      <img class="gallery-preview" src="<?= e($url) ?>" alt="" loading="lazy">
      <?php endif; ?>
    </button>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<div class="lightbox" id="lightbox" aria-hidden="true">
  <button class="lightbox-close" id="lightboxClose" type="button">Close</button>
  <div class="lightbox-content" id="lightboxContent"></div>
</div>

<script src="script.js"></script>
</body>
</html>
