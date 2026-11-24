<?php $ordersNew = count(array_filter($orders, fn($o) => $o['status'] === 'new')); ?>
<div class="sh"><div><p class="hint" style="margin:0"><?= e(now_local()->format('l, F j')) ?></p><h1>Today</h1></div>
  <div class="acts"><a class="btn primary" href="/admin/kitchen">Kitchen screen</a><a class="btn ghost" href="/admin/reservations#add">Take a booking</a></div></div>
<div class="grid g4" style="margin-bottom:22px">
  <a class="stat<?= $ordersNew ? ' hot' : '' ?>" href="/admin/orders"><b><?= $ordersNew ?></b><span>New orders</span></a>
  <a class="stat<?= $pending ? ' hot' : '' ?>" href="/admin/reservations"><b><?= $pending ?></b><span>Table requests to answer</span></a>
  <a class="stat" href="/admin/reservations"><b><?= array_sum(array_column($res, 'party')) ?></b><span>Guests booked today</span></a>
  <a class="stat" href="/admin/orders?show=done"><b><?= money($sales['t']) ?></b><span><?= (int)$sales['n'] ?> online orders today</span></a>
</div>
<div class="grid g2">
  <section class="card"><h2>Open orders</h2>
    <?php if (!$orders): ?><p class="muted" style="margin:0">No open orders.</p><?php else: ?>
    <table class="tbl"><thead><tr><th>Pickup</th><th>Order</th><th>Status</th><th>Total</th></tr></thead><tbody>
    <?php foreach ($orders as $o): ?><tr><td><b><?= e(fmt_time($o['pickup_at'])) ?></b></td><td><?= e($o['ref']) ?> · <?= e($o['name']) ?></td><td><?= order_tag($o['status']) ?></td><td><?= money($o['total']) ?></td></tr><?php endforeach ?>
    </tbody></table><?php endif ?>
    <p style="margin:12px 0 0"><a href="/admin/orders">All orders →</a></p></section>
  <section class="card"><h2>Today’s tables</h2>
    <?php if (!$res): ?><p class="muted" style="margin:0">No reservations today.</p><?php else: ?>
    <table class="tbl"><thead><tr><th>Time</th><th>Guest</th><th>Party</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($res as $r): ?><tr><td><b><?= e(fmt_time($r['starts_at'])) ?></b></td><td><?= e($r['name']) ?><?= $r['occasion'] ? ' · ' . e(OCCASIONS[$r['occasion']] ?? '') : '' ?></td><td><?= (int)$r['party'] ?></td><td><?= res_tag($r['status']) ?></td></tr><?php endforeach ?>
    </tbody></table><?php endif ?>
    <p style="margin:12px 0 0"><a href="/admin/reservations">All reservations →</a></p></section>
  <section class="card"><h2>Games today</h2>
    <?php if (!$games): ?><p class="muted" style="margin:0">No games scheduled today. <a href="/admin/games">Add one</a>.</p><?php else: ?><ul style="margin:0;padding-left:18px">
    <?php foreach ($games as $g): ?><li><b><?= e(fmt_time($g['starts_at'])) ?></b> · <?= e($g['league']) ?> · <?= e($g['away']) ?> at <?= e($g['home']) ?><?= $g['screens'] ? ' · <span class="muted">' . e($g['screens']) . '</span>' : '' ?><?= $g['sound'] ? ' · <b>sound on</b>' : '' ?></li><?php endforeach ?></ul><?php endif ?></section>
  <section class="card"><h2>Heads up</h2>
    <ul style="margin:0;padding-left:18px">
      <li><?= $msgs ?> unanswered <a href="/admin/messages">message<?= $msgs === 1 ? '' : 's' ?></a></li>
      <li><?= $soldout ? 'Sold out: ' . e(implode(', ', array_column($soldout, 'name'))) : 'Nothing is marked sold out.' ?> <a href="/admin/menu">Menu</a></li>
      <li>Online ordering is <b><?= pickup_slots() ? 'open' : 'closed' ?></b> right now · reservations are <b><?= (int)settings()['reservations_on'] ? 'on' : 'off' ?></b></li>
    </ul></section>
</div>
