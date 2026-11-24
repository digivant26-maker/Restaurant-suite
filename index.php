<?php
/* Restaurant & sports bar website and management system — front controller. All requests (except real files) arrive here via .htaccess. */
require __DIR__ . '/app/bootstrap.php';

set_exception_handler(function (Throwable $e) {
    error_log('[app] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    if (cfg('debug')) { echo '<pre>' . e($e) . '</pre>'; return; }
    echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Something went wrong</title><body style="font-family:system-ui;padding:40px;max-width:560px;margin:auto"><h1>Something went wrong</h1><p>Please try again in a moment.</p>';
});

$path = path();
if (cfg('staging')) header('X-Robots-Tag: noindex, nofollow'); // staging is never indexed

// One address only: a visit at any other host name (e.g. the main site's /demo folder) goes to site_url.
$want = strtolower((string)parse_url((string)cfg('site_url'), PHP_URL_HOST));
$host = strtolower(preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? '')));
if (preg_match('/(^|\.)example\.(com|org|net)$/', $want)) { // site_url still the placeholder: say so instead of redirecting there
    http_response_code(500); header('Content-Type: text/plain; charset=utf-8');
    exit("Setup needed: set 'site_url' in config.php to this site's address (for example https://demo.digivants.com).");
}
if ($want && $host && $host !== $want && !in_array($host, ['localhost', '127.0.0.1'], true)) {
    $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
    $base = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/index.php'))), '/'); // e.g. /demo when reached as digivants.com/demo/
    if ($base !== '' && ($uri === $base || str_starts_with($uri, $base . '/') || str_starts_with($uri, $base . '?'))) $uri = substr($uri, strlen($base)) ?: '/';
    header('Location: ' . rtrim((string)cfg('site_url'), '/') . $uri, true, 302); exit; // temporary: browsers don't cache it if settings change
}
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Live demo builder: picks the visitor's demo shop (must run before any session starts).
demo_boot($path, $method);
if (demo_enabled() && tenant_id() === null && !str_starts_with($path, '/admin') && !str_starts_with($path, '/brand')) redirect('/demo');

// Only the live address is indexed by search engines; the test site and any other host stay hidden.
if (!is_live_host()) header('X-Robots-Tag: noindex, nofollow');

// A form larger than the server accepts arrives empty (PHP drops it); say so instead of "page expired".
if ($method === 'POST' && !$_POST && !$_FILES && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > post_limit_bytes()) {
    http_response_code(413);
    view('pages/message', ['title' => 'Upload too large', 'msg' => 'The file you sent is larger than the server accepts. Please use a smaller photo.', 'noindex' => true]);
    exit;
}

// Reject cross-site form posts (CSRF) everywhere.
if ($method === 'POST' && !csrf_ok()) {
    http_response_code(419);
    view('pages/message', ['title' => 'Page expired', 'msg' => 'This page was open too long. Go back, reload the page and try again.', 'noindex' => true]);
    exit;
}

if (str_starts_with($path, '/admin')) { require ROOT . '/app/routes-admin.php'; exit; }
require ROOT . '/app/routes-site.php';
