<?php
// admin_pages/bank_codes.php
if (!isset($_SESSION['is_admin'])) { die('Akses ditolak'); }
$csrf_token = generateCSRFToken();
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold text-slate-800">Bank Codes Management</h1>
    <div class="flex gap-3">
        <button id="import-json-btn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold text-sm">
            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
            </svg>
            Import JSON
        </button>
        <button id="add-bank-code-btn" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold text-sm">
            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Tambah Bank Code
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-4 rounded-xl shadow-lg flex items-center gap-4 card-hover">
        <div class="bg-blue-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
        </div>
        <div>
            <p class="text-sm text-slate-500">Total Banks</p>
            <p id="stats-banks" class="text-2xl font-bold text-slate-800">...</p>
        </div>
    </div>
    
    <div class="bg-white p-4 rounded-xl shadow-lg flex items-center gap-4 card-hover">
        <div class="bg-green-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg>
        </div>
        <div>
            <p class="text-sm text-slate-500">E-Wallets</p>
            <p id="stats-ewallets" class="text-2xl font-bold text-slate-800">...</p>
        </div>
    </div>
    
    <div class="bg-white p-4 rounded-xl shadow-lg flex items-center gap-4 card-hover">
        <div class="bg-yellow-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <div>
            <p class="text-sm text-slate-500">Active</p>
            <p id="stats-active" class="text-2xl font-bold text-slate-800">...</p>
        </div>
    </div>
    
    <div class="bg-white p-4 rounded-xl shadow-lg flex items-center gap-4 card-hover">
        <div class="bg-red-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <div>
            <p class="text-sm text-slate-500">Inactive</p>
            <p id="stats-inactive" class="text-2xl font-bold text-slate-800">...</p>
        </div>
    </div>
</div>

<!-- Filter and Search -->
<div class="bg-white p-4 rounded-xl shadow-lg mb-6">
    <div class="flex flex-wrap gap-4 items-center">
        <div class="flex-1 min-w-64">
            <input type="text" id="search-input" placeholder="Cari bank atau kode..." 
                   class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <div>
            <select id="type-filter" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">Semua Tipe</option>
                <option value="bank">Bank</option>
                <option value="ewallet">E-Wallet</option>
            </select>
        </div>
        <div>
            <select id="status-filter" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">Semua Status</option>
                <option value="1">Aktif</option>
                <option value="0">Non-Aktif</option>
            </select>
        </div>
        <button id="reset-filter-btn" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 font-semibold text-sm">
            Reset
        </button>
    </div>
</div>

