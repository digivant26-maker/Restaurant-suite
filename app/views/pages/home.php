<?php $S = shop(); ?>
<section class="hero"><div class="wrap">
  <div>
    <p class="eyebrow"><?= e($S['tagline']) ?> · <?= e($S['city']) ?></p>
    <h1><?= e($S['headline']) ?></h1>
    <p class="lead"><?= e($S['about']) ?></p>
    <div class="ctas"><a class="btn primary" href="/menu">View menu &amp; order</a><a class="btn ghost" href="/reservations">Reserve a table</a></div>
    <div class="info"><span><b>Open</b> <?= e($S['hours_text']) ?></span><?php if ($S['happy_hour_text']): ?><span><b>Happy hour</b> <?= e(preg_replace('/^Happy hour\s*/i', '', $S['happy_hour_text'])) ?></span><?php endif ?></div>
  </div>
  <aside class="scoreboard" aria-labelledby="sb-h">
    <h2 id="sb-h"><span class="live" aria-hidden="true"></span>On our screens</h2>
    <?php if (!$games): ?><p class="muted">Check back soon for the games we’re showing.</p><?php else: ?>
    <div class="games"><?php $day = null; foreach (array_slice($games, 0, 5) as $g): $d = game_day_label($g['starts_at']); if ($d !== $day): $day = $d; ?><p class="daylabel" style="margin:8px 0 0"><?= e($d) ?></p><?php endif; require __DIR__ . '/_game.php'; endforeach ?></div><?php endif ?>
    <p style="margin:14px 0 0"><a href="/games">Full schedule →</a></p>
  </aside>
</div></section>

<?php if ($featured): ?>
<section class="s"><div class="wrap">
  <div class="sh"><div><p class="eyebrow">From the kitchen</p><h2>Fan favorites</h2></div><a class="btn ghost" href="/menu">Full menu</a></div>
  <div class="grid g3"><?php foreach ($featured as $i) require __DIR__ . '/_dish.php'; ?></div>
</div></section>
<?php endif ?>

<?php if ($events || $specials): ?>
<section class="s alt"><div class="wrap">
  <div class="sh"><div><p class="eyebrow">Every week</p><h2>Events &amp; specials</h2></div><a class="btn ghost" href="/events">All events</a></div>
  <div class="grid g2">
    <?php foreach ($specials as $ev): ?><div class="card special"><h3><?= e($ev['title']) ?></h3><p class="muted" style="margin:0 0 6px"><?= e(event_when($ev)) ?></p><p style="margin:0"><?= e($ev['description']) ?></p></div><?php endforeach ?>
    <?php foreach ($events as $ev): require __DIR__ . '/_event.php'; endforeach ?>
  </div>
</div></section>
<?php endif ?>

<section class="s"><div class="wrap grid g2" style="align-items:center">
  <div><p class="eyebrow">Plan ahead</p><h2>Game day for your group</h2><p class="muted">Birthdays, watch parties, team nights and office gatherings. Tell us the date and size and we’ll set it up.</p>
    <div class="ctas" style="display:flex;gap:12px;flex-wrap:wrap"><a class="btn primary" href="/private-events">Plan a private event</a><a class="btn ghost" href="tel:<?= e($S['phone_office_tel']) ?>">Call <?= e($S['phone_office']) ?></a></div></div>
  <iframe class="map" title="Map to <?= e($S['name']) ?>" src="<?= e($S['maps_embed']) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
</div></section>
