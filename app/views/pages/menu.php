<section class="s" style="padding-bottom:20px"><div class="wrap">
  <p class="eyebrow"><?= $ordering ? 'Order online for pickup · pay when you pick up' : 'Online ordering is closed right now' ?></p>
  <h1>Menu</h1>
  <?php if (!$ordering): ?><p class="muted">You can still browse the menu. Call <a href="tel:<?= e(shop()['phone_office_tel']) ?>"><?= e(shop()['phone_office']) ?></a> to order.</p><?php endif ?>
</div></section>
<?php if ($cats): ?><nav class="menunav" aria-label="Menu sections"><div class="wrap"><?php foreach ($cats as $c) if ($c['items']): ?><a href="#c<?= (int)$c['id'] ?>"><?= e($c['name']) ?></a><?php endif ?></div></nav><?php endif ?>
<section class="s" style="padding-top:30px"><div class="wrap">
  <?php if (!$cats): ?><p class="muted">The menu is being updated. Please check back soon.</p><?php endif ?>
  <?php foreach ($cats as $c) if ($c['items']): ?>
  <section class="mcat" id="c<?= (int)$c['id'] ?>" aria-labelledby="ch<?= (int)$c['id'] ?>">
    <h2 id="ch<?= (int)$c['id'] ?>"><?= e($c['name']) ?></h2><?php if ($c['description']): ?><p class="muted"><?= e($c['description']) ?></p><?php endif ?>
    <?php foreach ($c['items'] as $i): $img = media_url('menu', $i['photo']); $can = $ordering && $i['orderable'] && $i['available']; ?>
    <div class="mitem<?= $img ? '' : ' nophoto' ?>" id="i<?= (int)$i['id'] ?>">
      <?php if ($img): ?><div class="thumb"><img src="<?= e($img) ?>" alt="" loading="lazy" width="96" height="96"></div><?php endif ?>
      <div><h3><?= e($i['name']) ?><?php foreach (item_tags($i['tags']) as $t): ?><span class="tag<?= in_array($t, ['popular', 'new'], true) ? ' hot' : '' ?>"><?= e(ITEM_TAGS[$t]) ?></span><?php endforeach ?><?php if (!$i['available']): ?><span class="tag out">Sold out</span><?php endif ?></h3>
        <?php if ($i['description']): ?><p><?= e($i['description']) ?></p><?php endif ?></div>
      <div class="act"><span class="price"><?= money($i['price']) ?></span>
        <?php if ($can): ?><form method="post" action="/cart" class="addform"><?= csrf_field() ?><input type="hidden" name="action" value="add"><input type="hidden" name="item" value="<?= (int)$i['id'] ?>">
          <label class="sr" for="q<?= (int)$i['id'] ?>">Quantity of <?= e($i['name']) ?></label><input id="q<?= (int)$i['id'] ?>" type="number" name="qty" value="1" min="1" max="<?= CART_MAX_QTY ?>">
          <button class="btn primary sm">Add<span class="sr"> <?= e($i['name']) ?></span></button></form><?php endif ?>
      </div>
    </div>
    <?php endforeach ?>
  </section>
  <?php endif ?>
</div></section>