<!-- Bank Codes Table -->
<div class="bg-white p-6 rounded-xl shadow-lg">
    <div class="overflow-x-auto border border-slate-200 rounded-lg">
        <table class="w-full table-auto text-sm">
            <thead class="bg-slate-50 text-slate-600 text-left">
                <tr>
                    <th class="p-3 font-semibold">Code</th>
                    <th class="p-3 font-semibold">Name</th>
                    <th class="p-3 font-semibold">Type</th>
                    <th class="p-3 font-semibold">Status</th>
                    <th class="p-3 font-semibold">Created</th>
                    <th class="p-3 font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody id="bank-codes-tbody" class="divide-y divide-slate-200">
                <tr>
                    <td colspan="6" class="text-center p-6 text-slate-500">Memuat data...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="bank-code-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white w-full max-w-md p-6 rounded-2xl shadow-xl">
        <div class="flex justify-between items-center mb-4">
            <h2 id="modal-title" class="text-xl font-bold text-slate-800">Tambah Bank Code</h2>
            <button id="close-modal-btn" class="text-slate-500 hover:text-slate-800 text-2xl">&times;</button>
        </div>
        <form id="bank-code-form">
            <input type="hidden" id="edit-id" name="id">
            <div class="space-y-4">
                <div>
                    <label for="code" class="block text-sm font-medium text-slate-700 mb-1">Code</label>
                    <input type="text" id="code" name="code" required 
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                    <input type="text" id="name" name="name" required 
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="type" class="block text-sm font-medium text-slate-700 mb-1">Type</label>
                    <select id="type" name="type" required 
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="bank">Bank</option>
                        <option value="ewallet">E-Wallet</option>
                    </select>
                </div>
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" id="is_active" name="is_active" value="1" checked 
                               class="h-4 w-4 text-indigo-600 border-slate-300 rounded">
                        <span class="ml-2 text-sm text-slate-900">Aktif</span>
                    </label>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold">
                    <span class="btn-text">Simpan</span>
                    <span class="btn-loading hidden">
                        <svg class="animate-spin h-4 w-4 inline mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Menyimpan...
                    </span>
                </button>
                <button type="button" id="cancel-btn" class="px-4 py-2 bg-slate-200 text-slate-800 rounded-lg hover:bg-slate-300">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '<?= $csrf_token ?>';
    let allBankCodes = [];
    let filteredBankCodes = [];
    
    // Elements
    const modal = document.getElementById('bank-code-modal');
    const form = document.getElementById('bank-code-form');
    const modalTitle = document.getElementById('modal-title');
    const searchInput = document.getElementById('search-input');
    const typeFilter = document.getElementById('type-filter');
    const statusFilter = document.getElementById('status-filter');
    
    // Fetch bank codes
    async function fetchBankCodes() {
        try {
            const response = await fetch('?api=true&action=get_bank_codes');
            const result = await response.json();
            
            if (result.success) {
                allBankCodes = result.data.bank_codes;
                filteredBankCodes = [...allBankCodes];
                renderBankCodes();
                updateStats();
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            document.getElementById('bank-codes-tbody').innerHTML = 
                '<tr><td colspan="6" class="text-center p-4 text-red-500">Gagal memuat data.</td></tr>';
        }
    }
    
    // Render bank codes table
    function renderBankCodes() {
        const tbody = document.getElementById('bank-codes-tbody');
        
        if (filteredBankCodes.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center p-6 text-slate-500">Tidak ada data.</td></tr>';
            return;
        }
        
        tbody.innerHTML = filteredBankCodes.map(code => {
            const statusBadge = code.is_active == 1 
                ? '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Aktif</span>'
                : '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Non-Aktif</span>';
            
            const typeBadge = code.type === 'ewallet'
                ? '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">E-Wallet</span>'
                : '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-slate-100 text-slate-800">Bank</span>';
            
            const createdAt = new Date(code.created_at).toLocaleDateString('id-ID');
            
            return `
                <tr class="hover:bg-slate-50/50">
                    <td class="p-3 font-mono text-xs">${code.code}</td>
                    <td class="p-3 font-medium">${code.name}</td>
                    <td class="p-3">${typeBadge}</td>
                    <td class="p-3">${statusBadge}</td>
                    <td class="p-3 text-slate-600">${createdAt}</td>
                    <td class="p-3">
                        <div class="flex gap-2">
                            <button class="edit-btn text-indigo-600 hover:underline text-xs" data-id="${code.id}">Edit</button>
                            <button class="delete-btn text-red-600 hover:underline text-xs" data-id="${code.id}" data-name="${code.name}">Hapus</button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }
    
    // Update statistics
    function updateStats() {
        const banks = allBankCodes.filter(c => c.type === 'bank').length;
        const ewallets = allBankCodes.filter(c => c.type === 'ewallet').length;
        const active = allBankCodes.filter(c => c.is_active == 1).length;
        const inactive = allBankCodes.filter(c => c.is_active == 0).length;
        
        document.getElementById('stats-banks').textContent = banks;
        document.getElementById('stats-ewallets').textContent = ewallets;
        document.getElementById('stats-active').textContent = active;
        document.getElementById('stats-inactive').textContent = inactive;
    }
    
    // Filter function
    function applyFilters() {
        const searchTerm = searchInput.value.toLowerCase();
        const typeValue = typeFilter.value;
        const statusValue = statusFilter.value;
        
        filteredBankCodes = allBankCodes.filter(code => {
            const matchesSearch = code.name.toLowerCase().includes(searchTerm) || 
                                code.code.toLowerCase().includes(searchTerm);
            const matchesType = !typeValue || code.type === typeValue;
            const matchesStatus = statusValue === '' || code.is_active == statusValue;
            
            return matchesSearch && matchesType && matchesStatus;
        });
        
        renderBankCodes();
    }
    
    // Event listeners for filters
    searchInput.addEventListener('input', applyFilters);
    typeFilter.addEventListener('change', applyFilters);
    statusFilter.addEventListener('change', applyFilters);
    
    document.getElementById('reset-filter-btn').addEventListener('click', function() {
        searchInput.value = '';
        typeFilter.value = '';
        statusFilter.value = '';
        applyFilters();
    });
    
    // Modal handlers
    document.getElementById('add-bank-code-btn').addEventListener('click', function() {
        modalTitle.textContent = 'Tambah Bank Code';
        form.reset();
        document.getElementById('edit-id').value = '';
        document.getElementById('is_active').checked = true;
        modal.classList.remove('hidden');
    });
    
    document.getElementById('close-modal-btn').addEventListener('click', function() {
        modal.classList.add('hidden');
    });
    
    document.getElementById('cancel-btn').addEventListener('click', function() {
        modal.classList.add('hidden');
    });
    
    // Form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const isEdit = !!document.getElementById('edit-id').value;
        const action = isEdit ? 'update_bank_code' : 'add_bank_code';
        
        const btnText = form.querySelector('.btn-text');
        const btnLoading = form.querySelector('.btn-loading');
        const submitBtn = form.querySelector('button[type="submit"]');
        
        // Show loading state
        btnText.classList.add('hidden');
        btnLoading.classList.remove('hidden');
        submitBtn.disabled = true;
        
        try {
            const data = {
                csrf_token: csrfToken,
                code: formData.get('code'),
                name: formData.get('name'),
                type: formData.get('type'),
                is_active: formData.get('is_active') ? 1 : 0
            };
            
            if (isEdit) {
                data.id = formData.get('id');
            }
            
            const response = await fetch(`?api=true&action=${action}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                modal.classList.add('hidden');
                fetchBankCodes();
                window.showNotification(result.message, 'success');
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            window.showNotification(`Error: ${error.message}`, 'error');
        } finally {
            // Hide loading state
            btnText.classList.remove('hidden');
            btnLoading.classList.add('hidden');
            submitBtn.disabled = false;
        }
    });
    
    // Table event handlers
    document.addEventListener('click', function(e) {
        if (e.target.matches('.edit-btn')) {
            const id = e.target.dataset.id;
            const code = allBankCodes.find(c => c.id == id);
            
            if (code) {
                modalTitle.textContent = 'Edit Bank Code';
                document.getElementById('edit-id').value = code.id;
                document.getElementById('code').value = code.code;
                document.getElementById('name').value = code.name;
                document.getElementById('type').value = code.type;
                document.getElementById('is_active').checked = code.is_active == 1;
                modal.classList.remove('hidden');
            }
        }
        
        if (e.target.matches('.delete-btn')) {
            const id = e.target.dataset.id;
            const name = e.target.dataset.name;
            
            window.showConfirmModal({
                title: 'Hapus Bank Code',
                message: `Yakin ingin menghapus "${name}"?`,
                type: 'danger',
                confirmText: 'Ya, Hapus',
                onConfirm: async () => {
                    try {
                        const response = await fetch('?api=true&action=delete_bank_code', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                csrf_token: csrfToken,
                                id: id
                            })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            fetchBankCodes();
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
    
    // Import JSON button
    document.getElementById('import-json-btn').addEventListener('click', function() {
        window.showConfirmModal({
            title: 'Import dari JSON',
            message: 'Ini akan mengimport data dari kode_bank.json. Data yang sudah ada akan diupdate.',
            confirmText: 'Ya, Import',
            onConfirm: async () => {
                try {
                    // This would typically call the import script
                    window.showNotification('Import JSON feature akan segera tersedia!', 'info');
                } catch (error) {
                    window.showNotification(`Import gagal: ${error.message}`, 'error');
                }
            }
        });
    });
    
    // Initial load
    fetchBankCodes();
});
</script>