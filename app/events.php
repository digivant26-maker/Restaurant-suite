<?php
/** Events (weekly or one-off), specials, and the games schedule on the screens. */
const LEAGUES = ['NFL' => 'NFL', 'NCAAF' => 'College football', 'NBA' => 'NBA', 'NCAAB' => 'College basketball', 'MLB' => 'MLB', 'NHL' => 'NHL', 'MLS' => 'MLS', 'SOCCER' => 'Soccer', 'UFC' => 'UFC / boxing', 'F1' => 'Formula 1', 'GOLF' => 'Golf', 'OTHER' => 'Other'];

/** Active events or specials, weekly ones in weekday order then upcoming one-offs (past one-offs are hidden). */
function events_list(string $kind = 'event', bool $public = true): array {
    $today = now_local()->format('Y-m-d');
    $rows = q('SELECT * FROM events WHERE kind=?' . ($public ? ' AND active=1 AND (event_date IS NULL OR event_date >= ?)' : '') . ' ORDER BY event_date IS NOT NULL, weekday, event_date, position, id',
        $public ? [$kind, $today] : [$kind]);
    return $rows;
}
function event_when(array $ev): string {
    $d = $ev['event_date'] ? DateTimeImmutable::createFromFormat('!Y-m-d', $ev['event_date'], tz())->format('D, M j') : ($ev['weekday'] ? 'Every ' . WEEKDAYS[(int)$ev['weekday']] : '');
    return trim($d . ($ev['time_text'] ? ' · ' . $ev['time_text'] : ''), ' ·');
}
function validate_event(array $in): array {
    $d = ['kind' => in_array($in['kind'] ?? '', ['event', 'special'], true) ? $in['kind'] : 'event', 'title' => clean($in['title'] ?? '', 100), 'description' => clean($in['description'] ?? '', 400),
          'weekday' => (int)($in['weekday'] ?? 0) ?: null, 'event_date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($in['event_date'] ?? '')) ? $in['event_date'] : null,
          'time_text' => clean($in['time_text'] ?? '', 60), 'active' => empty($in['active']) ? 0 : 1, 'position' => (int)($in['position'] ?? 0)];
    if (mb_strlen($d['title']) < 2) return [$d, 'Enter a title.'];
    if ($d['weekday'] && ($d['weekday'] < 1 || $d['weekday'] > 7)) $d['weekday'] = null;
    if (!$d['weekday'] && !$d['event_date']) return [$d, 'Choose a weekday (repeats every week) or a date (one time).'];
    if ($d['weekday'] && $d['event_date']) $d['weekday'] = null; // a date wins
    return [$d, null];
}

/** Games from the start of today (local) for $days days. */
function games_upcoming(int $days = 7, ?int $limit = null): array {
    $from = to_utc(now_local()->setTime(0, 0)); $to = to_utc(now_local()->setTime(0, 0)->modify("+$days days"));
    return q('SELECT * FROM games WHERE starts_at >= ? AND starts_at < ? ORDER BY starts_at, featured DESC, id' . ($limit ? ' LIMIT ' . (int)$limit : ''), [$from, $to]);
}
function validate_game(array $in): array {
    $d = ['league' => array_key_exists($in['league'] ?? '', LEAGUES) ? $in['league'] : '', 'away' => clean($in['away'] ?? '', 60), 'home' => clean($in['home'] ?? '', 60),
          'screens' => clean($in['screens'] ?? '', 60), 'sound' => empty($in['sound']) ? 0 : 1, 'featured' => empty($in['featured']) ? 0 : 1, 'note' => clean($in['note'] ?? '', 160)];
    if ($d['league'] === '') return [$d, 'Choose the league.'];
    if (mb_strlen($d['away']) < 2 || mb_strlen($d['home']) < 2) return [$d, 'Enter both teams (or the event name in both).'];
    $t = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', (string)($in['starts_at'] ?? ''), tz());
    if (!$t) return [$d, 'Enter the date and start time.'];
    $d['starts_at'] = to_utc($t);
    return [$d, null];
}
function game_day_label(string $utc): string {
    $d = to_local($utc); $today = now_local()->format('Y-m-d');
    return $d->format('Y-m-d') === $today ? 'Today' : ($d->format('Y-m-d') === now_local()->modify('+1 day')->format('Y-m-d') ? 'Tomorrow' : $d->format('l, M j'));
}
