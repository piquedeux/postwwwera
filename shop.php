<?php
require_once __DIR__ . '/includes/functions.php';
$content = load_content();
$active = 'shop';
$shop = $content['shop'] ?? [];
$iframeUrl = trim($shop['iframe_url'] ?? 'https://r2s.bigcartel.com');
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($content['nav']['shop'] ?? 'Shop') . ' – ' . ($content['site_title'] ?? 'Olafur Mowa')) ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body class="page-shop">

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="section-page shop-page">
  <section class="section-intro">
    <p class="page-subtitle"><?= e($content['nav']['shop'] ?? 'Shop') ?></p>
    <?php if (!empty($shop['note'])): ?><p><?= e($shop['note']) ?></p><?php endif; ?>
  </section>

  <div class="shop-frame-wrap">
    <iframe src="<?= e($iframeUrl) ?>" title="BigCartel shop" loading="lazy"></iframe>
  </div>
</main>

</body>
</html>