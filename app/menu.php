<?php
/** Menu: categories, items, dietary/feature tags. */

const ITEM_TAGS = ['popular' => 'Popular', 'new' => 'New', 'spicy' => 'Spicy', 'vegetarian' => 'Vegetarian', 'vegan' => 'Vegan', 'gluten_free' => 'Gluten-free', 'shareable' => 'Shareable'];

function item_tags(?string $csv): array { return array_values(array_intersect(array_map('trim', explode(',', (string)$csv)), array_keys(ITEM_TAGS))); }

/** Categories with their items. $public = only active categories and their items (sold-out items still listed, marked). */
function menu_full(bool $public = true): array {
    $cats = q('SELECT * FROM menu_categories' . ($public ? ' WHERE active=1' : '') . ' ORDER BY position, id');
    $items = q('SELECT * FROM menu_items ORDER BY position, id');
    $by = [];
    foreach ($items as $i) $by[(int)$i['category_id']][] = $i;
    foreach ($cats as &$c) $c['items'] = $by[(int)$c['id']] ?? [];
    return $cats;
}
function menu_item(int $id): ?array { return q1('SELECT i.*, c.active cat_active FROM menu_items i JOIN menu_categories c ON c.id=i.category_id WHERE i.id=?', [$id]); }
function featured_items(int $n = 6): array {
    return q('SELECT i.* FROM menu_items i JOIN menu_categories c ON c.id=i.category_id WHERE i.featured=1 AND i.available=1 AND c.active=1 ORDER BY i.position, i.id LIMIT ' . max(1, $n));
}
/** Validates an item from the admin form. Returns [data, error|null]. */
function validate_item(array $in): array {
    $d = ['category_id' => (int)($in['category_id'] ?? 0), 'name' => clean($in['name'] ?? '', 100), 'description' => clean($in['description'] ?? '', 400),
          'price' => str_replace(['$', ','], '', trim((string)($in['price'] ?? ''))), 'tags' => implode(',', array_intersect((array)($in['tags'] ?? []), array_keys(ITEM_TAGS))),
          'available' => empty($in['available']) ? 0 : 1, 'orderable' => empty($in['orderable']) ? 0 : 1, 'featured' => empty($in['featured']) ? 0 : 1,
          'position' => (int)($in['position'] ?? 0)];
    if (mb_strlen($d['name']) < 2) return [$d, 'Enter the item name.'];
    if (!q1('SELECT id FROM menu_categories WHERE id=?', [$d['category_id']])) return [$d, 'Choose a category.'];
    if (!preg_match('/^\d{1,5}(\.\d{1,2})?$/', $d['price'])) return [$d, 'Enter a price like 12.50.'];
    $d['price'] = number_format((float)$d['price'], 2, '.', '');
    return [$d, null];
}
