<?php
/**
 * Product page shown on the front page of DIGIVANT's demo site. Content comes from PRODUCT_PAGE (app/product.php);
 * the screenshots are real pages from a demo with fictional data.
 */
$P = PRODUCT_PAGE; $img = fn(string $f) => asset('img/product/' . $f);
$contact = (string)cfg('demo_sales_url', '') ?: 'mailto:' . (string)cfg('demo_sales_email', 'info@digivants.com'); ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($P['name']) ?> — <?= e($P['title']) ?> | DIGIVANT</title><meta name="description" content="<?= e($P['lead']) ?>">
<link rel="icon" href="<?= asset('img/digivant-mark-160.webp') ?>"><link rel="stylesheet" href="<?= asset('css/product.css') ?>">
<meta property="og:title" content="<?= e($P['name'] . ' — ' . $P['title']) ?>"><meta property="og:description" content="<?= e($P['lead']) ?>"><meta property="og:image" content="<?= e(site_url() . $img($P['hero'])) ?>">
</head><body>
<a class="skip" href="#main">Skip to content</a>
<header class="pt"><div class="wrap">
  <a class="logo" href="/" aria-label="<?= e($P['name']) ?> by DIGIVANT — home"><img src="<?= asset('img/digivant-mark-160.webp') ?>" alt="" width="36" height="36"><span><b><?= e($P['name']) ?></b><small>by DIGIVANT</small></span></a>
  <nav aria-label="Page"><a href="#features">Features</a><a href="#tour">Tour</a><a href="#how">How it works</a><a href="#faq">FAQ</a></nav>
  <a class="b b1" href="/demo">Try the free demo</a>
</div></header>
<main id="main">
<section class="hero"><div class="wrap">
  <div class="copy"><p class="eye"><?= e($P['audience']) ?></p><h1><?= e($P['h1']) ?></h1><p class="lead"><?= e($P['lead']) ?></p>
    <div class="ctas"><a class="b b1" href="/demo">Build your free demo</a><a class="b b2" href="<?= e($contact) ?>">Contact us for pricing</a></div>
    <p class="small"><?= e($P['demo_note']) ?></p></div>
  <div class="shots" aria-hidden="true"><figure class="browser"><span class="dots"><i></i><i></i><i></i></span><img src="<?= $img($P['hero']) ?>" alt="" width="1280" height="800"></figure>
    <figure class="phone"><img src="<?= $img($P['phone']) ?>" alt="" width="390" height="780"></figure></div>
</div></section>

<section class="sec" id="features"><div class="wrap">
  <p class="eye">What you get</p><h2><?= e($P['features_h']) ?></h2>
  <div class="feats"><?php foreach ($P['features'] as [$h, $t]): ?><article class="feat"><h3><?= e($h) ?></h3><p><?= e($t) ?></p></article><?php endforeach ?></div>
</div></section>

<section class="sec alt" id="tour"><div class="wrap">
  <p class="eye">A closer look</p><h2>See it in action</h2>
  <?php foreach ($P['tour'] as $i => [$h, $t, $f, $alt, $points]): ?>
  <div class="tour<?= $i % 2 ? ' flip' : '' ?>">
    <div><h3><?= e($h) ?></h3><p><?= e($t) ?></p><ul><?php foreach ($points as $pt): ?><li><?= e($pt) ?></li><?php endforeach ?></ul></div>
    <figure class="browser"><span class="dots" aria-hidden="true"><i></i><i></i><i></i></span><img src="<?= $img($f) ?>" alt="<?= e($alt) ?>" width="1280" height="800" loading="lazy"></figure>
  </div>
  <?php endforeach ?>
</div></section>

<section class="sec" id="how"><div class="wrap">
  <p class="eye">How it works</p><h2>From demo to live in three steps</h2>
  <ol class="steps"><?php foreach ($P['steps'] as $i => [$h, $t]): ?><li><span><?= $i + 1 ?></span><h3><?= e($h) ?></h3><p><?= e($t) ?></p></li><?php endforeach ?></ol>
  <div class="ctas center"><a class="b b1" href="/demo">Build your free demo</a></div>
</div></section>

<section class="sec alt" id="faq"><div class="wrap narrow">
  <p class="eye">Questions</p><h2>Frequently asked</h2>
  <?php foreach ($P['faq'] as [$q, $a]): ?><details class="qa"><summary><?= e($q) ?></summary><p><?= e($a) ?></p></details><?php endforeach ?>
</div></section>

<section class="band"><div class="wrap">
  <div><h2><?= e($P['cta_h']) ?></h2><p><?= e($P['cta_t']) ?></p></div>
  <div class="ctas"><a class="b b1" href="/demo">Build your free demo</a><a class="b b3" href="<?= e($contact) ?>">Contact us for pricing</a></div>
</div></section>
</main>
<footer class="pf"><div class="wrap">
  <a href="https://digivants.com" class="logo"><img src="<?= asset('img/digivant-logo-480.webp') ?>" alt="DIGIVANT Solutions" width="160" height="40" style="width:160px;height:auto"></a>
  <p><?= e($P['name']) ?> is a product of DIGIVANT Solutions. Screenshots show a demo with fictional data.</p>
  <p><a href="https://digivants.com">digivants.com</a> · <a href="mailto:info@digivants.com">info@digivants.com</a> · <a href="/admin/login">Staff sign-in</a></p>
</div></footer>
</body></html>
