<?php
/**
 * Restaurant profile and branding.
 * Every shop-specific fact (name, phones, address, hours, colours, logo) is a setting the owner edits in
 * Admin → Settings → Shop profile & branding, stored in the shop_profile table. SHOP_DEFAULTS is a neutral
 * placeholder used until the owner fills it in (and in tests).
 */
const SHOP_DEFAULTS = [
    'name' => 'Your Sports Bar',
    'tagline' => 'Sports Bar & Grill',
    'headline' => 'Every game. Great food. Cold drinks.',
    'about' => 'A neighborhood sports bar and grill with big screens, a full bar and a kitchen that cooks everything to order.',
    'phone_office' => '(555) 010-0200',
    'email' => 'hello@example.com',
    'street' => '123 Main Street',
    'city' => 'Springfield',
    'region' => 'ST',
    'zip' => '00000',
    'hours_text' => 'Mon–Thu 11 AM – 12 AM · Fri–Sat 11 AM – 2 AM · Sun 10 AM – 12 AM',
    'kitchen_text' => 'Kitchen open until 11 PM',
    'happy_hour_text' => 'Happy hour Mon–Fri 3–6 PM',
    'facebook_url' => '',
    'instagram_url' => '',
    'tiktok_url' => '',
    'brand_color' => '#D4A017',
    'staff_prefix' => 'BAR',
    'logo_file' => '',
];
/** Fields the owner may edit, with a maximum length each. */
const SHOP_FIELDS = ['name' => 80, 'tagline' => 80, 'headline' => 90, 'about' => 600, 'phone_office' => 30, 'email' => 120, 'street' => 120,
    'city' => 80, 'region' => 40, 'zip' => 20, 'hours_text' => 160, 'kitchen_text' => 80, 'happy_hour_text' => 80,
    'facebook_url' => 200, 'instagram_url' => 200, 'tiktok_url' => 200, 'brand_color' => 7, 'staff_prefix' => 6];

