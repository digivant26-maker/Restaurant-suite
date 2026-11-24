<?php
/** Staff side. Everything except sign-in and first-time setup needs a signed-in staff member. */
header('X-Robots-Tag: noindex, nofollow');
$m = [];

/* ---------- first-time setup: the first Admin (only while there are no users; needs setup_key) ---------- */
if ($path === '/admin/setup') {
    if ((int)q1('SELECT COUNT(*) n FROM users')['n'] > 0) redirect('/admin/login');
    $errors = []; $v = [];
    if ($method === 'POST') {
        $v = $_POST; $key = (string)cfg('setup_key', '');
        if (strlen($key) < 16 || !hash_equals($key, (string)($_POST['setup_key'] ?? ''))) $errors['setup_key'] = 'Setup key is not correct.';
        if (rate_limited('setup:' . client_ip(), 10, 900)) $errors['form'] = 'Too many attempts. Wait 15 minutes.';
        $name = clean($_POST['name'] ?? '', 120); $email = strtolower(clean($_POST['email'] ?? '', 191)); $pass = (string)($_POST['password'] ?? ''); $shopName = clean($_POST['shop_name'] ?? '', 80);
        if (mb_strlen($shopName) < 2) $errors['shop_name'] = 'Enter the restaurant name.';
        if (mb_strlen($name) < 2) $errors['name'] = 'Enter your name.';
        if (!valid_email($email)) $errors['email'] = 'Enter a valid email.';
        if (strlen($pass) < 10) $errors['password'] = 'Use at least 10 characters.';
        if (!$errors) {
            $ini = preg_replace('/[^A-Z]/', '', shop_initials($shopName));
            shop_save(['name' => $shopName] + (strlen($ini) >= 2 ? ['staff_prefix' => $ini] : []));
            ex('INSERT INTO users (email, name, password_hash, role) VALUES (?,?,?,"owner")', [$email, $name, password_hash($pass, PASSWORD_DEFAULT)]);
            $code = assign_staff_code((int)db()->lastInsertId());
            login_user(q1('SELECT * FROM users WHERE staff_code=?', [$code]));
            flash("Admin account created. Your Staff ID is $code — use it with your password to sign in.");
            redirect('/admin');
        }
    }
    view('admin/setup', ['title' => 'First-time setup', 'errors' => $errors, 'v' => $v], 'bare'); exit;
}

/* ---------- sign in / out ---------- */
if ($path === '/admin/login') {
    if ((int)q1('SELECT COUNT(*) n FROM users')['n'] === 0) redirect('/admin/setup');
    $error = null; $code = '';
    if ($method === 'POST') {
        $code = normalize_staff_code((string)($_POST['staff_id'] ?? '')); $pass = (string)($_POST['password'] ?? '');
        if (rate_limited('login:' . client_ip(), 10, 900)) $error = 'Too many attempts. Wait 15 minutes and try again.';
        else {
            $u = $code !== '' ? q1('SELECT * FROM users WHERE staff_code=?', [$code]) : null;
            if ($u && (int)$u['active'] && password_verify($pass, $u['password_hash'])) {
                login_user($u); ex('UPDATE users SET last_login_at=UTC_TIMESTAMP() WHERE id=?', [$u['id']]);
                redirect($u['role'] === 'kitchen' ? '/admin/kitchen' : '/admin');
            }
            if (!$u) password_verify($pass, '$2y$10$tuOKTUxmll8OwWWZExqsCOvq76eYkiDNy3p8HP7HCiTYiVKznE9jm'); // same work either way, so the page doesn't reveal which Staff IDs exist
            $error = 'That Staff ID and password didn’t match.';
        }
    }
    view('admin/login', ['title' => 'Sign in', 'error' => $error, 'code' => $code], 'bare'); exit;
}
if ($path === '/admin/logout' && $method === 'POST') { logout(); redirect('/admin/login'); }

$user = require_admin();
$role = $user['role'];
/** Which roles may open which areas. */
$AREAS = ['dashboard' => ['owner', 'manager', 'server'], 'orders' => ['owner', 'manager', 'server', 'kitchen'], 'kitchen' => ['owner', 'manager', 'server', 'kitchen'],
    'reservations' => ['owner', 'manager', 'server'], 'menu' => ['owner', 'manager'], 'events' => ['owner', 'manager'], 'games' => ['owner', 'manager', 'server'],
    'gallery' => ['owner', 'manager'], 'messages' => ['owner', 'manager'], 'activity' => ['owner', 'manager'], 'staff' => ['owner'], 'settings' => ['owner'], 'demos' => ['owner'], 'account' => array_keys(ROLES)];
