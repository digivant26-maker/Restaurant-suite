<?php $steps = ['new' => 'Received', 'accepted' => 'Preparing', 'ready' => 'Ready', 'picked_up' => 'Picked up']; $at = array_search($o['status'], array_keys($steps), true); ?>
<section class="s"><div class="wrap" style="max-width:760px" data-autorefresh>
  <?php if (!empty($_GET['new'])): ?><div class="alert ok" role="status">Thanks, <?= e($o['name']) ?>! Your order is in. Keep this page to see when it’s ready.</div><?php endif ?>
  <p class="eyebrow">Order <?= e($o['ref']) ?></p><h1><?= $o['status'] === 'cancelled' ? 'Order cancelled' : e(ORDER_STATUS[$o['status']]) ?></h1>
  <?php if ($o['status'] === 'cancelled'): ?><div class="alert bad">This order was cancelled<?= $o['cancel_reason'] ? ': ' . e($o['cancel_reason']) : '' ?>. Questions? Call <?= e(shop()['phone_office']) ?>.</div>
  <?php else: ?><div class="steps" aria-label="Order progress"><?php $k = 0; foreach ($steps as $st => $l): ?><div class="<?= $k < $at ? 'done' : ($k === $at ? 'now' : '') ?>"<?= $k === $at ? ' aria-current="step"' : '' ?>><?= e($l) ?></div><?php $k++; endforeach ?></div><?php endif ?>
  <div class="card">
    <p style="margin:0 0 10px"><b>Pickup:</b> <?= e(fmt_when($o['pickup_at'])) ?> at <?= e(shop()['address']) ?></p>
    <table class="lines"><tbody><?php foreach ($o['items'] as $i): ?><tr><td><?= (int)$i['qty'] ?> × <?= e($i['name']) ?><?php if ($i['note']): ?><div class="muted"><?= e($i['note']) ?></div><?php endif ?></td><td class="n"><?= money($i['line_total']) ?></td></tr><?php endforeach ?></tbody></table>
    <div class="totals"><span>Subtotal <?= money($o['subtotal']) ?> · Tax <?= money($o['tax']) ?></span><span class="big">Total <?= money($o['total']) ?></span><span class="muted">Pay when you pick up</span></div>
  </div>
  <p class="muted" style="margin-top:16px">This page updates on its own. Need to change something? Call <a href="tel:<?= e(shop()['phone_office_tel']) ?>"><?= e(shop()['phone_office']) ?></a>.</p>
</div></section>
<script>if (document.querySelector('[data-autorefresh]') && !/picked_up|cancelled/.test('<?= e($o['status']) ?>')) setTimeout(function () { location.replace(location.pathname); }, 30000);</script>
