<?php
/*
 * Fictional sample data for a demo restaurant. Every person, phone (555-01xx), email (example.com) and team name
 * is invented. Dates are relative to now, so the demo always looks current. Runs inside the demo's own tables.
 */
function demo_seed(string $ownerName, string $password): string {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $now = now_local();

    // Demos are tried at any hour, so ordering is open all day; reservations follow normal evening hours.
    ex("UPDATE settings SET ordering_on=1, order_open='00:00:00', order_close='23:45:00', order_lead_minutes=15, tax_pct=8.25,
        reservations_on=1, res_open='11:00:00', res_close='22:00:00', res_slot_minutes=30, res_seats_per_slot=40, res_max_party=12, res_lead_hours=1, res_auto_confirm=0 WHERE id=1");

    // Staff: the visitor is the Admin; everyone else shares the same demo password so each role can be tried.
    $staff = [];
    foreach ([[$ownerName, 'owner@example.com', 'owner'], ['Morgan Ellis', 'morgan@example.com', 'manager'], ['Riley Chen', null, 'server'], ['Jordan Price', null, 'server'], ['Sam Ortega', null, 'kitchen']] as $k => [$n, $e, $r]) {
        ex('INSERT INTO users (email, name, password_hash, role, phone) VALUES (?,?,?,?,?)', [$e, $n, $hash, $r, '(555) 010-01' . (20 + $k)]);
        $staff[$r][] = ['id' => $uid = (int)db()->lastInsertId(), 'code' => assign_staff_code($uid)];
    }

    // Menu
    $menu = [
        ['Starters', 'To share — or not.', [
            ['Loaded Stadium Nachos', 'Tortilla chips, queso, black beans, pico, jalapeños, sour cream. Add brisket +$4.', 14.50, 'popular,shareable', 1],
            ['Pretzel Bites', 'Warm salted pretzel bites with beer cheese and spicy mustard.', 10.00, 'vegetarian,shareable', 0],
            ['Fried Pickles', 'Dill pickle chips in a crisp beer batter with ranch.', 9.00, 'vegetarian', 0],
            ['Street Corn Dip', 'Charred corn, cotija, lime and chile with tortilla chips.', 11.00, 'vegetarian,new', 1],
        ]],
        ['Wings', 'Ten wings, with celery and your choice of ranch or blue cheese.', [
            ['Classic Buffalo Wings', 'Tossed in our house buffalo sauce.', 16.00, 'popular,spicy', 1],
            ['Honey Chipotle Wings', 'Sweet heat with a smoky finish.', 16.00, 'spicy', 0],
            ['Lemon Pepper Dry Rub', 'Crispy wings, bright lemon pepper, no sauce.', 16.00, 'gluten_free', 0],
        ]],
        ['Tacos', 'Three tacos on corn tortillas with rice and beans.', [
            ['Brisket Tacos', 'Smoked brisket, pickled onion, cilantro, salsa verde.', 15.00, 'popular', 1],
            ['Baja Fish Tacos', 'Beer-battered cod, cabbage slaw, chipotle crema.', 15.00, '', 0],
            ['Cauliflower Tacos', 'Roasted cauliflower, avocado, cotija, salsa roja.', 13.00, 'vegetarian', 0],
        ]],
        ['Burgers & Sandwiches', 'Served with fries. Swap for a side salad at no charge.', [
            ['The Clubhouse Burger', 'Two smashed patties, American cheese, pickles, house sauce.', 15.50, 'popular', 1],
            ['Jalapeño Popper Burger', 'Cream cheese, roasted jalapeños, bacon, crispy onions.', 16.50, 'spicy', 0],
            ['Nashville Hot Chicken Sandwich', 'Hot fried chicken, slaw, pickles, brioche.', 15.00, 'spicy,new', 1],
            ['Black Bean Burger', 'House black bean patty, avocado, chipotle mayo.', 14.00, 'vegetarian', 0],
        ]],
        ['Salads', '', [
            ['Grilled Chicken Cobb', 'Romaine, chicken, bacon, egg, avocado, tomato, blue cheese.', 15.00, 'gluten_free', 0],
            ['Southwest Salad', 'Mixed greens, corn, black beans, tortilla strips, chipotle ranch.', 12.00, 'vegetarian', 0],
        ]],
        ['Desserts', '', [
            ['Skillet Cookie', 'Warm chocolate chip cookie with vanilla ice cream.', 9.00, 'shareable', 0],
            ['Churro Bites', 'Cinnamon sugar churros with chocolate sauce.', 8.00, 'vegetarian', 0],
        ]],
        ['Drinks', 'Must be 21+ for alcohol; ID checked at pickup.', [
            ['House Margarita', 'Blanco tequila, fresh lime, agave. Dine-in only.', 10.00, 'popular', 0],
            ['Local IPA (16 oz)', 'Rotating local draft. Dine-in only.', 7.00, '', 0],
            ['Fresh Lemonade', 'Classic or strawberry.', 4.50, 'vegan', 0],
            ['Iced Tea', 'Sweet or unsweet.', 3.50, 'vegan', 0],
        ]],
    ];
    $items = [];
    foreach ($menu as $cp => [$cat, $cdesc, $list]) {
        ex('INSERT INTO menu_categories (name, description, position) VALUES (?,?,?)', [$cat, $cdesc ?: null, $cp]);
        $cid = (int)db()->lastInsertId();
        foreach ($list as $ip => [$n, $d, $p, $tags, $feat]) {
            $orderable = $cat === 'Drinks' && str_contains($d, 'Dine-in') ? 0 : 1;
            ex('INSERT INTO menu_items (category_id, name, description, price, tags, featured, orderable, position) VALUES (?,?,?,?,?,?,?,?)', [$cid, $n, $d, $p, $tags ?: null, $feat, $orderable, $ip]);
            $items[$n] = ['id' => (int)db()->lastInsertId(), 'price' => $p];
        }
    }
    ex('UPDATE menu_items SET available=0 WHERE name=?', ['Fried Pickles']); // one sold-out item, to show how it looks

    // Orders: one in each kitchen column, and two finished earlier today.
    $order = function (string $status, string $name, string $phone, ?string $email, int $pickupMin, array $lines, ?string $notes = null, int $placedMin = 20) use ($items, $now) {
        $sub = 0; foreach ($lines as [$n, $q]) $sub += $items[$n]['price'] * $q;
        $tax = round($sub * 0.0825, 2); $pickup = $now->modify("$pickupMin minutes");
        ex('INSERT INTO orders (ref, token, name, phone, email, pickup_at, notes, status, subtotal, tax, total, created_at, accepted_at, ready_at, done_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [make_ref(), make_token(), $name, $phone, $email, to_utc($pickup), $notes, $status, round($sub, 2), $tax, round($sub + $tax, 2), to_utc($now->modify("-$placedMin minutes")),
             in_array($status, ['accepted', 'ready', 'picked_up'], true) ? to_utc($now->modify('-' . max(1, $placedMin - 3) . ' minutes')) : null,
             in_array($status, ['ready', 'picked_up'], true) ? to_utc($now->modify('-2 minutes')) : null, $status === 'picked_up' ? to_utc($pickup) : null]);
        $oid = (int)db()->lastInsertId();
        foreach ($lines as [$n, $q, $note]) ex('INSERT INTO order_items (order_id, item_id, name, price, qty, note, line_total) VALUES (?,?,?,?,?,?,?)', [$oid, $items[$n]['id'], $n, $items[$n]['price'], $q, $note ?? null, round($items[$n]['price'] * $q, 2)]);
    };
    $order('new', 'Alex Romero', '(555) 010-0141', 'alex.r@example.com', 25, [['Classic Buffalo Wings', 2, 'Extra ranch'], ['Loaded Stadium Nachos', 1, null]], 'Picking up for the game — thanks!', 2);
    $order('new', 'Taylor Brooks', '(555) 010-0142', null, 35, [['The Clubhouse Burger', 1, 'No pickles'], ['Fresh Lemonade', 1, null]], null, 1);
    $order('accepted', 'Casey Morgan', '(555) 010-0143', 'casey.m@example.com', 12, [['Brisket Tacos', 2, null], ['Street Corn Dip', 1, null]], 'Allergic to sesame', 9);
    $order('ready', 'Jamie Fox', '(555) 010-0144', 'jamie.f@example.com', -3, [['Nashville Hot Chicken Sandwich', 1, 'Extra hot'], ['Churro Bites', 1, null]], null, 25);
    $order('picked_up', 'Drew Patel', '(555) 010-0145', null, -120, [['Baja Fish Tacos', 1, null], ['Iced Tea', 2, null]], null, 150);
    $order('picked_up', 'Quinn Lee', '(555) 010-0146', 'quinn@example.com', -200, [['Grilled Chicken Cobb', 1, null]], null, 230);

    // Reservations: requests waiting for an answer, and confirmed tables today and tomorrow.
    $res = function (string $status, string $name, string $phone, ?string $email, int $party, DateTimeImmutable $at, ?string $occ = null, ?string $seat = null, ?string $notes = null) {
        ex('INSERT INTO reservations (ref, token, name, phone, email, party, starts_at, occasion, seating, notes, status, decided_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
            [make_ref(), make_token(), $name, $phone, $email, $party, to_utc($at), $occ, $seat, $notes, $status, $status === 'requested' ? null : gmdate('Y-m-d H:i:s')]);
    };
    $today = $now->setTime(0, 0); $tom = $today->modify('+1 day');
    $res('confirmed', 'Morgan Hayes', '(555) 010-0151', 'morgan.h@example.com', 4, $today->setTime(18, 30), 'game', 'inside', 'Want a view of the main screen');
    $res('confirmed', 'Priya Nair', '(555) 010-0152', 'priya.n@example.com', 2, $today->setTime(19, 0), 'anniversary', 'patio');
    $res('requested', 'Chris Walker', '(555) 010-0153', 'chris.w@example.com', 8, $tom->setTime(19, 30), 'birthday', 'inside', 'Birthday — can we bring a cake?');
    $res('requested', 'Dana Kim', '(555) 010-0154', 'dana.k@example.com', 6, $tom->setTime(18, 0), 'game', 'bar');
    $res('confirmed', 'Luis Romero', '(555) 010-0155', 'luis.r@example.com', 3, $tom->setTime(20, 0));

    // Events and specials
    foreach ([['special', 'Happy Hour', '$2 off drafts, $6 house margaritas and half-price starters at the bar.', null, 'Mon–Fri 3–6 PM'],
              ['special', 'Game Day Wing Deal', '50-cent wings during every pro football game.', null, 'During the games'],
              ['event', 'Taco Tuesday', '$3 tacos and $5 margaritas all night.', 2, 'All day'],
              ['event', 'Trivia Night', 'Teams of up to 6. Prizes for the top three.', 3, '7–9 PM'],
              ['event', 'Thirsty Thursday', '$4 local drafts and a DJ on the patio.', 4, '8 PM – close'],
              ['event', 'Live Music Friday', 'Local bands on the patio stage.', 5, '8–11 PM'],
              ['event', 'Sunday Brunch', 'Brunch menu, mimosas and the early games.', 7, '10 AM – 2 PM']] as $k => [$kind, $t, $d, $wd, $time])
        ex('INSERT INTO events (kind, title, description, weekday, time_text, position) VALUES (?,?,?,?,?,?)', [$kind, $t, $d, $wd, $time, $k]);
    ex('INSERT INTO events (kind, title, description, event_date, time_text) VALUES (?,?,?,?,?)', ['event', 'Championship Watch Party', 'Every screen on the big game. Reserve early — tables go fast.', $now->modify('+10 days')->format('Y-m-d'), 'Doors 5 PM']);

    // Games (fictional teams)
    foreach ([[0, '19:00', 'NBA', 'Riverton Comets', 'Lakeside Kings', 'Main bar, Big screen', 1, 1],
              [0, '19:30', 'NHL', 'Northfield Blades', 'Harbor City Waves', 'Patio', 0, 0],
              [0, '21:00', 'NBA', 'Mesa Suns', 'Bay Valley Storm', 'Main bar', 0, 0],
              [1, '18:30', 'NCAAB', 'State Tech', 'Central University', 'Back room', 0, 0],
              [1, '19:15', 'NFL', 'Granite City Bears', 'Coastal Hawks', 'All screens', 1, 1],
              [2, '18:00', 'MLS', 'Prairie FC', 'Summit United', 'Patio', 0, 0],
              [3, '12:00', 'NFL', 'Iron Valley Miners', 'Capital Rangers', 'Main bar', 0, 0],
              [3, '15:25', 'NFL', 'Desert Scorpions', 'Bayview Pelicans', 'All screens', 1, 1]] as [$d, $t, $lg, $a, $h, $scr, $snd, $ft]) {
        [$hh, $mm] = array_map('intval', explode(':', $t));
        ex('INSERT INTO games (league, away, home, starts_at, screens, sound, featured) VALUES (?,?,?,?,?,?,?)', [$lg, $a, $h, to_utc($today->modify("+$d days")->setTime($hh, $mm)), $scr, $snd, $ft]);
    }

    // Website messages
    ex('INSERT INTO messages (kind, name, phone, email, event_date, guests, body, created_at) VALUES (?,?,?,?,?,?,?,?)', ['private', 'Avery Johnson', '(555) 010-0161', 'avery.j@example.com', $now->modify('+18 days')->format('Y-m-d'), 30, 'Office holiday party — we’d like the back room with appetizers and a drink package.', to_utc($now->modify('-3 hours'))]);
    ex('INSERT INTO messages (kind, name, email, body, created_at) VALUES (?,?,?,?,?)', ['contact', 'Sam Rivera', 'sam.r@example.com', 'Do you show international soccer matches on weekend mornings?', to_utc($now->modify('-1 day'))]);
    ex('INSERT INTO messages (kind, name, phone, email, subject, body, created_at) VALUES (?,?,?,?,?,?,?)', ['careers', 'Jesse Moreno', '(555) 010-0162', 'jesse.m@example.com', 'Bartender', 'Three years behind the bar, available evenings and weekends.', to_utc($now->modify('-2 days'))]);

    log_activity('demo.created', 'Demo restaurant created with fictional sample data', 'System');
    return $staff['owner'][0]['code'];
}
