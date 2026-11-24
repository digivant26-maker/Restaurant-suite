<?php
/*
 * Shop separation for demo sandboxes.
 *
 * The main shop uses the plain tables (repair_orders, users, ...). Each demo shop gets its own complete
 * copy of every table, named with its own prefix (d7k2m9x4qa_repair_orders, ...). The switch happens in
 * one place: prefix_sql() rewrites table names in every statement that goes through q()/q1()/ex() and in
 * migrations. Nothing else in the app knows about demos, so no query can forget a "which shop" filter.
 *
 * The list of tables is read from db/*.sql (every CREATE TABLE), so a table added later is covered
 * automatically. The demo registry (demo_shops) is created separately and is never prefixed.
 */

const DEMO_COOKIE = 'shop_demo';
const DEMO_ID_RE = '/^[a-z0-9]{10}$/';

/** Every table the app creates, from the migration files. */
function app_tables(): array {
    static $t = null;
    if ($t !== null) return $t;
    $t = ['schema_migrations'];
    foreach (glob(ROOT . '/db/*.sql') as $f)
        if (preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([a-z_][a-z0-9_]*)`?/i', file_get_contents($f), $m))
            foreach ($m[1] as $name) $t[] = strtolower($name);
    $t = array_values(array_unique($t));
    usort($t, fn($a, $b) => strlen($b) <=> strlen($a)); // longest first, so ro_tasks never matches inside a longer name
    return $t;
}

/** The demo shop this request belongs to, or null for the main shop. Set by demo_select() / the demo cookie. */
function tenant_id(): ?string { return $GLOBALS['TENANT'] ?? null; }
/** Sign-in cookie name: each demo shop has its own, so signing in to one never signs in to another. */
function session_cookie_name(): string { return tenant_id() === null ? 'shop_sess' : 'shop_sess_' . tenant_id(); }
function table_prefix(): string { $t = tenant_id(); return $t === null ? '' : "d{$t}_"; }
function set_tenant(?string $id): void {
    if ($id !== null && !preg_match(DEMO_ID_RE, $id)) throw new InvalidArgumentException('Bad demo id');
    $GLOBALS['TENANT'] = $id;
    unset($GLOBALS['SHOP_CACHE']);
}

/**
 * Rewrites table names to this shop's tables, only where SQL expects a table: after FROM, JOIN, INTO,
 * UPDATE, TABLE (incl. IF [NOT] EXISTS), REFERENCES and CREATE INDEX … ON, and in table.column references. A column or
 * nickname that happens to share a table's name (e.g. "COUNT(*) vehicles") is left alone, and so is
 * anything inside quotes. Foreign-key names get the prefix too, as they must be unique per database.
 */
function prefix_sql(string $sql, ?string $prefix = null): string {
    $prefix ??= table_prefix();
    if ($prefix === '') return $sql;
    static $kw = null, $dot = null;
    $names = implode('|', array_map('preg_quote', app_tables()));
    $kw ??= '/\b(FROM|JOIN|INTO|UPDATE|TABLE|REFERENCES)(\s+(?:IF\s+(?:NOT\s+)?EXISTS\s+)?)`?(' . $names . ')`?(?![\w$])/i';
    $dot ??= '/(?<![\w.$`])(' . $names . ')(?=\.)/i';
    $parts = preg_split("/('(?:[^'\\\\]|\\\\.|'')*'|\"(?:[^\"\\\\]|\\\\.)*\")/", $sql, -1, PREG_SPLIT_DELIM_CAPTURE);
    foreach ($parts as $i => $p) if ($i % 2 === 0) {
        $p = preg_replace($kw, '$1$2' . $prefix . '$3', $p);
        $p = preg_replace($dot, $prefix . '$1', $p);
        $p = preg_replace('/\bON(\s+)`?(' . $names . ')`?(?=\s*\()/i', 'ON$1' . $prefix . '$2', $p); // CREATE INDEX … ON table (…)
        $parts[$i] = preg_replace('/\bCONSTRAINT\s+`?(\w+)`?/i', 'CONSTRAINT ' . $prefix . '$1', $p);
    }
    return implode('', $parts);
}

/** Where this shop's uploaded files and logs live. The main shop keeps the original folders. */
function shop_storage(string $sub = ''): string {
    $base = tenant_id() === null ? ROOT . '/storage' : ROOT . '/storage/demos/' . tenant_id();
    return $sub === '' ? $base : $base . '/' . ltrim($sub, '/');
}
function uploads_dir(string $sub): string { return shop_storage('uploads/' . ltrim($sub, '/')); }
