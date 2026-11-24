<?php
/* Deletes expired demo shops (their tables and files). Safe to run any time; the app also does this now and then.
 * Hostinger: hPanel → Advanced → Cron Jobs → every hour:  php /home/USER/domains/SITE/public_html/deploy/demo-cleanup.php */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/bootstrap.php';
if (!demo_enabled()) { echo "Demo builder is off; nothing to do.\n"; exit; }
$total = 0; while (($n = demo_cleanup(50)) > 0) $total += $n;
echo "Deleted $total expired demo(s).\n";
