<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">
<title>Kitchen | <?= e(shop()['name']) ?></title><link rel="icon" href="<?= brand_logo_url() ?>"><link rel="stylesheet" href="<?= asset('css/admin.css') ?>"><link rel="stylesheet" href="<?= brand_css_url() ?>"></head>
<body class="kitchen">
<?php if (tenant_id()) require ROOT . '/app/views/demo/banner.php'; ?>
<?php $fl = take_flash(); if ($fl): ?><div class="alert <?= $fl[1] === 'bad' ? 'bad' : 'ok' ?>" role="alert" style="margin:12px"><?= e($fl[0]) ?></div><?php endif ?>
<?= $content ?>
<script src="<?= asset('js/site.js') ?>" defer></script>
</body></html>
