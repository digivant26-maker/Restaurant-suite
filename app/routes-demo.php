<?php
/** Demo builder pages (/demo...). Loaded by demo_boot() only when 'demo_builder' is on. */
$m = [];
header('X-Robots-Tag: noindex, nofollow');
if ($method === 'POST' && !csrf_ok()) { http_response_code(419); view('pages/message', ['title' => 'Page expired', 'msg' => 'This page was open too long. Go back, reload and try again.', 'noindex' => true], 'bare'); exit; }

/* ---------- reopen a demo from the emailed link (another browser or device) ---------- */
if (preg_match('#^/demo/open/([a-z0-9]{10})/([a-f0-9]{32})$#', $path, $m)) {
    $d = demo_row($m[1]);
    if (!demo_active($d) || !hash_equals($d['open_key'], $m[2])) { http_response_code(404); view('demo/gone', ['title' => 'Demo not available'], 'bare'); exit; }
    demo_set_cookie($d['id']);
    redirect('/');
}

/* ---------- one-time sign-in to the new demo's admin ---------- */
if ($path === '/demo/start') {
    if (tenant_id() && demo_start((string)($_GET['t'] ?? ''))) { flash('Welcome to your demo. Everything here is fictional sample data — try anything.'); redirect('/admin'); }
    redirect(tenant_id() ? '/admin/login' : '/demo');
}

/* ---------- emails the demo would have sent (nothing leaves the server) ---------- */
if ($path === '/demo/outbox') {
    if (!tenant_id()) redirect('/demo');
    $f = shop_storage('mail.log'); $mails = [];
    if (is_file($f)) foreach (array_reverse(array_filter(explode("---- ", (string)file_get_contents($f)))) as $chunk) {
        [$head, $body] = array_pad(explode("\n\n", $chunk, 2), 2, '');
        $lines = explode("\n", $head);
        $mails[] = ['at' => trim($lines[0] ?? ''), 'to' => trim(substr($lines[1] ?? '', 4)), 'subject' => trim(substr($lines[2] ?? '', 9)), 'body' => trim($body)];
        if (count($mails) >= 30) break;
    }
    view('demo/outbox', ['title' => 'Demo outbox', 'mails' => $mails], 'bare'); exit;
}

/* ---------- end the demo now (deletes it) ---------- */
if ($path === '/demo/end' && $method === 'POST') {
    if (tenant_id()) { $id = tenant_id(); logout(); set_tenant(null); demo_delete($id); }
    demo_set_cookie(null);
    redirect('/demo?ended=1');
}

/* ---------- the builder ---------- */
if ($path !== '/demo') not_found();
$errors = []; $v = ['brand_color' => '#1F4FD6'];
if ($method === 'POST') {
    $v = array_map(fn($x) => is_string($x) ? $x : '', $_POST);
    $in = [
        'shop_name' => clean($_POST['shop_name'] ?? '', 80), 'name' => clean($_POST['name'] ?? '', 120),
        'email' => strtolower(clean($_POST['email'] ?? '', 191)), 'phone' => clean($_POST['phone'] ?? '', 30),
        'city' => clean($_POST['city'] ?? '', 80), 'brand_color' => strtoupper(clean($_POST['brand_color'] ?? '', 7)),
    ];
    if (mb_strlen($in['shop_name']) < 2) $errors['shop_name'] = 'Enter your restaurant’s name.';
    if (mb_strlen($in['name']) < 2) $errors['name'] = 'Enter your name.';
    if (!valid_email($in['email'])) $errors['email'] = 'Enter a valid email — we send your demo link there.';
    if (!preg_match('/^#[0-9A-F]{6}$/', $in['brand_color'])) $errors['brand_color'] = 'Pick a colour.';
    if (empty($_POST['consent'])) $errors['consent'] = 'Please agree so we can send your demo link and follow up.';
    if (!empty($_POST['website'])) $errors['form'] = 'Something went wrong. Please try again.'; // honeypot
    $recent = (int)demo_db('SELECT COUNT(*) FROM demo_shops WHERE ip_hash=? AND created_at > UTC_TIMESTAMP() - INTERVAL 1 DAY', [demo_ip_hash()])->fetchColumn();
    $active = (int)demo_db('SELECT COUNT(*) FROM demo_shops WHERE deleted_at IS NULL AND expires_at > UTC_TIMESTAMP()')->fetchColumn();
    if ($recent >= (int)cfg('demo_per_day', 3)) $errors['form'] = 'You’ve built several demos today. Use the link in your email to reopen one, or try again tomorrow.';
    elseif ($active >= (int)cfg('demo_max_active', 100)) { demo_cleanup(20); $errors['form'] = 'The demo system is busy right now. Please try again in a few minutes.'; }
    if (!$errors) {
        try {
            if (tenant_id()) { logout(); }                     // leave any previous demo's sign-in
            [$d, $staffId, $password, $start] = demo_create($in, $_FILES['logo'] ?? null);
            demo_set_cookie($d['id']);
            demo_notify($d, $staffId, $password);
            set_tenant($d['id']);                                // render the welcome page in the new shop's branding
            view('demo/ready', ['title' => 'Your demo is ready', 'd' => $d, 'staffId' => $staffId, 'password' => $password, 'start' => $start], 'bare'); exit;
        } catch (RuntimeException $e) { $errors['form'] = $e->getMessage(); }
    }
}
view('demo/builder', ['title' => 'Build your restaurant’s demo', 'errors' => $errors, 'v' => $v, 'current' => current_demo()], 'bare');
