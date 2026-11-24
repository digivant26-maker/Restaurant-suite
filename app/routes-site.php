<?php
/** Public website. */
$m = [];
switch (true) {
    /* ---------- branding and photos ---------- */
    case $path === '/brand.css':
        header('Content-Type: text/css; charset=utf-8'); header('Cache-Control: public, max-age=31536000, immutable'); header('X-Content-Type-Options: nosniff');
        echo brand_css(); exit;
    case $path === '/brand/logo':
        $lf = shop()['logo_file'];
        if ($lf && preg_match('/^logo-[a-f0-9]{16}\.png$/', $lf) && is_file($file = brand_dir() . '/' . $lf)) { header('Content-Type: image/png'); readfile_with_cache_headers($file); exit; }
        header('Content-Type: image/svg+xml; charset=utf-8'); header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'"); header('Cache-Control: public, max-age=31536000, immutable');
        echo brand_monogram_svg(); exit;
    case (bool)preg_match('#^/media/(menu|gallery)/([a-f0-9]{24}\.(?:jpg|png|webp))$#', $path, $m):
        serve_media($m[1], $m[2]);

    /* ---------- pages ---------- */
    case $path === '/':
        view('pages/home', ['title' => null, 'featured' => featured_items(6), 'games' => games_upcoming(2, 6), 'events' => array_slice(events_list('event'), 0, 4), 'specials' => events_list('special')]); break;
    case $path === '/menu':
        view('pages/menu', ['title' => 'Menu', 'desc' => 'Food and drinks at ' . shop()['name'] . '. Order online for pickup.', 'cats' => menu_full(), 'ordering' => (bool)pickup_slots()]); break;
    case $path === '/events':
        view('pages/events', ['title' => 'Events & specials', 'events' => events_list('event'), 'specials' => events_list('special')]); break;
    case $path === '/games':
        view('pages/games', ['title' => 'Games on our screens', 'games' => games_upcoming(14)]); break;
    case $path === '/gallery':
        view('pages/gallery', ['title' => 'Gallery', 'photos' => q('SELECT * FROM gallery_photos ORDER BY position, id DESC')]); break;
    case $path === '/privacy':
        view('pages/privacy', ['title' => 'Privacy']); break;

    /* ---------- forms: contact, private events, careers ---------- */
    case in_array($path, ['/contact', '/private-events', '/careers'], true):
        $kind = ['/contact' => 'contact', '/private-events' => 'private', '/careers' => 'careers'][$path];
        $errors = []; $v = []; $sent = false;
        if ($method === 'POST') {
            $v = array_map(fn($x) => is_string($x) ? $x : '', $_POST);
            $d = ['name' => clean($v['name'] ?? '', 120), 'phone' => clean($v['phone'] ?? '', 30), 'email' => strtolower(clean($v['email'] ?? '', 191)),
                  'subject' => clean($v['subject'] ?? '', 160), 'body' => clean_text($v['body'] ?? '', 3000),
                  'event_date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $v['event_date'] ?? '') ? $v['event_date'] : null, 'guests' => (int)($v['guests'] ?? 0) ?: null];
            if (mb_strlen($d['name']) < 2) $errors['name'] = 'Enter your name.';
            if (!valid_email($d['email'])) $errors['email'] = 'Enter your email so we can reply.';
            if ($d['phone'] !== '' && !valid_phone($d['phone'])) $errors['phone'] = 'Enter a phone number with area code, or leave it empty.';
            if (mb_strlen($d['body']) < 5) $errors['body'] = $kind === 'careers' ? 'Tell us a little about yourself and the job you want.' : 'Write a short message.';
            if ($kind === 'private' && !$d['event_date']) $errors['event_date'] = 'Choose the date of your event.';
            if (!empty($v['website'])) $errors['form'] = 'Something went wrong. Please try again.';
            if (!$errors && rate_limited('msg:' . client_ip(), 5, 3600)) $errors['form'] = 'Too many messages from this connection. Please call us.';
            if (!$errors) {
                ex('INSERT INTO messages (kind, name, phone, email, subject, event_date, guests, body) VALUES (?,?,?,?,?,?,?,?)', [$kind, $d['name'], $d['phone'] ?: null, $d['email'], $d['subject'] ?: null, $d['event_date'], $d['guests'], $d['body']]);
                $label = ['contact' => 'Website message', 'private' => 'Private event request', 'careers' => 'Job application'][$kind];
                $rows = array_values(array_filter([['From', $d['name']], ['Email', $d['email']], $d['phone'] ? ['Phone', $d['phone']] : null, $d['subject'] ? ['Subject', $d['subject']] : null,
                    $d['event_date'] ? ['Event date', $d['event_date']] : null, $d['guests'] ? ['Guests', (string)$d['guests']] : null, ['Message', $d['body']]]));
                $notify = (string)cfg('shop_notify_email', '') ?: shop()['email'];
                if (valid_email($notify)) send_mail($notify, "$label from {$d['name']}", rows_text($rows), mail_html($label, $rows, 'Reply to this email to answer.', [site_url() . '/admin/messages', 'Open messages']), $d['email']);
                $sent = true; $v = [];
            }
        }
        view('pages/form-' . $kind, ['title' => ['contact' => 'Contact', 'private' => 'Private events', 'careers' => 'Careers'][$kind], 'errors' => $errors, 'v' => $v, 'sent' => $sent]); break;

    /* ---------- online ordering ---------- */
    case $path === '/cart' && $method === 'POST':
        $a = $_POST['action'] ?? '';
        if ($a === 'add') { $err = cart_add((int)($_POST['item'] ?? 0), (int)($_POST['qty'] ?? 1), (string)($_POST['note'] ?? '')); flash($err ?? 'Added to your order.', $err ? 'bad' : 'ok'); redirect(($_POST['back'] ?? '') === 'order' ? '/order' : '/menu#i' . (int)($_POST['item'] ?? 0)); }
        $c = cart(); $k = (int)($_POST['key'] ?? -1);
        if ($a === 'qty' && isset($c[$k])) { $q = (int)($_POST['qty'] ?? 1); if ($q < 1) unset($c[$k]); else $c[$k]['qty'] = min(CART_MAX_QTY, $q); cart_save($c); }
        if ($a === 'remove' && isset($c[$k])) { unset($c[$k]); cart_save($c); }
        if ($a === 'clear') cart_save([]);
        redirect('/order');
    case $path === '/order':
        $errors = []; $v = [];
        if ($method === 'POST') {
            $v = array_map(fn($x) => is_string($x) ? $x : '', $_POST);
            if (!empty($v['website'])) $errors['form'] = 'Something went wrong. Please try again.';
            else { [$o, $errors] = place_order($v); if ($o) redirect('/order/' . $o['token'] . '?new=1'); }
        }
        [$lines, $gone] = cart_lines();
        view('pages/order', ['title' => 'Your order', 'lines' => $lines, 'gone' => $gone, 'tot' => order_totals($lines, (float)settings()['tax_pct']), 'slots' => pickup_slots(), 'errors' => $errors, 'v' => $v, 'noindex' => true]); break;
    case (bool)preg_match('#^/order/([a-f0-9]{48})$#', $path, $m):
        $o = order_by_token($m[1]) ?? not_found();
        view('pages/order-status', ['title' => 'Order ' . $o['ref'], 'o' => $o, 'noindex' => true]); break;

    /* ---------- reservations ---------- */
    case $path === '/reservations':
        $errors = []; $v = ['party' => '2'];
        if ($method === 'POST') {
            $v = array_map(fn($x) => is_string($x) ? $x : '', $_POST);
            if (!empty($v['website'])) $errors['form'] = 'Something went wrong. Please try again.';
            else { [$r, $errors] = create_reservation($v); if ($r) redirect('/reservation/' . $r['token'] . '?new=1'); }
        }
        $days = res_days(); $day = (string)($v['day'] ?? $_GET['day'] ?? array_key_first($days) ?? '');
        if (!isset($days[$day])) $day = (string)(array_key_first($days) ?? '');
        $party = max(1, min((int)settings()['res_max_party'], (int)($v['party'] ?? $_GET['party'] ?? 2)));
        view('pages/reserve', ['title' => 'Reserve a table', 'days' => $days, 'day' => $day, 'party' => $party, 'times' => $day ? res_times($day, null, null, null, $party) : [], 'errors' => $errors, 'v' => $v]); break;
    case $path === '/reservations/times':
        header('Content-Type: application/json'); header('Cache-Control: no-store');
        echo json_encode(res_times((string)($_GET['day'] ?? ''), null, null, null, max(1, min((int)settings()['res_max_party'], (int)($_GET['party'] ?? 2))))); exit;
    case (bool)preg_match('#^/reservation/([a-f0-9]{48})$#', $path, $m):
        $r = res_by_token($m[1]) ?? not_found();
        if ($method === 'POST' && ($_POST['action'] ?? '') === 'cancel') { $err = res_set_status((int)$r['id'], 'cancelled', '', true); flash($err ?? 'Your reservation is cancelled.', $err ? 'bad' : 'ok'); redirect('/reservation/' . $r['token']); }
        view('pages/reservation', ['title' => 'Your reservation', 'r' => $r, 'noindex' => true]); break;

    default:
        not_found();
}
