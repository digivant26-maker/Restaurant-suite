<?php
require_once ROOT . '/vendor/phpmailer/Exception.php';
require_once ROOT . '/vendor/phpmailer/PHPMailer.php';
require_once ROOT . '/vendor/phpmailer/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Test mode (staging): with mail_redirect set, every email goes to that one inbox instead of the customer or the shop,
 * and the subject and first line say who it was meant for. Real customers can never be emailed from staging.
 * Returns [to, subject, text, html].
 */
function mail_test_mode(string $to, string $subject, string $text, string $html): array {
    $r = trim((string)cfg('mail_redirect', ''));
    if ($r === '') return [$to, $subject, $text, $html];
    $note = "[TEST MODE] This email was meant for: $to";
    return [$r, "[STAGING → $to] $subject", "$note\n\n$text",
        '<div style="background:#FFD24A;color:#3A2A00;padding:10px 14px;font:600 14px Arial,sans-serif">' . htmlspecialchars($note) . '</div>' . $html];
}

/** Sends an email. Without smtp_host (local dev) it writes to storage/mail.log. Never throws. */
function send_mail(string $to, string $subject, string $text, string $html, ?string $replyTo = null): bool {
    $GLOBALS['MAIL_ERROR'] = null;
    [$to, $subject, $text, $html] = mail_test_mode($to, $subject, $text, $html);
    // Demo shops never send real email: their messages are kept in the demo's own log (shown in the demo's Outbox).
    if (!cfg('smtp_host') || tenant_id() !== null) {
        @mkdir(shop_storage(), 0775, true);
        @file_put_contents(shop_storage('mail.log'), "---- " . date('c') . "\nTo: $to\nSubject: $subject\n\n$text\n\n", FILE_APPEND);
        return true;
    }
    // A slow or misconfigured mail server must never hold a page: short limits, then skip SMTP for 5 minutes.
    static $down = false; $flag = ROOT . '/storage/smtp-down';
    if ($down || (is_file($flag) && filemtime($flag) > time() - 300)) { $GLOBALS['MAIL_ERROR'] = 'Email server unavailable a moment ago; skipped for 5 minutes.'; return false; }
    try {
        $m = new PHPMailer(true);
        $m->isSMTP();
        $m->Host = cfg('smtp_host'); $m->Port = (int)cfg('smtp_port', 465);
        $m->SMTPAuth = true; $m->Username = cfg('smtp_user'); $m->Password = cfg('smtp_pass');
        $m->SMTPSecure = (int)cfg('smtp_port', 465) === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $m->CharSet = 'UTF-8';
        $m->setFrom(cfg('mail_from'), (cfg('mail_from_name') ?: shop()['name']));
        $m->addAddress($to);
        if ($replyTo && valid_email($replyTo)) $m->addReplyTo($replyTo);
        $m->Timeout = 8; $m->getSMTPInstance()->Timelimit = 10;
        $m->Subject = $subject; $m->Body = $html; $m->AltBody = $text; $m->isHTML(true);
        $m->send();
        if (is_file($flag)) @unlink($flag);
        return true;
    } catch (Throwable $e) {
        $down = true; @touch($flag);
        $msg = isset($m) && $m->ErrorInfo ? $m->ErrorInfo : $e->getMessage();
        $GLOBALS['MAIL_ERROR'] = $msg;
        error_log('[mail] ' . $msg);
        @file_put_contents(ROOT . '/storage/mail-errors.log', date('c') . " to=$to subject=\"$subject\" error=$msg\n", FILE_APPEND);
        return false;
    }
}

/** Branded HTML email. $rows = [[label, value], ...] */
function mail_html(string $title, array $rows, string $intro, ?array $cta = null): string {
    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $s = shop(); $pal = brand_palette($s['brand_color']);
    $r = '';
    foreach ($rows as [$k, $v]) $r .= '<tr><td style="padding:8px 0;border-top:1px solid #e5e7eb;color:#5b6170;width:130px;vertical-align:top">' . $h($k) . '</td><td style="padding:8px 0;border-top:1px solid #e5e7eb;white-space:pre-wrap">' . $h($v) . '</td></tr>';
    $btn = $cta ? '<p style="margin:20px 0 0"><a href="' . $h($cta[0]) . '" style="background:' . $pal['--blue'] . ';color:' . $pal['--on-blue'] . ';text-decoration:none;padding:12px 18px;border-radius:6px;display:inline-block;font-weight:bold">' . $h($cta[1]) . '</a></p>' : '';
    return '<!doctype html><html><body style="margin:0;background:#f3f4f7;font-family:Arial,Helvetica,sans-serif;color:#1F2228">'
      . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:24px 12px">'
      . '<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;background:#fff;border-radius:8px;overflow:hidden">'
      . '<tr><td style="background:' . $pal['--night'] . ';padding:18px 24px;color:#fff;font-size:18px;font-weight:bold">' . $h($s['name']) . '<div style="font-size:12px;color:#CFCFCF;font-weight:normal;margin-top:2px">' . $h($s['tagline']) . '</div></td></tr>'
      . '<tr><td style="padding:24px"><h1 style="font-size:20px;margin:0 0 8px">' . $h($title) . '</h1><p style="margin:0 0 16px;line-height:1.5">' . $h($intro) . '</p>'
      . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse">' . $r . '</table>' . $btn . '</td></tr>'
      . '<tr><td style="padding:16px 24px;background:#f7f7f9;font-size:12px;color:#5b6170;line-height:1.5">' . $h("{$s['street']}, {$s['city']}, {$s['region']} {$s['zip']}") . '<br>' . $h($s['phone_office']) . ' · ' . $h($s['email']) . '<br>' . $h($s['hours_text']) . '</td></tr>'
      . '</table></td></tr></table></body></html>';
}
function rows_text(array $rows): string { return implode("\n", array_map(fn($r) => "$r[0]: $r[1]", $rows)); }

function last_mail_error(): ?string { return $GLOBALS['MAIL_ERROR'] ?? null; }

/** Last few lines of storage/mail-errors.log (never contains passwords). */
function recent_mail_errors(int $n = 5): array {
    $f = ROOT . '/storage/mail-errors.log';
    if (!is_file($f)) return [];
    return array_slice(array_filter(explode("\n", (string)file_get_contents($f))), -$n);
}
