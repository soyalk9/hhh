<?php $isAuthPage = in_array($route, ['login','register','forgot-password','reset-password'], true); ?>
<!doctype html>
<html lang="en" class="h-full">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($config['app_name']) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="h-full bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
<?php if ($isAuthPage): ?>
<div class="min-h-full flex items-center justify-center p-6"><?php include $viewFile; ?></div>
<?php else: ?>
<div class="flex min-h-screen">
  <aside class="w-64 bg-indigo-700 text-white p-4 space-y-2">
    <h1 class="text-2xl font-bold">DevBuzz Panel</h1>
    <a class="block" href="?route=dashboard">Dashboard</a>
    <a class="block" href="?route=domains">Domains</a>
    <a class="block" href="?route=databases">Databases</a>
    <a class="block" href="?route=tickets">Tickets</a>
    <a class="block" href="?route=billing">Billing</a>
    <a class="block" href="?route=profile">Profile</a>
    <a class="block" href="/admin/index.php">Admin</a>
    <a class="block" href="?route=logout">Logout</a>
    <button id="themeToggle" class="bg-indigo-900 px-2 py-1 rounded">Toggle Theme</button>
  </aside>
  <main class="flex-1 p-6">
    <?php if ($m = flash('success')): ?><div class="bg-green-200 text-green-900 p-2 mb-3 rounded"><?= e($m) ?></div><?php endif; ?>
    <?php if ($m = flash('error')): ?><div class="bg-red-200 text-red-900 p-2 mb-3 rounded"><?= e($m) ?></div><?php endif; ?>
    <?php include $viewFile; ?>
  </main>
</div>
<script src="assets/js/app.js"></script>
<?php endif; ?>
</body>
</html>
