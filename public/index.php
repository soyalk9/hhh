<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Mailer.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Hestia.php';
require_once __DIR__ . '/../core/helpers.php';

use Core\Auth;
use Core\Database;
use Core\Hestia;

date_default_timezone_set('Asia/Kolkata');
$config = require __DIR__ . '/../config/app.php';
date_default_timezone_set($config['timezone'] ?? 'UTC');
session_set_cookie_params(['httponly' => true, 'secure' => false, 'samesite' => 'Lax']);
session_start();

$db = Database::init($config);
$auth = new Auth($db, $config);
$hestiaClient = new Hestia($config);

$route = $_GET['route'] ?? 'dashboard';
$method = $_SERVER['REQUEST_METHOD'];

$publicRoutes = ['login', 'register', 'forgot-password', 'reset-password'];
if (!in_array($route, $publicRoutes, true) && !$auth->user()) {
    header('Location: ?route=login');
    exit;
}

function redirect(string $route): void { header('Location: ?route=' . $route); exit; }

if ($method === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'CSRF validation failed');
        redirect($route);
    }

    switch ($route) {
        case 'register':
            $result = $auth->register($_POST['name'] ?? '', $_POST['email'] ?? '', $_POST['password'] ?? '');
            flash($result['success'] ? 'success' : 'error', $result['message']);
            redirect($result['success'] ? 'login' : 'register');
        case 'login':
            $result = $auth->login($_POST['email'] ?? '', $_POST['password'] ?? '');
            flash($result['success'] ? 'success' : 'error', $result['success'] ? 'Welcome back!' : $result['message']);
            redirect($result['success'] ? 'dashboard' : 'login');
        case 'forgot-password':
            $result = $auth->createResetToken($_POST['email'] ?? '');
            flash('success', $result['message']);
            redirect('forgot-password');
        case 'reset-password':
            $result = $auth->resetPassword($_POST['token'] ?? '', $_POST['password'] ?? '');
            flash($result['success'] ? 'success' : 'error', $result['message']);
            redirect($result['success'] ? 'login' : 'reset-password&token=' . urlencode($_POST['token'] ?? ''));
        case 'profile':
            $user = $auth->user();
            $result = $auth->changePassword((int)$user['id'], $_POST['current_password'] ?? '', $_POST['new_password'] ?? '');
            flash($result['success'] ? 'success' : 'error', $result['message']);
            redirect('profile');
        case 'domains':
            $user = $auth->user();
            if (isset($_POST['add_domain'])) {
                $domain = strtolower(trim($_POST['domain']));
                $res = $hestiaClient->hestia('v-add-web-domain', $config['hestia_user'], $domain);
                if ($res['success']) {
                    $db->insert('INSERT INTO domains(user_id,domain,php_version,ssl_enabled,status,created_at,updated_at) VALUES(?,?,?,?,?,?,NOW())', [$user['id'], $domain, '8.1', 0, 'active', date('Y-m-d H:i:s')]);
                }
                flash($res['success'] ? 'success' : 'error', $res['raw'] ?: ($res['success'] ? 'Domain created' : 'Domain failed'));
            }
            if (isset($_POST['delete_domain'])) {
                $domainId = (int)$_POST['domain_id'];
                $domain = $db->first('SELECT * FROM domains WHERE id = ? AND user_id = ?', [$domainId, $user['id']]);
                if ($domain) {
                    $res = $hestiaClient->hestia('v-delete-web-domain', $config['hestia_user'], $domain['domain']);
                    if ($res['success']) {
                        $db->execute('DELETE FROM domains WHERE id = ?', [$domainId]);
                    }
                    flash($res['success'] ? 'success' : 'error', $res['raw'] ?: 'Delete result received');
                }
            }
            if (isset($_POST['ssl_domain'])) {
                $domainId = (int)$_POST['domain_id'];
                $domain = $db->first('SELECT * FROM domains WHERE id = ? AND user_id = ?', [$domainId, $user['id']]);
                if ($domain) {
                    $res = $hestiaClient->hestia('v-add-letsencrypt-domain', $config['hestia_user'], $domain['domain']);
                    if ($res['success']) {
                        $db->execute('UPDATE domains SET ssl_enabled = 1, updated_at = NOW() WHERE id = ?', [$domainId]);
                    }
                    flash($res['success'] ? 'success' : 'error', $res['raw'] ?: 'SSL result received');
                }
            }
            if (isset($_POST['php_change'])) {
                $domainId = (int)$_POST['domain_id'];
                $phpVersion = preg_replace('/[^0-9.]/', '', $_POST['php_version']);
                $domain = $db->first('SELECT * FROM domains WHERE id = ? AND user_id = ?', [$domainId, $user['id']]);
                if ($domain) {
                    $res = $hestiaClient->hestia('v-change-web-domain-php', $config['hestia_user'], $domain['domain'], $phpVersion);
                    if ($res['success']) {
                        $db->execute('UPDATE domains SET php_version = ?, updated_at = NOW() WHERE id = ?', [$phpVersion, $domainId]);
                    }
                    flash($res['success'] ? 'success' : 'error', $res['raw'] ?: 'PHP version updated');
                }
            }
            redirect('domains');
        case 'databases':
            $user = $auth->user();
            if (isset($_POST['create_db'])) {
                $name = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['db_name']);
                $dbUser = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['db_user']);
                $password = $_POST['db_password'] ?: bin2hex(random_bytes(4));
                $res = $hestiaClient->hestia('v-add-database', $config['hestia_user'], $name, $dbUser, $password);
                if ($res['success']) {
                    $db->insert('INSERT INTO databases(user_id,name,db_user,db_password,status,created_at,updated_at) VALUES(?,?,?,?,?,NOW(),NOW())', [$user['id'], $name, $dbUser, password_hash($password, PASSWORD_ARGON2ID), 'active']);
                }
                flash($res['success'] ? 'success' : 'error', $res['raw'] ?: 'Database created');
            }
            if (isset($_POST['delete_db'])) {
                $id = (int)$_POST['db_id'];
                $record = $db->first('SELECT * FROM databases WHERE id = ? AND user_id = ?', [$id, $user['id']]);
                if ($record) {
                    $res = $hestiaClient->hestia('v-delete-database', $config['hestia_user'], $record['name']);
                    if ($res['success']) {
                        $db->execute('DELETE FROM databases WHERE id = ?', [$id]);
                    }
                    flash($res['success'] ? 'success' : 'error', $res['raw'] ?: 'Database delete result');
                }
            }
            redirect('databases');
        case 'tickets':
            $user = $auth->user();
            if (isset($_POST['create_ticket'])) {
                $attachment = null;
                if (!empty($_FILES['attachment']['name'])) {
                    $dir = __DIR__ . '/uploads';
                    if (!is_dir($dir)) mkdir($dir, 0775, true);
                    $name = time() . '_' . basename($_FILES['attachment']['name']);
                    $target = $dir . '/' . $name;
                    move_uploaded_file($_FILES['attachment']['tmp_name'], $target);
                    $attachment = 'uploads/' . $name;
                }
                $db->insert('INSERT INTO tickets(user_id,subject,message,status,attachment,created_at,updated_at) VALUES(?,?,?,?,?,NOW(),NOW())', [$user['id'], trim($_POST['subject']), trim($_POST['message']), 'open', $attachment]);
                flash('success', 'Ticket created');
            }
            if (isset($_POST['reply_ticket'])) {
                $ticketId = (int)$_POST['ticket_id'];
                $db->execute('UPDATE tickets SET admin_reply = ?, status = ?, updated_at = NOW() WHERE id = ? AND user_id = ?', [trim($_POST['reply']), trim($_POST['status']), $ticketId, $user['id']]);
                flash('success', 'Ticket updated');
            }
            redirect('tickets');
        case 'billing':
            $user = $auth->user();
            if (isset($_POST['order_plan'])) {
                $plan = $db->first('SELECT * FROM hosting_services WHERE id = ?', [(int)$_POST['plan_id']]);
                if ($plan) {
                    $amount = (float)$plan['price'];
                    $due = date('Y-m-d', strtotime('+7 days'));
                    $db->insert('INSERT INTO invoices(user_id,plan_id,amount,status,due_date,created_at,updated_at) VALUES(?,?,?,?,?,NOW(),NOW())', [$user['id'], $plan['id'], $amount, 'pending', $due]);
                    flash('success', 'Invoice created for selected plan');
                }
            }
            if (isset($_POST['mark_paid'])) {
                $db->execute('UPDATE invoices SET status = "paid", paid_at = NOW(), updated_at = NOW() WHERE id = ? AND user_id = ?', [(int)$_POST['invoice_id'], $user['id']]);
                flash('success', 'Invoice marked paid');
            }
            redirect('billing');
    }
}

