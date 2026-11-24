<?php
/**
 * Photos for the menu and gallery. Uploads are re-encoded (which removes hidden data such as GPS location),
 * resized to at most 1600 px, given random names and stored outside the web root. /media/<kind>/<file> serves them.
 */
const MEDIA_KINDS = ['menu', 'gallery'];

function media_dir(string $kind): string { return uploads_dir($kind); }
function media_url(string $kind, ?string $file): ?string { return $file && preg_match('/^[a-f0-9]{24}\.(jpg|png|webp)$/', $file) ? "/media/$kind/$file" : null; }

/** Saves an uploaded photo. Returns [filename|null, error|null]; no file chosen gives [null, null]. */
function save_photo(array $f, string $kind): array {
    if (!in_array($kind, MEDIA_KINDS, true)) return [null, 'Unknown photo type.'];
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return [null, null];
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) return [null, 'The photo could not be uploaded.'];
    if ($f['size'] > 8 * 1024 * 1024) return [null, 'The photo is too large (max 8 MB).'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) return [null, 'Use a JPG, PNG or WebP photo.'];
    return save_photo_file($f['tmp_name'], $kind);
}
/** Re-encodes an image file into the shop's media folder (also used for sample photos). */
function save_photo_file(string $src, string $kind): array {
    if (!function_exists('imagecreatefromstring')) return [null, 'The server cannot process images (PHP GD is missing).'];
    $img = @imagecreatefromstring((string)file_get_contents($src));
    if (!$img) return [null, 'That file is not a readable image.'];
    $w = imagesx($img); $h = imagesy($img); $max = 1600; $k = min(1, $max / max($w, $h));
    $nw = max(1, (int)round($w * $k)); $nh = max(1, (int)round($h * $k));
    $out = imagecreatetruecolor($nw, $nh); imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
    imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $dir = media_dir($kind); if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $name = bin2hex(random_bytes(12)) . '.jpg';
    if (!imagejpeg($out, "$dir/$name", 84)) return [null, 'The photo could not be saved.'];
    return [$name, null];
}
function delete_photo(string $kind, ?string $file): void { if ($file && preg_match('/^[a-f0-9]{24}\.(jpg|png|webp)$/', $file)) @unlink(media_dir($kind) . '/' . $file); }
function serve_media(string $kind, string $file): never {
    $p = media_dir($kind) . '/' . $file;
    if (!in_array($kind, MEDIA_KINDS, true) || !preg_match('/^[a-f0-9]{24}\.(jpg|png|webp)$/', $file) || !is_file($p)) not_found();
    header('Content-Type: image/jpeg'); header('Content-Length: ' . filesize($p)); header('X-Content-Type-Options: nosniff');
    readfile_with_cache_headers($p); exit;
}
