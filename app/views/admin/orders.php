<div class="sh"><div><h1>Orders</h1><p class="muted" style="margin:0">Online pickup orders. Customers pay at pickup.</p></div><a class="btn primary" href="/admin/kitchen">Open the kitchen screen</a></div>
<nav class="tabs" aria-label="Show"><a href="/admin/orders"<?= $show === 'open' ? ' aria-current="page"' : '' ?>>Open</a><a href="/admin/orders?show=done"<?= $show === 'done' ? ' aria-current="page"' : '' ?>>Finished &amp; cancelled</a></nav>
<?php if (!$rows): ?><div class="empty"><?= $show === 'open' ? 'No open orders right now.' : 'No finished orders yet.' ?></div>
<?php elseif ($show === 'open'): ?><div class="grid g3" data-autorefresh><?php foreach ($rows as $o) { $back = '/admin/orders'; require __DIR__ . '/_ordercard.php'; } ?></div>
<?php else: ?><div class="tblwrap"><table class="tbl"><thead><tr><th>Order</th><th>Customer</th><th>Pickup</th><th>Items</th><th>Total</th><th>Status</th></tr></thead><tbody>
<?php foreach ($rows as $o): ?><tr><td><b><?= e($o['ref']) ?></b></td><td><?= e($o['name']) ?><div class="muted"><?= e($o['phone']) ?></div></td><td><?= e(fmt_when($o['pickup_at'])) ?></td>
<td><?= e(implode(', ', array_map(fn($i) => $i['qty'] . '× ' . $i['name'], $o['items']))) ?></td><td><?= money($o['total']) ?></td><td><?= order_tag($o['status']) ?><?= $o['cancel_reason'] ? '<div class="muted">' . e($o['cancel_reason']) . '</div>' : '' ?></td></tr><?php endforeach ?>
</tbody></table></div><?php endif ?>
