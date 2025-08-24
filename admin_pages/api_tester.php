<?php
// admin_pages/api_tester.php
if (!isset($_SESSION['is_admin'])) { die('Akses ditolak'); }
$csrf_token = generateCSRFToken();
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold text-slate-800">API Tester</h1>
    <div class="flex gap-3">
        <button id="clear-logs-btn" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold text-sm">
            Clear Logs
        </button>
        <button id="refresh-logs-btn" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 font-semibold text-sm">
            Refresh
        </button>
    </div>
</div>

<!-- API Tester Form -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white p-6 rounded-2xl shadow-lg border border-slate-100">
        <h2 class="text-xl font-bold text-slate-800 mb-4">Test API Endpoint</h2>
        <form id="api-test-form" class="space-y-4">
            <div>
                <label for="endpoint" class="block text-sm font-medium text-slate-700 mb-1">Endpoint URL</label>
                <input type="url" id="endpoint" name="endpoint" placeholder="https://example.com/api/endpoint" 
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
            </div>
            
            <div>
                <label for="method" class="block text-sm font-medium text-slate-700 mb-1">HTTP Method</label>
                <select id="method" name="method" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="GET">GET</option>
                    <option value="POST">POST</option>
                    <option value="PUT">PUT</option>
                    <option value="DELETE">DELETE</option>
                </select>
            </div>
            
            <div id="request-data-wrapper" class="hidden">
                <label for="request_data" class="block text-sm font-medium text-slate-700 mb-1">Request Data (JSON)</label>
                <textarea id="request_data" name="request_data" rows="4" placeholder='{"key": "value"}' 
                          class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm"></textarea>
            </div>
            
            <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold">
                <span class="btn-text">Test API</span>
                <span class="btn-loading hidden">
                    <svg class="animate-spin h-4 w-4 inline mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Testing...
                </span>
            </button>
        </form>
    </div>
    
    <!-- Response Display -->
    <div class="bg-white p-6 rounded-2xl shadow-lg border border-slate-100">
        <h2 class="text-xl font-bold text-slate-800 mb-4">Response</h2>
        <div id="response-container" class="space-y-4">
            <div class="text-center p-8 text-slate-500">
                <svg class="w-12 h-12 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364-.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
                <p>Response akan ditampilkan di sini</p>
            </div>
        </div>
    </div>
</div>

<!-- API Status Check -->
<div class="bg-white p-6 rounded-xl shadow-lg mb-8">
    <h2 class="text-xl font-bold text-slate-800 mb-4">API Status Check</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="p-4 border border-slate-200 rounded-lg">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-slate-700">Main API</span>
                <span id="main-api-status" class="px-2 py-1 text-xs font-semibold rounded-full bg-slate-100 text-slate-600">Checking...</span>
            </div>
            <p class="text-xs text-slate-500 mt-1">valid-api.php</p>
        </div>
        
        <div class="p-4 border border-slate-200 rounded-lg">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-slate-700">Admin Panel</span>
                <span id="admin-api-status" class="px-2 py-1 text-xs font-semibold rounded-full bg-slate-100 text-slate-600">Checking...</span>
            </div>
            <p class="text-xs text-slate-500 mt-1">andrias.php</p>
        </div>
        
        <div class="p-4 border border-slate-200 rounded-lg">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-slate-700">Database</span>
                <span id="db-status" class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
            </div>
            <p class="text-xs text-slate-500 mt-1">MySQL Connection</p>
        </div>
    </div>
</div>

