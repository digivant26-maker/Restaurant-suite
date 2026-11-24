<?php
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** Render app/views/<name>.php inside a layout ('site', 'admin' or null). */
function view(string $name, array $vars = [], ?string $layout = 'site'): void {
    extract($vars, EXTR_SKIP);
    ob_start();
    require ROOT . "/app/views/$name.php";
    $content = ob_get_clean();
    if ($layout) require ROOT . "/app/views/layout-$layout.php"; else echo $content;
}
function redirect(string $to, int $code = 303): never { header('Location: ' . $to, true, $code); exit; }
function not_found(): never { http_response_code(404); view('pages/404', ['title' => 'Page not found', 'noindex' => true]); exit; }

function flash(string $msg, string $type = 'ok'): void { start_session(); $_SESSION['flash'] = [$msg, $type]; }
function take_flash(): ?array { start_session(); $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }

/** Field helpers: error message + aria attributes. */
function ferr(array $errors, string $k, string $prefix = ''): string { return isset($errors[$k]) ? '<span class="err" id="' . $prefix . $k . '-err">' . e($errors[$k]) . '</span>' : ''; }
function faria(array $errors, string $k, string $prefix = ''): string { return isset($errors[$k]) ? ' aria-invalid="true" aria-describedby="' . $prefix . $k . '-err"' : ''; }

