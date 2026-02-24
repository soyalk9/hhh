<?php
namespace Core;

class Auth
{
    public function __construct(private Database $db, private array $config)
    {
    }

    public function register(string $name, string $email, string $password): array
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email address.'];
        }
        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters.'];
        }
        $exists = $this->db->first('SELECT id FROM users WHERE email = ?', [$email]);
        if ($exists) {
            return ['success' => false, 'message' => 'Email already registered.'];
        }
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $id = $this->db->insert('INSERT INTO users(name,email,password_hash,role,plan_name,plan_expires_at,created_at,updated_at) VALUES(?,?,?,?,?,?,NOW(),NOW())', [
            trim($name), $email, $hash, 'user', 'Student', date('Y-m-d', strtotime('+30 days')),
        ]);
        return ['success' => true, 'user_id' => $id, 'message' => 'Registration successful.'];
    }

    public function login(string $email, string $password, bool $admin = false): array
    {
        $user = $this->db->first('SELECT * FROM users WHERE email = ?', [strtolower(trim($email))]);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        if ((int)$user['is_suspended'] === 1) {
            return ['success' => false, 'message' => 'Account suspended'];
        }
        if ($admin && $user['role'] !== 'admin') {
            return ['success' => false, 'message' => 'Admin access denied'];
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        $this->db->insert('INSERT INTO sessions(user_id,session_id,ip_address,user_agent,created_at,updated_at) VALUES(?,?,?,?,NOW(),NOW())', [
            $user['id'], session_id(), $_SERVER['REMOTE_ADDR'] ?? 'cli', $_SERVER['HTTP_USER_AGENT'] ?? 'cli',
        ]);
        return ['success' => true, 'user' => $user];
    }

    public function logout(): void
    {
        if (!empty($_SESSION['user_id'])) {
            $this->db->execute('DELETE FROM sessions WHERE session_id = ?', [session_id()]);
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public function user(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        return $this->db->first('SELECT * FROM users WHERE id = ?', [$_SESSION['user_id']]);
    }

    public function requireLogin(bool $admin = false): void
    {
        $user = $this->user();
        if (!$user) {
            header('Location: /?route=login');
            exit;
        }
        if ($admin && $user['role'] !== 'admin') {
            http_response_code(403);
            exit('Forbidden');
        }
    }

    public function changePassword(int $userId, string $current, string $new): array
    {
        $user = $this->db->first('SELECT * FROM users WHERE id = ?', [$userId]);
        if (!$user || !password_verify($current, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
        if (strlen($new) < 8) {
            return ['success' => false, 'message' => 'New password too short'];
        }
        $hash = password_hash($new, PASSWORD_ARGON2ID);
        $this->db->execute('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?', [$hash, $userId]);
        return ['success' => true, 'message' => 'Password updated'];
    }

    public function createResetToken(string $email): array
    {
        $user = $this->db->first('SELECT * FROM users WHERE email = ?', [strtolower(trim($email))]);
        if (!$user) {
            return ['success' => true, 'message' => 'If the account exists, reset instructions were sent'];
        }
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $this->db->execute('UPDATE users SET reset_token = ?, reset_expires_at = ? WHERE id = ?', [$token, $expires, $user['id']]);
        $resetLink = rtrim($this->config['base_url'], '/') . '/?route=reset-password&token=' . urlencode($token);
        Mailer::send($this->config, $email, 'Password Reset', "Reset your password: {$resetLink}");
        return ['success' => true, 'message' => 'Reset instructions sent'];
    }

    public function resetPassword(string $token, string $newPassword): array
    {
        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'Password too short'];
        }
        $user = $this->db->first('SELECT * FROM users WHERE reset_token = ? AND reset_expires_at > NOW()', [$token]);
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid or expired token'];
        }
        $hash = password_hash($newPassword, PASSWORD_ARGON2ID);
        $this->db->execute('UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires_at = NULL, updated_at = NOW() WHERE id = ?', [$hash, $user['id']]);
        return ['success' => true, 'message' => 'Password reset successful'];
    }
}
