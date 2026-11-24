<?php $fl = take_flash(); $role = $user['role'] ?? 'server'; $p = path();
$all = [
  ['/admin', 'Today', ['owner', 'manager', 'server']], ['/admin/orders', 'Orders', ['owner', 'manager', 'server']], ['/admin/kitchen', 'Kitchen screen', ['owner', 'manager', 'server', 'kitchen']],
  ['/admin/reservations', 'Reservations', ['owner', 'manager', 'server']], ['/admin/menu', 'Menu', ['owner', 'manager']], ['/admin/games', 'Games schedule', ['owner', 'manager', 'server']],
  ['/admin/events', 'Events & specials', ['owner', 'manager']], ['/admin/gallery', 'Gallery', ['owner', 'manager']], ['/admin/messages', 'Messages', ['owner', 'manager']],
  ['/admin/activity', 'Activity', ['owner', 'manager']], ['/admin/staff', 'Staff', ['owner']], ['/admin/settings', 'Settings', ['owner']],
];
if (demo_enabled() && tenant_id() === null) $all[] = ['/admin/demos', 'Demo requests', ['owner']];
$newOrders = in_array($role, ['owner', 'manager', 'server'], true) ? (int)q1("SELECT COUNT(*) n FROM orders WHERE status='new'")['n'] : 0;
$newRes = in_array($role, ['owner', 'manager', 'server'], true) ? (int)q1("SELECT COUNT(*) n FROM reservations WHERE status='requested' AND starts_at >= UTC_TIMESTAMP()")['n'] : 0;
$badge = ['/admin/orders' => $newOrders, '/admin/reservations' => $newRes]; ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">
<title><?= e($title ?? 'Admin') ?> | <?= e(shop()['name']) ?></title><link rel="icon" href="<?= brand_logo_url() ?>"><link rel="stylesheet" href="<?= asset('css/admin.css') ?>"><link rel="stylesheet" href="<?= brand_css_url() ?>"></head>
<body class="adm">
<?php if (tenant_id()) require ROOT . '/app/views/demo/banner.php'; ?>
<a class="skip" href="#main">Skip to content</a>
<div class="shell">
  <aside class="side" id="side" aria-label="Staff menu">
    <a class="side-brand" href="/admin"><img src="<?= brand_logo_url() ?>" alt="" width="40" height="40"><span><b><?= e(shop()['name']) ?></b><small>Staff</small></span></a>
    <nav><?php foreach ($all as [$h, $l, $roles]) if (in_array($role, $roles, true)): $on = $h === '/admin' ? $p === '/admin' : ($p === $h || str_starts_with($p, $h . '/')); ?>
      <a href="<?= $h ?>"<?= $on ? ' aria-current="page"' : '' ?>><?= e($l) ?><?php if (!empty($badge[$h])): ?><b class="badge" aria-label="<?= $badge[$h] ?> new"><?= $badge[$h] ?></b><?php endif ?></a>
    <?php endif ?></nav>
    <div class="side-foot"><a href="/" target="_blank" rel="noopener">View website ↗</a><span><?= e($user['name'] ?? '') ?> · <?= e(ROLES[$role] ?? '') ?> · <?= e($user['staff_code'] ?? '') ?></span>
      <a href="/admin/account">My account</a><form method="post" action="/admin/logout"><?= csrf_field() ?><button class="linkish">Sign out</button></form></div>
  </aside>
  <main class="content" id="main">
    <button class="btn sm ghost menubtn" type="button" data-side aria-controls="side" aria-expanded="false">Menu</button>
    <?php if ($fl): ?><div class="alert <?= $fl[1] === 'bad' ? 'bad' : 'ok' ?>" role="<?= $fl[1] === 'bad' ? 'alert' : 'status' ?>"><?= e($fl[0]) ?></div><?php endif ?>
    <?= $content ?>
  </main>
</div>
<script src="<?= asset('js/site.js') ?>" defer></script><script src="<?= asset('js/admin.js') ?>" defer></script>
</body></html>
