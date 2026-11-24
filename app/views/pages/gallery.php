<section class="s"><div class="wrap">
  <p class="eyebrow">Inside <?= e(shop()['name']) ?></p><h1>Gallery</h1>
  <?php if (!$photos): ?><p class="muted">Photos coming soon.</p><?php else: ?>
  <div class="gal"><?php foreach ($photos as $p): ?><figure><img src="<?= e(media_url('gallery', $p['file'])) ?>" alt="<?= e($p['caption'] ?: 'Photo at ' . shop()['name']) ?>" loading="lazy" width="400" height="300"><?php if ($p['caption']): ?><figcaption><?= e($p['caption']) ?></figcaption><?php endif ?></figure><?php endforeach ?></div><?php endif ?>
</div></section>
