<h2 class="text-2xl font-bold mb-4">Dashboard</h2>
<div class="grid md:grid-cols-3 gap-4">
<div class="bg-white dark:bg-gray-800 p-4 rounded">Total Domains: <?= (int)$stats['domains'] ?></div>
<div class="bg-white dark:bg-gray-800 p-4 rounded">Total Databases: <?= (int)$stats['databases'] ?></div>
<div class="bg-white dark:bg-gray-800 p-4 rounded">Plan: <?= e($stats['plan_name']) ?></div>
<div class="bg-white dark:bg-gray-800 p-4 rounded">Expires: <?= e($stats['plan_expires']) ?></div>
<div class="bg-white dark:bg-gray-800 p-4 rounded">Disk Usage: <?= e($stats['disk_usage']) ?></div>
<div class="bg-white dark:bg-gray-800 p-4 rounded">Bandwidth: <?= e($stats['bandwidth_usage']) ?></div>
</div>
