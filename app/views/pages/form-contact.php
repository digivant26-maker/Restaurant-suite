<section class="s"><div class="wrap" style="max-width:760px">
  <h1>Contact us</h1>
  <p class="muted">Questions, feedback or lost and found — send us a note and we’ll get back to you.</p>
  <?php if ($sent): ?><div class="alert ok" role="status">Thanks! Your message was sent. We’ll reply by email.</div><?php endif ?>
  <?php if (!empty($errors['form'])): ?><div class="alert bad" role="alert"><?= e($errors['form']) ?></div><?php endif ?>
  <form method="post" class="form card" novalidate>
    <?= csrf_field() ?>
    <?php require __DIR__ . '/_fields.php'; ?>
    
    <div class="field"><label for="f-body">Message</label><textarea id="f-body" name="body" required<?= faria($errors, 'body') ?>><?= e($v['body'] ?? '') ?></textarea><?= ferr($errors, 'body') ?></div>
    <button class="btn primary">Send</button>
  </form>
  <p class="muted" style="margin-top:20px">Or call <a href="tel:<?= e(shop()['phone_office_tel']) ?>"><?= e(shop()['phone_office']) ?></a> · <?= e(shop()['address']) ?></p>
</div></section>
