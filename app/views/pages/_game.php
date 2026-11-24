<?php /** One game row. $g = games row. */ ?>
<div class="game<?= $g['featured'] ? ' ft' : '' ?>"><span class="lg"><?= e($g['league']) ?></span>
  <span><span class="tm"><?= e($g['away']) ?> <span class="muted">at</span> <?= e($g['home']) ?></span><small><?= e(implode(' · ', array_filter([$g['screens'] ? 'Screens: ' . $g['screens'] : '', $g['sound'] ? 'Sound on' : '', $g['note']]))) ?></small></span>
  <span class="when"><?= e(fmt_time($g['starts_at'])) ?></span></div>
