<?php
/**
 * Online pickup orders: a cart in the visitor's session, pickup times the kitchen can make, the order itself,
 * and its life in the kitchen (new → preparing → ready → picked up, or cancelled). Payment is at pickup.
 */
const ORDER_STATUS = ['new' => 'New', 'accepted' => 'Preparing', 'ready' => 'Ready for pickup', 'picked_up' => 'Picked up', 'cancelled' => 'Cancelled'];
const ORDER_OPEN = ['new', 'accepted', 'ready'];
const ORDER_NEXT = ['new' => 'accepted', 'accepted' => 'ready', 'ready' => 'picked_up'];
const CART_MAX_LINES = 30;
const CART_MAX_QTY = 20;

/* ---------- cart (session) ---------- */
function cart(): array { start_session(); return array_values(array_filter((array)($_SESSION['cart'] ?? []), 'is_array')); }
function cart_save(array $c): void { start_session(); $_SESSION['cart'] = array_slice(array_values($c), 0, CART_MAX_LINES); }
function cart_add(int $itemId, int $qty, string $note): ?string {
    $i = menu_item($itemId);
    if (!$i || !(int)$i['cat_active'] || !(int)$i['orderable']) return 'That item can’t be ordered online.';
    if (!(int)$i['available']) return $i['name'] . ' is sold out right now.';
    $qty = max(1, min(CART_MAX_QTY, $qty)); $note = clean($note, 200);
    $c = cart();
    foreach ($c as &$l) if ($l['id'] === $itemId && $l['note'] === $note) { $l['qty'] = min(CART_MAX_QTY, $l['qty'] + $qty); cart_save($c); return null; }
    if (count($c) >= CART_MAX_LINES) return 'Your order is full. Check out first, or call us for a large order.';
    $c[] = ['id' => $itemId, 'qty' => $qty, 'note' => $note]; cart_save($c); return null;
}
/** Cart lines priced from the current menu. Items that went away or sold out are reported, not ordered. */
function cart_lines(): array {
    $lines = []; $gone = [];
    foreach (cart() as $k => $l) {
        $i = menu_item((int)$l['id']);
        if (!$i || !(int)$i['cat_active'] || !(int)$i['orderable'] || !(int)$i['available']) { $gone[] = $i['name'] ?? 'An item'; continue; }
        $qty = max(1, min(CART_MAX_QTY, (int)$l['qty']));
        $lines[] = ['key' => $k, 'item_id' => (int)$i['id'], 'name' => $i['name'], 'price' => (float)$i['price'], 'qty' => $qty, 'note' => (string)$l['note'], 'line_total' => round($qty * (float)$i['price'], 2)];
    }
    return [$lines, $gone];
}
function order_totals(array $lines, float $taxPct): array {
    $sub = round(array_sum(array_column($lines, 'line_total')), 2); $tax = round($sub * $taxPct / 100, 2);
    return ['subtotal' => $sub, 'tax' => $tax, 'total' => round($sub + $tax, 2), 'count' => array_sum(array_column($lines, 'qty'))];
}

/* ---------- pickup times ---------- */
/** Pickup times (local DateTimeImmutable, every 15 minutes) for today from now + lead time to closing. */
function pickup_slots(?DateTimeImmutable $now = null, ?array $s = null): array {
    $s ??= settings(); $now ??= now_local();
    if (!(int)$s['ordering_on'] || !in_array((int)$now->format('N'), days_set($s['order_days']), true)) return [];
    [$oh, $om] = array_map('intval', explode(':', $s['order_open'])); [$ch, $cm] = array_map('intval', explode(':', $s['order_close']));
    $open = $now->setTime($oh, $om); $close = $now->setTime($ch, $cm);
    if ($close <= $open) $close = $close->modify('+1 day'); // closes after midnight
    $earliest = $now->modify('+' . max(5, (int)$s['order_lead_minutes']) . ' minutes');
    $t = $open > $earliest ? $open : $earliest;
    $m = (int)$t->format('i'); $t = $t->setTime((int)$t->format('G'), 0)->modify('+' . (int)(ceil($m / 15) * 15) . ' minutes'); // next quarter hour
    $out = [];
    for (; $t <= $close && count($out) < 80; $t = $t->modify('+15 minutes')) $out[] = $t;
    return $out;
}
function slot_value(DateTimeImmutable $t): string { return $t->format('Y-m-d\TH:i'); }

