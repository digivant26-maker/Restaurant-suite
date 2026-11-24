<?php $P = shop(); $hm = fn($t) => substr((string)$t, 0, 5);
$fld = fn(string $k, string $label, string $type = 'text', string $hint = '') => '<div class="field"><label for="p-' . $k . '">' . e($label) . '</label><input id="p-' . $k . '" name="' . $k . '" type="' . $type . '" value="' . e($P[$k]) . '"' . ($hint ? ' aria-describedby="p-' . $k . '-h"' : '') . '>' . ($hint ? '<span class="hint" id="p-' . $k . '-h">' . e($hint) . '</span>' : '') . '</div>';
$days = fn(string $name, string $csv) => '<div class="checks">' . implode('', array_map(fn($n) => '<label class="check"><input type="checkbox" name="' . $name . '[]" value="' . $n . '"' . (in_array($n, days_set($csv), true) ? ' checked' : '') . '>' . substr(WEEKDAYS[$n], 0, 3) . '</label>', array_keys(WEEKDAYS))) . '</div>'; ?>
<h1>Settings</h1>
<form method="post" action="/admin/settings" class="card form" id="profile" enctype="multipart/form-data" style="margin-bottom:22px"><?= csrf_field() ?><input type="hidden" name="action" value="profile">
  <h2>Restaurant profile &amp; branding</h2><p class="muted" style="margin:0">Shown on every page of the website and in emails.</p>
  <div class="grid g2"><?= $fld('name', 'Restaurant name') ?><?= $fld('tagline', 'Tagline', 'text', 'e.g. Sports Bar & Grill') ?></div>
  <?= $fld('headline', 'Home page headline', 'text', 'Big line at the top of the home page') ?>
  <div class="field"><label for="p-about">About (home page and footer)</label><textarea id="p-about" name="about" maxlength="600"><?= e($P['about']) ?></textarea></div>
  <div class="grid g3"><?= $fld('phone_office', 'Phone', 'tel') ?><?= $fld('email', 'Email', 'email') ?><?= $fld('street', 'Street address') ?>
    <?= $fld('city', 'City') ?><?= $fld('region', 'State') ?><?= $fld('zip', 'ZIP code') ?></div>
  <?= $fld('hours_text', 'Opening hours', 'text', 'e.g. Mon–Thu 11 AM – 12 AM · Fri–Sat 11 AM – 2 AM') ?>
  <div class="grid g2"><?= $fld('kitchen_text', 'Kitchen hours note') ?><?= $fld('happy_hour_text', 'Happy hour line', 'text', 'Shown on the home page; leave empty to hide') ?></div>
  <div class="grid g3"><?= $fld('facebook_url', 'Facebook link', 'url') ?><?= $fld('instagram_url', 'Instagram link', 'url') ?><?= $fld('tiktok_url', 'TikTok link', 'url') ?></div>
  <div class="grid g3" style="align-items:end">
    <div class="field"><label for="p-brand_color">Brand colour</label><input id="p-brand_color" name="brand_color" type="color" value="<?= e($P['brand_color']) ?>" style="width:96px;height:48px;padding:4px"><span class="hint">Buttons and highlights. Text colours adjust automatically to stay readable.</span></div>
    <div class="field"><label for="p-logo">Logo</label><div class="acts"><img src="<?= brand_logo_url() ?>" alt="Current logo" width="56" height="56" style="border-radius:10px;object-fit:contain;background:#fff;border:1px solid var(--line)"><input id="p-logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp" style="width:auto"></div><?php if ($P['logo_file']): ?><label class="check"><input type="checkbox" name="remove_logo" value="1">Remove logo (use initials)</label><?php endif ?></div>
    <?= $fld('staff_prefix', 'Staff ID prefix', 'text', 'Letters before staff numbers. Existing IDs don’t change.') ?>
  </div>
  <button class="btn primary">Save profile &amp; branding</button>
</form>
<form method="post" action="/admin/settings" class="card form" id="ordering" style="margin-bottom:22px"><?= csrf_field() ?><input type="hidden" name="action" value="ordering">
  <h2>Online ordering</h2>
  <label class="check"><input type="checkbox" name="ordering_on" value="1"<?= $s['ordering_on'] ? ' checked' : '' ?>>Take pickup orders online</label>
  <div class="grid g4"><div class="field"><label for="oo">Orders from</label><input id="oo" name="order_open" type="time" value="<?= e($hm($s['order_open'])) ?>"></div><div class="field"><label for="oc">Last pickup</label><input id="oc" name="order_close" type="time" value="<?= e($hm($s['order_close'])) ?>"></div>
    <div class="field"><label for="ol">Ready in (minutes)</label><input id="ol" name="order_lead_minutes" type="number" min="5" max="180" value="<?= (int)$s['order_lead_minutes'] ?>"></div><div class="field"><label for="tx">Sales tax %</label><input id="tx" name="tax_pct" inputmode="decimal" value="<?= e(rtrim(rtrim((string)$s['tax_pct'], '0'), '.')) ?>"></div></div>
  <fieldset style="border:0;padding:0;margin:0"><legend style="font-weight:600;font-size:14px;margin-bottom:6px">Days</legend><?= $days('order_days', $s['order_days']) ?></fieldset>
  <button class="btn primary">Save ordering</button>
</form>
<form method="post" action="/admin/settings" class="card form" id="reservations"><?= csrf_field() ?><input type="hidden" name="action" value="reservations">
  <h2>Reservations</h2>
  <div class="checks"><label class="check"><input type="checkbox" name="reservations_on" value="1"<?= $s['reservations_on'] ? ' checked' : '' ?>>Take table requests online</label><label class="check"><input type="checkbox" name="res_auto_confirm" value="1"<?= $s['res_auto_confirm'] ? ' checked' : '' ?>>Confirm automatically when there’s room</label></div>
  <div class="grid g4"><div class="field"><label for="ro">First table</label><input id="ro" name="res_open" type="time" value="<?= e($hm($s['res_open'])) ?>"></div><div class="field"><label for="rc">Last table</label><input id="rc" name="res_close" type="time" value="<?= e($hm($s['res_close'])) ?>"></div>
    <div class="field"><label for="rs">Every</label><select id="rs" name="res_slot_minutes"><?php foreach ([15, 30, 60] as $n): ?><option value="<?= $n ?>"<?= (int)$s['res_slot_minutes'] === $n ? ' selected' : '' ?>><?= $n ?> minutes</option><?php endforeach ?></select></div>
    <div class="field"><label for="rp">Seats per time slot</label><input id="rp" name="res_seats_per_slot" type="number" min="1" max="500" value="<?= (int)$s['res_seats_per_slot'] ?>"></div>
    <div class="field"><label for="rm">Largest party online</label><input id="rm" name="res_max_party" type="number" min="1" max="50" value="<?= (int)$s['res_max_party'] ?>"></div>
    <div class="field"><label for="rl">Book at least (hours ahead)</label><input id="rl" name="res_lead_hours" type="number" min="0" max="72" value="<?= (int)$s['res_lead_hours'] ?>"></div>
    <div class="field"><label for="rw">Up to (days ahead)</label><input id="rw" name="res_window_days" type="number" min="1" max="180" value="<?= (int)$s['res_window_days'] ?>"></div></div>
  <fieldset style="border:0;padding:0;margin:0"><legend style="font-weight:600;font-size:14px;margin-bottom:6px">Days</legend><?= $days('res_days', $s['res_days']) ?></fieldset>
  <button class="btn primary">Save reservations</button>
</form>
