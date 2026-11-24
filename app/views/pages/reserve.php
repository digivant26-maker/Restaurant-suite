<?php $s = settings(); ?>
<section class="s"><div class="wrap" style="max-width:820px">
  <p class="eyebrow">Book a table</p><h1>Reserve a table</h1>
  <?php if (!$days): ?><div class="card"><p style="margin:0">Online reservations aren’t available right now. Please call <a href="tel:<?= e(shop()['phone_office_tel']) ?>"><?= e(shop()['phone_office']) ?></a>.</p></div>
  <?php else: ?>
  <p class="muted">Parties up to <?= (int)$s['res_max_party'] ?>. Bigger group? <a href="/private-events">Send a private event request</a>. <?= (int)$s['res_auto_confirm'] ? 'Your table is confirmed right away.' : 'We confirm every request by email.' ?></p>
  <?php if (!empty($errors['form'])): ?><div class="alert bad" role="alert"><?= e($errors['form']) ?></div><?php endif ?>
  <form method="post" action="/reservations" class="form card" id="res-form" novalidate>
    <?= csrf_field() ?>
    <div class="grid g2">
      <div class="field"><label for="r-day">Day</label><select id="r-day" name="day"><?php foreach ($days as $d => $l): ?><option value="<?= e($d) ?>"<?= $d === $day ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach ?></select></div>
      <div class="field"><label for="r-party">Party size</label><select id="r-party" name="party"<?= faria($errors, 'party') ?>><?php for ($n = 1; $n <= (int)$s['res_max_party']; $n++): ?><option value="<?= $n ?>"<?= $n === $party ? ' selected' : '' ?>><?= $n ?> <?= $n === 1 ? 'guest' : 'guests' ?></option><?php endfor ?></select><?= ferr($errors, 'party') ?></div>
    </div>
    <fieldset class="field" style="border:0;padding:0;margin:0"><legend style="font-weight:600;font-size:15px;margin-bottom:6px">Time</legend>
      <div class="times" id="res-times"><?php if (!$times): ?><p class="hint">No times left on this day for your party size. Try another day.</p><?php endif ?>
        <?php foreach ($times as $k => $l): ?><label><input type="radio" name="time" value="<?= e($k) ?>"<?= ($v['time'] ?? '') === $k ? ' checked' : '' ?>><span><?= e($l) ?></span></label><?php endforeach ?></div>
      <?= ferr($errors, 'time') ?>
      <noscript><p class="hint">Changed the day or party size? <button class="btn ghost sm" formaction="/reservations" formmethod="get">Show times</button></p></noscript>
    </fieldset>
    <div class="grid g2">
      <div class="field"><label for="r-name">Name</label><input id="r-name" name="name" autocomplete="name" required value="<?= e($v['name'] ?? '') ?>"<?= faria($errors, 'name') ?>><?= ferr($errors, 'name') ?></div>
      <div class="field"><label for="r-phone">Phone</label><input id="r-phone" name="phone" type="tel" autocomplete="tel" required value="<?= e($v['phone'] ?? '') ?>"<?= faria($errors, 'phone') ?>><?= ferr($errors, 'phone') ?></div>
    </div>
    <div class="field"><label for="r-email">Email</label><input id="r-email" name="email" type="email" autocomplete="email" required value="<?= e($v['email'] ?? '') ?>"<?= faria($errors, 'email') ?>><?= ferr($errors, 'email') ?></div>
    <div class="grid g2">
      <div class="field"><label for="r-seating">Seating</label><select id="r-seating" name="seating"><?php foreach (SEATING as $k => $l): ?><option value="<?= e($k) ?>"<?= ($v['seating'] ?? '') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach ?></select></div>
      <div class="field"><label for="r-occasion">Occasion</label><select id="r-occasion" name="occasion"><?php foreach (OCCASIONS as $k => $l): ?><option value="<?= e($k) ?>"<?= ($v['occasion'] ?? '') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach ?></select></div>
    </div>
    <div class="field"><label for="r-notes">Notes <span class="hint">(which game, high chair, accessibility needs…)</span></label><textarea id="r-notes" name="notes" maxlength="500"><?= e($v['notes'] ?? '') ?></textarea></div>
    <div class="hp" aria-hidden="true"><label for="r-website">Website</label><input id="r-website" name="website" tabindex="-1" autocomplete="off"></div>
    <button class="btn primary"><?= (int)$s['res_auto_confirm'] ? 'Book table' : 'Request table' ?></button>
  </form>
  <?php endif ?>
</div></section>