/** The shop's profile with derived fields (dial strings, map links, initials). */
function shop(): array {
    if (isset($GLOBALS['SHOP_CACHE'])) return $GLOBALS['SHOP_CACHE'];
    $v = [];
    if (function_exists('q')) { try { foreach (q('SELECT k, v FROM shop_profile') as $r) $v[$r['k']] = (string)$r['v']; } catch (Throwable $e) { /* not migrated yet */ } }
    $s = array_merge(SHOP_DEFAULTS, array_intersect_key($v, SHOP_DEFAULTS));
    $tel = fn(string $p) => ($d = preg_replace('/\D/', '', $p)) === '' ? '' : (strlen($d) === 10 ? '+1' . $d : '+' . $d);
    $addr = trim("{$s['street']}, {$s['city']}, {$s['region']} {$s['zip']}", ' ,');
    $s += ['phone_office_tel' => $tel($s['phone_office']), 'address' => $addr,
        'maps_url' => 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($addr),
        'maps_embed' => 'https://www.google.com/maps?q=' . rawurlencode($addr) . '&output=embed',
        'initials' => shop_initials($s['name'])];
    return $GLOBALS['SHOP_CACHE'] = $s;
}
function shop_initials(string $name): string {
    $w = array_values(array_filter(preg_split('/[^\p{L}\p{N}]+/u', preg_replace('/[\x{2019}\x{2018}\']/u', '', $name)), fn($x) => $x !== '' && !in_array(mb_strtolower($x), ['the', 'and', 'of', '&'], true)));
    $i = mb_strtoupper(mb_substr($w[0] ?? 'S', 0, 1) . mb_substr($w[1] ?? '', 0, 1));
    return $i !== '' ? $i : 'S';
}
/** Validates and saves profile fields. Returns [errors]. */
function shop_save(array $in): array {
    $err = []; $out = [];
    foreach (SHOP_FIELDS as $k => $max) {
        if (!array_key_exists($k, $in)) continue;
        $v = trim(preg_replace('/\s+/u', ' ', (string)$in[$k]));
        if (mb_strlen($v) > $max) $err[$k] = 'Too long.';
        $out[$k] = $v;
    }
    if (isset($out['name']) && mb_strlen($out['name']) < 2) $err['name'] = 'Enter the restaurant name.';
    foreach (['facebook_url', 'instagram_url', 'tiktok_url'] as $k) if (!empty($out[$k]) && !preg_match('#^https://[^\s<>"]+$#', $out[$k])) $err[$k] = 'Use a full link starting with https://';
    if (isset($out['email']) && $out['email'] !== '' && !filter_var($out['email'], FILTER_VALIDATE_EMAIL)) $err['email'] = 'Enter a valid email.';
    if (isset($out['brand_color']) && !preg_match('/^#[0-9a-fA-F]{6}$/', $out['brand_color'])) $err['brand_color'] = 'Pick a colour.';
    if (isset($out['staff_prefix']) && !preg_match('/^[A-Z]{2,6}$/', $out['staff_prefix'] = strtoupper($out['staff_prefix']))) $err['staff_prefix'] = 'Use 2–6 letters, e.g. SSB.';
    foreach (['phone_office'] as $k) if (!empty($out[$k]) && strlen(preg_replace('/\D/', '', $out[$k])) < 10) $err[$k] = 'Enter a phone number with area code.';
    if ($err) return $err;
    foreach ($out as $k => $v) shop_set($k, $v);
    return [];
}
function shop_set(string $k, string $v): void {
    ex('INSERT INTO shop_profile (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$k, $v]);
    unset($GLOBALS['SHOP_CACHE']);
}

/* ---------- brand colours: one colour in, an accessible palette out ---------- */
function hex_rgb(string $h): array { $h = ltrim($h, '#'); return [hexdec(substr($h, 0, 2)), hexdec(substr($h, 2, 2)), hexdec(substr($h, 4, 2))]; }
function rgb_hex(array $c): string { return sprintf('#%02X%02X%02X', ...array_map(fn($x) => max(0, min(255, (int)round($x))), $c)); }
function rel_lum(array $c): float {
    $f = fn($v) => ($v /= 255) <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
    return 0.2126 * $f($c[0]) + 0.7152 * $f($c[1]) + 0.0722 * $f($c[2]);
}
function contrast(array $a, array $b): float { [$x, $y] = [rel_lum($a), rel_lum($b)]; return (max($x, $y) + 0.05) / (min($x, $y) + 0.05); }
function rgb_hsl(array $c): array {
    [$r, $g, $b] = array_map(fn($v) => $v / 255, $c); $mx = max($r, $g, $b); $mn = min($r, $g, $b); $l = ($mx + $mn) / 2; $d = $mx - $mn;
    if ($d == 0) return [0.0, 0.0, $l];
    $s = $l > 0.5 ? $d / (2 - $mx - $mn) : $d / ($mx + $mn);
    $h = match ($mx) { $r => fmod(($g - $b) / $d + ($g < $b ? 6 : 0), 6), $g => ($b - $r) / $d + 2, default => ($r - $g) / $d + 4 } / 6;
    return [$h, $s, $l];
}
function hsl_rgb(float $h, float $s, float $l): array {
    if ($s == 0) return [$l * 255, $l * 255, $l * 255];
    $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s; $p = 2 * $l - $q;
    $t = function ($t) use ($p, $q) { if ($t < 0) $t += 1; if ($t > 1) $t -= 1; return $t < 1/6 ? $p + ($q - $p) * 6 * $t : ($t < 1/2 ? $q : ($t < 2/3 ? $p + ($q - $p) * (2/3 - $t) * 6 : $p)); };
    return [$t($h + 1/3) * 255, $t($h) * 255, $t($h - 1/3) * 255];
}
/** Darkens (or lightens) a colour in small steps until it reaches the contrast ratio against $bg. */
function reach_contrast(array $c, array $bg, float $ratio, int $dir = -1): array {
    [$h, $s, $l] = rgb_hsl($c);
    for ($i = 0; $i < 100 && contrast($c, $bg) < $ratio; $i++) { $l = max(0, min(1, $l + 0.01 * $dir)); $c = hsl_rgb($h, $s, $l); }
    return $c;
}
/** CSS custom properties for a brand colour. Every text pairing meets WCAG AA (4.5:1), on the light admin and the dark website. */
function brand_palette(string $hex): array {
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $hex)) $hex = SHOP_DEFAULTS['brand_color'];
    $c = hex_rgb($hex); [$h, $s] = rgb_hsl($c); $white = [255, 255, 255]; $black = [17, 17, 17]; $page = hex_rgb('#F5F6FA');
    $on = contrast($c, $white) >= contrast($c, $black) ? $white : $black;
    $fill = reach_contrast($c, $on, 4.5, $on === $white ? -1 : 1);           // button background that works with its text colour
    $hover = reach_contrast(hsl_rgb($h, $s, max(0, min(1, rgb_hsl($fill)[2] + ($on === $white ? -0.07 : 0.07)))), $on, 4.5, $on === $white ? -1 : 1);
    $link = reach_contrast($c, $page, 4.5, -1);                               // text colour on the light page and on white cards
    $accent = reach_contrast($c, hex_rgb('#1A2133'), 4.5, 1);                 // text colour on the dark website (checked on its lightest panel)
    $sat = min($s, 0.65);
    return [
        '--blue' => rgb_hex($fill), '--blue2' => rgb_hex($hover), '--on-blue' => rgb_hex($on), '--link' => rgb_hex($link), '--accent' => rgb_hex($accent),
        '--ink' => rgb_hex(hsl_rgb($h, $sat, 0.07)), '--night' => rgb_hex(hsl_rgb($h, $sat, 0.11)), '--deep' => rgb_hex(hsl_rgb($h, $sat, 0.2)),
        '--tint' => rgb_hex(hsl_rgb($h, min($s, 0.9), 0.96)), '--tint2' => rgb_hex(hsl_rgb($h, min($s, 0.7), 0.86)),
        '--ring' => vsprintf('rgba(%d,%d,%d,.2)', array_map('intval', $c)),
    ];
}
function brand_css(): string {
    $p = brand_palette(shop()['brand_color']);
    return ':root{' . implode(';', array_map(fn($k, $v) => "$k:$v", array_keys($p), $p)) . "}\n";
}
function brand_version(): string { $s = shop(); return substr(md5($s['brand_color'] . '|' . $s['logo_file'] . '|' . $s['name']), 0, 10); }
function brand_logo_url(): string { return '/brand/logo?v=' . brand_version(); }
function brand_css_url(): string { return '/brand.css?v=' . brand_version(); }
/** Monogram logo used until the owner uploads one: the shop's initials on its brand colour. */
function brand_monogram_svg(): string {
    $p = brand_palette(shop()['brand_color']); $i = htmlspecialchars(shop()['initials'], ENT_XML1);
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="14" fill="' . $p['--blue'] . '"/>'
        . '<text x="32" y="41" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-weight="700" font-size="' . (mb_strlen($i) > 1 ? 26 : 32) . '" fill="' . $p['--on-blue'] . '">' . $i . '</text></svg>';
}
function readfile_with_cache_headers(string $file): void { header('Cache-Control: public, max-age=31536000, immutable'); header('X-Content-Type-Options: nosniff'); readfile($file); }
function brand_dir(): string { return uploads_dir('brand'); }
/** Saves an uploaded logo (PNG, JPG or WebP), re-encoded as PNG up to 512 px so hidden data is dropped. Returns an error or null. */
function save_brand_logo(array $f): ?string {
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) return 'The logo could not be uploaded.';
    if ($f['size'] > 4 * 1024 * 1024) return 'The logo is too large (max 4 MB).';
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true)) return 'The logo must be a PNG, JPG or WebP image.';
    if (!function_exists('imagecreatefromstring')) return 'The server cannot process images (PHP GD extension is missing).';
    $img = @imagecreatefromstring((string)file_get_contents($f['tmp_name']));
    if (!$img) return 'The logo could not be read. Try a different file.';
    $w = imagesx($img); $h = imagesy($img); $k = min(1, 512 / max($w, $h)); $nw = max(1, (int)round($w * $k)); $nh = max(1, (int)round($h * $k));
    $out = imagecreatetruecolor($nw, $nh); imagealphablending($out, false); imagesavealpha($out, true);
    imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127)); imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $dir = brand_dir(); if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return 'Could not create the logo folder on the server.';
    $name = 'logo-' . bin2hex(random_bytes(8)) . '.png';
    if (!imagepng($out, "$dir/$name", 6)) return 'The logo could not be saved.';
    $old = shop()['logo_file']; shop_set('logo_file', $name);
    if ($old && preg_match('/^logo-[a-f0-9]{16}\.png$/', $old)) @unlink("$dir/$old");
    return null;
}
