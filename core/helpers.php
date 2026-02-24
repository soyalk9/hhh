<?php
use Core\Database;

function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function csrf_token(): string {
    if (empty($_SESSION['_csrf'])) { $_SESSION['_csrf'] = bin2hex(random_bytes(16)); }
    return $_SESSION['_csrf'];
}
function csrf_verify(): bool {
    return isset($_POST['_csrf'], $_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $_POST['_csrf']);
}
function flash(string $key, ?string $message = null): ?string {
    if ($message !== null) { $_SESSION['_flash'][$key] = $message; return null; }
    $msg = $_SESSION['_flash'][$key] ?? null; unset($_SESSION['_flash'][$key]); return $msg;
}
function log_event(Database $db, ?int $userId, string $level, string $message, array $context = []): void {
    $db->insert('INSERT INTO logs(user_id,level,message,context,created_at) VALUES(?,?,?,?,NOW())', [$userId, $level, $message, json_encode($context)]);
}
