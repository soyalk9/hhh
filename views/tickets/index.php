<h2 class="text-xl font-bold mb-2">Support Tickets</h2>
<form method="post" enctype="multipart/form-data" class="bg-white dark:bg-gray-800 p-3 rounded mb-4 space-y-2"><input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input name="subject" class="w-full border p-2" placeholder="Subject" required><textarea name="message" class="w-full border p-2" placeholder="Message" required></textarea><input type="file" name="attachment"><button name="create_ticket" class="bg-indigo-600 text-white px-3 py-1">Create Ticket</button></form>
<?php foreach ($db->query('SELECT * FROM tickets WHERE user_id = ? ORDER BY id DESC', [$user['id']]) as $t): ?>
<div class="bg-white dark:bg-gray-800 p-3 mb-2 rounded"><strong><?= e($t['subject']) ?></strong> (<?= e($t['status']) ?>)
<p><?= nl2br(e($t['message'])) ?></p>
<?php if ($t['attachment']): ?><a href="<?= e($t['attachment']) ?>" target="_blank">Attachment</a><?php endif; ?>
<p class="text-sm">Admin reply: <?= e($t['admin_reply'] ?: 'Pending') ?></p>
</div>
<?php endforeach; ?>
