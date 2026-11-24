<?php
/*
 * Live demo builder (only when config 'demo_builder' => true).
 *
 * A visitor enters a shop name, colour and logo and gets a complete branded shop — website and
 * management system — filled with fictional sample data, in its own set of tables (see tenant.php).
 * The browser keeps a cookie naming its demo; each demo has its own sign-in and its email never leaves
 * the server. Demos are deleted after 'demo_hours' (default 48). The request is saved as a lead and
 * emailed to 'demo_sales_email'.
 *
 * The registry (demo_shops) belongs to the main install and is always queried with the raw connection,
 * never through q()/ex(), so it can't be switched to a demo's tables.
 */

function demo_enabled(): bool { return (bool)cfg('demo_builder', false); }
function demo_hours(): int { return max(1, (int)cfg('demo_hours', 48)); }

/** Raw registry queries (never prefixed). */
function demo_db(string $sql, array $p = []): PDOStatement {
    static $ready = false;
    $pdo = db();
    if (!$ready) {
        $pdo->exec('CREATE TABLE IF NOT EXISTS demo_shops (
            id CHAR(10) NOT NULL PRIMARY KEY,
            shop_name VARCHAR(80) NOT NULL,
            contact_name VARCHAR(120) NOT NULL,
            email VARCHAR(191) NOT NULL,
            phone VARCHAR(30) NULL,
            city VARCHAR(80) NULL,
            brand_color CHAR(7) NOT NULL,
            open_key CHAR(32) NOT NULL,
            start_hash CHAR(64) NULL,
            start_until DATETIME NULL,
            ip_hash CHAR(64) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NOT NULL,
            last_seen_at DATETIME NULL,
            deleted_at DATETIME NULL,
            INDEX idx_expires (deleted_at, expires_at),
            INDEX idx_ip (ip_hash, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $ready = true;
    }
    $s = $pdo->prepare($sql); $s->execute($p); return $s;
}
function demo_row(string $id): ?array {
    if (!preg_match(DEMO_ID_RE, $id)) return null;
    return demo_db('SELECT * FROM demo_shops WHERE id=?', [$id])->fetch() ?: null;
}
function demo_active(?array $d): bool { return $d && $d['deleted_at'] === null && $d['expires_at'] > gmdate('Y-m-d H:i:s'); }
function current_demo(): ?array { static $d = false; if ($d === false || ($d && $d['id'] !== tenant_id())) $d = tenant_id() ? demo_row(tenant_id()) : null; return $d; }
function demo_hours_left(?array $d = null): int { $d ??= current_demo(); return $d ? max(0, (int)ceil((strtotime($d['expires_at'] . ' UTC') - time()) / 3600)) : 0; }
function demo_ip_hash(): string { return hash('sha256', client_ip() . '|' . (string)cfg('db_name')); }

function demo_set_cookie(?string $id): void {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    setcookie(DEMO_COOKIE, $id ?? '', ['expires' => $id ? time() + demo_hours() * 3600 : time() - 3600, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
    if ($id) $_COOKIE[DEMO_COOKIE] = $id; else unset($_COOKIE[DEMO_COOKIE]);
}

/** Runs before routing: picks the visitor's demo from the cookie, and handles /demo/* pages. */
function demo_boot(string $path, string $method): void {
    if (!demo_enabled()) return;
    $id = (string)($_COOKIE[DEMO_COOKIE] ?? '');
    if ($id !== '') {
        $d = demo_row($id);
        if (demo_active($d)) {
            set_tenant($id);
            if (!$d['last_seen_at'] || $d['last_seen_at'] < gmdate('Y-m-d H:i:s', time() - 600)) demo_db('UPDATE demo_shops SET last_seen_at=UTC_TIMESTAMP() WHERE id=?', [$id]);
        } else {
            demo_set_cookie(null);
            if (!str_starts_with($path, '/demo')) { header('Location: /demo?expired=1', true, 303); exit; }
        }
    }
    if (mt_rand(1, 20) === 1) demo_cleanup(3);
    if ($path === '/demo' || str_starts_with($path, '/demo/')) { require ROOT . '/app/routes-demo.php'; exit; }
}

/** Creates a demo shop. Returns [demo row, owner staff ID, password, one-time sign-in token] or throws RuntimeException with a message for the visitor. */
function demo_create(array $in, ?array $logo): array {
    $id = ''; $abc = 'abcdefghijkmnpqrstuvwxyz23456789';
    for ($i = 0; $i < 10; $i++) $id .= $abc[random_int(0, strlen($abc) - 1)];
    $openKey = bin2hex(random_bytes(16)); $start = bin2hex(random_bytes(24));
    demo_db('INSERT INTO demo_shops (id, shop_name, contact_name, email, phone, city, brand_color, open_key, start_hash, start_until, ip_hash, expires_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
        [$id, $in['shop_name'], $in['name'], $in['email'], $in['phone'] ?: null, $in['city'] ?: null, $in['brand_color'], $openKey, hash('sha256', $start),
         gmdate('Y-m-d H:i:s', time() + 1800), demo_ip_hash(), gmdate('Y-m-d H:i:s', time() + demo_hours() * 3600)]);
    $prev = tenant_id();
    set_tenant($id);
    try {
        // Tables are created on first use (migrations), then the shop profile and fictional sample data.
        $ini = preg_replace('/[^A-Z]/', '', shop_initials($in['shop_name']));
        $profile = ['name' => $in['shop_name'], 'brand_color' => $in['brand_color'], 'staff_prefix' => strlen($ini) >= 2 && strlen($ini) <= 6 ? $ini : 'DEMO',
            'city' => $in['city'] ?: SHOP_DEFAULTS['city'], 'email' => 'hello@example.com'];
        if (strlen(preg_replace('/\D/', '', $in['phone'])) >= 10) $profile['phone_office'] = $in['phone']; // their own number makes the demo feel like theirs
        if ($bad = shop_save($profile)) throw new RuntimeException('Please check: ' . implode(' ', $bad));
        if ($logo && ($err = save_brand_logo($logo))) throw new RuntimeException($err);
        $password = demo_password();
        $owner = demo_seed($in['name'], $password);
    } catch (Throwable $e) {
        demo_delete($id);
        set_tenant($prev);
        if ($e instanceof RuntimeException) throw $e;
        error_log('[demo] create failed: ' . $e->getMessage());
        throw new RuntimeException('Sorry — the demo could not be created. Please try again.');
    }
    set_tenant($prev);
    return [demo_row($id), $owner, $password, $start];
}

/** Easy to read and type: three short words and two digits. */
function demo_password(): string {
    $w = ['amber', 'bolt', 'cedar', 'delta', 'ember', 'flint', 'garnet', 'harbor', 'iron', 'jade', 'kite', 'lumen', 'maple', 'nova', 'onyx', 'pine', 'quartz', 'ridge', 'slate', 'tundra'];
    return $w[random_int(0, 19)] . '-' . $w[random_int(0, 19)] . '-' . $w[random_int(0, 19)] . '-' . random_int(10, 99);
}

/** One-time link that signs the visitor in to their demo's admin (valid 30 minutes). */
function demo_start(string $token): bool {
    $d = current_demo();
    if (!$d || !$d['start_hash'] || $d['start_until'] < gmdate('Y-m-d H:i:s') || !hash_equals($d['start_hash'], hash('sha256', $token))) return false;
    demo_db('UPDATE demo_shops SET start_hash=NULL, start_until=NULL WHERE id=?', [$d['id']]);
    $owner = q1("SELECT * FROM users WHERE role='owner' ORDER BY id LIMIT 1");
    if (!$owner) return false;
    login_user($owner);
    return true;
}

function demo_open_url(array $d): string { return site_url() . '/demo/open/' . $d['id'] . '/' . $d['open_key']; }

/** Drops a demo's tables and files. */
function demo_delete(string $id): void {
    if (!preg_match(DEMO_ID_RE, $id)) return;
    $pdo = db(); $p = "d{$id}_";
    $have = $pdo->prepare('SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE ?');
    $have->execute([str_replace('_', '\\_', $p) . '%']);
    $tables = array_values(array_filter(array_column($have->fetchAll(PDO::FETCH_NUM), 0), fn($t) => str_starts_with($t, $p) && in_array(substr($t, strlen($p)), app_tables(), true)));
    if ($tables) {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        try { $pdo->exec('DROP TABLE IF EXISTS ' . implode(', ', array_map(fn($t) => "`$t`", $tables))); }
        finally { $pdo->exec('SET FOREIGN_KEY_CHECKS=1'); }
    }
    rrmdir(ROOT . '/storage/demos/' . $id);
    demo_db('UPDATE demo_shops SET deleted_at=UTC_TIMESTAMP(), start_hash=NULL WHERE id=? AND deleted_at IS NULL', [$id]);
}
function rrmdir(string $dir): void {
    if (!is_dir($dir) || is_link($dir)) return;
    foreach (scandir($dir) as $f) { if ($f === '.' || $f === '..') continue; $p = "$dir/$f"; is_dir($p) && !is_link($p) ? rrmdir($p) : @unlink($p); }
    @rmdir($dir);
}
/** Deletes expired demos (a few per call). Returns how many. */
function demo_cleanup(int $limit = 10): int {
    $ids = array_column(demo_db('SELECT id FROM demo_shops WHERE deleted_at IS NULL AND expires_at < UTC_TIMESTAMP() ORDER BY expires_at LIMIT ' . max(1, $limit))->fetchAll(), 'id');
    foreach ($ids as $id) { try { demo_delete($id); } catch (Throwable $e) { error_log('[demo] cleanup ' . $id . ': ' . $e->getMessage()); } }
    return count($ids);
}

/** Emails the sales team about a new demo and the visitor their links (both from the main install, never from the demo). */
function demo_notify(array $d, string $staffId, string $password): void {
    $prev = tenant_id(); set_tenant(null);
    try {
        $hours = demo_hours();
        if (valid_email((string)cfg('demo_sales_email', ''))) {
            $rows = [['Shop', $d['shop_name']], ['Contact', $d['contact_name']], ['Email', $d['email']], ['Phone', $d['phone'] ?: '—'], ['City', $d['city'] ?: '—'], ['Brand colour', $d['brand_color']], ['Expires', $d['expires_at'] . ' UTC']];
            send_mail((string)cfg('demo_sales_email'), 'New restaurant demo: ' . $d['shop_name'], "A restaurant built a demo.\n\n" . rows_text($rows), mail_html('New restaurant demo', $rows, 'A restaurant just built a live demo. Follow up while it is fresh.'), $d['email']);
        }
        $rows = [['Your demo', demo_open_url($d)], ['Staff ID', $staffId], ['Password', $password], ['Available for', "$hours hours"]];
        $intro = "Here is the demo of {$d['shop_name']}'s website, online ordering and reservations system. Everything in it is fictional sample data, and it is deleted automatically after $hours hours.";
        send_mail($d['email'], "Your demo: {$d['shop_name']}", $intro . "\n\n" . rows_text($rows), mail_html('Your restaurant demo is ready', $rows, $intro, [demo_open_url($d), 'Open my demo']));
    } finally { set_tenant($prev); }
}
