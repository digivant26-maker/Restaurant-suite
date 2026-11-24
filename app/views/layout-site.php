<?php $S = shop(); $fl = take_flash_if_session(); $cartN = cart_count_if_session(); $p = path();
$nav = ['/menu' => 'Menu', '/events' => 'Events', '/games' => 'Games', '/private-events' => 'Private events', '/gallery' => 'Gallery', '/contact' => 'Contact'];
$pageTitle = $title ? "$title | {$S['name']}" : "{$S['name']} — {$S['tagline']}"; ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title><meta name="description" content="<?= e($desc ?? ($S['tagline'] . ' in ' . $S['city'] . '. ' . $S['headline'])) ?>">
<?php if (!empty($noindex)): ?><meta name="robots" content="noindex"><?php endif ?>
<link rel="icon" href="<?= brand_logo_url() ?>"><link rel="stylesheet" href="<?= asset('css/site.css') ?>"><link rel="stylesheet" href="<?= brand_css_url() ?>">
<script type="application/ld+json"><?= json_encode(['@context' => 'https://schema.org', '@type' => 'BarOrPub', 'name' => $S['name'], 'telephone' => $S['phone_office'], 'url' => site_url(), 'logo' => site_url() . brand_logo_url(),
  'address' => ['@type' => 'PostalAddress', 'streetAddress' => $S['street'], 'addressLocality' => $S['city'], 'addressRegion' => $S['region'], 'postalCode' => $S['zip']], 'servesCuisine' => 'American', 'acceptsReservations' => 'True', 'menu' => site_url() . '/menu'], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
</head><body>
<?php if (tenant_id()) require ROOT . '/app/views/demo/banner.php'; ?>
<a class="skip" href="#main">Skip to content</a>
<header class="top"><div class="wrap">
  <a class="brand" href="/"><img src="<?= brand_logo_url() ?>" alt="" width="48" height="48"><span><b><?= e($S['name']) ?></b><span><?= e($S['tagline']) ?></span></span></a>
  <button class="btn ghost sm navtoggle" type="button" aria-expanded="false" aria-controls="nav" data-navtoggle>Menu</button>
  <nav class="nav" id="nav" aria-label="Main">
    <?php foreach ($nav as $h => $l): ?><a href="<?= $h ?>"<?= cur($h) ?>><?= e($l) ?></a><?php endforeach ?>
    <a class="btn ghost sm" href="/reservations"<?= cur('/reservations') ?>>Reserve</a>
    <a class="btn primary sm cartbtn" href="<?= $cartN ? '/order' : '/menu' ?>">Order online<?php if ($cartN): ?><span class="n" aria-label="<?= $cartN ?> in your order"><?= $cartN ?></span><?php endif ?></a>
  </nav>
</div></header>
<main id="main">
<?php if ($fl): ?><div class="wrap" style="margin-top:18px"><div class="alert <?= $fl[1] === 'bad' ? 'bad' : 'ok' ?>" role="<?= $fl[1] === 'bad' ? 'alert' : 'status' ?>"><?= e($fl[0]) ?></div></div><?php endif ?>
<?= $content ?>
</main>
<footer class="foot"><div class="wrap">
  <div class="grid">
    <div><h3><?= e($S['name']) ?></h3><p style="margin:0 0 10px"><?= e($S['about']) ?></p>
      <div class="social"><?php foreach (['facebook_url' => 'Facebook', 'instagram_url' => 'Instagram', 'tiktok_url' => 'TikTok'] as $k => $l) if ($S[$k]): ?><a href="<?= e($S[$k]) ?>" rel="noopener" target="_blank"><?= $l ?></a><?php endif ?></div></div>
    <div><h3>Visit</h3><p style="margin:0"><a href="<?= e($S['maps_url']) ?>" target="_blank" rel="noopener"><?= e($S['street']) ?><br><?= e("{$S['city']}, {$S['region']} {$S['zip']}") ?></a></p>
      <p style="margin:10px 0 0"><a href="tel:<?= e($S['phone_office_tel']) ?>"><?= e($S['phone_office']) ?></a><br><a href="mailto:<?= e($S['email']) ?>"><?= e($S['email']) ?></a></p></div>
    <div><h3>Hours</h3><p style="margin:0"><?= e($S['hours_text']) ?></p><p style="margin:8px 0 0"><?= e($S['kitchen_text']) ?></p>
      <ul style="margin-top:14px"><li><a href="/careers">Careers</a></li><li><a href="/privacy">Privacy</a></li></ul></div>
  </div>
  <div class="legal"><span>© <?= date('Y') ?> <?= e($S['name']) ?></span><span><a href="/admin/login">Staff sign-in</a></span></div>
</div></footer>
<script src="<?= asset('js/site.js') ?>" defer></script>
</body></html>