if ($route === 'logout') {
    $auth->logout();
    redirect('login');
}

$user = $auth->user();
$stats = [];
if ($user) {
    $stats = [
        'domains' => $db->first('SELECT COUNT(*) c FROM domains WHERE user_id = ?', [$user['id']])['c'] ?? 0,
        'databases' => $db->first('SELECT COUNT(*) c FROM databases WHERE user_id = ?', [$user['id']])['c'] ?? 0,
        'plan_name' => $user['plan_name'] ?? 'Student',
        'plan_expires' => $user['plan_expires_at'] ?? '',
        'disk_usage' => '0 MB',
        'bandwidth_usage' => '0 MB',
    ];
    $hlist = $hestiaClient->hestia('v-list-web-domains', $config['hestia_user'], 'json');
    if ($hlist['success'] && is_array($hlist['formatted'])) {
        $stats['disk_usage'] = (string)(array_sum(array_column($hlist['formatted'], 'DISK')) ?? 0) . ' MB';
        $stats['bandwidth_usage'] = (string)(array_sum(array_column($hlist['formatted'], 'BANDWIDTH')) ?? 0) . ' MB';
    }
}

$viewFile = __DIR__ . '/../views/' . ($route === 'dashboard' ? 'dashboard/index' : $route) . '.php';
if (!file_exists($viewFile)) {
    $map = ['login'=>'auth/login','register'=>'auth/register','forgot-password'=>'auth/forgot-password','reset-password'=>'auth/reset-password','domains'=>'domains/index','databases'=>'databases/index','tickets'=>'tickets/index','billing'=>'billing/index','profile'=>'profile/index'];
    $viewFile = __DIR__ . '/../views/' . ($map[$route] ?? 'dashboard/index') . '.php';
}
include __DIR__ . '/../views/partials/layout.php';
