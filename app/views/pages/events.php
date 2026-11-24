<section class="s"><div class="wrap">
  <p class="eyebrow">What’s on</p><h1>Events &amp; specials</h1>
  <?php if ($specials): ?><h2 style="margin-top:30px">Specials</h2><div class="grid g2"><?php foreach ($specials as $ev): ?><div class="card special"><h3><?= e($ev['title']) ?></h3><p class="muted" style="margin:0 0 6px"><?= e(event_when($ev)) ?></p><p style="margin:0"><?= e($ev['description']) ?></p></div><?php endforeach ?></div><?php endif ?>
  <h2 style="margin-top:40px">Events</h2>
  <?php if (!$events): ?><p class="muted">New events coming soon.</p><?php else: ?><div class="grid g2"><?php foreach ($events as $ev) require __DIR__ . '/_event.php'; ?></div><?php endif ?>
  <p style="margin-top:30px"><a class="btn primary" href="/reservations">Reserve a table</a> <a class="btn ghost" href="/private-events">Book a private event</a></p>
</div></section>
