<h2 class="text-xl font-bold mb-2">Plans & Billing</h2>
<div class="grid md:grid-cols-4 gap-2 mb-4">
<?php foreach ($db->query('SELECT * FROM hosting_services WHERE is_active = 1 ORDER BY price ASC') as $plan): ?>
<div class="bg-white dark:bg-gray-800 p-3 rounded"><h3 class="font-semibold"><?= e($plan['name']) ?></h3><p>₹<?= e($plan['price']) ?></p>
<form method="post"><input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="plan_id" value="<?= $plan['id'] ?>"><button name="order_plan" class="bg-indigo-600 text-white px-3 py-1">Order</button></form>
</div>
<?php endforeach; ?></div>
<table class="w-full bg-white dark:bg-gray-800"><tr><th>ID</th><th>Amount</th><th>Status</th><th>Due</th><th></th></tr>
<?php foreach ($db->query('SELECT i.*, h.name plan_name FROM invoices i LEFT JOIN hosting_services h ON h.id=i.plan_id WHERE i.user_id = ? ORDER BY i.id DESC', [$user['id']]) as $inv): ?>
<tr><td>#<?= $inv['id'] ?> <?= e($inv['plan_name']) ?></td><td>₹<?= e($inv['amount']) ?></td><td><?= e($inv['status']) ?></td><td><?= e($inv['due_date']) ?></td><td><?php if ($inv['status'] !== 'paid'): ?><form method="post"><input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="invoice_id" value="<?= $inv['id'] ?>"><button name="mark_paid">Mark Paid</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></table>
