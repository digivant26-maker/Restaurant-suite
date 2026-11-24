<?php /** Shown once, right after a demo is created, in the new shop's own branding. */ ?>
<div class="card demo-ready">
  <div class="brand" style="color:var(--text)"><img src="<?= brand_logo_url() ?>" alt="" width="48" height="48"><div><b><?= e($d['shop_name']) ?></b><span>Your demo is ready</span></div></div>
  <h1>Your demo is ready</h1>
  <p>It has a website with a fictional menu, online ordering and reservations, plus the staff side with today’s orders, table requests, the kitchen screen and the games schedule. It’s available for <?= demo_hours() ?> hours, and we’ve emailed you a link to come back to it.</p>
  <dl class="demo-creds">
    <dt>Staff ID</dt><dd class="mono"><?= e($staffId) ?></dd>
    <dt>Password</dt><dd class="mono"><?= e($password) ?></dd>
  </dl>
  <p class="hint">The manager, front-of-house and kitchen accounts use the same demo password — see their Staff IDs under Staff.</p>
  <div class="demo-actions">
    <a class="btn primary" href="/demo/start?t=<?= e($start) ?>">Open the staff side</a>
    <a class="btn ghost" href="/">See the website</a>
  </div>
</div>
