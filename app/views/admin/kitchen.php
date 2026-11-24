<header class="kh"><b><?= e(shop()['name']) ?> · Kitchen</b><span><?= e(now_local()->format('g:i A')) ?> · updates every 20 s · <?php if ($user['role'] !== 'kitchen'): ?><a href="/admin/orders">Orders list</a> · <?php endif ?><form method="post" action="/admin/logout" style="display:inline"><?= csrf_field() ?><button class="linkish">Sign out</button></form></span></header>
<div class="kcols">
<?php foreach (['new' => 'New', 'accepted' => 'Preparing', 'ready' => 'Ready for pickup'] as $st => $label): ?>
  <section class="kcol" aria-labelledby="k-<?= $st ?>"><h2 id="k-<?= $st ?>"><?= e($label) ?> <span><?= count($cols[$st]) ?></span></h2>
    <?php if (!$cols[$st]): ?><p style="color:#9aa1b2">Nothing here.</p><?php endif ?>
    <?php foreach ($cols[$st] as $o) { $back = '/admin/kitchen'; $noCancel = $user['role'] === 'kitchen'; require __DIR__ . '/_ordercard.php'; } ?>
  </section>
<?php endforeach ?>
</div>
