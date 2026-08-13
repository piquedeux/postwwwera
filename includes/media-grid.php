<?php
/** Erwartet vor dem include: $media (bereits geladen und sortiert) */
?>
<?php if (empty($media)): ?>
  <p class="empty">Noch keine Arbeiten hinzugefügt.</p>
<?php else: ?>
<div class="collage">
<?php foreach ($media as $item):
    $url = UPLOAD_URL . '/' . rawurlencode($item['file']);
    $title = $item['title'] ?? '';
?>
  <figure class="tile" data-type="<?= e($item['type']) ?>" data-title="<?= e($title) ?>">
    <?php if ($item['type'] === 'video'): ?>
    <video class="thumb" src="<?= e($url) ?>" muted loop playsinline preload="metadata"></video>
    <?php else: ?>
    <img class="thumb" src="<?= e($url) ?>" loading="lazy" alt="<?= e($title) ?>">
    <?php endif; ?>
  </figure>
<?php endforeach; ?>
</div>
<?php endif; ?>
