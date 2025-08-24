<?php
// admin_pages/settings.php
if (!isset($_SESSION['is_admin'])) { die('Akses ditolak'); }
$csrf_token = generateCSRFToken();

// Mengambil nilai jeda API saat ini dari database
$current_delay = getSetting('api_request_delay', 0); 
?>

<h1 class="text-3xl font-bold text-slate-800 mb-6">Settings</h1>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Card untuk Ubah Kata Sandi -->
    <div class="bg-white p-6 rounded-2xl shadow-lg border border-slate-100 card-hover">
        <h2 class="text-xl font-bold text-slate-800 mb-4">Ubah Kata Sandi Admin</h2>
        <form action="andrias.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="change_admin_password">
            <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
            
            <div>
                <label for="current_password" class="block text-sm font-medium text-slate-700 mb-1">Kata Sandi Saat Ini</label>
                <input type="password" id="current_password" name="current_password" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            
            <div>
                <label for="new_password" class="block text-sm font-medium text-slate-700 mb-1">Kata Sandi Baru</label>
                <input type="password" id="new_password" name="new_password" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label for="confirm_password" class="block text-sm font-medium text-slate-700 mb-1">Konfirmasi Kata Sandi Baru</label>
                <input type="password" id="confirm_password" name="confirm_password" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold">
                    Simpan Kata Sandi
                </button>
            </div>
        </form>
    </div>

    <!-- [BARU] Card untuk Pengaturan API -->
    <div class="bg-white p-6 rounded-2xl shadow-lg border border-slate-100 card-hover">
        <h2 class="text-xl font-bold text-slate-800 mb-4">Pengaturan API</h2>
        <form action="andrias.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="update_api_settings">
            <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
            
            <div>
                <label for="api_request_delay" class="block text-sm font-medium text-slate-700 mb-1">Jeda Antar Permintaan (detik)</label>
                <input type="number" id="api_request_delay" name="api_request_delay" min="0" value="<?= e($current_delay) ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                <p class="text-xs text-slate-500 mt-1">Atur jeda waktu (dalam detik) antar setiap permintaan API untuk mencegah spam. Isi dengan 0 untuk menonaktifkan.</p>
            </div>

            <div>
                <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold">
                    Simpan Pengaturan API
                </button>
            </div>
        </form>
    </div>
</div>
