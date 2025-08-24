<?php
// admin_pages/api_keys.php
if (!isset($_SESSION['is_admin'])) { die('Akses ditolak'); }
$db = getDbConnection();
$csrf_token = generateCSRFToken();

/**
 * Fungsi baru untuk menghitung statistik global dari semua API key.
 * Ditempatkan di sini untuk kemudahan, idealnya bisa diletakkan di admin_functions.php
 * @param PDO $db Objek koneksi database.
 * @return array Statistik global.
 */
function getGlobalApiStats(PDO $db): array
{
    // Menghitung total API Key
    $total_keys = (int) $db->query("SELECT COUNT(id) FROM api_keys")->fetchColumn();

    // Hanya hitung key yang aktif dan belum kedaluwarsa untuk statistik limit
    $stmt = $db->query("SELECT api_key, monthly_limit FROM api_keys WHERE is_active = 1 AND (expiry_date IS NULL OR expiry_date >= CURDATE())");
    $active_keys = $stmt->fetchAll();

    $total_limit = 0;
    $total_usage = 0;

    if (empty($active_keys)) {
         return [
            'total_keys' => $total_keys,
            'total_limit' => 0,
            'total_usage' => 0,
            'total_remaining' => 0,
            'percentage' => 0
        ];
    }

    foreach ($active_keys as $key) {
        $total_limit += (int)$key['monthly_limit'];
        // Fungsi getCurrentCycleUsage() sudah tersedia dari admin_functions.php
        $cycle_info = getCurrentCycleUsage($key['api_key']);
        $total_usage += $cycle_info['usage_count'];
    }

    $total_remaining = max(0, $total_limit - $total_usage);
    $percentage = ($total_limit > 0) ? ($total_usage / $total_limit) * 100 : 0;

    return [
        'total_keys' => $total_keys,
        'total_limit' => $total_limit,
        'total_usage' => $total_usage,
        'total_remaining' => $total_remaining,
        'percentage' => round($percentage, 2)
    ];
}

// Panggil fungsi baru untuk mendapatkan statistik global
$global_stats = getGlobalApiStats($db);
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold text-slate-800">Manajemen API Key</h1>
    <button id="add-key-btn" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
        <span>Tambah Key</span>
    </button>
</div>

<!-- [BARU] Bagian Rekapan Global -->
<div class="mb-8">
    <h2 class="text-xl font-bold text-slate-700 mb-4">Rekapan Penggunaan Global</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card Total API Key -->
        <div class="bg-white p-4 rounded-xl shadow-lg flex items-center gap-4 card-hover">
            <div class="bg-purple-100 p-3 rounded-full">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H5v-2H3v-2H1v-4a6 6 0 016-6h4a6 6 0 016 6z"></path></svg>
            </div>
            <div>
                <p class="text-sm text-slate-500">Total API Key</p>
                <p class="text-2xl font-bold text-slate-800"><?= number_format($global_stats['total_keys']) ?></p>
            </div>
        </div>
        <!-- Card Total Limit -->
        <div class="bg-white p-4 rounded-xl shadow-lg flex items-center gap-4 card-hover">
            <div class="bg-blue-100 p-3 rounded-full">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
            </div>
            <div>
                <p class="text-sm text-slate-500">Total Limit</p>
                <p class="text-2xl font-bold text-slate-800"><?= number_format($global_stats['total_limit']) ?></p>
            </div>
        </div>
        <!-- Card Sisa Limit -->
        <div class="bg-white p-4 rounded-xl shadow-lg flex items-center gap-4 card-hover">
            <div class="bg-green-100 p-3 rounded-full">
                 <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <p class="text-sm text-slate-500">Sisa Limit</p>
                <p class="text-2xl font-bold text-slate-800"><?= number_format($global_stats['total_remaining']) ?></p>
            </div>
        </div>
        <!-- Card Penggunaan -->
        <div class="bg-white p-4 rounded-xl shadow-lg flex items-center gap-4 card-hover">
            <div class="bg-orange-100 p-3 rounded-full">
                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </div>
            <div>
                <p class="text-sm text-slate-500">Total Penggunaan</p>
                <p class="text-2xl font-bold text-slate-800"><?= number_format($global_stats['total_usage']) ?></p>
            </div>
        </div>
    </div>
    <!-- Progress Bar -->
    <div class="bg-white p-4 rounded-xl shadow-lg mt-4 card-hover">
        <div class="flex justify-between items-center mb-2">
            <p class="text-sm font-medium text-slate-600">Persentase Penggunaan</p>
            <p class="text-sm font-bold text-indigo-600"><?= $global_stats['percentage'] ?>%</p>
        </div>
        <div class="w-full bg-slate-200 rounded-full h-2.5">
            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-2.5 rounded-full transition-all duration-500" style="width: <?= $global_stats['percentage'] ?>%"></div>
        </div>
    </div>
</div>
<!-- [AKHIR BARU] -->


