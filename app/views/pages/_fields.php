<?php /** Shared contact fields for the public forms. Needs $errors, $v. */
$fv = fn(string $k) => e($v[$k] ?? ''); ?>
<div class="grid g2">
  <div class="field"><label for="f-name">Your name</label><input id="f-name" name="name" autocomplete="name" required value="<?= $fv('name') ?>"<?= faria($errors, 'name') ?>><?= ferr($errors, 'name') ?></div>
  <div class="field"><label for="f-email">Email</label><input id="f-email" name="email" type="email" autocomplete="email" required value="<?= $fv('email') ?>"<?= faria($errors, 'email') ?>><?= ferr($errors, 'email') ?></div>
</div>
<div class="field"><label for="f-phone">Phone <span class="hint">(optional)</span></label><input id="f-phone" name="phone" type="tel" autocomplete="tel" value="<?= $fv('phone') ?>"<?= faria($errors, 'phone') ?>><?= ferr($errors, 'phone') ?></div>
<div class="hp" aria-hidden="true"><label for="f-website">Website</label><input id="f-website" name="website" tabindex="-1" autocomplete="off"></div>
