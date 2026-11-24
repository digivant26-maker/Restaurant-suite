<?php /** Featured menu item card. $i = menu_items row. */ $img = media_url('menu', $i['photo']); ?>
<article class="card dish">
  <div class="ph"><?php if ($img): ?><img src="<?= e($img) ?>" alt="" loading="lazy" width="400" height="300"><?php else: ?><span aria-hidden="true"><?= e(initials($i['name'])) ?></span><?php endif ?></div>
  <div class="bd"><h3><?= e($i['name']) ?></h3><p class="muted" style="margin:0;font-size:15px"><?= e($i['description']) ?></p>
    <div class="tags"><?php foreach (item_tags($i['tags']) as $t): ?><span class="tag<?= in_array($t, ['popular', 'new'], true) ? ' hot' : '' ?>"><?= e(ITEM_TAGS[$t]) ?></span><?php endforeach ?></div>
    <span class="price" style="margin-top:auto"><?= money($i['price']) ?></span></div>
</article>
