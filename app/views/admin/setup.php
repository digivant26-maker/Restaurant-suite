<form method="post" action="/admin/setup" class="card form" style="padding:30px" novalidate>
  <?= csrf_field() ?>
  <div class="brand"><img src="<?= brand_logo_url() ?>" alt="" width="44" height="44"><div><b><?= e(shop()['name']) ?></b><span>First-time setup</span></div></div>
  <h1 style="font-size:30px">Create the Admin account</h1>
  <p class="hint" style="margin:0">This page works only once, before anyone has an account. You need the setup key from config.php. Your Staff ID is shown after you create the account; everything else (address, hours, colours, logo, menu) is set in the admin afterwards.</p>
  <?php if (!empty($errors['form'])): ?><div class="alert bad" role="alert"><?= e($errors['form']) ?></div><?php endif ?>
  <?php foreach ([['setup_key', 'Setup key', 'password', 'off'], ['shop_name', 'Restaurant name', 'text', 'organization'], ['name', 'Your name', 'text', 'name'], ['email', 'Your email', 'email', 'email'], ['password', 'Password (10+ characters)', 'password', 'new-password']] as [$k, $l, $t, $ac]): ?>
  <div class="field"><label for="<?= $k ?>"><?= e($l) ?></label><input id="<?= $k ?>" name="<?= $k ?>" type="<?= $t ?>" autocomplete="<?= $ac ?>" value="<?= $t === 'password' ? '' : e($v[$k] ?? '') ?>"<?= faria($errors, $k) ?>><?= ferr($errors, $k) ?></div>
  <?php endforeach ?>
  <button class="btn primary">Create account</button>
</form>
