<?php
$config = require __DIR__ . '/../config/app.php';

try {
    $pdo = new PDO(sprintf('mysql:host=%s;charset=%s', $config['db_host'], $config['db_charset'] ?? 'utf8mb4'), $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $config['db_name'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('USE `' . $config['db_name'] . '`');
    $sql = file_get_contents(__DIR__ . '/database.sql');
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
        if ($stmt !== '') $pdo->exec($stmt);
    }
    $adminEmail = 'admin@devbuzz.online';
    $exists = $pdo->query("SELECT id FROM users WHERE email='{$adminEmail}'")->fetch();
    if (!$exists) {
        $hash = password_hash('Admin@12345', PASSWORD_ARGON2ID);
        $pdo->prepare('INSERT INTO users(name,email,password_hash,role,plan_name,plan_expires_at,created_at,updated_at) VALUES(?,?,?,?,?,?,NOW(),NOW())')
            ->execute(['Administrator',$adminEmail,$hash,'admin','Agency',date('Y-m-d',strtotime('+365 days'))]);
    }
    echo "Installer completed successfully. Admin login: {$adminEmail} / Admin@12345";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Installation failed: ' . $e->getMessage();
}
