<?php /** One event card. $ev = events row. */
$badge = $ev['event_date'] ? [DateTimeImmutable::createFromFormat('!Y-m-d', $ev['event_date'], tz())->format('M'), DateTimeImmutable::createFromFormat('!Y-m-d', $ev['event_date'], tz())->format('j')] : ['Every', substr(WEEKDAYS[(int)$ev['weekday']] ?? '', 0, 3)]; ?>
<div class="card ev"><span class="badge" aria-hidden="true"><small><?= e($badge[0]) ?></small><b><?= e($badge[1]) ?></b></span>
  <div><h3><?= e($ev['title']) ?></h3><p class="muted" style="margin:0 0 6px"><?= e(event_when($ev)) ?></p><?php if ($ev['description']): ?><p style="margin:0"><?= e($ev['description']) ?></p><?php endif ?></div></div>