$area = function (string $a) use ($AREAS) { require_role(...$AREAS[$a]); };
$back = fn(string $fallback) => (str_starts_with((string)($_POST['back'] ?? ''), '/admin') ? (string)$_POST['back'] : $fallback);

/* ---------- dashboard ---------- */
if ($path === '/admin') {
    if ($role === 'kitchen') redirect('/admin/kitchen');
    $area('dashboard');
    $today0 = to_utc(now_local()->setTime(0, 0)); $today1 = to_utc(now_local()->setTime(0, 0)->modify('+1 day +4 hours'));
    view('admin/dashboard', ['title' => 'Today', 'user' => $user,
        'orders' => q("SELECT * FROM orders WHERE status IN ('new','accepted','ready') ORDER BY pickup_at LIMIT 30"),
        'res' => q("SELECT * FROM reservations WHERE starts_at >= ? AND starts_at < ? AND status IN ('requested','confirmed','seated') ORDER BY starts_at", [$today0, $today1]),
        'pending' => (int)q1("SELECT COUNT(*) n FROM reservations WHERE status='requested' AND starts_at >= UTC_TIMESTAMP()")['n'],
        'games' => games_upcoming(1),
        'msgs' => (int)q1('SELECT COUNT(*) n FROM messages WHERE handled=0')['n'],
        'sales' => q1("SELECT COUNT(*) n, COALESCE(SUM(total),0) t FROM orders WHERE created_at >= ? AND status <> 'cancelled'", [$today0]),
        'soldout' => q('SELECT name FROM menu_items WHERE available=0 ORDER BY name')], 'admin'); exit;
}

/* ---------- orders and the kitchen screen ---------- */
if (preg_match('#^/admin/orders/(\d+)$#', $path, $m) && $method === 'POST') {
    $area('orders');
    $err = order_set_status((int)$m[1], (string)($_POST['to'] ?? ''), (string)($_POST['reason'] ?? ''));
    if ($err) flash($err, 'bad');
    redirect($back('/admin/orders'));
}
if ($path === '/admin/orders') {
    $area('orders');
    $show = in_array($_GET['show'] ?? '', ['open', 'done'], true) ? $_GET['show'] : 'open';
    $rows = $show === 'open' ? q("SELECT * FROM orders WHERE status IN ('new','accepted','ready') ORDER BY pickup_at") : q("SELECT * FROM orders WHERE status IN ('picked_up','cancelled') ORDER BY created_at DESC LIMIT 100");
    foreach ($rows as &$r) $r['items'] = q('SELECT * FROM order_items WHERE order_id=? ORDER BY id', [$r['id']]);
    view('admin/orders', ['title' => 'Orders', 'user' => $user, 'rows' => $rows, 'show' => $show], 'admin'); exit;
}
if ($path === '/admin/kitchen') {
    $area('kitchen');
    $cols = [];
    foreach (['new', 'accepted', 'ready'] as $st) {
        $cols[$st] = q('SELECT * FROM orders WHERE status=? ORDER BY pickup_at, id', [$st]);
        foreach ($cols[$st] as &$o) $o['items'] = q('SELECT * FROM order_items WHERE order_id=? ORDER BY id', [$o['id']]);
        unset($o);
    }
    view('admin/kitchen', ['title' => 'Kitchen', 'user' => $user, 'cols' => $cols], 'kitchen'); exit;
}

/* ---------- reservations ---------- */
if (preg_match('#^/admin/reservations/(\d+)$#', $path, $m) && $method === 'POST') {
    $area('reservations');
    $err = res_set_status((int)$m[1], (string)($_POST['to'] ?? ''), (string)($_POST['reason'] ?? ''));
    flash($err ?? 'Reservation updated.', $err ? 'bad' : 'ok');
    redirect($back('/admin/reservations'));
}
if ($path === '/admin/reservations') {
    $area('reservations');
    $errors = []; $v = [];
    if ($method === 'POST') { // staff take a booking by phone
        $v = array_map(fn($x) => is_string($x) ? $x : '', $_POST);
        [$r, $errors] = create_reservation($v, true);
        if ($r) { flash("Booked: {$r['name']}, party of {$r['party']}, " . fmt_when($r['starts_at']) . '.'); redirect('/admin/reservations?day=' . to_local($r['starts_at'])->format('Y-m-d')); }
    }
    $day = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_GET['day'] ?? '')) ? $_GET['day'] : now_local()->format('Y-m-d');
    $from = to_utc(new DateTimeImmutable("$day 00:00", tz())); $to = to_utc((new DateTimeImmutable("$day 00:00", tz()))->modify('+1 day +4 hours'));
    view('admin/reservations', ['title' => 'Reservations', 'user' => $user, 'day' => $day,
        'rows' => q('SELECT * FROM reservations WHERE starts_at >= ? AND starts_at < ? ORDER BY starts_at, id', [$from, $to]),
        'requests' => q("SELECT * FROM reservations WHERE status='requested' AND starts_at >= UTC_TIMESTAMP() ORDER BY starts_at"),
        'times' => res_times($day, null, now_local()->modify('-1 day'), array_merge(settings(), ['res_lead_hours' => 0])), 'errors' => $errors, 'v' => $v], 'admin'); exit;
}

