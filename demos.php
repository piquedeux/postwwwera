<?php
require_once __DIR__ . '/includes/functions.php';
$content = load_content();
$active = 'demos';
$demos = $content['demos'] ?? [];
usort($demos, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($content['nav']['demos'] ?? 'Demos') . ' – ' . ($content['site_title'] ?? 'Olafur Mowa')) ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body class="page-audio">

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="section-page">

  <div class="audio-list">
    <?php foreach ($demos as $demo):
        $file = trim($demo['file'] ?? '');
        $url = $file ? upload_url_for_file($file) : '';
        $thumbnail = trim($demo['thumbnail'] ?? '');
        $thumbnailUrl = $thumbnail ? upload_url_for_file($thumbnail) : '';
    ?>
    <article class="audio-card">
      <?php if ($thumbnailUrl): ?>
      <div class="audio-thumb">
        <img src="<?= e($thumbnailUrl) ?>" alt="">
      </div>
      <?php endif; ?>
      <div class="audio-copy">
        <h2><?= e($demo['title'] ?? '') ?></h2>
        <?php if (!empty($demo['note'])): ?><p><?= e($demo['note']) ?></p><?php endif; ?>
      </div>
      <?php if ($url): ?>
      <audio controls preload="metadata" src="<?= e($url) ?>"></audio>
      <?php endif; ?>
    </article>
    <?php endforeach; ?>
    <?php if (empty($demos)): ?>
    <p class="empty-state">No demos yet.</p>
    <?php endif; ?>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>