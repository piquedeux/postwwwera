<?php
if (!isset($content)) {
  $content = load_content();
}
$settings = [
  'site_title' => $content['site_title'] ?? 'Olafur Mowa',
  'instagram_url' => $content['instagram_url'] ?? '',
];
$navLabels = $content['nav'] ?? [];
$navIcons  = $content['nav_icons'] ?? [];

$navItems = [
  'music'    => ['label' => $navLabels['music'] ?? 'Music', 'href' => 'music.php', 'icon' => $navIcons['music'] ?? ''],
  'gallery'  => ['label' => $navLabels['gallery'] ?? 'Picture & Film', 'href' => 'gallery.php', 'icon' => $navIcons['gallery'] ?? ''],
  'demos'    => ['label' => $navLabels['demos'] ?? 'Demos', 'href' => 'demos.php', 'icon' => $navIcons['demos'] ?? ''],
  'concerts' => ['label' => $navLabels['concerts'] ?? 'Concerts', 'href' => 'concerts.php', 'icon' => $navIcons['concerts'] ?? ''],
  'shop'     => ['label' => $navLabels['shop'] ?? 'Shop', 'href' => 'shop.php', 'icon' => $navIcons['shop'] ?? ''],
  'message'  => ['label' => $navLabels['message'] ?? 'Message', 'href' => 'message.php', 'icon' => $navIcons['message'] ?? ''],
];
?>
<header class="site-header">

<header class="site-header">
  <div class="headline">
    <div class="headline-brand">
<img img src="/postwwwera/material/IMG_0367.GIF" alt="" class="headline-img">
  <a href="index.php" class="site-title"><?= e($settings['site_title'] ?? 'olafur mowa') ?></a>
    </div>
  <p class="site-title">post www era</p>
  </div>
  

  <nav class="site-nav" aria-label="Main navigation">
    <ul>
      <?php foreach ($navItems as $key => $item): ?>
      <li>
        <a href="<?= e($item['href']) ?>" data-nav-icon="<?= !empty($item['icon']) ? e(upload_url_for_file($item['icon'])) : '' ?>"<?= $active === $key ? ' class="active"' : '' ?>>
          <span><?= e($item['label']) ?></span>
          <?php if (!empty($item['icon'])): ?>
            <img src="<?= e(upload_url_for_file($item['icon'])) ?>" alt="" class="nav-icon">
          <?php endif; ?>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
    <div class="nav-preview" aria-hidden="true">
      <img src="" alt="" class="nav-preview-img">
    </div>
  </nav>

  <script>
    (function () {
      var nav = document.querySelector(".site-nav");
      if (!nav) return;
      var preview = nav.querySelector(".nav-preview");
      var previewImg = preview ? preview.querySelector(".nav-preview-img") : null;
      if (!preview || !previewImg) return;

      function showPreview(link) {
        var url = link.getAttribute("data-nav-icon");
        if (!url) return;
        previewImg.src = url;
        nav.classList.add("has-preview");
      }

      function hidePreview() {
        var active = nav.querySelector("a.active[data-nav-icon]");
        if (active && active.getAttribute("data-nav-icon")) {
          previewImg.src = active.getAttribute("data-nav-icon");
          nav.classList.add("has-preview");
        } else {
          nav.classList.remove("has-preview");
        }
      }

      var links = nav.querySelectorAll("a[data-nav-icon]");
      links.forEach(function (link) {
        link.addEventListener("mouseenter", function () { showPreview(link); });
        link.addEventListener("focus", function () { showPreview(link); });
      });
      nav.addEventListener("mouseleave", hidePreview);
      hidePreview();
    })();
  </script>

  <?php if (!empty($settings['hero_kicker'])): ?>
  <p class="hero-kicker"><?= e($settings['hero_kicker']) ?></p>
  <?php endif; ?>

  <?php if (!empty($settings['hero_headline'])): ?>
  <p class="hero-headline"><?= e($settings['hero_headline']) ?></p>
  <?php endif; ?>

  <?php if (!empty($settings['hero_intro'])): ?>
  <p class="hero-intro"><?= e($settings['hero_intro']) ?></p>
  <?php endif; ?>

</header>