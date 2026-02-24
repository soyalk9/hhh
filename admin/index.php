<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Mailer.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/helpers.php';

use Core\Auth;
use Core\Database;

$config = require __DIR__ . '/../config/app.php';
session_start();
$db = Database::init($config);
$auth = new Auth($db, $config);
$route = $_GET['route'] ?? 'dashboard';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { die('CSRF invalid'); }
    if ($route === 'login') {
        $res = $auth->login($_POST['email'] ?? '', $_POST['password'] ?? '', true);
        flash($res['success'] ? 'success' : 'error', $res['success'] ? 'Admin logged in' : $res['message']);
        header('Location: /admin/index.php');
        exit;
    }
    $auth->requireLogin(true);
    switch ($route) {
        case 'users':
            if (isset($_POST['suspend'])) $db->execute('UPDATE users SET is_suspended = 1 WHERE id = ?', [(int)$_POST['user_id']]);
            if (isset($_POST['activate'])) $db->execute('UPDATE users SET is_suspended = 0 WHERE id = ?', [(int)$_POST['user_id']]);
            if (isset($_POST['impersonate'])) { $_SESSION['user_id'] = (int)$_POST['user_id']; $_SESSION['role'] = 'user'; header('Location: /public/index.php?route=dashboard'); exit; }
            break;
        case 'domains':
            if (isset($_POST['disable_domain'])) $db->execute('UPDATE domains SET status = "disabled" WHERE id = ?', [(int)$_POST['domain_id']]);
            if (isset($_POST['enable_domain'])) $db->execute('UPDATE domains SET status = "active" WHERE id = ?', [(int)$_POST['domain_id']]);
            break;
        case 'tickets':
            $db->execute('UPDATE tickets SET admin_reply = ?, status = ?, updated_at = NOW() WHERE id = ?', [trim($_POST['reply'] ?? ''), trim($_POST['status'] ?? 'open'), (int)$_POST['ticket_id']]);
            break;
        case 'plans':
            if (isset($_POST['add_plan'])) $db->insert('INSERT INTO hosting_services(name,price,description,is_active,created_at,updated_at) VALUES(?,?,?,?,NOW(),NOW())', [$_POST['name'], $_POST['price'], $_POST['description'], 1]);
            if (isset($_POST['edit_plan'])) $db->execute('UPDATE hosting_services SET name=?, price=?, description=?, updated_at=NOW() WHERE id=?', [$_POST['name'], $_POST['price'], $_POST['description'], (int)$_POST['plan_id']]);
            break;
    }
    header('Location: /admin/index.php?route=' . $route); exit;
}

if ($route !== 'login') $auth->requireLogin(true);
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-gray-100 p-6">
<?php if ($route==='login'): ?>
<form method="post" class="max-w-md mx-auto bg-white p-4 rounded"><input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><h1 class="text-xl font-bold">Admin Login</h1><input class="border p-2 w-full my-2" type="email" name="email" placeholder="Email"><input class="border p-2 w-full my-2" type="password" name="password" placeholder="Password"><button class="bg-indigo-600 text-white px-3 py-1">Login</button></form>
<?php else: ?>
<nav class="mb-4 space-x-3"><a href="?route=dashboard">Dashboard</a><a href="?route=users">Users</a><a href="?route=domains">Domains</a><a href="?route=tickets">Tickets</a><a href="?route=logs">Logs</a><a href="?route=plans">Plans</a><a href="?route=settings">Settings</a></nav>
<?php if ($route==='dashboard'): ?><div class="grid grid-cols-4 gap-2"><?php foreach (['users','domains','tickets','invoices'] as $t): $c=$db->first("SELECT COUNT(*) c FROM {$t}")['c']; ?><div class="bg-white p-4 rounded"><?= strtoupper($t) ?>: <?= $c ?></div><?php endforeach; ?></div><?php endif; ?>
<?php if ($route==='users'): foreach($db->query('SELECT * FROM users ORDER BY id DESC') as $u): ?><div class="bg-white p-2 mb-2"><?= e($u['email']) ?> (<?= e($u['role']) ?>)
<form method="post" class="inline"><input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="user_id" value="<?= $u['id'] ?>"><?php if($u['is_suspended']): ?><button name="activate">Activate</button><?php else: ?><button name="suspend">Suspend</button><?php endif; ?><button name="impersonate">Impersonate</button></form>
</div><?php endforeach; endif; ?>
<?php if ($route==='domains'): foreach($db->query('SELECT d.*,u.email FROM domains d JOIN users u ON u.id=d.user_id ORDER BY d.id DESC') as $d): ?><div class="bg-white p-2 mb-2"><?= e($d['domain']) ?> - <?= e($d['status']) ?>
<form method="post" class="inline"><input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="domain_id" value="<?= $d['id'] ?>"><button name="enable_domain">Enable</button><button name="disable_domain">Disable</button></form></div><?php endforeach; endif; ?>
<?php if ($route==='tickets'): foreach($db->query('SELECT t.*,u.email FROM tickets t JOIN users u ON u.id=t.user_id ORDER BY t.id DESC') as $t): ?><form method="post" class="bg-white p-2 mb-2"><input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="ticket_id" value="<?= $t['id'] ?>"><strong><?= e($t['subject']) ?></strong> by <?= e($t['email']) ?><p><?= e($t['message']) ?></p><textarea name="reply" class="border p-1 w-full"><?= e($t['admin_reply']) ?></textarea><select name="status"><option<?= $t['status']==='open'?' selected':'' ?>>open</option><option<?= $t['status']==='answered'?' selected':'' ?>>answered</option><option<?= $t['status']==='closed'?' selected':'' ?>>closed</option></select><button class="bg-indigo-600 text-white px-2">Save</button></form><?php endforeach; endif; ?>
<?php if ($route==='logs'): foreach($db->query('SELECT l.*,u.email FROM logs l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.id DESC LIMIT 200') as $l): ?><div class="bg-white p-2 mb-1 text-sm"><?= e($l['created_at']) ?> [<?= e($l['level']) ?>] <?= e($l['email'] ?? 'system') ?> - <?= e($l['message']) ?></div><?php endforeach; endif; ?>
<?php if ($route==='plans'): ?><form method="post" class="bg-white p-2 mb-3"><input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input name="name" class="border p-1" placeholder="Plan name"><input name="price" class="border p-1" placeholder="Price"><input name="description" class="border p-1" placeholder="Description"><button name="add_plan" class="bg-indigo-600 text-white px-2">Add</button></form><?php foreach($db->query('SELECT * FROM hosting_services ORDER BY id DESC') as $p): ?><form method="post" class="bg-white p-2 mb-1"><input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="plan_id" value="<?= $p['id'] ?>"><input name="name" value="<?= e($p['name']) ?>" class="border p-1"><input name="price" value="<?= e($p['price']) ?>" class="border p-1"><input name="description" value="<?= e($p['description']) ?>" class="border p-1"><button name="edit_plan">Save</button></form><?php endforeach; endif; ?>
<?php if ($route==='settings'): ?><div class="bg-white p-3">Settings are sourced from <code>/config/app.php</code>.</div><?php endif; ?>
<?php endif; ?></body></html>