/* ---------- placing an order ---------- */
/** Places the order. Returns [order|null, errors]. */
function place_order(array $in): array {
    [$lines, $gone] = cart_lines();
    $e = [];
    $name = clean($in['name'] ?? '', 120); $phone = clean($in['phone'] ?? '', 30); $email = strtolower(clean($in['email'] ?? '', 191));
    if (!$lines) $e['form'] = 'Your order is empty.';
    if ($gone) $e['form'] = implode(', ', $gone) . ' can’t be ordered right now and was left out. Please check your order.';
    if (mb_strlen($name) < 2) $e['name'] = 'Enter your name.';
    if (!valid_phone($phone)) $e['phone'] = 'Enter a phone number with area code.';
    if ($email !== '' && !valid_email($email)) $e['email'] = 'Enter a valid email or leave it empty.';
    $slots = array_map('slot_value', pickup_slots()); $pick = (string)($in['pickup'] ?? '');
    if (!$slots) $e['pickup'] = 'Online ordering is closed right now.';
    elseif (!in_array($pick, $slots, true)) $e['pickup'] = 'Choose a pickup time.';
    if (!$e && rate_limited('order:' . client_ip(), 6, 3600)) $e['form'] = 'Too many orders from this connection. Please call us to order.';
    if ($e) return [null, $e];
    $tot = order_totals($lines, (float)settings()['tax_pct']);
    $pickup = to_utc(new DateTimeImmutable($pick, tz()));
    $pdo = db(); $pdo->beginTransaction();
    try {
        do { $ref = make_ref(); } while (q1('SELECT id FROM orders WHERE ref=?', [$ref]));
        ex('INSERT INTO orders (ref, token, name, phone, email, pickup_at, notes, subtotal, tax, total) VALUES (?,?,?,?,?,?,?,?,?,?)',
            [$ref, make_token(), $name, $phone, $email ?: null, $pickup, clean_text($in['notes'] ?? '', 500) ?: null, $tot['subtotal'], $tot['tax'], $tot['total']]);
        $id = (int)$pdo->lastInsertId();
        foreach ($lines as $l) ex('INSERT INTO order_items (order_id, item_id, name, price, qty, note, line_total) VALUES (?,?,?,?,?,?,?)', [$id, $l['item_id'], $l['name'], $l['price'], $l['qty'], $l['note'] ?: null, $l['line_total']]);
        $pdo->commit();
    } catch (Throwable $ex) { $pdo->rollBack(); throw $ex; }
    cart_save([]);
    $o = order_full($id);
    log_activity('order.placed', "Order {$o['ref']} · " . money($o['total']), $name);
    email_order($o, 'placed');
    $notify = (string)cfg('shop_notify_email', '') ?: shop()['email'];
    if (valid_email($notify)) send_mail($notify, "New pickup order {$o['ref']} · " . fmt_time($o['pickup_at']), order_text($o), mail_html('New pickup order', order_rows($o), 'A new online order is waiting on the kitchen screen.', [site_url() . '/admin/orders', 'Open orders']));
    return [$o, []];
}
function order_full(int $id): ?array { $o = q1('SELECT * FROM orders WHERE id=?', [$id]); if ($o) $o['items'] = q('SELECT * FROM order_items WHERE order_id=? ORDER BY id', [$id]); return $o; }
function order_by_token(string $t): ?array { if (!preg_match('/^[a-f0-9]{48}$/', $t)) return null; $o = q1('SELECT id FROM orders WHERE token=?', [$t]); return $o ? order_full((int)$o['id']) : null; }
function order_url(array $o): string { return site_url() . '/order/' . $o['token']; }
function order_rows(array $o): array {
    $rows = [['Order', $o['ref']], ['Pickup', fmt_when($o['pickup_at'])], ['Name', $o['name']], ['Phone', $o['phone']]];
    foreach ($o['items'] as $i) $rows[] = [$i['qty'] . ' ×', $i['name'] . ($i['note'] ? " ({$i['note']})" : '') . ' — ' . money($i['line_total'])];
    $rows[] = ['Total', money($o['total']) . ' (pay at pickup)'];
    if ($o['notes']) $rows[] = ['Notes', $o['notes']];
    return $rows;
}
function order_text(array $o): string { return rows_text(order_rows($o)) . "\n\nTrack your order: " . order_url($o); }

/** Emails the customer (only when they gave an email). */
function email_order(array $o, string $what): void {
    if (!$o['email']) return;
    $s = shop()['name'];
    [$subj, $title, $intro] = match ($what) {
        'placed' => ["Your order {$o['ref']} at $s", 'Order received', 'Thanks! We’ve got your order. We’ll have it ready at your pickup time. You pay when you pick it up.'],
        'ready' => ["Your order {$o['ref']} is ready", 'Your order is ready', 'Your order is ready for pickup. See you soon!'],
        'cancelled' => ["Your order {$o['ref']} was cancelled", 'Order cancelled', 'Sorry — we had to cancel your order' . ($o['cancel_reason'] ? ': ' . $o['cancel_reason'] : '.') . ' Please call us if you have questions.'],
        default => [null, null, null],
    };
    if ($subj) send_mail($o['email'], $subj, "$intro\n\n" . order_text($o), mail_html($title, order_rows($o), $intro, [order_url($o), 'View my order']));
}

/** Moves an order to a new status (kitchen screen). Returns an error message or null. */
function order_set_status(int $id, string $to, string $reason = ''): ?string {
    $o = order_full($id);
    if (!$o) return 'Order not found.';
    if (!isset(ORDER_STATUS[$to])) return 'Unknown status.';
    if (!in_array($o['status'], ORDER_OPEN, true)) return 'This order is already closed.';
    if ($to !== 'cancelled' && (ORDER_NEXT[$o['status']] ?? null) !== $to) return 'That step isn’t possible from here.';
    $col = ['accepted' => 'accepted_at', 'ready' => 'ready_at', 'picked_up' => 'done_at', 'cancelled' => 'done_at'][$to];
    ex("UPDATE orders SET status=?, $col=UTC_TIMESTAMP(), cancel_reason=? WHERE id=? AND status=?", [$to, $to === 'cancelled' ? (clean($reason, 255) ?: null) : $o['cancel_reason'], $id, $o['status']]);
    log_activity('order.' . $to, "Order {$o['ref']} → " . ORDER_STATUS[$to]);
    if (in_array($to, ['ready', 'cancelled'], true)) email_order(order_full($id), $to);
    return null;
}
function order_tag(string $st): string { return '<span class="tag o-' . e($st) . '">' . e(ORDER_STATUS[$st] ?? $st) . '</span>'; }
