<?php
function start_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name(session_cookie_name());
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
function csrf_token(): string { start_session(); return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">'; }
function csrf_ok(): bool { start_session(); return isset($_POST['_csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['_csrf']); }

/** Staff ID from the account number and the shop's prefix (Settings): 1 → SHOP001. Derived from the auto-increment id, so a number is never reused. */
function staff_code(int $id): string { return shop()['staff_prefix'] . str_pad((string)$id, 3, '0', STR_PAD_LEFT); }
/** Normalises what someone typed as a Staff ID: case and spaces don't matter. */
function normalize_staff_code(string $s): string { return strtoupper(preg_replace('/\s+/', '', $s)); }
/** Gives a newly inserted user their Staff ID and returns it. */
function assign_staff_code(int $id): string { $c = staff_code($id); ex('UPDATE users SET staff_code=? WHERE id=?', [$c, $id]); return $c; }

/** Signed-in staff member, re-read from the database each request so role changes and deactivation apply immediately. */
function current_user(): ?array {
    static $cache = false;
    if ($cache !== false) return $cache;
    start_session();
    if (empty($_SESSION['uid'])) return $cache = null;
    if (($_SESSION['at'] ?? 0) < time() - 12 * 3600) { logout(); return $cache = null; } // 12h max session
    $u = q1('SELECT * FROM users WHERE id=?', [(int)$_SESSION['uid']]);
    if (!$u || !(int)$u['active']) { logout(); return $cache = null; }
    return $cache = ['id' => (int)$u['id'], 'staff_code' => $u['staff_code'], 'name' => $u['name'], 'email' => $u['email'], 'role' => $u['role'], 'language' => $u['language'] ?? 'en', 'must_change' => (int)($u['must_change_password'] ?? 0)];
}
function require_admin(): array {
    $u = current_user();
    if (!$u) { header('Location: /admin/login'); exit; }
    return $u;
}
function can(string ...$roles): bool { $u = current_user(); return $u && in_array($u['role'], $roles, true); }
/** Stop with 403 unless the signed-in user has one of the roles. */
function require_role(string ...$roles): void {
    if (can(...$roles)) return;
    http_response_code(403);
    view('admin/forbidden', ['title' => t('Not allowed'), 'user' => current_user()], 'admin');
    exit;
}
function login_user(array $u): void {
    start_session(); session_regenerate_id(true);
    $_SESSION['uid'] = (int)$u['id']; $_SESSION['uname'] = $u['name']; $_SESSION['uemail'] = $u['email']; $_SESSION['at'] = time();
}
function logout(): void {
    start_session(); $_SESSION = [];
    setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => '/']);
    session_destroy();
}
