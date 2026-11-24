<?php
/*
 * Copy this file to config.php and fill it in. config.php is never committed to Git.
 * On Hostinger: File Manager > (site folder) > create config.php with these values.
 */
return [
    // Hostinger > Databases
    'db_host' => 'localhost',
    'db_name' => 'u000000000_restaurant',
    'db_user' => 'u000000000_restaurant',
    'db_pass' => '',

    // Public address, no trailing slash
    'site_url' => 'https://www.example.com',
    'timezone' => 'America/Chicago',

    // Email (Hostinger > Emails). Leave smtp_host empty to write emails to storage/mail.log instead.
    'smtp_host' => 'smtp.hostinger.com',
    'smtp_port' => 465,
    'smtp_user' => 'info@example.com',
    'smtp_pass' => '',
    'mail_from' => 'info@example.com',
    'mail_from_name' => '',        // empty = the restaurant name from Settings
    'shop_notify_email' => '',     // new orders, table requests and messages; empty = the email in Settings

    // One-time key for creating the first Admin at /admin/setup. Use a long random value, then remove it.
    'setup_key' => '',

    // Staging copy: keeps search engines out. Show detailed errors (local development only).
    'staging' => false,
    'debug' => false,

    // Live demo builder (for DIGIVANT's demo site only — leave off for a real restaurant).
    'demo_builder' => false,
    'demo_hours' => 48,
    'demo_per_day' => 3,
    'demo_max_active' => 100,
    'demo_sales_email' => '',
    'demo_sales_url' => '',
];