<!-- Test Logs -->
<div class="bg-white p-6 rounded-xl shadow-lg">
    <h2 class="text-xl font-bold text-slate-800 mb-4">Test Logs</h2>
    <div class="overflow-x-auto border border-slate-200 rounded-lg">
        <table class="w-full table-auto text-sm">
            <thead class="bg-slate-50 text-slate-600 text-left">
                <tr>
                    <th class="p-3 font-semibold">Endpoint</th>
                    <th class="p-3 font-semibold">Method</th>
                    <th class="p-3 font-semibold">Status</th>
                    <th class="p-3 font-semibold">Response Code</th>
                    <th class="p-3 font-semibold">Response Time</th>
                    <th class="p-3 font-semibold">Waktu</th>
                    <th class="p-3 font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody id="test-logs-tbody" class="divide-y divide-slate-200">
                <tr>
                    <td colspan="7" class="text-center p-6 text-slate-500">Memuat data...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Detail Response -->
<div id="response-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white w-full max-w-4xl max-h-[90vh] p-6 rounded-2xl shadow-xl overflow-hidden flex flex-col">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-slate-800">Response Detail</h3>
            <button id="close-response-modal" class="text-slate-500 hover:text-slate-800 text-2xl">&times;</button>
        </div>
        <div class="flex-1 overflow-auto">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 h-full">
                <div>
                    <h4 class="font-semibold text-slate-700 mb-2">Request Data</h4>
                    <pre id="modal-request-data" class="bg-slate-100 p-3 rounded text-xs overflow-auto h-64 font-mono"></pre>
                </div>
                <div>
                    <h4 class="font-semibold text-slate-700 mb-2">Response Data</h4>
                    <pre id="modal-response-data" class="bg-slate-100 p-3 rounded text-xs overflow-auto h-64 font-mono"></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '<?= $csrf_token ?>';
    const form = document.getElementById('api-test-form');
    const methodSelect = document.getElementById('method');
    const requestDataWrapper = document.getElementById('request-data-wrapper');
    
    // Show/hide request data based on method
    methodSelect.addEventListener('change', function() {
        if (this.value === 'POST' || this.value === 'PUT') {
            requestDataWrapper.classList.remove('hidden');
        } else {
            requestDataWrapper.classList.add('hidden');
        }
    });
    
    // Form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const btnText = form.querySelector('.btn-text');
        const btnLoading = form.querySelector('.btn-loading');
        const submitBtn = form.querySelector('button[type="submit"]');
        
        // Show loading state
        btnText.classList.add('hidden');
        btnLoading.classList.remove('hidden');
        submitBtn.disabled = true;
        
        try {
            const response = await fetch('?api=true&action=test_api_endpoint', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    csrf_token: csrfToken,
                    endpoint: formData.get('endpoint'),
                    method: formData.get('method'),
                    request_data: formData.get('request_data')
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                displayResponse(result.data);
                fetchTestLogs(); // Refresh logs
                window.showNotification('API test berhasil!', 'success');
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            displayResponse({
                status: 'error',
                response: error.message,
                response_code: 0,
                response_time: 0
            });
            window.showNotification(`Test gagal: ${error.message}`, 'error');
        } finally {
            // Hide loading state
            btnText.classList.remove('hidden');
            btnLoading.classList.add('hidden');
            submitBtn.disabled = false;
        }
    });
    
    function displayResponse(data) {
        const container = document.getElementById('response-container');
        const statusClass = data.status === 'success' ? 'text-green-600' : 'text-red-600';
        const statusBg = data.status === 'success' ? 'bg-green-100' : 'bg-red-100';
        
        container.innerHTML = `
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-slate-700">Status:</span>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full ${statusBg} ${statusClass}">${data.status}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-slate-700">Response Code:</span>
                    <span class="font-mono text-sm">${data.response_code}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-slate-700">Response Time:</span>
                    <span class="font-mono text-sm">${data.response_time}ms</span>
                </div>
                <div>
                    <span class="text-sm font-medium text-slate-700 block mb-2">Response Data:</span>
                    <pre class="bg-slate-100 p-3 rounded text-xs overflow-auto max-h-64 font-mono">${JSON.stringify(JSON.parse(data.response || '{}'), null, 2)}</pre>
                </div>
            </div>
        `;
    }
    
    async function fetchTestLogs() {
        try {
            const response = await fetch('?api=true&action=get_api_test_logs');
            const result = await response.json();
            
            if (result.success) {
                renderTestLogs(result.data.logs);
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            document.getElementById('test-logs-tbody').innerHTML = 
                '<tr><td colspan="7" class="text-center p-4 text-red-500">Gagal memuat data logs.</td></tr>';
        }
    }
    
    function renderTestLogs(logs) {
        const tbody = document.getElementById('test-logs-tbody');
        
        if (!logs || logs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center p-6 text-slate-500">Belum ada test logs.</td></tr>';
            return;
        }
        
        tbody.innerHTML = logs.map(log => {
            const statusBadge = log.status === 'success' 
                ? '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Success</span>'
                : '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Error</span>';
            
            const createdAt = new Date(log.created_at).toLocaleString('id-ID');
            const endpoint = log.endpoint.length > 30 ? log.endpoint.substring(0, 30) + '...' : log.endpoint;
            
            return `
                <tr class="hover:bg-slate-50/50">
                    <td class="p-3 font-mono text-xs" title="${log.endpoint}">${endpoint}</td>
                    <td class="p-3">
                        <span class="px-2 py-1 text-xs font-semibold rounded bg-slate-100 text-slate-700">${log.method}</span>
                    </td>
                    <td class="p-3">${statusBadge}</td>
                    <td class="p-3 font-mono">${log.response_code || '-'}</td>
                    <td class="p-3 font-mono">${log.response_time || '-'}ms</td>
                    <td class="p-3">${createdAt}</td>
                    <td class="p-3">
                        <button class="text-indigo-600 hover:underline text-xs view-response-btn" 
                                data-request="${escapeHtml(log.request_data || '')}" 
                                data-response="${escapeHtml(log.response_data || '')}">
                            View
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // View response modal
    document.addEventListener('click', function(e) {
        if (e.target.matches('.view-response-btn')) {
            const requestData = e.target.dataset.request;
            const responseData = e.target.dataset.response;
            
            document.getElementById('modal-request-data').textContent = requestData || 'No request data';
            document.getElementById('modal-response-data').textContent = responseData || 'No response data';
            document.getElementById('response-modal').classList.remove('hidden');
        }
    });
    
    document.getElementById('close-response-modal').addEventListener('click', function() {
        document.getElementById('response-modal').classList.add('hidden');
    });
    
    // Check API status
    async function checkApiStatus() {
        // Check main API
        try {
            const response = await fetch('valid-api.php?action=get_options');
            const statusEl = document.getElementById('main-api-status');
            if (response.ok) {
                statusEl.className = 'px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800';
                statusEl.textContent = 'Active';
            } else {
                throw new Error('Not responding');
            }
        } catch (error) {
            const statusEl = document.getElementById('main-api-status');
            statusEl.className = 'px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800';
            statusEl.textContent = 'Error';
        }
        
        // Check admin API
        try {
            const response = await fetch('?api=true&action=get_notifications');
            const statusEl = document.getElementById('admin-api-status');
            if (response.ok) {
                statusEl.className = 'px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800';
                statusEl.textContent = 'Active';
            } else {
                throw new Error('Not responding');
            }
        } catch (error) {
            const statusEl = document.getElementById('admin-api-status');
            statusEl.className = 'px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800';
            statusEl.textContent = 'Error';
        }
    }
    
    // Refresh logs button
    document.getElementById('refresh-logs-btn').addEventListener('click', fetchTestLogs);
    
    // Clear logs button
    document.getElementById('clear-logs-btn').addEventListener('click', function() {
        window.showConfirmModal({
            title: 'Clear Test Logs',
            message: 'Yakin ingin menghapus semua test logs?',
            type: 'danger',
            confirmText: 'Ya, Hapus',
            onConfirm: async () => {
                // Implementation for clearing logs would go here
                window.showNotification('Feature coming soon!', 'info');
            }
        });
    });
    
    // Initial load
    fetchTestLogs();
    checkApiStatus();
});
</script>