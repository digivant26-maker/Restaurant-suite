<?php /** Demo builder: the visitor enters their shop details and sees a live preview before creating the demo. */ ?>
<div class="demo-build">
  <section class="card demo-form">
    <p class="eyebrow">Live demo · free · <?= demo_hours() ?> hours</p>
    <h1>See your restaurant’s website and ordering system</h1>
    <p class="hint">Enter your restaurant’s name and colour, add a logo if you like, and get a working demo in seconds: a public website with your menu, online pickup ordering, table reservations, events and the games on your screens — plus the staff side with a kitchen screen. Filled with fictional sample data.</p>
    <?php if (!empty($_GET['expired'])): ?><div class="alert warn" role="status">That demo has ended and was deleted. You can build a new one below.</div><?php endif ?>
    <?php if (!empty($_GET['ended'])): ?><div class="alert ok" role="status">Your demo was deleted.</div><?php endif ?>
    <?php if ($current): ?>
      <div class="alert ok" role="status">You already have a demo of <b><?= e($current['shop_name']) ?></b> (<?= demo_hours_left($current) ?> hours left). <a href="/">Open the website</a> · <a href="/admin/login">Admin sign-in</a>. Building a new one replaces it in this browser.</div>
    <?php endif ?>
    <?php if (!empty($errors['form'])): ?><div class="alert bad" role="alert"><?= e($errors['form']) ?></div><?php endif ?>
    <form method="post" action="/demo" enctype="multipart/form-data" class="form" novalidate id="demo-form">
      <?= csrf_field() ?>
      <div class="field"><label for="shop_name">Restaurant or bar name</label><input id="shop_name" name="shop_name" maxlength="80" required value="<?= e($v['shop_name'] ?? '') ?>" placeholder="e.g. Riverside Taphouse"<?= faria($errors, 'shop_name') ?>><?= ferr($errors, 'shop_name') ?></div>
      <div class="demo-row">
        <div class="field"><label for="brand_color">Brand colour</label><input id="brand_color" name="brand_color" type="color" value="<?= e($v['brand_color'] ?? '#D4A017') ?>"<?= faria($errors, 'brand_color') ?>><?= ferr($errors, 'brand_color') ?></div>
        <div class="field"><label for="logo">Logo <span class="hint">(optional · PNG, JPG or WebP)</span></label><input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp"></div>
      </div>
      <div class="demo-row">
        <div class="field"><label for="name">Your name</label><input id="name" name="name" autocomplete="name" required value="<?= e($v['name'] ?? '') ?>"<?= faria($errors, 'name') ?>><?= ferr($errors, 'name') ?></div>
        <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" autocomplete="email" required value="<?= e($v['email'] ?? '') ?>"<?= faria($errors, 'email') ?>><?= ferr($errors, 'email') ?></div>
      </div>
      <div class="demo-row">
        <div class="field"><label for="phone">Phone <span class="hint">(optional)</span></label><input id="phone" name="phone" type="tel" autocomplete="tel" value="<?= e($v['phone'] ?? '') ?>"></div>
        <div class="field"><label for="city">City <span class="hint">(optional)</span></label><input id="city" name="city" autocomplete="address-level2" value="<?= e($v['city'] ?? '') ?>"></div>
      </div>
      <div class="demo-hp" aria-hidden="true"><label for="website">Website</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div>
      <div class="field"><label class="check"><input type="checkbox" name="consent" value="1"<?= !empty($v['consent']) ? ' checked' : '' ?><?= faria($errors, 'consent') ?>> Email me my demo link, and DIGIVANT may contact me about it.</label><?= ferr($errors, 'consent') ?></div>
      <button class="btn primary">Build my demo</button>
      <p class="hint" style="margin:0">Your demo is private to you and deleted automatically after <?= demo_hours() ?> hours. It never sends email to anyone.</p>
    </form>
  </section>
  <aside class="demo-preview" id="demo-preview" aria-label="Preview">
    <p class="eyebrow">Preview</p>
    <div class="pv-site">
      <div class="pv-head"><span class="pv-logo" id="pv-logo"><span id="pv-ini">YS</span></span><b id="pv-name">Your Sports Bar</b></div>
      <div class="pv-hero"><span class="pv-kicker">Sports bar &amp; grill</span><span class="pv-h">Every game. Great food.</span><span class="pv-btn">Order online</span></div>
      <div class="pv-cards"><span><b>Reserve a table</b>Pick a time online</span><span><b>On our screens</b>Tonight’s games</span></div>
    </div>
    <div class="pv-admin"><span class="pv-side"></span><span class="pv-board"><i></i><i></i><i></i><i></i></span></div>
  </aside>
</div>
<script src="<?= asset('js/demo-builder.js') ?>" defer></script>
