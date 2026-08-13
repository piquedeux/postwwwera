<?php
if (!isset($content)) {
  $content = load_content();
}

$settings = [
  'site_title'    => $content['site_title'] ?? 'Olafur Mowa',
  'instagram_url' => $content['instagram_url'] ?? '',
];
?>

<footer class="site-footer">
  <p class="footer-text">&copy; <?= date('Y') ?> <?= e($settings['site_title']) ?></p>
  
  <?php if (!empty($settings['instagram_url'])): ?>
    <a href="<?= e($settings['instagram_url']) ?>" class="site-instagram" target="_blank" rel="noopener">Instagram</a>
  <?php endif; ?>
</footer>