<?php
// admin_pages/security.php
if (!isset($_SESSION['is_admin'])) { die('Akses ditolak'); }
$csrf_token = generateCSRFToken();

// Mengambil pengaturan keamanan saat ini
$rate_limit_count = getSetting('rate_limit_count', 60);
$auto_block_duration = getSetting('auto_block_duration', 24);
$auto_block_enabled = getSetting('auto_block_enabled', 1);
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold text-slate-800">Security Monitor</h1>
    <div class="flex gap-3">
        <button id="refresh-logs-btn" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 font-semibold text-sm">
            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Refresh
        </button>
    </div>
</div>

<!-- Security Settings -->
<div class="bg-white p-6 rounded-xl shadow-lg mb-6">
    <h2 class="text-xl font-bold text-slate-800 mb-4">Pengaturan Keamanan Otomatis</h2>
    <form action="andrias.php" method="POST">
        <input type="hidden" name="action" value="update_security_settings">
        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-4 border border-slate-200 rounded-lg">
                <h3 class="font-semibold text-slate-700 mb-2">Rate Limiting</h3>
                <p class="text-sm text-slate-600 mb-3">Blokir jika ada lebih dari X request dalam 1 menit.</p>
                <div class="flex items-center gap-2">
                    <input type="number" id="rate-limit-count" name="rate_limit_count" value="<?= e($rate_limit_count) ?>" min="1" max="1000" 
                           class="w-20 px-2 py-1 border border-slate-300 rounded text-sm">
                    <span class="text-sm text-slate-600">requests/minute</span>
                </div>
            </div>
            
            <div class="p-4 border border-slate-200 rounded-lg">
                <h3 class="font-semibold text-slate-700 mb-2">Durasi Blokir Otomatis</h3>
                <p class="text-sm text-slate-600 mb-3">Durasi pemblokiran otomatis saat rate limit terlampaui.</p>
                <div class="flex items-center gap-2">
                    <input type="number" id="auto-block-duration" name="auto_block_duration" value="<?= e($auto_block_duration) ?>" min="1" max="720" 
                           class="w-20 px-2 py-1 border border-slate-300 rounded text-sm">
                    <span class="text-sm text-slate-600">jam</span>
                </div>
            </div>
            
            <div class="p-4 border border-slate-200 rounded-lg">
                <h3 class="font-semibold text-slate-700 mb-2">Monitoring & Blokir Otomatis</h3>
                <p class="text-sm text-slate-600 mb-3">Aktifkan atau nonaktifkan sistem blokir otomatis.</p>
                <div class="flex items-center gap-2">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" id="auto-block-enabled" name="auto_block_enabled" value="1"
                               class="h-4 w-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500"
                               <?= $auto_block_enabled == 1 ? 'checked' : '' ?>>
                        <span class="ml-2 text-sm text-slate-700">Aktif</span>
                    </label>
                </div>
            </div>
        </div>
        <div class="mt-4">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold text-sm">
                Simpan Pengaturan
            </button>
        </div>
    </form>
</div>


<!-- Security Logs Table -->
<div class="bg-white p-6 rounded-xl shadow-lg">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-slate-800">Security Logs</h2>
    </div>
    
    <div class="overflow-x-auto border border-slate-200 rounded-lg">
        <table class="w-full table-auto text-sm">
            <thead class="bg-slate-50 text-slate-600 text-left">
                <tr>
                    <th class="p-3 font-semibold">API Key</th>
                    <th class="p-3 font-semibold">IP Address</th>
                    <th class="p-3 font-semibold">Request Count (1m)</th>
                    <th class="p-3 font-semibold">Last Request</th>
                    <th class="p-3 font-semibold">Status</th>
                    <th class="p-3 font-semibold">Blocked Until</th>
                    <th class="p-3 font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody id="security-logs-tbody" class="divide-y divide-slate-200">
                <tr>
                    <td colspan="7" class="text-center p-6 text-slate-500">Memuat data...</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div id="pagination-controls" class="flex justify-between items-center mt-4"></div>
