<?php $f = $edit ?? []; ?>
<h1>Events &amp; specials</h1><p class="muted">Weekly events repeat every week; dated events disappear after their day. Specials (like happy hour) show on the home page.</p>
<form method="post" action="/admin/events" class="card form" style="margin-bottom:22px"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($f['id'] ?? 0) ?>">
  <h2><?= $edit ? 'Edit ' . e($f['title']) : 'Add an event or special' ?></h2>
  <div class="grid g3">
    <div class="field"><label for="e-kind">Type</label><select id="e-kind" name="kind"><option value="event"<?= ($f['kind'] ?? '') === 'special' ? '' : ' selected' ?>>Event</option><option value="special"<?= ($f['kind'] ?? '') === 'special' ? ' selected' : '' ?>>Special</option></select></div>
    <div class="field"><label for="e-title">Title</label><input id="e-title" name="title" value="<?= e($f['title'] ?? '') ?>" placeholder="e.g. Trivia Night"></div>
    <div class="field"><label for="e-time">Time</label><input id="e-time" name="time_text" value="<?= e($f['time_text'] ?? '') ?>" placeholder="e.g. 7–9 PM"></div>
    <div class="field"><label for="e-wd">Every…</label><select id="e-wd" name="weekday"><option value="">— one date —</option><?php foreach (WEEKDAYS as $n => $l): ?><option value="<?= $n ?>"<?= (int)($f['weekday'] ?? 0) === $n ? ' selected' : '' ?>><?= $l ?></option><?php endforeach ?></select></div>
    <div class="field"><label for="e-date">…or on this date</label><input id="e-date" name="event_date" type="date" value="<?= e($f['event_date'] ?? '') ?>"></div>
    <div class="field"><label for="e-pos">Order</label><input id="e-pos" name="position" type="number" value="<?= (int)($f['position'] ?? 0) ?>"></div>
  </div>
  <div class="field"><label for="e-desc">Description</label><input id="e-desc" name="description" maxlength="400" value="<?= e($f['description'] ?? '') ?>"></div>
  <label class="check"><input type="checkbox" name="active" value="1"<?= ($f['active'] ?? 1) ? ' checked' : '' ?>>Show on the website</label>
  <div class="acts"><button class="btn primary">Save</button><?php if ($edit): ?><a class="btn ghost" href="/admin/events">Cancel</a><?php endif ?></div>
</form>
<?php foreach (['Specials' => $specials, 'Events' => $events] as $h => $list): ?>
<h2><?= $h ?></h2>
<?php if (!$list): ?><div class="empty" style="margin-bottom:20px">None yet.</div><?php else: ?>
<div class="tblwrap" style="margin-bottom:22px"><table class="tbl"><thead><tr><th>Title</th><th>When</th><th>Shown</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($list as $ev): ?><tr><td><b><?= e($ev['title']) ?></b><div class="muted"><?= e($ev['description']) ?></div></td><td><?= e(event_when($ev)) ?></td><td><?= $ev['active'] ? 'Yes' : 'No' ?></td>
<td><div class="acts"><a class="btn ghost sm" href="/admin/events?edit=<?= (int)$ev['id'] ?>">Edit</a><form method="post" action="/admin/events" data-confirm="Delete <?= e($ev['title']) ?>?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$ev['id'] ?>"><button class="btn danger sm">Delete</button></form></div></td></tr><?php endforeach ?>
</tbody></table></div><?php endif ?>
<?php endforeach ?>
