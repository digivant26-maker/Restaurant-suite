<h1>Staff</h1><p class="muted">Everyone signs in with their Staff ID (given automatically, e.g. <?= e(staff_code(1)) ?>) and a password.</p>
<div class="grid g4" style="margin-bottom:20px"><?php foreach (ROLES as $k => $l): ?><div class="card"><b><?= e($l) ?></b><p class="hint" style="margin:6px 0 0"><?= e(ROLE_HELP[$k]) ?></p></div><?php endforeach ?></div>
<div class="tblwrap" style="margin-bottom:22px"><table class="tbl"><thead><tr><th>Person</th><th>Role &amp; access</th><th>New password</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr<?= $r['active'] ? '' : ' style="opacity:.6"' ?>>
  <td><b><?= e($r['name']) ?></b> <span class="tag"><?= e($r['staff_code']) ?></span><div class="muted"><?= e(implode(' · ', array_filter([$r['email'], $r['phone']]))) ?: 'No email' ?></div><div class="hint"><?= $r['last_login_at'] ? 'Last sign-in ' . e(to_local($r['last_login_at'])->format('M j, g:i A')) : 'Never signed in' ?></div></td>
  <td><form method="post" action="/admin/staff" class="acts"><?= csrf_field() ?><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
    <label class="sr" for="ro<?= (int)$r['id'] ?>">Role for <?= e($r['name']) ?></label><select id="ro<?= (int)$r['id'] ?>" name="role" style="width:auto"><?php foreach (ROLES as $k => $l): ?><option value="<?= $k ?>"<?= $k === $r['role'] ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach ?></select>
    <label class="check"><input type="checkbox" name="active" value="1"<?= $r['active'] ? ' checked' : '' ?>>Active</label><button class="btn ghost sm">Save</button></form></td>
  <td><form method="post" action="/admin/staff" class="acts"><?= csrf_field() ?><input type="hidden" name="action" value="reset"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><label class="sr" for="pw<?= (int)$r['id'] ?>">New password for <?= e($r['name']) ?></label><input id="pw<?= (int)$r['id'] ?>" name="password" type="text" autocomplete="off" placeholder="10+ characters" style="width:170px"><button class="btn ghost sm">Set</button></form></td>
</tr><?php endforeach ?></tbody></table></div>
<form method="post" action="/admin/staff" class="card form" autocomplete="off"><?= csrf_field() ?><input type="hidden" name="action" value="add">
  <h2>Add a staff member</h2>
  <div class="grid g3"><div class="field"><label for="s-name">Name</label><input id="s-name" name="name"></div>
    <div class="field"><label for="s-role">Role</label><select id="s-role" name="role"><?php foreach (ROLES as $k => $l): ?><option value="<?= $k ?>"<?= $k === 'server' ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach ?></select></div>
    <div class="field"><label for="s-pass">Temporary password</label><input id="s-pass" name="password" type="text" placeholder="10+ characters"></div>
    <div class="field"><label for="s-email">Email <span class="hint">(optional)</span></label><input id="s-email" name="email" type="email"></div>
    <div class="field"><label for="s-phone">Phone <span class="hint">(optional)</span></label><input id="s-phone" name="phone" type="tel"></div></div>
  <button class="btn primary">Add staff member</button><p class="hint" style="margin:0">Give them their Staff ID and temporary password in person; they change it under My account.</p></form>