/* ---------- menu ---------- */
if ($path === '/admin/menu') {
    $area('menu');
    if ($method === 'POST') {
        $a = $_POST['action'] ?? '';
        if ($a === 'cat_save') {
            $id = (int)($_POST['id'] ?? 0); $name = clean($_POST['name'] ?? '', 80);
            if (mb_strlen($name) < 2) flash('Enter the category name.', 'bad');
            else {
                $vals = [$name, clean($_POST['description'] ?? '', 255) ?: null, (int)($_POST['position'] ?? 0), empty($_POST['active']) ? 0 : 1];
                $id ? ex('UPDATE menu_categories SET name=?, description=?, position=?, active=? WHERE id=?', [...$vals, $id]) : ex('INSERT INTO menu_categories (name, description, position, active) VALUES (?,?,?,?)', $vals);
                log_activity('menu.category', ($id ? 'Updated' : 'Added') . " category $name"); flash('Category saved.');
            }
        } elseif ($a === 'cat_delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ((int)q1('SELECT COUNT(*) n FROM menu_items WHERE category_id=?', [$id])['n'] > 0) flash('Move or delete the items in this category first.', 'bad');
            else { ex('DELETE FROM menu_categories WHERE id=?', [$id]); flash('Category deleted.'); }
        } elseif ($a === 'item_save') {
            $id = (int)($_POST['id'] ?? 0);
            [$d, $err] = validate_item($_POST);
            if (!$err) { [$photo, $perr] = save_photo($_FILES['photo'] ?? [], 'menu'); $err = $perr; }
            if ($err) { flash($err, 'bad'); redirect('/admin/menu' . ($id ? "?edit=$id" : '?new=1') . '#item-form'); }
            $cols = ['category_id', 'name', 'description', 'price', 'tags', 'available', 'orderable', 'featured', 'position'];
            $vals = array_map(fn($c) => $d[$c] === '' ? null : $d[$c], $cols);
            if ($id) {
                $old = q1('SELECT photo FROM menu_items WHERE id=?', [$id]);
                ex('UPDATE menu_items SET ' . implode('=?, ', $cols) . '=? WHERE id=?', [...$vals, $id]);
                if ($photo || !empty($_POST['remove_photo'])) { ex('UPDATE menu_items SET photo=? WHERE id=?', [$photo, $id]); delete_photo('menu', $old['photo'] ?? null); }
            } else ex('INSERT INTO menu_items (' . implode(', ', $cols) . ', photo) VALUES (' . implode(',', array_fill(0, count($cols) + 1, '?')) . ')', [...$vals, $photo]);
            log_activity('menu.item', ($id ? 'Updated' : 'Added') . " {$d['name']} " . money($d['price'])); flash('Menu item saved.');
        } elseif ($a === 'item_toggle') {
            $id = (int)($_POST['id'] ?? 0); $i = q1('SELECT name, available FROM menu_items WHERE id=?', [$id]);
            if ($i) { ex('UPDATE menu_items SET available=? WHERE id=?', [(int)!$i['available'], $id]); log_activity('menu.available', $i['name'] . ($i['available'] ? ' sold out' : ' back on')); flash($i['name'] . ($i['available'] ? ' is marked sold out.' : ' is available again.')); }
        } elseif ($a === 'item_delete') {
            $id = (int)($_POST['id'] ?? 0); $i = q1('SELECT name, photo FROM menu_items WHERE id=?', [$id]);
            if ($i) { ex('DELETE FROM menu_items WHERE id=?', [$id]); delete_photo('menu', $i['photo']); log_activity('menu.deleted', $i['name']); flash('Item deleted.'); }
        }
        redirect('/admin/menu');
    }
    $edit = isset($_GET['edit']) ? q1('SELECT * FROM menu_items WHERE id=?', [(int)$_GET['edit']]) : null;
    view('admin/menu', ['title' => 'Menu', 'user' => $user, 'cats' => menu_full(false), 'edit' => $edit, 'new' => isset($_GET['new']), 'editCat' => isset($_GET['cat']) ? q1('SELECT * FROM menu_categories WHERE id=?', [(int)$_GET['cat']]) : null], 'admin'); exit;
}

