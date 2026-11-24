<section class="s"><div class="wrap" style="max-width:900px">
  <p class="eyebrow">Pickup · pay when you pick up</p><h1>Your order</h1>
  <?php if ($gone): ?><div class="alert bad" role="alert"><?= e(implode(', ', $gone)) ?> can’t be ordered right now and won’t be included.</div><?php endif ?>
  <?php if (!empty($errors['form'])): ?><div class="alert bad" role="alert"><?= e($errors['form']) ?></div><?php endif ?>
  <?php if (!$lines): ?><div class="card"><p style="margin:0 0 14px">Your order is empty.</p><a class="btn primary" href="/menu">Browse the menu</a></div>
  <?php else: ?>
  <div class="card" style="margin-bottom:22px">
    <table class="lines"><caption class="sr">Items in your order</caption><thead><tr><th scope="col">Item</th><th scope="col">Qty</th><th scope="col" class="n">Price</th><th scope="col"><span class="sr">Remove</span></th></tr></thead><tbody>
    <?php foreach ($lines as $l): ?><tr>
      <td><b><?= e($l['name']) ?></b><?php if ($l['note']): ?><div class="muted"><?= e($l['note']) ?></div><?php endif ?></td>
      <td><form method="post" action="/cart" class="addform"><?= csrf_field() ?><input type="hidden" name="action" value="qty"><input type="hidden" name="key" value="<?= (int)$l['key'] ?>"><label class="sr" for="cq<?= (int)$l['key'] ?>">Quantity of <?= e($l['name']) ?></label><input id="cq<?= (int)$l['key'] ?>" type="number" name="qty" min="0" max="<?= CART_MAX_QTY ?>" value="<?= (int)$l['qty'] ?>"><button class="btn ghost sm">Update</button></form></td>
      <td class="n"><?= money($l['line_total']) ?></td>
      <td><form method="post" action="/cart"><?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="key" value="<?= (int)$l['key'] ?>"><button class="btn ghost sm">Remove<span class="sr"> <?= e($l['name']) ?></span></button></form></td>
    </tr><?php endforeach ?></tbody></table>
    <div class="totals"><span>Subtotal <?= money($tot['subtotal']) ?></span><span class="muted">Tax <?= money($tot['tax']) ?></span><span class="big">Total <?= money($tot['total']) ?></span></div>
    <p style="margin:12px 0 0"><a href="/menu">← Add more items</a></p>
  </div>
  <?php if (!$slots): ?><div class="alert bad" role="alert">Online ordering is closed right now. Please call <?= e(shop()['phone_office']) ?>.</div>
  <?php else: ?>
  <form method="post" action="/order" class="form card" novalidate>
    <?= csrf_field() ?><h2>Pickup details</h2>
    <div class="grid g2">
      <div class="field"><label for="o-name">Name</label><input id="o-name" name="name" autocomplete="name" required value="<?= e($v['name'] ?? '') ?>"<?= faria($errors, 'name') ?>><?= ferr($errors, 'name') ?></div>
      <div class="field"><label for="o-phone">Phone</label><input id="o-phone" name="phone" type="tel" autocomplete="tel" required value="<?= e($v['phone'] ?? '') ?>"<?= faria($errors, 'phone') ?>><?= ferr($errors, 'phone') ?></div>
    </div>
    <div class="grid g2">
      <div class="field"><label for="o-email">Email <span class="hint">(for your receipt and “ready” message)</span></label><input id="o-email" name="email" type="email" autocomplete="email" value="<?= e($v['email'] ?? '') ?>"<?= faria($errors, 'email') ?>><?= ferr($errors, 'email') ?></div>
      <div class="field"><label for="o-pickup">Pickup time</label><select id="o-pickup" name="pickup" required<?= faria($errors, 'pickup') ?>><?php foreach ($slots as $i => $t): ?><option value="<?= e(slot_value($t)) ?>"<?= ($v['pickup'] ?? '') === slot_value($t) ? ' selected' : '' ?>><?= $i === 0 ? 'As soon as possible (' . $t->format('g:i A') . ')' : $t->format('g:i A') ?></option><?php endforeach ?></select><?= ferr($errors, 'pickup') ?></div>
    </div>
    <div class="field"><label for="o-notes">Notes for the kitchen <span class="hint">(allergies, no onions, extra sauce…)</span></label><textarea id="o-notes" name="notes" maxlength="500"><?= e($v['notes'] ?? '') ?></textarea></div>
    <div class="hp" aria-hidden="true"><label for="o-website">Website</label><input id="o-website" name="website" tabindex="-1" autocomplete="off"></div>
    <button class="btn primary">Place order · <?= money($tot['total']) ?></button>
    <p class="hint" style="margin:0">You pay when you pick up. We’ll call if there’s a problem with your order.</p>
  </form>
  <?php endif ?>
  <?php endif ?>
</div></section>
