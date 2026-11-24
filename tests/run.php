<?php
/* Unit checks (no database): php tests/run.php */
declare(strict_types=1);
define('ROOT', dirname(__DIR__));
$GLOBALS['CFG'] = ['timezone' => 'America/Chicago'];
function cfg(string $k, $d = null) { return $GLOBALS['CFG'][$k] ?? $d; }
require ROOT . '/app/tenant.php'; require ROOT . '/app/site.php'; require ROOT . '/app/core.php'; require ROOT . '/app/menu.php'; require ROOT . '/app/orders.php'; require ROOT . '/app/reservations.php'; require ROOT . '/app/events.php';
$fail = 0; $n = 0;
function check(string $name, $got, $want): void { global $fail, $n; $n++; if ($got !== $want) { $fail++; echo "FAIL $name\n  got:  " . var_export($got, true) . "\n  want: " . var_export($want, true) . "\n"; } }

/* shop separation */
$P = 'dabc123xyz9_';
check('main shop SQL unchanged', prefix_sql('SELECT * FROM orders'), 'SELECT * FROM orders');
check('demo: tables switched, nickname kept', prefix_sql('SELECT o.*, (SELECT COUNT(*) FROM order_items i WHERE i.order_id=o.id) order_items FROM orders o', $P),
    'SELECT o.*, (SELECT COUNT(*) FROM dabc123xyz9_order_items i WHERE i.order_id=o.id) order_items FROM dabc123xyz9_orders o');
check('demo: menu_items not confused with menu_categories', prefix_sql('SELECT i.* FROM menu_items i JOIN menu_categories c ON c.id=i.category_id', $P), 'SELECT i.* FROM dabc123xyz9_menu_items i JOIN dabc123xyz9_menu_categories c ON c.id=i.category_id');
check('demo: FK names unique', prefix_sql('CONSTRAINT fk_oi_order FOREIGN KEY (order_id) REFERENCES orders(id)', $P), 'CONSTRAINT dabc123xyz9_fk_oi_order FOREIGN KEY (order_id) REFERENCES dabc123xyz9_orders(id)');
check('demo: quoted text untouched', prefix_sql("UPDATE settings SET x='orders' WHERE id=1", $P), "UPDATE dabc123xyz9_settings SET x='orders' WHERE id=1");
$all = []; foreach (glob(ROOT . '/db/*.sql') as $f) { preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)/i', file_get_contents($f), $m); $all = array_merge($all, $m[1]); }
check('every table is switched', array_values(array_diff($all, app_tables())), []);
$cols = []; foreach (glob(ROOT . '/db/*.sql') as $f) { preg_match_all('/^\s+`?([a-z_]+)`? (?:INT|VARCHAR|TEXT|DATE|DATETIME|TINYINT|DECIMAL|CHAR|TIME|SMALLINT)/mi', file_get_contents($f), $m); $cols = array_merge($cols, $m[1]); }
check('no column shares a table name', array_values(array_intersect(array_unique($cols), app_tables())), []);

/* brand colours: readable on the light admin and the dark website */
foreach (['#D4A017', '#0B1F4D', '#0E7490', '#E11D48', '#111111', '#FFFFFF', '#FDE047', '#22C55E', '#7C3AED'] as $c) {
    $p = brand_palette($c);
    check("brand $c readable everywhere", contrast(hex_rgb($p['--blue']), hex_rgb($p['--on-blue'])) >= 4.5 && contrast(hex_rgb($p['--link']), [255, 255, 255]) >= 4.5
        && contrast(hex_rgb($p['--accent']), hex_rgb('#1A2133')) >= 4.5 && contrast(hex_rgb($p['--accent']), hex_rgb('#0B0E16')) >= 4.5, true);
}

/* pickup times */
$s = ['ordering_on' => 1, 'order_days' => '1,2,3,4,5,6,7', 'order_open' => '11:00:00', 'order_close' => '21:30:00', 'order_lead_minutes' => 20];
$at = fn(string $t) => new DateTimeImmutable($t, tz());
$sl = pickup_slots($at('2026-11-24 12:07'), $s);
check('first pickup: next quarter hour after lead time', $sl[0]->format('H:i'), '12:30');
check('last pickup at closing', end($sl)->format('H:i'), '21:30');
check('before opening: starts at opening', pickup_slots($at('2026-11-24 08:00'), $s)[0]->format('H:i'), '11:00');
check('after closing: none', pickup_slots($at('2026-11-24 21:20'), $s), []);
check('closed day: none', pickup_slots($at('2026-11-24 12:00'), ['order_days' => '6,7'] + $s), []);
check('ordering off: none', pickup_slots($at('2026-11-24 12:00'), ['ordering_on' => 0] + $s), []);
$late = pickup_slots($at('2026-11-24 23:00'), ['order_open' => '17:00:00', 'order_close' => '02:00:00'] + $s);
check('closes after midnight', [reset($late)->format('H:i'), end($late)->format('Y-m-d H:i')], ['23:30', '2026-11-25 02:00']);

/* reservation times and capacity */
$rs = ['reservations_on' => 1, 'res_days' => '1,2,3,4,5,6,7', 'res_open' => '17:00:00', 'res_close' => '21:00:00', 'res_slot_minutes' => 30, 'res_seats_per_slot' => 10, 'res_max_party' => 8, 'res_lead_hours' => 2, 'res_window_days' => 14];
$now = $at('2026-11-24 12:00');
$t = res_times('2026-11-25', [], $now, $rs, 2);
check('slots every 30 min, last at closing', [array_key_first($t), array_key_last($t), count($t)], ['2026-11-25T17:00', '2026-11-25T21:00', 9]);
$t = res_times('2026-11-25', ['2026-11-25 19:00' => 8], $now, $rs, 4);
check('full slot hidden for a party that does not fit', isset($t['2026-11-25T19:00']), false);
check('…but shown for a party that fits', isset(res_times('2026-11-25', ['2026-11-25 19:00' => 8], $now, $rs, 2)['2026-11-25T19:00']), true);
check('lead time: today before 2 hours from now hidden', array_key_first(res_times('2026-11-24', [], $at('2026-11-24 17:40'), $rs, 2)), '2026-11-24T20:00');
check('closed weekday: no times', res_times('2026-11-25', [], $now, ['res_days' => '6,7'] + $rs, 2), []);
check('bad day: no times', res_times('25/11/2026', [], $now, $rs, 2), []);

/* money and menu */
check('order totals', order_totals([['line_total' => 14.5, 'qty' => 1], ['line_total' => 32.0, 'qty' => 2]], 8.25), ['subtotal' => 46.5, 'tax' => 3.84, 'total' => 50.34, 'count' => 3]);
check('tags filtered to known ones', item_tags('spicy, popular,<script>,vegan'), ['spicy', 'popular', 'vegan']);
check('refs avoid look-alike characters', (bool)preg_match('/^[A-HJ-NP-Z2-9]{6}$/', make_ref()), true);

echo $fail ? "\n$fail of $n checks failed\n" : "All $n checks passed\n";
exit($fail ? 1 : 0);
