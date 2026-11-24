<section class="s"><div class="wrap" style="max-width:860px">
  <p class="eyebrow">Next two weeks</p><h1>Games on our screens</h1>
  <p class="muted">Times are local. Want a game that isn’t listed? Ask your server — we’ll put it on if a screen is free.</p>
  <?php if (!$games): ?><p>No games listed yet. Check back soon.</p><?php endif ?>
  <?php $day = null; foreach ($games as $g): $d = game_day_label($g['starts_at']); if ($d !== $day): $day = $d; ?><h2 class="daylabel"><?= e($d) ?></h2><?php endif ?>
    <div class="games" style="margin-bottom:8px"><?php require __DIR__ . '/_game.php'; ?></div>
  <?php endforeach ?>
  <p style="margin-top:30px"><a class="btn primary" href="/reservations">Reserve a table for the game</a></p>
</div></section>
