<?php
// admin_pages/notifications.php
if (!isset($_SESSION['is_admin'])) { die('Akses ditolak'); }
?>
<h1 class="text-3xl font-bold text-slate-800 mb-6">Manajemen Notifikasi</h1>
<div class="grid md:grid-cols-3 gap-6">
    <div class="md:col-span-1">
        <div class="bg-white p-6 rounded-xl shadow-lg">
            <h2 class="text-xl font-semibold text-slate-700 mb-4">Tambah Notifikasi</h2>
            <form id="add-notification-form" class="space-y-4">
                <div>
                    <label for="notification_message" class="block text-sm font-medium text-slate-700">Pesan Baru</label>
                    <textarea name="message" id="notification_message" rows="5" maxlength="500" placeholder="Tulis notifikasi untuk pengguna..." class="mt-1 block w-full px-3 py-2 bg-white border border-slate-300 rounded-md shadow-sm resize-none focus:ring-indigo-500 focus:border-indigo-500" required></textarea>
                </div>
                <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold">Kirim Notifikasi</button>
            </form>
        </div>
    </div>
    <div class="md:col-span-2">
        <div class="bg-white p-6 rounded-xl shadow-lg">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-slate-700">Daftar Notifikasi</h2>
                <button id="delete-all-notifications-btn" class="text-xs text-red-600 hover:underline">Hapus Semua</button>
            </div>
            <div id="notification-list-container" class="space-y-3 max-h-[30rem] overflow-y-auto pr-2">
                <p class="text-center p-4 text-slate-500">Memuat notifikasi...</p>
            </div>
        </div>
    </div>
</div>
