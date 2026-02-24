<form method="post" class="bg-white dark:bg-gray-800 p-6 rounded shadow w-full max-w-md space-y-3">
<input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
<input type="hidden" name="token" value="<?= e($_GET['token'] ?? '') ?>">
<h2 class="text-xl font-bold">Reset Password</h2>
<input name="password" type="password" placeholder="New Password" class="w-full border p-2" required>
<button class="w-full bg-indigo-600 text-white p-2 rounded">Update Password</button>
</form>
