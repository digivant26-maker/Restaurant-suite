<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));

$cfgFile = getenv('RS_CONFIG') ?: ROOT . '/config.php'; // RS_CONFIG lets the test suite use a test configuration
if (!is_file($cfgFile)) {
    http_response_code(500);
    exit('Setup needed: copy config.example.php to config.php and fill it in.');
}
$GLOBALS['CFG'] = require $cfgFile;
function cfg(string $k, $d = null) { return $GLOBALS['CFG'][$k] ?? $d; }

date_default_timezone_set('UTC');
ini_set('display_errors', cfg('debug') ? '1' : '0');
error_reporting(E_ALL);

require ROOT . '/app/tenant.php';
require ROOT . '/app/site.php';
require ROOT . '/app/db.php';
require ROOT . '/app/core.php';
require ROOT . '/app/mail.php';
require ROOT . '/app/auth.php';
require ROOT . '/app/view.php';
require ROOT . '/app/media.php';
require ROOT . '/app/menu.php';
require ROOT . '/app/orders.php';
require ROOT . '/app/reservations.php';
require ROOT . '/app/events.php';
require ROOT . '/app/demo.php';
require ROOT . '/app/demo-seed.php';
require ROOT . '/app/product.php';

function site_url(): string { return rtrim((string)cfg('site_url', 'http://localhost'), '/'); }
/** True only on the public live address (the host of site_url). Everything else is kept out of search engines. */
function is_live_host(): bool { return !cfg('staging', false) && strtolower((string)($_SERVER['HTTP_HOST'] ?? '')) === strtolower((string)parse_url(site_url(), PHP_URL_HOST)); }
function post_limit_bytes(): int {
    $v = trim((string)ini_get('post_max_size')); $n = (int)$v; $u = strtolower(substr($v, -1));
    return $u === 'g' ? $n << 30 : ($u === 'm' ? $n << 20 : ($u === 'k' ? $n << 10 : $n));
}
