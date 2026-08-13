<?php
require_once __DIR__ . '/includes/functions.php';
$content = load_content();
$active = 'concerts';
$concerts = $content['concerts'] ?? [];
usort($concerts, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($content['nav']['concerts'] ?? 'Concerts') . ' – ' . ($content['site_title'] ?? 'Olafur Mowa')) ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body class="page-concerts">

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="section-page">

  <div class="concert-list">
    <?php foreach ($concerts as $concert): ?>
    <article class="concert-card">
      <p class="concert-date"><?= e($concert['date'] ?? '') ?></p>
      <h2><?= e($concert['venue'] ?? '') ?></h2>
      <p><?= e(trim(($concert['city'] ?? '') . ' ' . ($concert['country'] ?? ''))) ?></p>
      <?php if (!empty($concert['note'])): ?><p class="concert-note"><?= e($concert['note']) ?></p><?php endif; ?>
      <?php if (!empty($concert['ticket_url'])): ?>
      <a class="ticket-link" href="<?= e($concert['ticket_url']) ?>" target="_blank" rel="noopener">Tickets</a>
      <?php endif; ?>
    </article>
    <?php endforeach; ?>
    <?php if (empty($concerts)): ?>
    <p class="empty-state">No concerts added yet.</p>
    <?php endif; ?>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>