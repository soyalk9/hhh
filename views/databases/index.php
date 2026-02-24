<h2 class="text-xl font-bold mb-2">Database Manager</h2>
<form method="post" class="mb-4 grid md:grid-cols-4 gap-2"><input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input name="db_name" class="border p-2" placeholder="db_name" required><input name="db_user" class="border p-2" placeholder="db_user" required><input name="db_password" class="border p-2" placeholder="password"><button name="create_db" class="bg-indigo-600 text-white px-3">Create</button></form>
<table class="w-full bg-white dark:bg-gray-800"><tr><th>Name</th><th>User</th><th>phpMyAdmin</th><th></th></tr>
<?php foreach ($db->query('SELECT * FROM databases WHERE user_id = ? ORDER BY id DESC', [$user['id']]) as $item): ?>
<tr><td><?= e($item['name']) ?></td><td><?= e($item['db_user']) ?></td><td><a href="https://<?= parse_url($config['base_url'], PHP_URL_HOST) ?>/phpmyadmin" target="_blank">Open</a></td><td>
<form method="post"><input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="db_id" value="<?= $item['id'] ?>"><button name="delete_db">Delete</button></form>
</td></tr>
<?php endforeach; ?></table>
