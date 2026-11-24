<section class="s"><div class="wrap" style="max-width:760px">
  <h1>Private events</h1>
  <p class="muted">Watch parties, birthdays, team nights and office gatherings. Tell us what you have in mind and we’ll reply with options.</p>
  <?php if ($sent): ?><div class="alert ok" role="status">Thanks! Your message was sent. We’ll reply by email.</div><?php endif ?>
  <?php if (!empty($errors['form'])): ?><div class="alert bad" role="alert"><?= e($errors['form']) ?></div><?php endif ?>
  <form method="post" class="form card" novalidate>
    <?= csrf_field() ?>
    <?php require __DIR__ . '/_fields.php'; ?>
    <div class="grid g2"><div class="field"><label for="f-date">Event date</label><input id="f-date" name="event_date" type="date" required value="<?= e($v['event_date'] ?? '') ?>"<?= faria($errors, 'event_date') ?>><?= ferr($errors, 'event_date') ?></div><div class="field"><label for="f-guests">Number of guests</label><input id="f-guests" name="guests" type="number" min="1" max="500" value="<?= e($v['guests'] ?? '') ?>"></div></div>
    <div class="field"><label for="f-body">Tell us about the event</label><textarea id="f-body" name="body" required<?= faria($errors, 'body') ?>><?= e($v['body'] ?? '') ?></textarea><?= ferr($errors, 'body') ?></div>
    <button class="btn primary">Send</button>
  </form>
  <p class="muted" style="margin-top:20px">Or call <a href="tel:<?= e(shop()['phone_office_tel']) ?>"><?= e(shop()['phone_office']) ?></a> · <?= e(shop()['address']) ?></p>
</div></section>
