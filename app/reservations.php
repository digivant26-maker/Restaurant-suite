<?php
/**
 * Table reservations. Each time slot holds a number of seats (Settings); a request fits when the seats already
 * held in that slot (requested + confirmed + seated) plus the party size stay within it.
 * Staff confirm or decline requests, unless auto-confirm is on. Customers can cancel from their link.
 */
const RES_STATUS = ['requested' => 'Requested', 'confirmed' => 'Confirmed', 'seated' => 'Seated', 'declined' => 'Declined', 'cancelled' => 'Cancelled', 'no_show' => 'No-show'];
const RES_HOLDING = ['requested', 'confirmed', 'seated'];
const OCCASIONS = ['' => 'None', 'birthday' => 'Birthday', 'anniversary' => 'Anniversary', 'game' => 'Watching a game', 'business' => 'Business', 'other' => 'Other'];
const SEATING = ['' => 'No preference', 'inside' => 'Inside', 'patio' => 'Patio', 'bar' => 'Near the bar'];

/** Days a guest can book (local Y-m-d => label), from tomorrow-or-today up to the booking window. */
function res_days(?DateTimeImmutable $now = null, ?array $s = null): array {
    $s ??= settings(); $now ??= now_local(); $out = [];
    if (!(int)$s['reservations_on']) return [];
    for ($i = 0; $i <= (int)$s['res_window_days'] && count($out) < 60; $i++) {
        $d = $now->modify("+$i days");
        if (!in_array((int)$d->format('N'), days_set($s['res_days']), true)) continue;
        if (res_times($d->format('Y-m-d'), [], $now, $s)) $out[$d->format('Y-m-d')] = $i === 0 ? 'Today, ' . $d->format('M j') : ($i === 1 ? 'Tomorrow, ' . $d->format('M j') : $d->format('D, M j'));
    }
    return $out;
}
/** Seats already held per slot on a day: ['Y-m-d H:i' => seats]. */
function res_held(string $day): array {
    $from = to_utc(new DateTimeImmutable("$day 00:00", tz())); $to = to_utc((new DateTimeImmutable("$day 00:00", tz()))->modify('+1 day +6 hours'));
    $held = [];
    foreach (q("SELECT starts_at, party FROM reservations WHERE starts_at >= ? AND starts_at < ? AND status IN ('requested','confirmed','seated')", [$from, $to]) as $r)
        $held[to_local($r['starts_at'])->format('Y-m-d H:i')] = ($held[to_local($r['starts_at'])->format('Y-m-d H:i')] ?? 0) + (int)$r['party'];
    return $held;
}
/** Times on a day with room for $party: ['Y-m-d\TH:i' => 'h:mm AM']. Pass $held = [] to only check opening hours. */
function res_times(string $day, ?array $held = null, ?DateTimeImmutable $now = null, ?array $s = null, int $party = 1): array {
    $s ??= settings(); $now ??= now_local();
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) return [];
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $day, tz());
    if (!$d || !in_array((int)$d->format('N'), days_set($s['res_days']), true)) return [];
    [$oh, $om] = array_map('intval', explode(':', $s['res_open'])); [$ch, $cm] = array_map('intval', explode(':', $s['res_close']));
    $t = $d->setTime($oh, $om); $last = $d->setTime($ch, $cm); if ($last <= $t) $last = $last->modify('+1 day');
    $earliest = $now->modify('+' . (int)$s['res_lead_hours'] . ' hours');
    $step = max(15, (int)$s['res_slot_minutes']); $cap = (int)$s['res_seats_per_slot'];
    $held ??= res_held($day); $out = [];
    for (; $t <= $last && count($out) < 96; $t = $t->modify("+$step minutes")) {
        if ($t < $earliest) continue;
        if (($held[$t->format('Y-m-d H:i')] ?? 0) + $party > $cap) continue;
        $out[$t->format('Y-m-d\TH:i')] = $t->format('g:i A');
    }
    return $out;
}

