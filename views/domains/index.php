<h2 class="text-xl font-bold mb-2">Domain Manager</h2>
<form method="post" class="mb-4 flex gap-2"><input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input name="domain" class="border p-2" placeholder="example.com" required><button name="add_domain" class="bg-indigo-600 text-white px-3">Add</button></form>
<table class="w-full bg-white dark:bg-gray-800"><tr><th>Domain</th><th>PHP</th><th>SSL</th><th>Actions</th></tr>
<?php foreach ($db->query('SELECT * FROM domains WHERE user_id = ? ORDER BY id DESC', [$user['id']]) as $d): ?>
<tr><td><?= e($d['domain']) ?></td><td><?= e($d['php_version']) ?></td><td><?= $d['ssl_enabled'] ? 'Yes' : 'No' ?></td><td>
<form method="post" class="inline"><input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="domain_id" value="<?= $d['id'] ?>"><button name="ssl_domain">Issue SSL</button></form>
<form method="post" class="inline"><input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="domain_id" value="<?= $d['id'] ?>"><input name="php_version" class="border" value="<?= e($d['php_version']) ?>"><button name="php_change">Change PHP</button></form>
<form method="post" class="inline"><input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="domain_id" value="<?= $d['id'] ?>"><button name="delete_domain">Delete</button></form>
<a href="https://<?= e($d['domain']) ?>" target="_blank">Open</a> <a href="https://<?= e($d['domain']) ?>:8083" target="_blank">File Manager</a>
</td></tr>
<?php endforeach; ?></table>
