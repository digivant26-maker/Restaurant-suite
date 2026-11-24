<section class="s"><div class="wrap" style="max-width:700px">
  <?php if (!empty($_GET['new'])): ?><div class="alert ok" role="status"><?= $r['status'] === 'confirmed' ? 'Your table is booked. A confirmation is on its way to your email.' : 'Request received! We’ll confirm by email shortly.' ?></div><?php endif ?>
  <p class="eyebrow">Reservation <?= e($r['ref']) ?></p><h1><?= e(RES_STATUS[$r['status']] ?? $r['status']) ?></h1>
  <div class="card"><table class="lines"><tbody><?php foreach (res_rows($r) as [$k, $val]): ?><tr><th scope="row"><?= e($k) ?></th><td><?= e($val) ?></td></tr><?php endforeach ?></tbody></table>
    <?php if ($r['status'] === 'declined' && $r['decline_reason']): ?><p class="muted"><?= e($r['decline_reason']) ?></p><?php endif ?>
    <?php if (in_array($r['status'], ['requested', 'confirmed'], true) && $r['starts_at'] > gmdate('Y-m-d H:i:s')): ?>
    <form method="post" style="margin-top:18px" data-confirm="Cancel this reservation?"><?= csrf_field() ?><input type="hidden" name="action" value="cancel"><button class="btn ghost">Cancel reservation</button></form><?php endif ?>
  </div>
  <p class="muted" style="margin-top:16px"><?= e(shop()['name']) ?> · <?= e(shop()['address']) ?> · <a href="tel:<?= e(shop()['phone_office_tel']) ?>"><?= e(shop()['phone_office']) ?></a></p>
</div></section>
