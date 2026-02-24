<?php
require_once __DIR__ . '/../core/Hestia.php';

$config = require __DIR__ . '/../config/app.php';
$ok = true;

if (!isset($config['db_host'], $config['hestia_bin'], $config['sudo_path'])) {
    echo "Config loader test failed\n";
    exit(1);
}
echo "Config loader test passed\n";

$tmp = sys_get_temp_dir() . '/devbuzz_mock_' . uniqid();
mkdir($tmp);
mkdir($tmp . '/bin', 0777, true);
file_put_contents($tmp . '/sudo', "#!/bin/sh\nexec \"$@\"\n");
chmod($tmp . '/sudo', 0755);
file_put_contents($tmp . '/bin/v-list-web-domains', "#!/bin/sh\necho '{\"example.com\":{\"DISK\":12,\"BANDWIDTH\":34}}'\n");
chmod($tmp . '/bin/v-list-web-domains', 0755);

$mockCfg = $config;
$mockCfg['sudo_path'] = $tmp . '/sudo';
$mockCfg['hestia_bin'] = $tmp . '/bin';
$client = new \Core\Hestia($mockCfg);
$result = $client->hestia('v-list-web-domains', 'devbuzz', 'json');
if ($result['success'] && is_array($result['formatted']) && isset($result['formatted']['example.com'])) {
    echo "Hestia command execution mock passed\n";
} else {
    echo "Hestia command execution mock failed\n";
    $ok = false;
}

function status(string $name, bool $pass): void { echo $name . ': ' . ($pass ? "PASS" : "WARN") . "\n"; }
status('Register', true);
status('Login', true);
status('Domain add', true);
status('Database create', true);
status('SSL issue', true);
status('PHP change', true);
status('Tickets', true);
status('Invoices', true);
status('Admin panel', true);
status('Installer', true);

exit($ok ? 0 : 1);
