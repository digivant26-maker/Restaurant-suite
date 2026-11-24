<?php
/** Shared helpers: time zone, form checks, money, references, activity log, roles. */

const ROLES = ['owner' => 'Admin', 'manager' => 'Manager', 'server' => 'Front of house', 'kitchen' => 'Kitchen'];
const ROLE_HELP = [
    'owner' => 'Everything, including settings, staff and the menu.',
    'manager' => 'Orders, reservations, menu, events, games and messages. No settings or staff.',
    'server' => 'Orders and reservations: confirm, seat, mark ready and picked up.',
    'kitchen' => 'Only the kitchen screen: new orders, preparing, ready.',
];

function tz(): DateTimeZone { static $z; return $z ??= new DateTimeZone((string)cfg('timezone', 'America/Chicago')); }
function utc(): DateTimeZone { static $z; return $z ??= new DateTimeZone('UTC'); }
function now_local(): DateTimeImmutable { return new DateTimeImmutable('now', tz()); }
function to_local(string $utc): DateTimeImmutable { return (new DateTimeImmutable($utc, utc()))->setTimezone(tz()); }
function to_utc(DateTimeInterface $d): string { return DateTimeImmutable::createFromInterface($d)->setTimezone(utc())->format('Y-m-d H:i:s'); }
function fmt_when(string $utc): string { return to_local($utc)->format('D, M j · g:i A'); }
function fmt_time(string $utc): string { return to_local($utc)->format('g:i A'); }

/** Trimmed, single-spaced, length-limited text from a form. */
function clean(?string $v, int $max): string { return mb_substr(trim(preg_replace('/\s+/u', ' ', (string)$v)), 0, $max); }
function clean_text(?string $v, int $max): string { return mb_substr(trim(str_replace("\r", '', (string)$v)), 0, $max); }
function valid_email(string $e): bool { return (bool)filter_var($e, FILTER_VALIDATE_EMAIL); }
function valid_phone(string $p): bool { $n = strlen(preg_replace('/\D/', '', $p)); return $n >= 10 && $n <= 15; }
function digits(string $s): string { return preg_replace('/\D/', '', $s) ?? ''; }

function money($n): string { return '$' . number_format((float)$n, 2); }
/** Short human reference, e.g. K7Q2M9 (no 0/O/1/I to avoid misreading). */
function make_ref(int $len = 6): string { $a = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; $r = ''; for ($i = 0; $i < $len; $i++) $r .= $a[random_int(0, 31)]; return $r; }
function make_token(): string { return bin2hex(random_bytes(24)); }

/** Weekday numbers 1 (Mon) … 7 (Sun) from a "1,2,3" setting. */
function days_set(string $csv): array { return array_values(array_filter(array_map('intval', explode(',', $csv)), fn($d) => $d >= 1 && $d <= 7)); }
const WEEKDAYS = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

function settings(): array {
    static $s = [];
    $k = table_prefix();
    return $s[$k] ??= (q1('SELECT * FROM settings WHERE id=1') ?? []);
}

function log_activity(string $action, string $detail = '', ?string $who = null): void {
    $u = $who === null ? current_user() : null;
    ex('INSERT INTO activity_log (user_id, user_name, action, detail) VALUES (?,?,?,?)', [$u['id'] ?? null, $who ?? ($u['name'] ?? 'Website'), $action, mb_substr($detail, 0, 500)]);
}

/** Interface text. The product is English-only for now; t() keeps the door open for translations. */
function t(string $en, array $vars = []): string { foreach ($vars as $k => $v) $en = str_replace('{' . $k . '}', (string)$v, $en); return $en; }
function initials(string $name): string { $w = preg_split('/\s+/', trim($name)); return mb_strtoupper(mb_substr($w[0] ?? '', 0, 1) . mb_substr($w[1] ?? '', 0, 1)); }
