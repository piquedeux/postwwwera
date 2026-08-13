<?php
require_once __DIR__ . '/includes/functions.php';
$content = load_content();
$active = 'music';
$releases = $content['music_releases'] ?? [];
usort($releases, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($content['nav']['music'] ?? 'Music') . ' – ' . ($content['site_title'] ?? 'Olafur Mowa')) ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body class="page-releases">

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="section-page">

  <div class="release-list">
    <?php foreach ($releases as $release):
        $thumb = trim($release['thumbnail'] ?? '');
        $thumbUrl = $thumb ? upload_url_for_file($thumb) : '';
        $links = [
            'spotify_url' => 'Spotify',
            'apple_url' => 'Apple Music',
            'youtube_url' => 'YouTube',
            'bandcamp_url' => 'Bandcamp',
            'other_url' => $release['other_url_label'] ?? 'Link',
        ];
    ?>
    <details class="release-card">
      <summary>
        <span class="release-thumb">
          <?php if ($thumbUrl): ?>
          <img src="<?= e($thumbUrl) ?>" alt="<?= e($release['title'] ?? '') ?>">
          <?php else: ?>
          <span class="media-placeholder">Release</span>
          <?php endif; ?>
        </span>
        <span class="release-summary">
          <strong><?= e($release['title'] ?? '') ?></strong>
          <?php if (!empty($release['subtitle'])): ?><span><?= e($release['subtitle']) ?></span><?php endif; ?>
        </span>
      </summary>
      <div class="release-links">
        <?php foreach ($links as $field => $label): ?>
          <?php $url = trim($release[$field] ?? ''); ?>
          <?php if ($url !== ''): ?><a href="<?= e($url) ?>" target="_blank" rel="noopener"><?= e($label) ?></a><?php endif; ?>
        <?php endforeach; ?>
      </div>
    </details>
    <?php endforeach; ?>
    <?php if (empty($releases)): ?>
    <p class="empty-state">No releases yet.</p>
    <?php endif; ?>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>