<h1>My account</h1><p class="muted"><?= e($user['name']) ?> · Staff ID <b><?= e($user['staff_code']) ?></b> · <?= e(ROLES[$user['role']] ?? '') ?></p>
<form method="post" action="/admin/account" class="card form" style="max-width:520px"><?= csrf_field() ?><h2>Change password</h2>
  <div class="field"><label for="cur">Current password</label><input id="cur" name="current" type="password" autocomplete="current-password" required></div>
  <div class="field"><label for="new">New password <span class="hint">(10+ characters)</span></label><input id="new" name="new" type="password" autocomplete="new-password" required></div>
  <button class="btn primary">Change password</button></form>
