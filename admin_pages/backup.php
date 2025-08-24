<?php
// admin_pages/backup.php
if (!isset($_SESSION['is_admin'])) { die('Akses ditolak'); }
$csrf_token = generateCSRFToken();
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold text-slate-800">Backup System</h1>
    <div class="flex gap-3">
        <button id="refresh-logs-btn" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 font-semibold text-sm">
            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Refresh
        </button>
    </div>
</div>

<!-- Backup Actions -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-2xl shadow-lg border border-slate-100 card-hover">
        <div class="flex items-center gap-4 mb-4">
            <div class="bg-blue-100 p-3 rounded-xl">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-800">Database Only</h3>
                <p class="text-sm text-slate-600">Backup database saja</p>
            </div>
        </div>
        <button class="backup-btn w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold" data-type="database">
            <span class="btn-text">Backup Database</span>
            <span class="btn-loading hidden">
                <svg class="animate-spin h-4 w-4 inline mr-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing...
            </span>
        </button>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-lg border border-slate-100 card-hover">
        <div class="flex items-center gap-4 mb-4">
            <div class="bg-green-100 p-3 rounded-xl">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2v0"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h2a2 2 0 012 2v0H8v0z"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-800">Files Only</h3>
                <p class="text-sm text-slate-600">Backup semua file proyek</p>
            </div>
        </div>
        <button class="backup-btn w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold" data-type="files">
            <span class="btn-text">Backup Files</span>
            <span class="btn-loading hidden">
                <svg class="animate-spin h-4 w-4 inline mr-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing...
            </span>
        </button>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-lg border border-slate-100 card-hover">
        <div class="flex items-center gap-4 mb-4">
            <div class="bg-purple-100 p-3 rounded-xl">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-800">Full Backup</h3>
                <p class="text-sm text-slate-600">Database + Files</p>
            </div>
        </div>
        <button class="backup-btn w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-semibold" data-type="full">
            <span class="btn-text">Full Backup</span>
            <span class="btn-loading hidden">
                <svg class="animate-spin h-4 w-4 inline mr-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing...
            </span>
        </button>
    </div>
</div>

<!-- Backup Logs -->
<div class="bg-white p-6 rounded-xl shadow-lg">
    <h2 class="text-xl font-bold text-slate-800 mb-4">Riwayat Backup</h2>
    <div class="overflow-x-auto border border-slate-200 rounded-lg">
        <table class="w-full table-auto text-sm">
            <thead class="bg-slate-50 text-slate-600 text-left">
                <tr>
                    <th class="p-3 font-semibold">Tipe</th>
                    <th class="p-3 font-semibold">Filename</th>
                    <th class="p-3 font-semibold">Size</th>
                    <th class="p-3 font-semibold">Status</th>
                    <th class="p-3 font-semibold">Waktu</th>
                    <th class="p-3 font-semibold">Error</th>
                </tr>
            </thead>
            <tbody id="backup-logs-tbody" class="divide-y divide-slate-200">
                <tr>
                    <td colspan="6" class="text-center p-6 text-slate-500">Memuat data...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '<?= $csrf_token ?>';
    
    async function fetchBackupLogs() {
        try {
            const response = await fetch('?api=true&action=get_backup_logs');
            const result = await response.json();
            
            if (result.success) {
                renderBackupLogs(result.data.logs);
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            document.getElementById('backup-logs-tbody').innerHTML = 
                '<tr><td colspan="6" class="text-center p-4 text-red-500">Gagal memuat data backup.</td></tr>';
        }
    }
    
    function renderBackupLogs(logs) {
        const tbody = document.getElementById('backup-logs-tbody');
        
        if (!logs || logs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center p-6 text-slate-500">Belum ada backup.</td></tr>';
            return;
        }
        
        tbody.innerHTML = logs.map(log => {
            const statusBadge = getStatusBadge(log.status);
            const fileSize = log.file_size ? formatFileSize(log.file_size) : '-';
            const createdAt = new Date(log.created_at).toLocaleString('id-ID');
            const typeBadge = getTypeBadge(log.backup_type);
            
            return `
                <tr class="hover:bg-slate-50/50">
                    <td class="p-3">${typeBadge}</td>
                    <td class="p-3 font-mono text-xs">${log.filename}</td>
                    <td class="p-3">${fileSize}</td>
                    <td class="p-3">${statusBadge}</td>
                    <td class="p-3">${createdAt}</td>
                    <td class="p-3 max-w-xs truncate text-red-600 text-xs" title="${log.error_message || ''}">${log.error_message || '-'}</td>
                </tr>
            `;
        }).join('');
    }
    
    function getStatusBadge(status) {
        const badges = {
            'success': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Berhasil</span>',
            'failed': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Gagal</span>',
            'in_progress': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Proses</span>'
        };
        return badges[status] || status;
    }
    
    function getTypeBadge(type) {
        const badges = {
            'database': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Database</span>',
            'files': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Files</span>',
            'full': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">Full</span>'
        };
        return badges[type] || type;
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Backup button handlers
    document.querySelectorAll('.backup-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const type = this.dataset.type;
            const btnText = this.querySelector('.btn-text');
            const btnLoading = this.querySelector('.btn-loading');
            
            // Show loading state
            btnText.classList.add('hidden');
            btnLoading.classList.remove('hidden');
            this.disabled = true;
            
            try {
                const response = await fetch('?api=true&action=create_backup', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        csrf_token: csrfToken,
                        backup_type: type
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    window.showNotification(`Backup ${type} berhasil dibuat!`, 'success');
                    fetchBackupLogs(); // Refresh logs
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                window.showNotification(`Backup gagal: ${error.message}`, 'error');
            } finally {
                // Hide loading state
                btnText.classList.remove('hidden');
                btnLoading.classList.add('hidden');
                this.disabled = false;
            }
        });
    });
    
    // Refresh logs button
    document.getElementById('refresh-logs-btn').addEventListener('click', fetchBackupLogs);
    
    // Initial load
    fetchBackupLogs();
});
</script>