/** Creates a reservation request from the public form or by staff. Returns [reservation|null, errors]. */
function create_reservation(array $in, bool $staff = false): array {
    $s = settings(); $e = [];
    $name = clean($in['name'] ?? '', 120); $phone = clean($in['phone'] ?? '', 30); $email = strtolower(clean($in['email'] ?? '', 191));
    $party = (int)($in['party'] ?? 0); $day = (string)($in['day'] ?? ''); $time = (string)($in['time'] ?? '');
    if (!$staff && !(int)$s['reservations_on']) $e['form'] = 'Online reservations are turned off. Please call us.';
    if (mb_strlen($name) < 2) $e['name'] = 'Enter your name.';
    if (!valid_phone($phone)) $e['phone'] = 'Enter a phone number with area code.';
    if (!$staff && !valid_email($email)) $e['email'] = 'Enter your email — we send your confirmation there.';
    if ($staff && $email !== '' && !valid_email($email)) $e['email'] = 'Enter a valid email or leave it empty.';
    if ($party < 1 || $party > (int)$s['res_max_party']) $e['party'] = 'For parties over ' . (int)$s['res_max_party'] . ', please call us or send a private event request.';
    if (!$staff && !$e && rate_limited('res:' . client_ip(), 5, 3600)) $e['form'] = 'Too many requests from this connection. Please call us.';
    if ($e) return [null, $e];
    $auto = $staff || (int)$s['res_auto_confirm'];
    // Lock the settings row so two guests can't both take the last seats of a slot; check room inside the lock.
    $pdo = db(); $pdo->beginTransaction();
    try {
        $pdo->query(prefix_sql('SELECT id FROM settings WHERE id=1 FOR UPDATE'));
        if (!isset(res_times($day, null, null, $s, $party)[$time])) { $pdo->rollBack(); return [null, ['time' => 'That time isn’t available for your party size. Please choose another.']]; }
        do { $ref = make_ref(); } while (q1('SELECT id FROM reservations WHERE ref=?', [$ref]));
    ex('INSERT INTO reservations (ref, token, name, phone, email, party, starts_at, occasion, seating, notes, status, source, decided_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
        [$ref, make_token(), $name, $phone, $email ?: null, $party, to_utc(new DateTimeImmutable($time, tz())), array_key_exists($in['occasion'] ?? '', OCCASIONS) ? ($in['occasion'] ?: null) : null,
         array_key_exists($in['seating'] ?? '', SEATING) ? ($in['seating'] ?: null) : null, clean_text($in['notes'] ?? '', 500) ?: null, $auto ? 'confirmed' : 'requested', $staff ? 'staff' : 'web', $auto ? gmdate('Y-m-d H:i:s') : null]);
        $pdo->commit();
    } catch (Throwable $ex) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $ex; }
    $r = q1('SELECT * FROM reservations WHERE ref=?', [$ref]);
    log_activity('reservation.' . $r['status'], "{$r['name']}, party of {$r['party']}, " . fmt_when($r['starts_at']), $staff ? null : $name);
    email_reservation($r, $auto ? 'confirmed' : 'requested');
    if (!$staff) { $notify = (string)cfg('shop_notify_email', '') ?: shop()['email']; if (valid_email($notify)) send_mail($notify, "Table request: {$r['name']}, {$r['party']} · " . fmt_when($r['starts_at']), rows_text(res_rows($r)), mail_html('New table request', res_rows($r), $auto ? 'Confirmed automatically.' : 'Please confirm or decline it.', [site_url() . '/admin/reservations', 'Open reservations'])); }
    return [$r, []];
}
function res_by_token(string $t): ?array { return preg_match('/^[a-f0-9]{48}$/', $t) ? q1('SELECT * FROM reservations WHERE token=?', [$t]) : null; }
function res_url(array $r): string { return site_url() . '/reservation/' . $r['token']; }
function res_rows(array $r): array {
    return array_values(array_filter([['Reference', $r['ref']], ['When', fmt_when($r['starts_at'])], ['Party', $r['party'] . ($r['party'] == 1 ? ' guest' : ' guests')], ['Name', $r['name']], ['Phone', $r['phone']],
        $r['seating'] ? ['Seating', SEATING[$r['seating']] ?? $r['seating']] : null, $r['occasion'] ? ['Occasion', OCCASIONS[$r['occasion']] ?? $r['occasion']] : null, $r['notes'] ? ['Notes', $r['notes']] : null]));
}
function email_reservation(array $r, string $what): void {
    if (!$r['email']) return;
    $n = shop()['name'];
    [$subj, $title, $intro] = match ($what) {
        'requested' => ["Table request received — $n", 'We got your request', 'Thanks! We’ll confirm your table by email shortly.'],
        'confirmed' => ["Your table is confirmed — $n", 'Your table is confirmed', 'See you soon! Need to change plans? Use the button below to cancel, or call us.'],
        'declined' => ["About your table request — $n", 'We can’t take this booking', 'Sorry — we can’t fit this request' . ($r['decline_reason'] ? ': ' . $r['decline_reason'] : '.') . ' Please try another time or call us.'],
        'cancelled' => ["Reservation cancelled — $n", 'Reservation cancelled', 'Your reservation is cancelled. We hope to see you another time.'],
        default => [null, null, null],
    };
    if ($subj) send_mail($r['email'], $subj, "$intro\n\n" . rows_text(res_rows($r)) . "\n\n" . res_url($r), mail_html($title, res_rows($r), $intro, in_array($what, ['requested', 'confirmed'], true) ? [res_url($r), 'View or cancel'] : null));
}
/** Staff and guest status changes. Returns an error or null. */
function res_set_status(int $id, string $to, string $reason = '', bool $byGuest = false): ?string {
    $r = q1('SELECT * FROM reservations WHERE id=?', [$id]);
    if (!$r) return 'Reservation not found.';
    $allowed = ['requested' => ['confirmed', 'declined', 'cancelled'], 'confirmed' => ['seated', 'no_show', 'cancelled'], 'seated' => [], 'declined' => [], 'cancelled' => [], 'no_show' => []][$r['status']] ?? [];
    if ($byGuest) $allowed = array_intersect($allowed, ['cancelled']);
    if (!in_array($to, $allowed, true)) return 'That change isn’t possible for this reservation.';
    ex('UPDATE reservations SET status=?, decided_at=COALESCE(decided_at, UTC_TIMESTAMP()), decline_reason=? WHERE id=? AND status=?', [$to, $to === 'declined' ? (clean($reason, 255) ?: null) : $r['decline_reason'], $id, $r['status']]);
    log_activity('reservation.' . $to, "{$r['name']}, party of {$r['party']}, " . fmt_when($r['starts_at']), $byGuest ? $r['name'] : null);
    if (in_array($to, ['confirmed', 'declined', 'cancelled'], true)) email_reservation(q1('SELECT * FROM reservations WHERE id=?', [$id]), $to);
    return null;
}
function res_tag(string $st): string { return '<span class="tag r-' . e($st) . '">' . e(RES_STATUS[$st] ?? $st) . '</span>'; }