</div>

<!-- Block Modal -->
<div id="block-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white w-full max-w-md p-6 rounded-2xl shadow-xl">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-slate-800">Block API Key</h2>
            <button id="close-block-modal" class="text-slate-500 hover:text-slate-800 text-2xl">&times;</button>
        </div>
        <form id="block-form">
            <input type="hidden" id="block-api-key" name="api_key">
            <input type="hidden" id="block-ip-address" name="ip_address">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">API Key</label>
                    <p id="block-api-key-display" class="font-mono text-sm bg-slate-100 p-2 rounded"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">IP Address</label>
                    <p id="block-ip-display" class="font-mono text-sm bg-slate-100 p-2 rounded"></p>
                </div>
                <div>
                    <label for="block-hours" class="block text-sm font-medium text-slate-700 mb-1">Block Duration (Hours)</label>
                    <input type="number" id="block-hours" name="block_hours" value="24" min="1" max="168" required 
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold">
                    Block API Key
                </button>
                <button type="button" id="cancel-block" class="px-4 py-2 bg-slate-200 text-slate-800 rounded-lg hover:bg-slate-300">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '<?= $csrf_token ?>';
    let currentPage = 1;
    
    // Fetch security logs
    async function fetchSecurityLogs(page = 1) {
        try {
            const response = await fetch(`?api=true&action=get_security_logs&page=${page}`);
            const result = await response.json();
            
            if (result.success) {
                renderSecurityLogs(result.data.logs);
                renderPagination(result.data.pagination);
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            document.getElementById('security-logs-tbody').innerHTML = 
                '<tr><td colspan="7" class="text-center p-4 text-red-500">Gagal memuat data.</td></tr>';
        }
    }
    
    // Render security logs table
    function renderSecurityLogs(logs) {
        const tbody = document.getElementById('security-logs-tbody');
        
        if (!logs || logs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center p-6 text-slate-500">Tidak ada data security logs.</td></tr>';
            return;
        }
        
        tbody.innerHTML = logs.map(log => {
            const statusBadge = log.is_blocked == 1 
                ? '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Blocked</span>'
                : '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Normal</span>';
            
            const lastRequest = new Date(log.last_request).toLocaleString('id-ID');
            const blockedUntil = log.blocked_until ? new Date(log.blocked_until).toLocaleString('id-ID') : '-';
            const apiKeyDisplay = log.api_key.length > 12 ? log.api_key.substring(0, 12) + '...' : log.api_key;
            const userName = log.nama || 'Unknown';
            
            const actionButtons = log.is_blocked == 1 
                ? `<button class="unblock-btn text-green-600 hover:underline text-xs" data-api-key="${log.api_key}" data-ip="${log.ip_address}">Unblock</button>`
                : `<button class="block-btn text-red-600 hover:underline text-xs" data-api-key="${log.api_key}" data-ip="${log.ip_address}">Block</button>`;
            
            return `
                <tr class="hover:bg-slate-50/50">
                    <td class="p-3">
                        <div class="font-mono text-xs" title="${log.api_key}">${apiKeyDisplay}</div>
                        <div class="text-xs text-slate-500">${userName}</div>
                    </td>
                    <td class="p-3 font-mono text-xs">${log.ip_address}</td>
                    <td class="p-3 font-semibold ${log.request_count > 100 ? 'text-red-600' : 'text-slate-800'}">${log.request_count}</td>
                    <td class="p-3 text-slate-600">${lastRequest}</td>
                    <td class="p-3">${statusBadge}</td>
                    <td class="p-3 text-slate-600">${blockedUntil}</td>
                    <td class="p-3">${actionButtons}</td>
                </tr>
            `;
        }).join('');
    }
    
    // Render pagination
    function renderPagination({ currentPage, totalPages, totalRecords }) {
        const container = document.getElementById('pagination-controls');
        
        if (totalPages <= 1) {
            container.innerHTML = `<div class="text-sm text-slate-600">Total ${totalRecords} records</div>`;
            return;
        }
        
        let paginationHTML = `<div class="text-sm text-slate-600">Total ${totalRecords} records</div><div class="flex items-center gap-2">`;
        
        paginationHTML += `<button class="px-3 py-1 border border-slate-300 rounded-md bg-white hover:bg-slate-50 text-sm pagination-btn" data-page="${currentPage - 1}" ${currentPage === 1 ? 'disabled' : ''}>&laquo;</button>`;
        
        for (let i = 1; i <= totalPages; i++) {
            if (i === currentPage) {
                paginationHTML += `<button class="px-3 py-1 border border-indigo-500 rounded-md bg-indigo-500 text-white text-sm pagination-btn" data-page="${i}">${i}</button>`;
            } else if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                paginationHTML += `<button class="px-3 py-1 border border-slate-300 rounded-md bg-white hover:bg-slate-50 text-sm pagination-btn" data-page="${i}">${i}</button>`;
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                paginationHTML += `<span class="px-3 py-1 text-sm">...</span>`;
            }
        }
        
        paginationHTML += `<button class="px-3 py-1 border border-slate-300 rounded-md bg-white hover:bg-slate-50 text-sm pagination-btn" data-page="${currentPage + 1}" ${currentPage === totalPages ? 'disabled' : ''}>&raquo;</button></div>`;
        
        container.innerHTML = paginationHTML;
    }
    
    // Event handlers
    document.addEventListener('click', function(e) {
        if (e.target.matches('.pagination-btn:not(:disabled)')) {
            const page = parseInt(e.target.dataset.page);
            if (!isNaN(page)) {
                currentPage = page;
                fetchSecurityLogs(page);
            }
        }
        
        if (e.target.matches('.block-btn')) {
            const apiKey = e.target.dataset.apiKey;
            const ipAddress = e.target.dataset.ip;
            
            document.getElementById('block-api-key').value = apiKey;
            document.getElementById('block-ip-address').value = ipAddress;
            document.getElementById('block-api-key-display').textContent = apiKey;
            document.getElementById('block-ip-display').textContent = ipAddress;
            document.getElementById('block-modal').classList.remove('hidden');
        }
        
        if (e.target.matches('.unblock-btn')) {
            const apiKey = e.target.dataset.apiKey;
            const ipAddress = e.target.dataset.ip;
            
            window.showConfirmModal({
                title: 'Unblock API Key',
                message: `Unblock API key ${apiKey} dari IP ${ipAddress}?`,
                confirmText: 'Ya, Unblock',
                onConfirm: async () => {
                    try {
                        const response = await fetch('?api=true&action=unblock_api_key', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                csrf_token: csrfToken,
                                api_key: apiKey,
                                ip_address: ipAddress
                            })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            fetchSecurityLogs(currentPage);
                            window.showNotification(result.message, 'success');
                        } else {
                            throw new Error(result.message);
                        }
                    } catch (error) {
                        window.showNotification(`Error: ${error.message}`, 'error');
                    }
                }
            });
        }
    });
    
    document.getElementById('block-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        try {
            const response = await fetch('?api=true&action=block_api_key', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    csrf_token: csrfToken,
                    api_key: formData.get('api_key'),
                    ip_address: formData.get('ip_address'),
                    block_hours: formData.get('block_hours')
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                document.getElementById('block-modal').classList.add('hidden');
                fetchSecurityLogs(currentPage);
                window.showNotification(result.message, 'success');
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            window.showNotification(`Error: ${error.message}`, 'error');
        }
    });
    
    document.getElementById('close-block-modal').addEventListener('click', function() {
        document.getElementById('block-modal').classList.add('hidden');
    });
    
    document.getElementById('cancel-block').addEventListener('click', function() {
        document.getElementById('block-modal').classList.add('hidden');
    });
    
    document.getElementById('refresh-logs-btn').addEventListener('click', function() {
        fetchSecurityLogs(currentPage);
    });
    
    fetchSecurityLogs(1);
});
</script>