<!-- Modal Detail Key Baru -->
<div id="new-key-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white w-full max-w-lg p-6 rounded-2xl shadow-xl">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-slate-800">API Key Berhasil Dibuat!</h2>
            <button id="close-new-key-modal" class="text-slate-500 hover:text-slate-800 text-2xl">&times;</button>
        </div>
        <div id="new-key-details" class="space-y-4">
            <!-- Detail akan diisi oleh JavaScript -->
        </div>
        <div class="mt-6 flex gap-3">
            <button id="copy-key-details" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold">
                Salin Detail Lengkap
            </button>
            <button id="close-new-key-modal-2" class="px-4 py-2 bg-slate-200 text-slate-800 rounded-lg hover:bg-slate-300">
                Tutup
            </button>
        </div>
    </div>
</div>

<div class="bg-white p-6 rounded-xl shadow-lg">
    <div class="overflow-x-auto">
        <table class="w-full table-auto text-sm">
            <thead class="bg-slate-50 text-slate-600 text-left"><tr><th class="p-3 font-semibold">Nama</th><th class="p-3 font-semibold">API Key</th><th class="p-3 font-semibold">Sisa Limit</th><th class="p-3 font-semibold">Reset</th><th class="p-3 font-semibold">Kedaluwarsa</th><th class="p-3 font-semibold">Status</th><th class="p-3 font-semibold">Aksi</th></tr></thead>
            <tbody class="divide-y divide-slate-200">
                <?php
                try {
                    $stmt = $db->query("SELECT * FROM api_keys ORDER BY created_at DESC");
                    while ($row = $stmt->fetch()):
                        $is_expired = $row['expiry_date'] && $row['expiry_date'] < date('Y-m-d');
                        $cycle_info = getCurrentCycleUsage($row['api_key']);
                        $remaining_limit = max(0, (int)$row['monthly_limit'] - $cycle_info['usage_count']);
                ?>
                <tr class="hover:bg-slate-50/50 <?= !$row['is_active'] || $is_expired ? 'opacity-50' : '' ?>">
                    <td class="p-3 font-semibold text-slate-700"><?= e($row['nama'] ?: '-') ?><br><small class="font-normal text-slate-500"><?= e($row['nomor_wa']) ?></small></td>
                    <td class="p-3 font-mono text-xs"><?= e($row['api_key']) ?></td>
                    <td class="p-3 font-semibold text-slate-800"><?= e($remaining_limit) ?> / <?= e($row['monthly_limit']) ?></td>
                    <td class="p-3 text-slate-600"><?= e(date('d M Y', strtotime($cycle_info['next_reset']))) ?></td>
                    <td class="p-3 text-slate-600"><?= e($row['expiry_date'] ? date('d M Y', strtotime($row['expiry_date'])) : 'Tidak ada') ?></td>
                    <td class="p-3">
                        <?php if ($is_expired): ?><span class="px-2 py-1 text-xs font-semibold rounded-full bg-slate-200 text-slate-600">Expired</span>
                        <?php elseif ($row['is_active']): ?><span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Aktif</span>
                        <?php else: ?><span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Non-Aktif</span><?php endif; ?>
                    </td>
                    <td class="p-3">
                        <div class="flex items-center space-x-3">
                            <a href="?page=edit_key&id=<?= $row['id'] ?>" class="text-indigo-600 hover:underline">Edit</a>
                            <form method="POST" onsubmit="return confirm('Yakin ingin menghapus API Key ini?');" class="inline-block">
                                <input type="hidden" name="action" value="delete_key">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <button type="submit" class="text-red-600 hover:underline">Hapus</button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Yakin ingin mereset penggunaan key ini?');" class="inline-block">
                                <input type="hidden" name="action" value="reset_usage">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <input type="hidden" name="api_key" value="<?= e($row['api_key']) ?>">
                                <button type="submit" class="text-blue-600 hover:underline">Reset</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; } catch (PDOException $e) { echo '<tr><td colspan="7" class="p-4 text-center text-red-500">Error: ' . e($e->getMessage()) . '</td></tr>'; } ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Key -->
