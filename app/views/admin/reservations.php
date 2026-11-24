<?php $d = DateTimeImmutable::createFromFormat('!Y-m-d', $day, tz()); $seats = array_sum(array_map(fn($r) => in_array($r['status'], RES_HOLDING, true) ? (int)$r['party'] : 0, $rows)); ?>
<div class="sh"><div><h1>Reservations</h1><p class="muted" style="margin:0"><?= e($d->format('l, F j')) ?> · <?= $seats ?> guests booked</p></div>
  <form method="get" class="acts"><a class="btn ghost sm" href="?day=<?= $d->modify('-1 day')->format('Y-m-d') ?>">← Prev</a><label class="sr" for="rd">Day</label><input id="rd" type="date" name="day" value="<?= e($day) ?>" style="width:auto"><button class="btn ghost sm">Go</button><a class="btn ghost sm" href="?day=<?= $d->modify('+1 day')->format('Y-m-d') ?>">Next →</a><a class="btn ghost sm" href="/admin/reservations">Today</a></form></div>
<?php if ($requests): ?>
<section class="card" style="margin-bottom:20px;border-color:var(--blue)"><h2>Requests to answer (<?= count($requests) ?>)</h2>
  <div class="tblwrap"><table class="tbl"><thead><tr><th>When</th><th>Guest</th><th>Party</th><th>Notes</th><th>Answer</th></tr></thead><tbody>
  <?php foreach ($requests as $r): ?><tr><td><b><?= e(fmt_when($r['starts_at'])) ?></b></td><td><?= e($r['name']) ?><div class="muted"><?= e($r['phone']) ?></div></td><td><?= (int)$r['party'] ?></td>
  <td><?= e(implode(' · ', array_filter([$r['seating'] ? SEATING[$r['seating']] : '', $r['occasion'] ? OCCASIONS[$r['occasion']] : '', (string)$r['notes']]))) ?></td>
  <td><div class="acts"><form method="post" action="/admin/reservations/<?= (int)$r['id'] ?>"><?= csrf_field() ?><input type="hidden" name="to" value="confirmed"><input type="hidden" name="back" value="/admin/reservations?day=<?= e($day) ?>"><button class="btn ok sm">Confirm</button></form>
    <details><summary class="btn ghost sm">Decline</summary><form method="post" action="/admin/reservations/<?= (int)$r['id'] ?>" class="form" style="margin-top:8px;min-width:220px"><?= csrf_field() ?><input type="hidden" name="to" value="declined"><input type="hidden" name="back" value="/admin/reservations?day=<?= e($day) ?>"><label for="dr<?= (int)$r['id'] ?>">Reason for the guest</label><input id="dr<?= (int)$r['id'] ?>" name="reason" placeholder="e.g. Fully booked for the game"><button class="btn danger sm">Decline</button></form></details></div></td></tr><?php endforeach ?>
  </tbody></table></div></section>
<?php endif ?>
<?php if (!$rows): ?><div class="empty">No reservations on this day.</div><?php else: ?>
<div class="tblwrap" data-autorefresh><table class="tbl"><thead><tr><th>Time</th><th>Guest</th><th>Party</th><th>Seating · notes</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($rows as $r): $nx = ['requested' => [['confirmed', 'Confirm', 'ok']], 'confirmed' => [['seated', 'Seated', 'primary'], ['no_show', 'No-show', 'ghost'], ['cancelled', 'Cancel', 'danger']]][$r['status']] ?? []; ?>
<tr><td><b><?= e(fmt_time($r['starts_at'])) ?></b></td><td><?= e($r['name']) ?><div class="muted"><a href="tel:<?= e(digits($r['phone'])) ?>"><?= e($r['phone']) ?></a><?= $r['source'] === 'staff' ? ' · by phone' : '' ?></div></td><td><?= (int)$r['party'] ?></td>
<td><?= e(implode(' · ', array_filter([$r['seating'] ? SEATING[$r['seating']] : '', $r['occasion'] ? OCCASIONS[$r['occasion']] : '', (string)$r['notes']]))) ?></td><td><?= res_tag($r['status']) ?></td>
<td><div class="acts"><?php foreach ($nx as [$to, $l, $cls]): ?><form method="post" action="/admin/reservations/<?= (int)$r['id'] ?>"<?= $to === 'cancelled' ? ' data-confirm="Cancel this reservation? The guest is emailed."' : '' ?>><?= csrf_field() ?><input type="hidden" name="to" value="<?= $to ?>"><input type="hidden" name="back" value="/admin/reservations?day=<?= e($day) ?>"><button class="btn <?= $cls ?> sm"><?= $l ?></button></form><?php endforeach ?></div></td></tr>
<?php endforeach ?></tbody></table></div><?php endif ?>
<details class="box" id="add" style="margin-top:22px"<?= $errors ? ' open' : '' ?>><summary>Take a booking (phone or walk-in)</summary>
  <?php if (!empty($errors)): ?><div class="alert bad" role="alert" style="margin-top:12px"><?= e(implode(' ', $errors)) ?></div><?php endif ?>
  <form method="post" action="/admin/reservations?day=<?= e($day) ?>" class="form" style="margin-top:14px"><?= csrf_field() ?><input type="hidden" name="day" value="<?= e($day) ?>">
    <div class="grid g3">
      <div class="field"><label for="a-time">Time on <?= e($d->format('M j')) ?></label><select id="a-time" name="time"><?php foreach ($times as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach ?></select><?php if (!$times): ?><span class="hint">No times with room on this day.</span><?php endif ?></div>
      <div class="field"><label for="a-party">Party</label><input id="a-party" name="party" type="number" min="1" max="<?= (int)settings()['res_max_party'] ?>" value="<?= e($v['party'] ?? '2') ?>"></div>
      <div class="field"><label for="a-seating">Seating</label><select id="a-seating" name="seating"><?php foreach (SEATING as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach ?></select></div>
      <div class="field"><label for="a-name">Name</label><input id="a-name" name="name" value="<?= e($v['name'] ?? '') ?>"></div>
      <div class="field"><label for="a-phone">Phone</label><input id="a-phone" name="phone" type="tel" value="<?= e($v['phone'] ?? '') ?>"></div>
      <div class="field"><label for="a-email">Email <span class="hint">(optional)</span></label><input id="a-email" name="email" type="email" value="<?= e($v['email'] ?? '') ?>"></div>
    </div>
    <div class="field"><label for="a-notes">Notes</label><input id="a-notes" name="notes" value="<?= e($v['notes'] ?? '') ?>"></div>
    <button class="btn primary"<?= $times ? '' : ' disabled' ?>>Book (confirmed)</button></form></details>
