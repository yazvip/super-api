<?php
// admin_pages/edit_key.php
if (!isset($_SESSION['is_admin'])) { die('Akses ditolak'); }
$csrf_token = generateCSRFToken();
$db = getDbConnection();
$stmt = $db->prepare("SELECT * FROM api_keys WHERE id = :id");
$stmt->execute([':id' => $_GET['id']]);
$item = $stmt->fetch();
if ($item):
?>
<h1 class="text-3xl font-bold text-slate-800 mb-6">Edit API Key</h1>
<div class="bg-white p-6 rounded-xl shadow-lg">
    <form method="POST">
        <input type="hidden" name="action" value="update_key">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <input type="hidden" name="id" value="<?= e($item['id']) ?>">
        <div class="space-y-4">
            <div>
                <label class="block font-medium">API Key</label>
                <input type="text" value="<?= e($item['api_key']) ?>" class="mt-1 block w-full px-3 py-2 bg-slate-100 border border-slate-300 rounded-md" readonly>
            </div>
            <div>
                <label for="nama" class="block text-sm font-medium text-slate-700">Nama Pengguna</label>
                <input type="text" name="nama" id="nama" value="<?= e($item['nama']) ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-slate-300 rounded-md shadow-sm" required>
            </div>
            <div>
                <label for="nomor_wa" class="block text-sm font-medium text-slate-700">Nomor WA</label>
                <input type="text" name="nomor_wa" id="nomor_wa" value="<?= e($item['nomor_wa']) ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-slate-300 rounded-md shadow-sm">
            </div>
            <div>
                <label for="monthly_limit_edit" class="block text-sm font-medium text-slate-700">Limit per Siklus (28 hari)</label>
                <input type="number" name="monthly_limit" id="monthly_limit_edit" value="<?= e($item['monthly_limit']) ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-slate-300 rounded-md shadow-sm">
            </div>
            <div>
                <label for="expiry_date_edit" class="block text-sm font-medium text-slate-700">Tanggal Kedaluwarsa Key</label>
                <input type="date" name="expiry_date" id="expiry_date_edit" value="<?= e($item['expiry_date']) ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-slate-300 rounded-md shadow-sm">
            </div>
             <div>
                <label class="flex items-center"><input type="checkbox" name="is_active" value="1" <?= $item['is_active'] ? 'checked' : '' ?> class="h-4 w-4 text-indigo-600 border-slate-300 rounded"><span class="ml-2 text-sm text-slate-900">Aktif</span></label>
            </div>
        </div>
        <div class="mt-6 flex items-center gap-4">
            <button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold">Simpan Perubahan</button>
            <a href="?page=api_keys" class="text-slate-600 hover:underline">Batal</a>
        </div>
    </form>
</div>
<?php endif; ?>
