<?php /** Thin bar on every page of a demo shop. */ $dd = current_demo(); if ($dd): ?>
<div class="demo-bar" role="region" aria-label="Demo"><span><b>Demo</b> of <?= e($dd['shop_name']) ?> · fictional data · <?= demo_hours_left($dd) ?> h left</span>
<span class="demo-bar-links"><a href="/admin">Staff side</a><a href="/">Website</a><a href="/demo/outbox">Outbox</a><?php if (cfg('demo_sales_url')): ?><a href="<?= e(cfg('demo_sales_url')) ?>" target="_blank" rel="noopener">Get this for my restaurant</a><?php endif ?>
<form method="post" action="/demo/end" class="inline" data-confirm="Delete this demo now? This can’t be undone."><?= csrf_field() ?><button class="linkish">End demo</button></form></span></div>
<?php endif ?>
