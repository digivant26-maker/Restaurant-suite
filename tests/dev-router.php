<?php
// Local development only: emulates the .htaccess rules for `php -S localhost:3200 tests/dev-router.php`
$p = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('#^/(app|db|storage|vendor|tests)(/|$)|^/config(\.example)?\.php$|/\.|\.(sql|md|log)$#', $p)) { http_response_code(403); exit('Forbidden'); }
if ($p !== '/' && is_file(dirname(__DIR__) . $p) && str_starts_with($p, '/assets/')) return false;
require dirname(__DIR__) . '/index.php';