/* ---------- events & specials ---------- */
if ($path === '/admin/events') {
    $area('events');
    if ($method === 'POST') {
        $a = $_POST['action'] ?? ''; $id = (int)($_POST['id'] ?? 0);
        if ($a === 'save') {
            [$d, $err] = validate_event($_POST);
            if ($err) { flash($err, 'bad'); redirect('/admin/events' . ($id ? "?edit=$id" : '')); }
            $cols = array_keys($d);
            $id ? ex('UPDATE events SET ' . implode('=?, ', $cols) . '=? WHERE id=?', [...array_values($d), $id]) : ex('INSERT INTO events (' . implode(', ', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')', array_values($d));
            log_activity('event.saved', $d['title']); flash('Saved.');
        } elseif ($a === 'delete') { ex('DELETE FROM events WHERE id=?', [$id]); flash('Deleted.'); }
        redirect('/admin/events');
    }
    view('admin/events', ['title' => 'Events & specials', 'user' => $user, 'events' => events_list('event', false), 'specials' => events_list('special', false), 'edit' => isset($_GET['edit']) ? q1('SELECT * FROM events WHERE id=?', [(int)$_GET['edit']]) : null], 'admin'); exit;
}

/* ---------- games schedule ---------- */
if ($path === '/admin/games') {
    $area('games');
    if ($method === 'POST') {
        $a = $_POST['action'] ?? ''; $id = (int)($_POST['id'] ?? 0);
        if ($a === 'save') {
            [$d, $err] = validate_game($_POST);
            if ($err) { flash($err, 'bad'); start_session(); $_SESSION['game_form'] = $_POST; redirect('/admin/games' . ($id ? "?edit=$id" : '')); }
            $cols = array_keys($d);
            $id ? ex('UPDATE games SET ' . implode('=?, ', $cols) . '=? WHERE id=?', [...array_values($d), $id]) : ex('INSERT INTO games (' . implode(', ', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')', array_values($d));
            log_activity('game.saved', "{$d['away']} at {$d['home']}"); flash('Game saved.');
        } elseif ($a === 'delete') { ex('DELETE FROM games WHERE id=?', [$id]); flash('Game removed.'); }
        redirect('/admin/games');
    }
    start_session(); $form = $_SESSION['game_form'] ?? null; unset($_SESSION['game_form']);
    view('admin/games', ['title' => 'Games schedule', 'user' => $user, 'games' => games_upcoming(30), 'edit' => isset($_GET['edit']) ? q1('SELECT * FROM games WHERE id=?', [(int)$_GET['edit']]) : null, 'form' => $form], 'admin'); exit;
}

/* ---------- gallery ---------- */
if ($path === '/admin/gallery') {
    $area('gallery');
    if ($method === 'POST') {
        if (($_POST['action'] ?? '') === 'add') {
            [$f, $err] = save_photo($_FILES['photo'] ?? [], 'gallery');
            if ($err || !$f) flash($err ?? 'Choose a photo.', 'bad');
            else { ex('INSERT INTO gallery_photos (file, caption) VALUES (?,?)', [$f, clean($_POST['caption'] ?? '', 160) ?: null]); flash('Photo added.'); }
        } elseif (($_POST['action'] ?? '') === 'delete') {
            $p = q1('SELECT * FROM gallery_photos WHERE id=?', [(int)($_POST['id'] ?? 0)]);
            if ($p) { ex('DELETE FROM gallery_photos WHERE id=?', [$p['id']]); delete_photo('gallery', $p['file']); flash('Photo removed.'); }
        }
        redirect('/admin/gallery');
    }
    view('admin/gallery', ['title' => 'Gallery', 'user' => $user, 'photos' => q('SELECT * FROM gallery_photos ORDER BY position, id DESC')], 'admin'); exit;
}

/* ---------- messages: website, private events, job applications ---------- */
if ($path === '/admin/messages') {
    $area('messages');
    if ($method === 'POST') { ex('UPDATE messages SET handled=? WHERE id=?', [empty($_POST['handled']) ? 0 : 1, (int)($_POST['id'] ?? 0)]); redirect('/admin/messages' . (isset($_POST['kind']) ? '?kind=' . rawurlencode((string)$_POST['kind']) : '')); }
    $kind = in_array($_GET['kind'] ?? '', ['contact', 'private', 'careers'], true) ? $_GET['kind'] : null;
    view('admin/messages', ['title' => 'Messages', 'user' => $user, 'kind' => $kind, 'rows' => q('SELECT * FROM messages' . ($kind ? ' WHERE kind=?' : '') . ' ORDER BY handled, created_at DESC LIMIT 200', $kind ? [$kind] : [])], 'admin'); exit;
}
if ($path === '/admin/activity') { $area('activity'); view('admin/activity', ['title' => 'Activity', 'user' => $user, 'rows' => q('SELECT * FROM activity_log ORDER BY id DESC LIMIT 300')], 'admin'); exit; }

/* ---------- staff ---------- */
if ($path === '/admin/staff') {
    $area('staff');
    if ($method === 'POST') {
        $a = $_POST['action'] ?? '';
        if ($a === 'add') {
            $name = clean($_POST['name'] ?? '', 120); $email = strtolower(clean($_POST['email'] ?? '', 191)); $r = (string)($_POST['role'] ?? ''); $pass = (string)($_POST['password'] ?? '');
            $err = mb_strlen($name) < 2 ? 'Enter a name.' : (!isset(ROLES[$r]) ? 'Choose a role.' : ($email !== '' && !valid_email($email) ? 'Enter a valid email or leave it empty.' : (strlen($pass) < 10 ? 'Give a temporary password of at least 10 characters.' : null)));
            if ($err) flash($err, 'bad');
            else { ex('INSERT INTO users (email, name, password_hash, role, phone) VALUES (?,?,?,?,?)', [$email ?: null, $name, password_hash($pass, PASSWORD_DEFAULT), $r, clean($_POST['phone'] ?? '', 30) ?: null]);
                   $code = assign_staff_code((int)db()->lastInsertId()); log_activity('staff.added', "$name, $code (" . ROLES[$r] . ')'); flash("$name added. Their Staff ID is $code."); }
        } elseif ($a === 'update') {
            $id = (int)($_POST['id'] ?? 0); $r = (string)($_POST['role'] ?? ''); $active = empty($_POST['active']) ? 0 : 1;
            if ($id === $user['id'] && ($r !== 'owner' || !$active)) flash('You can’t remove your own Admin access.', 'bad');
            elseif (isset(ROLES[$r])) { ex('UPDATE users SET role=?, active=? WHERE id=?', [$r, $active, $id]); flash('Saved.'); }
        } elseif ($a === 'reset') {
            $id = (int)($_POST['id'] ?? 0); $pass = (string)($_POST['password'] ?? '');
            if (strlen($pass) < 10) flash('Use at least 10 characters.', 'bad'); else { ex('UPDATE users SET password_hash=? WHERE id=?', [password_hash($pass, PASSWORD_DEFAULT), $id]); flash('Password changed.'); }
        }
        redirect('/admin/staff');
    }
    view('admin/staff', ['title' => 'Staff', 'user' => $user, 'rows' => q('SELECT * FROM users ORDER BY active DESC, FIELD(role,"owner","manager","server","kitchen"), name')], 'admin'); exit;
}

/* ---------- settings ---------- */
if ($path === '/admin/settings') {
    $area('settings');
    if ($method === 'POST') {
        $a = $_POST['action'] ?? '';
        if ($a === 'profile') {
            $err = shop_save(array_intersect_key($_POST, SHOP_FIELDS));
            if (!$err && !empty($_POST['remove_logo'])) { $old = shop()['logo_file']; shop_set('logo_file', ''); if ($old && preg_match('/^logo-[a-f0-9]{16}\.png$/', $old)) @unlink(brand_dir() . '/' . $old); }
            if (!$err && ($le = save_brand_logo($_FILES['logo'] ?? []))) $err = ['logo' => $le];
            flash($err ? implode(' ', $err) : 'Profile and branding saved.', $err ? 'bad' : 'ok');
        } elseif ($a === 'ordering' || $a === 'reservations') {
            $t = fn($k) => preg_match('/^\d{2}:\d{2}$/', (string)($_POST[$k] ?? '')) ? $_POST[$k] . ':00' : null;
            $days = fn($k) => implode(',', array_filter(array_map('intval', (array)($_POST[$k] ?? [])), fn($d) => $d >= 1 && $d <= 7));
            if ($a === 'ordering') {
                $vals = ['ordering_on' => empty($_POST['ordering_on']) ? 0 : 1, 'order_open' => $t('order_open'), 'order_close' => $t('order_close'), 'order_days' => $days('order_days'),
                         'order_lead_minutes' => max(5, min(180, (int)($_POST['order_lead_minutes'] ?? 20))), 'tax_pct' => max(0, min(20, (float)($_POST['tax_pct'] ?? 0)))];
            } else {
                $vals = ['reservations_on' => empty($_POST['reservations_on']) ? 0 : 1, 'res_open' => $t('res_open'), 'res_close' => $t('res_close'), 'res_days' => $days('res_days'),
                         'res_slot_minutes' => in_array((int)($_POST['res_slot_minutes'] ?? 30), [15, 30, 60], true) ? (int)$_POST['res_slot_minutes'] : 30,
                         'res_seats_per_slot' => max(1, min(500, (int)($_POST['res_seats_per_slot'] ?? 40))), 'res_max_party' => max(1, min(50, (int)($_POST['res_max_party'] ?? 12))),
                         'res_lead_hours' => max(0, min(72, (int)($_POST['res_lead_hours'] ?? 2))), 'res_window_days' => max(1, min(180, (int)($_POST['res_window_days'] ?? 30))), 'res_auto_confirm' => empty($_POST['res_auto_confirm']) ? 0 : 1];
            }
            if (in_array(null, $vals, true) || in_array('', [$vals['order_days'] ?? 'x', $vals['res_days'] ?? 'x'], true)) flash('Check the times and choose at least one day.', 'bad');
            else { ex('UPDATE settings SET ' . implode('=?, ', array_keys($vals)) . '=? WHERE id=1', array_values($vals)); log_activity('settings.' . $a, 'Settings changed'); flash('Settings saved.'); }
        }
        redirect('/admin/settings#' . ($a ?: 'profile'));
    }
    view('admin/settings', ['title' => 'Settings', 'user' => $user, 's' => settings()], 'admin'); exit;
}

/* ---------- demo requests (DIGIVANT's own admin on the demo site; never inside a demo) ---------- */
if ($path === '/admin/demos') {
    $area('demos');
    if (!demo_enabled() || tenant_id() !== null) not_found();
    if ($method === 'POST' && ($_POST['action'] ?? '') === 'cleanup') { $n = demo_cleanup(50); flash($n ? "$n expired demo(s) deleted." : 'No expired demos to delete.'); redirect('/admin/demos'); }
    $rows = demo_db('SELECT id, shop_name, contact_name, email, phone, city, brand_color, created_at, expires_at, last_seen_at, deleted_at FROM demo_shops ORDER BY created_at DESC LIMIT 300')->fetchAll();
    if (($_GET['format'] ?? '') === 'csv') {
        header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="demo-requests.csv"');
        $o = fopen('php://output', 'w'); fputcsv($o, ['Created (UTC)', 'Restaurant', 'Contact', 'Email', 'Phone', 'City', 'Last visit (UTC)', 'Status']);
        foreach ($rows as $r) fputcsv($o, array_map(fn($x) => preg_match('/^[=+\-@]/', (string)$x) ? "'" . $x : $x, [$r['created_at'], $r['shop_name'], $r['contact_name'], $r['email'], $r['phone'], $r['city'], $r['last_seen_at'], $r['deleted_at'] ? 'Deleted' : (demo_active($r) ? 'Active' : 'Expired')]));
        exit;
    }
    view('admin/demos', ['title' => 'Demo requests', 'user' => $user, 'rows' => $rows], 'admin'); exit;
}

/* ---------- my account ---------- */
if ($path === '/admin/account') {
    $area('account');
    if ($method === 'POST') {
        $full = q1('SELECT password_hash FROM users WHERE id=?', [$user['id']]); $new = (string)($_POST['new'] ?? '');
        if (!password_verify((string)($_POST['current'] ?? ''), $full['password_hash'])) flash('Your current password is not correct.', 'bad');
        elseif (strlen($new) < 10) flash('Use at least 10 characters for the new password.', 'bad');
        else { ex('UPDATE users SET password_hash=? WHERE id=?', [password_hash($new, PASSWORD_DEFAULT), $user['id']]); flash('Password changed.'); }
        redirect('/admin/account');
    }
    view('admin/account', ['title' => 'My account', 'user' => $user], 'admin'); exit;
}

not_found();
