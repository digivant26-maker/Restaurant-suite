<?php
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $pdo = new PDO(
        'mysql:host=' . cfg('db_host') . ';dbname=' . cfg('db_name') . ';charset=utf8mb4',
        cfg('db_user'), cfg('db_pass'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
    );
    $pdo->exec("SET time_zone = '+00:00'");
    return $pdo;
}
/** Connection with this shop's tables up to date (runs new migrations once per shop). */
function dbt(): PDO { $pdo = db(); static $done = []; $k = table_prefix(); if (!isset($done[$k])) { migrate($pdo); $done[$k] = true; } return $pdo; }

function q(string $sql, array $p = []): array { $s = dbt()->prepare(prefix_sql($sql)); $s->execute($p); return $s->fetchAll(); }
function q1(string $sql, array $p = []): ?array { $r = q($sql, $p); return $r[0] ?? null; }
function ex(string $sql, array $p = []): int { $s = dbt()->prepare(prefix_sql($sql)); $s->execute($p); return $s->rowCount(); }

/** Applies new db/*.sql files once. A marker file avoids checking on every request. */
function migrate(PDO $pdo): void {
    $files = glob(ROOT . '/db/*.sql'); sort($files);
    $marker = shop_storage('migrated-' . md5(implode('|', array_map('basename', $files))));
    @mkdir(dirname($marker), 0775, true);
    if (is_file($marker)) return;
    $pdo->exec(prefix_sql('CREATE TABLE IF NOT EXISTS schema_migrations (name VARCHAR(100) PRIMARY KEY, applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'));
    $done = array_column($pdo->query(prefix_sql('SELECT name FROM schema_migrations'))->fetchAll(), 'name');
    foreach ($files as $f) {
        $name = basename($f);
        if (in_array($name, $done, true)) continue;
        foreach (array_filter(array_map('trim', preg_split('/;\s*\n/', preg_replace('/^--.*$/m', '', file_get_contents($f))))) as $stmt) $pdo->exec(prefix_sql($stmt));
        $pdo->prepare(prefix_sql('INSERT IGNORE INTO schema_migrations (name) VALUES (?)'))->execute([$name]);
    }
    @file_put_contents($marker, date('c'));
}

/** Simple DB-backed rate limit (works across PHP processes). Returns true when over the limit. */
function rate_limited(string $bucket, int $max, int $windowSec): bool {
    ex('DELETE FROM rate_limits WHERE created_at < (UTC_TIMESTAMP() - INTERVAL 1 DAY)');
    $n = (int)q1('SELECT COUNT(*) n FROM rate_limits WHERE bucket=? AND created_at > (UTC_TIMESTAMP() - INTERVAL ? SECOND)', [$bucket, $windowSec])['n'];
    ex('INSERT INTO rate_limits (bucket) VALUES (?)', [$bucket]);
    return $n >= $max;
}
function client_ip(): string { return substr((string)($_SERVER['REMOTE_ADDR'] ?? 'local'), 0, 45); }
