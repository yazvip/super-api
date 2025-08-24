<?php
// admin_pages/edit_notification.php
if (!isset($_SESSION['is_admin'])) { die('Akses ditolak'); }
$csrf_token = generateCSRFToken();
$db = getDbConnection();
$stmt = $db->prepare("SELECT * FROM notifications WHERE id = :id");
$stmt->execute([':id' => $_GET['id']]);
$item = $stmt->fetch();
if ($item):
?>
<h1 class="text-3xl font-bold text-slate-800 mb-6">Edit Notifikasi</h1>
<div class="bg-white p-6 rounded-xl shadow-lg">
    <form method="POST">
        <input type="hidden" name="action" value="do_update_notification">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <input type="hidden" name="id" value="<?= e($item['id']) ?>">
        <div class="space-y-4">
            <div>
                <label for="message_edit" class="block font-medium">Pesan</label>
                <textarea name="message" id="message_edit" rows="4" class="mt-1 block w-full px-3 py-2 bg-white border border-slate-300 rounded-md shadow-sm"><?= e($item['message']) ?></textarea>
            </div>
             <div>
                <label class="flex items-center"><input type="checkbox" name="is_active" value="1" <?= $item['is_active'] ? 'checked' : '' ?> class="h-4 w-4 text-indigo-600 border-slate-300 rounded"><span class="ml-2 text-sm text-slate-900">Aktif</span></label>
            </div>
        </div>
        <div class="mt-6 flex items-center gap-4">
            <button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold">Simpan Perubahan</button>
            <a href="?page=notifications" class="text-slate-600 hover:underline">Batal</a>
        </div>
    </form>
</div>
<?php endif; ?>