<div id="add-key-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white w-full max-w-md p-6 rounded-2xl shadow-xl">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-slate-800">Tambah API Key Baru</h2>
            <button id="close-modal-btn" class="text-slate-500 hover:text-slate-800">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_key">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <div class="space-y-4">
                <div>
                    <label for="nama" class="block text-sm font-medium text-slate-700">Nama Pengguna</label>
                    <input type="text" name="nama" id="nama" class="mt-1 block w-full px-3 py-2 bg-white border border-slate-300 rounded-md shadow-sm" required>
                </div>
                <div>
                    <label for="nomor_wa" class="block text-sm font-medium text-slate-700">Nomor WA</label>
                    <input type="text" name="nomor_wa" id="nomor_wa" class="mt-1 block w-full px-3 py-2 bg-white border border-slate-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label for="monthly_limit" class="block text-sm font-medium text-slate-700">Limit per Siklus (28 hari)</label>
                    <input type="number" name="monthly_limit" id="monthly_limit" value="100" class="mt-1 block w-full px-3 py-2 bg-white border border-slate-300 rounded-md shadow-sm" required>
                </div>
                <div>
                    <label for="expiry_date" class="block text-sm font-medium text-slate-700">Tanggal Kedaluwarsa (Opsional)</label>
                    <input type="date" name="expiry_date" id="expiry_date" class="mt-1 block w-full px-3 py-2 bg-white border border-slate-300 rounded-md shadow-sm">
                </div>
                <div class="flex justify-end gap-4 pt-2">
                    <button type="button" id="close-modal-btn-2" class="px-4 py-2 bg-slate-200 text-slate-800 rounded-lg hover:bg-slate-300">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if there's a new key ID in URL
    const urlParams = new URLSearchParams(window.location.search);
    const newKeyId = urlParams.get('new_key_id');
    
    if (newKeyId) {
        // Fetch key details and show modal
        fetchNewKeyDetails(newKeyId);
        // Clean URL
        window.history.replaceState({}, document.title, window.location.pathname + '?page=api_keys');
    }
    
    async function fetchNewKeyDetails(keyId) {
        try {
            const response = await fetch(`?api=true&action=get_key_details&id=${keyId}`);
            const result = await response.json();
            
            if (result.success) {
                showNewKeyModal(result.data);
            }
        } catch (error) {
            console.error('Error fetching key details:', error);
        }
    }
    
    function showNewKeyModal(keyData) {
        const modal = document.getElementById('new-key-modal');
        const detailsContainer = document.getElementById('new-key-details');
        
        const createdDate = new Date(keyData.created_at).toLocaleDateString('id-ID');
        const expiryDate = keyData.expiry_date ? new Date(keyData.expiry_date).toLocaleDateString('id-ID') : 'Tidak ada';
        
        detailsContainer.innerHTML = `
            <div class="bg-slate-50 p-4 rounded-lg space-y-3">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-slate-600">Nama Pengguna:</label>
                        <p class="font-semibold text-slate-800">${keyData.nama || '-'}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Nomor WhatsApp:</label>
                        <p class="font-semibold text-slate-800">${keyData.nomor_wa || '-'}</p>
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-600">API Key:</label>
                    <p class="font-mono text-sm bg-white p-2 rounded border break-all">${keyData.api_key}</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-slate-600">Limit Penggunaan:</label>
                        <p class="font-semibold text-slate-800">${keyData.monthly_limit} per siklus</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Tanggal Dibuat:</label>
                        <p class="font-semibold text-slate-800">${createdDate}</p>
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-600">Masa Aktif:</label>
                    <p class="font-semibold text-slate-800">${expiryDate}</p>
                </div>
            </div>
        `;
        
        modal.classList.remove('hidden');
        
        // Store key data for copying
        window.currentKeyData = keyData;
    }
    
    // Copy key details function
    document.getElementById('copy-key-details').addEventListener('click', function() {
        if (!window.currentKeyData) return;
        
        const keyData = window.currentKeyData;
        const createdDate = new Date(keyData.created_at).toLocaleDateString('id-ID');
        const expiryDate = keyData.expiry_date ? new Date(keyData.expiry_date).toLocaleDateString('id-ID') : 'Tidak ada';
        
        const detailText = `
═══════════════════════════════════
        DETAIL API KEY BARU
═══════════════════════════════════

👤 Nama Pengguna    : ${keyData.nama || '-'}
📱 Nomor WhatsApp   : ${keyData.nomor_wa || '-'}
🔑 API Key          : ${keyData.api_key}
📊 Limit Penggunaan : ${keyData.monthly_limit} per siklus (28 hari)
📅 Tanggal Dibuat   : ${createdDate}
⏰ Masa Aktif       : ${expiryDate}

═══════════════════════════════════
Simpan informasi ini dengan aman!
═══════════════════════════════════
        `.trim();
        
        navigator.clipboard.writeText(detailText).then(() => {
            const button = this;
            const originalText = button.textContent;
            button.textContent = 'Tersalin!';
            button.classList.add('bg-green-600', 'hover:bg-green-700');
            button.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
            
            setTimeout(() => {
                button.textContent = originalText;
                button.classList.remove('bg-green-600', 'hover:bg-green-700');
                button.classList.add('bg-indigo-600', 'hover:bg-indigo-700');
            }, 2000);
        });
    });
    
    // Close modal handlers
    document.getElementById('close-new-key-modal').addEventListener('click', function() {
        document.getElementById('new-key-modal').classList.add('hidden');
    });
    
    document.getElementById('close-new-key-modal-2').addEventListener('click', function() {
        document.getElementById('new-key-modal').classList.add('hidden');
    });
});
</script>