const ICONS = [
    'phone' => 'M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2',
    'mail' => 'M4 6h16v12H4zM4 7l8 6 8-6',
    'pin' => 'M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11zM12 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5',
    'clock' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 7v5l3 2',
    'cal' => 'M4 6h16v14H4zM4 10h16M8 3v4M16 3v4',
    'check' => 'M5 12.5l4.5 4.5L19 7.5',
    'arrow' => 'M5 12h14M13 6l6 6-6 6',
    'menu' => 'M4 7h16M4 12h16M4 17h16',
    'collision' => 'M3 15l2-5h9l4 5M3 15h18v3H3zM7 18v1M17 18v1M14 4l1.5 3L19 6l-1.5 3',
    'dent' => 'M3 16c3-6 6 2 9-4s6 2 9-4M3 20h18',
    'bumper' => 'M3 11h18v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2zM7 14h2M15 14h2M5 11l2-4h10l2 4',
    'paint' => 'M4 4h12v5H4zM16 6h3v5h-8v3M10 14h2v6h-2z',
    'scratch' => 'M4 18L18 4M8 20L20 8M4 12l8-8',
    'shield' => 'M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6zM8.5 12l2.5 2.5 4.5-4.5',
    'wrench' => 'M14.5 5.5a4 4 0 0 0-5 5L4 16l4 4 5.5-5.5a4 4 0 0 0 5-5l-2.5 2.5-2.5-.5-.5-2.5z',
    'user' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21a8 8 0 0 1 16 0',
    'car' => 'M3 16v-4l2-5h14l2 5v4zM3 16v2h3v-2M18 16v2h3v-2M7 13h.01M17 13h.01',
    'camera' => 'M4 8h3l2-3h6l2 3h3v11H4zM12 16.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7',
    'image' => 'M4 5h16v14H4zM4 16l5-5 4 4 3-3 4 4M15.5 9.5h.01',
    // Staff screens (UX V2)
    'home' => 'M3 11l9-7 9 7M5 9.5V20h5v-6h4v6h5V9.5',
    'users' => 'M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 21a7 7 0 0 1 14 0M16 3.5a4 4 0 0 1 0 7.5M18 14a6 6 0 0 1 4 7',
    'file' => 'M6 3h9l4 4v14H6zM14 3v5h5M9 12h7M9 16h7',
    'package' => 'M12 3l8 4.5v9L12 21l-8-4.5v-9zM4 7.5l8 4.5 8-4.5M12 12v9M8 5.3l8 4.4',
    'chat' => 'M4 5h16v11H9l-5 4zM8 9.5h8M8 12.5h5',
    'chart' => 'M4 20h16M7 16v-5M12 16V6M17 16v-8',
    'list' => 'M9 6h11M9 12h11M9 18h11M4.5 6h.01M4.5 12h.01M4.5 18h.01',
    'gear' => 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM12 2.5v3M12 18.5v3M2.5 12h3M18.5 12h3M5.3 5.3l2.1 2.1M16.6 16.6l2.1 2.1M5.3 18.7l2.1-2.1M16.6 7.4l2.1-2.1',
    'team' => 'M12 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM5 20a7 7 0 0 1 14 0M17.5 12.5l1.5 1.5 3-3',
    'search' => 'M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM20 20l-4-4',
    'bell' => 'M6 16V11a6 6 0 0 1 12 0v5l2 2H4zM10 20a2 2 0 0 0 4 0',
    'x' => 'M6 6l12 12M18 6L6 18',
    'logout' => 'M14 4h5v16h-5M10 8l-4 4 4 4M6 12h10',
    'play' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM10 8.5v7l6-3.5z',
    'pause' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM10 9v6M14 9v6',
    'forward' => 'M4 6l7 6-7 6zM13 6l7 6-7 6z',
    'swap' => 'M4 8h14l-3-3M20 16H6l3 3',
    'clipboard' => 'M8 4h8v3H8zM6 5.5H5V21h14V5.5h-1M9 12h6M9 16h6',
    'note' => 'M5 4h14v11l-5 5H5zM14 20v-5h5M8 9h8M8 12h5',
    'globe' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18',
    'chev' => 'M9 6l6 6-6 6',
    'plus' => 'M12 5v14M5 12h14',
    'more' => 'M5 12h.01M12 12h.01M19 12h.01',
    'board' => 'M4 5h4v14H4zM10 5h4v9h-4zM16 5h4v6h-4z',
    'tv' => 'M3 6h18v11H3zM8 21h8M12 17v4',
    'info' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 11v5M12 8h.01',
    'flag' => 'M5 21V4M5 4h11l-2 4 2 4H5',
    'dollar' => 'M12 3v18M16.5 7.5c-1-1.5-2.7-2-4.5-2-2.5 0-4 1.3-4 3s1.5 2.6 4 3 4 1.3 4 3.2-1.8 3.3-4.3 3.3c-2 0-3.6-.7-4.7-2.2',
    'stop' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM9.5 9.5h5v5h-5z',
];
function icon(string $n, string $cls = 'i'): string {
    return '<svg class="' . $cls . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' . (ICONS[$n] ?? ICONS['check']) . '"/></svg>';
}
/** "10 min ago", "3 h ago", "Nov 12" — for list columns. */
function ago(?string $utc): string {
    if (!$utc) return '';
    $d = time() - (new DateTimeImmutable($utc, utc()))->getTimestamp();
    if ($d < 60) return 'just now';
    if ($d < 3600) return intdiv($d, 60) . ' min ago';
    if ($d < 86400) return intdiv($d, 3600) . ' h ago';
    return to_local($utc)->format('M j');
}
function asset(string $p): string { $f = ROOT . '/assets/' . $p; return '/assets/' . $p . (is_file($f) ? '?v=' . filemtime($f) : ''); }
function path(): string { return '/' . trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/'); }
function cur(string $prefix): string { $p = path(); return ($p === $prefix || str_starts_with($p, $prefix . '/')) ? ' aria-current="page"' : ''; }

/** Flash message without starting a session for visitors who have none (keeps public pages cacheable). */
function take_flash_if_session(): ?array { return (session_status() === PHP_SESSION_ACTIVE || isset($_COOKIE[session_cookie_name()])) ? take_flash() : null; }
function cart_count_if_session(): int { return (session_status() === PHP_SESSION_ACTIVE || isset($_COOKIE[session_cookie_name()])) ? array_sum(array_column(cart(), 'qty')) : 0; }
