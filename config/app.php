<?php 
return [
    'db_host' => 'localhost',
    'db_user' => 'devbuzz_devpanel_user',
    'db_pass' => 'YOUR_DB_PASSWORD_HERE',   // <-- yaha apna real password daalna
    'db_name' => 'devbuzz_devpanel',

    'hestia_user' => 'devbuzz',
    'sudo_path' => '/usr/bin/sudo',
    'hestia_bin' => '/usr/local/hestia/bin/',

    'base_url' => 'https://devbuzz.online',
    'db_charset' => 'utf8mb4',

    'smtp_host' => 'localhost',
    'smtp_port' => 587,
    'smtp_user' => '',
    'smtp_pass' => '',
    'smtp_secure' => 'tls',

    'mail_from' => 'noreply@devbuzz.online',
    'mail_from_name' => 'DevBuzz Panel',

    'app_name' => 'DevBuzz Panel',
    'debug' => true,
    'timezone' => 'Asia/Kolkata',
    'session_lifetime' => 7200,
];
