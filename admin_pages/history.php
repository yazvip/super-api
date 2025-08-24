<?php
// admin_pages/history.php
if (!isset($_SESSION['is_admin'])) { die('Akses ditolak'); }
$csrf_token = generateCSRFToken();
?>
<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold text-slate-800">Riwayat Pengecekan</h1>
    <div class="flex gap-3">
        <select id="api-key-filter" class="px-3 py-2 border border-slate-300 rounded-lg text-sm">
            <option value="">Semua API Key</option>
        </select>
        <button id="delete-all-history-btn" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold text-sm">
            Hapus Semua History
        </button>
    </div>
</div>

<!-- Rekapan/Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white p-6 rounded-2xl shadow-lg flex items-center gap-4 border border-slate-100">
        <div class="bg-blue-100 p-3 rounded-xl">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-slate-500">Total Riwayat</p>
            <p id="stats-total" class="text-2xl font-bold text-slate-800">...</p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-lg flex items-center gap-4 border border-slate-100">
        <div class="bg-green-100 p-3 rounded-xl">
             <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-slate-500">Berhasil</p>
            <p id="stats-success" class="text-2xl font-bold text-green-600">...</p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-lg flex items-center gap-4 border border-slate-100">
        <div class="bg-red-100 p-3 rounded-xl">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-slate-500">Gagal</p>
            <p id="stats-failed" class="text-2xl font-bold text-red-600">...</p>
        </div>
    </div>
</div>
<!-- End of Rekapan -->


<div class="bg-white p-6 rounded-xl shadow-lg">
    <div class="overflow-x-auto border border-slate-200 rounded-lg">
        <table class="w-full table-auto text-sm">
            <thead class="bg-slate-50 text-slate-600 text-left"><tr><th class="p-3 font-semibold">Key</th><th class="p-3 font-semibold">Bank</th><th class="p-3 font-semibold">No. Rekening</th><th class="p-3 font-semibold">Nama Pemilik</th><th class="p-3 font-semibold">Status</th><th class="p-3 font-semibold">Pesan</th><th class="p-3 font-semibold">Waktu</th><th class="p-3 font-semibold">Aksi</th></tr></thead>
            <tbody id="history-tbody" class="divide-y divide-slate-200"><tr><td colspan="8" class="text-center p-6 text-slate-500">Memuat data...</td></tr></tbody>
        </table>
    </div>
    <div id="pagination-controls" class="flex justify-between items-center mt-4"></div>
</div>

<!-- Modal Konfirmasi Hapus Semua -->
<div id="delete-all-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white w-full max-w-md p-6 rounded-2xl shadow-xl">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-800">Konfirmasi Penghapusan</h3>
                <p class="text-sm text-slate-600">Tindakan ini tidak dapat dibatalkan!</p>
            </div>
        </div>
        <div class="mb-6">
            <p class="text-slate-700 mb-4">Anda yakin ingin menghapus <strong id="delete-count">semua</strong> riwayat pengecekan?</p>
            <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                <p class="text-red-800 text-sm font-medium">⚠️ Peringatan: Data yang dihapus tidak dapat dikembalikan!</p>
            </div>
        </div>
        <div class="flex gap-3">
            <button id="confirm-delete-all" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold">
                Ya, Hapus Semua
            </button>
            <button id="cancel-delete-all" class="px-4 py-2 bg-slate-200 text-slate-800 rounded-lg hover:bg-slate-300">
                Batal
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '<?= $csrf_token ?>';
    let currentApiKeyFilter = '';
    
    // Fungsi untuk memuat statistik rekapitulasi
    async function loadHistoryStats() {
        const apiKeyFilter = window.getCurrentApiKeyFilter ? window.getCurrentApiKeyFilter() : '';
        const totalEl = document.getElementById('stats-total');
        const successEl = document.getElementById('stats-success');
        const failedEl = document.getElementById('stats-failed');

        // Atur status loading
        totalEl.textContent = '...';
        successEl.textContent = '...';
        failedEl.textContent = '...';

        try {
            // Asumsi endpoint `get_history_stats` ada untuk mengambil data statistik
            const response = await fetch(`?api=true&action=get_history_stats&api_key_filter=${encodeURIComponent(apiKeyFilter)}`);
            const result = await response.json();

            if (result.success && result.data) {
                totalEl.textContent = result.data.total || 0;
                successEl.textContent = result.data.success || 0;
                failedEl.textContent = result.data.failed || 0;
            } else {
                throw new Error(result.message || 'Gagal memuat statistik.');
            }
        } catch (error) {
            console.error('Error loading history stats:', error);
            totalEl.textContent = '-';
            successEl.textContent = '-';
            failedEl.textContent = '-';
            if (window.showNotification) {
                window.showNotification(error.message, 'error');
            }
        }
    }

    // Load API keys for filter
    async function loadApiKeysFilter() {
        try {
            const response = await fetch('?api=true&action=get_api_keys_list');
            const result = await response.json();
            
            if (result.success) {
                const select = document.getElementById('api-key-filter');
                result.data.forEach(key => {
                    const option = document.createElement('option');
                    option.value = key.api_key;
                    option.textContent = `${key.nama || 'Tanpa Nama'} (${key.api_key.substring(0, 8)}...)`;
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error loading API keys:', error);
        }
    }
    
    // Panggil fungsi untuk memuat data saat halaman pertama kali dibuka
    loadApiKeysFilter();
    loadHistoryStats();
    
    // Filter change handler
    document.getElementById('api-key-filter').addEventListener('change', function() {
        currentApiKeyFilter = this.value;
        // Muat ulang tabel riwayat dan statistik
        if (window.fetchHistory) {
            window.fetchHistory(1);
        }
        loadHistoryStats();
    });
    
    // Delete all history handler
    document.getElementById('delete-all-history-btn').addEventListener('click', function() {
        const modal = document.getElementById('delete-all-modal');
        const deleteCount = document.getElementById('delete-count');
        
        if (currentApiKeyFilter) {
            const selectedOption = document.querySelector(`#api-key-filter option[value="${currentApiKeyFilter}"]`);
            deleteCount.textContent = `semua riwayat untuk ${selectedOption.textContent}`;
        } else {
            deleteCount.textContent = 'semua riwayat pengecekan';
        }
        
        modal.classList.remove('hidden');
    });
    
    // Confirm delete all
    document.getElementById('confirm-delete-all').addEventListener('click', async function() {
        try {
            const response = await fetch('?api=true&action=delete_all_history', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    csrf_token: csrfToken,
                    api_key_filter: currentApiKeyFilter
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                document.getElementById('delete-all-modal').classList.add('hidden');
                if (window.fetchHistory) {
                    window.fetchHistory(1);
                }
                // Muat ulang statistik setelah penghapusan
                loadHistoryStats();
                if (window.showNotification) {
                    window.showNotification(result.message, 'success');
                }
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            if (window.showNotification) {
                window.showNotification(error.message, 'error');
            }
        }
    });
    
    // Cancel delete all
    document.getElementById('cancel-delete-all').addEventListener('click', function() {
        document.getElementById('delete-all-modal').classList.add('hidden');
    });
    
    // Expose filter untuk digunakan oleh skrip lain
    window.getCurrentApiKeyFilter = function() {
        return currentApiKeyFilter;
    };
});
</script>
