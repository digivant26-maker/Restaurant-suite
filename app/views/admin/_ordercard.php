<?php /** Order card with its next step. $o (with items), $back. */ $next = ORDER_NEXT[$o['status']] ?? null;
$btn = ['accepted' => 'Start preparing', 'ready' => 'Mark ready', 'picked_up' => 'Picked up'][$next] ?? null; $late = in_array($o['status'], ['new', 'accepted'], true) && $o['pickup_at'] < gmdate('Y-m-d H:i:s'); ?>
<article class="ticket st-<?= e($o['status']) ?>">
  <div class="tt"><b><?= e($o['ref']) ?></b><span class="due<?= $late ? ' late' : '' ?>"><?= $late ? 'Late · ' : '' ?><?= e(fmt_time($o['pickup_at'])) ?></span></div>
  <div><?= e($o['name']) ?> · <a href="tel:<?= e(digits($o['phone'])) ?>"><?= e($o['phone']) ?></a></div>
  <ul><?php foreach ($o['items'] as $i): ?><li><b><?= (int)$i['qty'] ?>×</b> <?= e($i['name']) ?><?php if ($i['note']): ?><small><?= e($i['note']) ?></small><?php endif ?></li><?php endforeach ?></ul>
  <?php if ($o['notes']): ?><div class="note">Note: <?= e($o['notes']) ?></div><?php endif ?>
  <div class="tt" style="margin-bottom:10px"><span class="muted">Placed <?= e(fmt_time($o['created_at'])) ?></span><b style="font-size:20px"><?= money($o['total']) ?></b></div>
  <?php if ($btn): ?><form method="post" action="/admin/orders/<?= (int)$o['id'] ?>"><?= csrf_field() ?><input type="hidden" name="to" value="<?= e($next) ?>"><input type="hidden" name="back" value="<?= e($back) ?>"><button class="btn <?= $next === 'ready' ? 'ok' : 'primary' ?>"><?= e($btn) ?></button></form><?php endif ?>
  <?php if (in_array($o['status'], ORDER_OPEN, true) && empty($noCancel)): ?>
  <details style="margin-top:8px"><summary>Cancel order</summary><form method="post" action="/admin/orders/<?= (int)$o['id'] ?>" class="form" style="margin-top:8px" data-confirm="Cancel order <?= e($o['ref']) ?>? The customer is emailed if they gave an email."><?= csrf_field() ?><input type="hidden" name="to" value="cancelled"><input type="hidden" name="back" value="<?= e($back) ?>"><label for="cr<?= (int)$o['id'] ?>">Reason for the customer</label><input id="cr<?= (int)$o['id'] ?>" name="reason" maxlength="255" placeholder="e.g. Kitchen closing early"><button class="btn danger sm">Cancel order</button></form></details><?php endif ?>
</article>
