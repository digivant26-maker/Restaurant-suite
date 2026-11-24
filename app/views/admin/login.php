<form method="post" action="/admin/login" class="card form" style="padding:30px">
  <?= csrf_field() ?>
  <div class="brand"><img src="<?= brand_logo_url() ?>" alt="" width="44" height="44"><div><b><?= e(shop()['name']) ?></b><span>Staff sign-in</span></div></div>
  <h1 style="font-size:30px">Sign in</h1>
  <?php if ($error): ?><div class="alert bad" role="alert"><?= e($error) ?></div><?php endif ?>
  <div class="field"><label for="staff_id">Staff ID</label><input id="staff_id" name="staff_id" autocomplete="username" autocapitalize="characters" spellcheck="false" placeholder="e.g. <?= e(staff_code(1)) ?>" value="<?= e($code) ?>" required></div>
  <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
  <button class="btn primary">Sign in</button>
  <p class="hint" style="margin:0">Forgot your password? Ask the Admin to set a new one under Staff.</p>
</form>
