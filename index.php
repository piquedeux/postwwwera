<?php
require_once __DIR__ . '/includes/functions.php';
$content = load_content();
$active = 'home';
$galleryItems = $content['gallery_items'] ?? [];
usort($galleryItems, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
$releases = $content['music_releases'] ?? [];
usort($releases, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

$previews = [];
foreach (array_slice(array_reverse($releases), 0, 2) as $release) {
  $thumb = trim($release['thumbnail'] ?? '');
  if ($thumb === '') {
    continue;
  }
  $previews[] = [
    'kind' => 'music',
    'src' => upload_url_for_file($thumb),
    'title' => trim($release['title'] ?? ''),
    'subtitle' => trim($release['subtitle'] ?? ''),
    'href' => 'music.php',
  ];
}

foreach (array_slice(array_reverse($galleryItems), 0, 2) as $item) {
  $file = trim($item['file'] ?? '');
  if ($file === '') {
    continue;
  }
  $previews[] = [
    'kind' => 'gallery',
    'src' => upload_url_for_file($file),
    'type' => $item['type'] ?? 'image',
    'title' => trim($item['title'] ?? ''),
    'subtitle' => trim($item['caption'] ?? ''),
    'href' => 'gallery.php',
  ];
}

$previewSizes = [
  ['width' => '18rem', 'ratio' => '1.18'],
  ['width' => '14rem', 'ratio' => '0.78'],
  ['width' => '16rem', 'ratio' => '1.34'],
  ['width' => '12.5rem', 'ratio' => '0.92'],
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($content['site_title'] ?? 'Olafur Mowa') ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body class="page-home">

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="home-layout">

  <section class="home-previews" aria-label="Selected previews">
    <?php foreach ($previews as $index => $preview):
        $size = $previewSizes[$index % count($previewSizes)];
        $showText = !empty($preview['subtitle']);
    ?>
    <a class="preview-card" href="<?= e($preview['href']) ?>" style="--preview-width: <?= e($size['width']) ?>; --preview-ratio: <?= e($size['ratio']) ?>;">
      <span class="preview-media">
        <?php if (($preview['type'] ?? 'image') === 'video'): ?>
        <video src="<?= e($preview['src']) ?>" muted loop playsinline preload="metadata"></video>
        <?php else: ?>
        <img src="<?= e($preview['src']) ?>" alt="<?= e($preview['title']) ?>">
        <?php endif; ?>
      </span>
      <?php if ($showText && $preview['title'] !== ''): ?><span class="preview-title"><?= e($preview['title']) ?></span><?php endif; ?>
      <?php if ($showText): ?><span class="preview-caption"><?= e($preview['subtitle']) ?></span><?php endif; ?>
    </a>
    <?php endforeach; ?>
    <?php if (empty($previews)): ?>
    <p class="empty-state">Add previews in the admin panel.</p>
    <?php endif; ?>
  </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
