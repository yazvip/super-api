const App = {
    // --- STATE ---
    state: {
        currentApiKey: null,
        currentTesterTab: 'rekening', // [BARU] State untuk tab di halaman tester
        currentTab: 'tester',
        allBanks: [],
        allGames: [], // [BARU] State untuk daftar game
        limit: 0,
        remain: 0,
        chartInstance: null,
    },

    // --- KONFIGURASI ---
    config: {
        API_ENDPOINT: 'valid-api.php',
        FREE_API_KEY: 'free',
        // [BARU] Daftar kode game yang memerlukan zona
        GAME_CODES_WITH_ZONE: ['MOBILE_LEGENDS', 'MOBILE_LEGENDS_REG', 'MOBILE_LEGENDS_SO', 'MOBILE_LEGENDS_VC'],
    },

    // --- ELEMENT CACHE ---
    elements: {},

    // --- INISIALISASI ---
    init() {
        this.cacheElements();
        this.methods.initTheme();
        this.registerEventListeners();
        this.handleInitialLoad();
    },

    cacheElements() {
        this.elements = {
            body: document.body,
            apiKeyModal: document.getElementById('api-key-modal'),
            modalContent: document.querySelector('[data-modal-content]'),
            apiKeyForm: document.getElementById('api-key-form'),
            modalApiKeyInput: document.getElementById('modal-api-key-input'),
            modalError: document.getElementById('modal-error'),
            mainApp: document.getElementById('main-app'),
            contentContainer: document.getElementById('content-container'),
            bottomNav: document.querySelector('.bottom-nav'),
            logoutButton: document.getElementById('logout-button'),
            themeToggle: document.getElementById('theme-toggle'),
            themeIconLight: document.getElementById('theme-icon-light'),
            themeIconDark: document.getElementById('theme-icon-dark'),
            toastContainer: document.getElementById('toast-container'),
            freeKeyButton: document.getElementById('free-key-button'),
            notificationButton: document.getElementById('notification-button'),
            notificationBadge: document.getElementById('notification-badge'),
            notificationDropdown: document.getElementById('notification-dropdown'),
            notificationList: document.getElementById('notification-list'),
        };
    },

    registerEventListeners() {
        this.elements.apiKeyForm.addEventListener('submit', e => {
            e.preventDefault();
            this.methods.attemptLogin(this.elements.modalApiKeyInput.value.trim());
        });
        this.elements.logoutButton.addEventListener('click', () => this.methods.logout());
        this.elements.themeToggle.addEventListener('click', () => this.methods.toggleTheme());
        this.elements.bottomNav.addEventListener('click', e => {
            const button = e.target.closest('.bottom-nav-item');
            if (button && button.dataset.tab) {
                this.methods.showTab(button.dataset.tab);
            }
        });
        if (this.elements.freeKeyButton) {
            this.elements.freeKeyButton.addEventListener('click', () => this.methods.copyFreeKey());
        }
        if (this.elements.notificationButton) {
            this.elements.notificationButton.addEventListener('click', () => this.methods.toggleNotifications());
            document.addEventListener('click', (e) => {
                if (!this.elements.notificationDropdown.contains(e.target) && !this.elements.notificationButton.contains(e.target)) {
                    this.elements.notificationDropdown.classList.add('hidden');
                }
            });
        }
        
        this.elements.contentContainer.addEventListener('click', e => {
            // Paginasi
            const pageButton = e.target.closest('[data-page]');
            if (pageButton) {
                e.preventDefault();
                const page = parseInt(pageButton.dataset.page, 10);
                if (!isNaN(page)) this.methods.fetchHistory(page);
            }
            
            // Tab Dokumentasi
            const docTabButton = e.target.closest('[data-doc-tab]');
            if (docTabButton) {
                e.preventDefault();
                this.ui.updateDocTabs(docTabButton.dataset.docTab);
            }

            // [BARU] Tab Tester (Rekening/Game)
            const testerTabButton = e.target.closest('[data-tester-tab]');
            if (testerTabButton) {
                e.preventDefault();
                this.state.currentTesterTab = testerTabButton.dataset.testerTab;
                this.ui.renderTester(); // Render ulang konten tester
            }
        });
    },

    handleInitialLoad() {
        const savedApiKey = localStorage.getItem('apiKey');
        if (savedApiKey) {
            this.elements.modalApiKeyInput.value = savedApiKey;
            this.methods.attemptLogin(savedApiKey);
        } else {
            this.ui.showModal();
        }
    },

    // --- METODE UTAMA (LOGIKA APLIKASI) ---
    methods: {
        async apiFetch(endpoint, options = {}) {
            const headers = { 'Authorization': `Bearer ${App.state.currentApiKey}`, ...options.headers };
            const response = await fetch(endpoint, { ...options, headers });
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.msg || `HTTP Error ${response.status}`);
            }
            if(data.limit !== undefined && data.remain !== undefined) {
                App.state.limit = data.limit;
                App.state.remain = data.remain;
            }
            return data;
        },

        async attemptLogin(key) {
            if (!key) return;
            App.ui.setButtonLoading(App.elements.apiKeyForm.querySelector('button'), true);
            try {
                App.state.currentApiKey = key; 
                const data = await this.apiFetch(`${App.config.API_ENDPOINT}?action=get_options`);
                if (data.ok) {
                    localStorage.setItem('apiKey', key);
                    App.state.allBanks = data.options || [];
                    App.state.allGames = data.game_options || []; // [BARU] Simpan data game
                    App.ui.hideModal();
                    App.elements.mainApp.classList.remove('hidden');
                    this.showTab('tester');
                    this.showToast('Login berhasil!', 'success');
                    this.fetchNotifications();
                }
            } catch (error) {
                App.state.currentApiKey = null;
                App.elements.modalError.textContent = error.message;
                App.elements.modalError.classList.remove('hidden');
                localStorage.removeItem('apiKey');
                App.ui.showModal(); 
            } finally {
                App.ui.setButtonLoading(App.elements.apiKeyForm.querySelector('button'), false);
            }
        },

        logout() {
            localStorage.removeItem('apiKey');
            App.state.currentApiKey = null;
            App.elements.mainApp.classList.add('hidden');
            App.elements.modalApiKeyInput.value = '';
            App.elements.modalError.classList.add('hidden');
            App.ui.showModal();
        },

        showTab(tabName) {
            App.state.currentTab = tabName;
            App.ui.updateActiveTab(tabName);
            App.ui.renderTabContent(tabName);
        },

        showToast(message, type = 'info') {
            const toast = document.createElement('div');
            const colors = {
                info: 'bg-slate-800 dark:bg-slate-200 text-white dark:text-slate-800',
                success: 'bg-green-600 text-white',
                error: 'bg-red-600 text-white',
            };
            toast.className = `flex items-center gap-3 w-full max-w-xs p-4 rounded-lg shadow-lg transform-gpu transition-all duration-300 translate-x-full opacity-0 ${colors[type]}`;
            toast.innerHTML = `<p class="text-sm font-medium">${message}</p>`;
            App.elements.toastContainer.appendChild(toast);
            setTimeout(() => {
                toast.classList.remove('translate-x-full', 'opacity-0');
            }, 10);
            setTimeout(() => {
                toast.classList.add('opacity-0');
                toast.addEventListener('transitionend', () => toast.remove());
            }, 3000);
        },

        initTheme() {
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
                App.ui.updateThemeIcons(true);
            } else {
                document.documentElement.classList.remove('dark');
                App.ui.updateThemeIcons(false);
            }
        },
        
        toggleTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.theme = isDark ? 'dark' : 'light';
            App.ui.updateThemeIcons(isDark);
        },

        copyFreeKey() {
            const key = App.config.FREE_API_KEY;
            navigator.clipboard.writeText(key).then(() => {
                const button = document.getElementById('free-key-button');
                const originalText = button.innerHTML;
                button.innerHTML = 'Tersalin!';
                button.disabled = true;
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }, 2000);
                App.elements.modalApiKeyInput.value = key;
                this.showToast('API Key Gratis disalin!', 'success');
            });
        },
        
        toggleNotifications() {
            App.elements.notificationDropdown.classList.toggle('hidden');
        },

        async fetchNotifications() {
            App.ui.renderNotificationList(null);
            try {
                const response = await fetch(`${App.config.API_ENDPOINT}?action=get_notifications`);
                const data = await response.json();
                if (data.ok) {
                    App.ui.renderNotificationList(data.notifications);
                } else {
                    throw new Error('Gagal memuat notifikasi.');
                }
            } catch (error) {
                App.ui.renderNotificationList([]);
                console.error(error);
            }
        },

        async fetchHistory(page = 1) {
            App.ui.renderHistorySkeleton();
            try {
                const data = await App.methods.apiFetch(`${App.config.API_ENDPOINT}?action=get_history&page=${page}`);
                App.ui.renderHistory(data);
            } catch (error) {
                App.elements.contentContainer.innerHTML = App.ui.renderEmptyState(error.message, true);
            }
        }
    },

    // --- UI & RENDER ---
    ui: {
        showModal() {
            const modal = App.elements.apiKeyModal;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                if (modal.querySelector('[data-modal-content]')) {
                    modal.querySelector('[data-modal-content]').classList.add('scale-100', 'opacity-100');
                }
            }, 10);
        },
        hideModal() {
            const modal = App.elements.apiKeyModal;
            const content = modal.querySelector('[data-modal-content]');
            if (content) {
                content.classList.remove('scale-100', 'opacity-100');
            }
            setTimeout(() => modal.classList.add('hidden'), 200);
        },
        setButtonLoading(button, isLoading) {
            if (!button) return;
            const text = button.querySelector('[data-button-text]');
            const spinner = button.querySelector('[data-button-spinner]');
            if (isLoading) {
                button.disabled = true;
                if (text) text.classList.add('hidden');
                if (spinner) spinner.classList.remove('hidden');
            } else {
                button.disabled = false;
                if (text) text.classList.remove('hidden');
                if (spinner) spinner.classList.add('hidden');
            }
        },
        updateActiveTab(tabName) {
            App.elements.bottomNav.querySelectorAll('.bottom-nav-item').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.tab === tabName);
            });
        },
        renderTabContent(tabName) {
            const container = App.elements.contentContainer;
            container.innerHTML = this.renderSkeleton();
            
            setTimeout(() => {
                switch(tabName) {
                    case 'tester': this.renderTester(); break;
                    case 'history': App.methods.fetchHistory(1); break;
                    case 'stats': this.renderStats(); break;
                    case 'docs': this.renderDocs(); break;
                }
            }, 100);
        },
        renderTester() {
            const limitInfo = `Sisa: ${App.state.remain} / ${App.state.limit}`;
            const isRekeningTab = App.state.currentTesterTab === 'rekening';
            
            App.elements.contentContainer.innerHTML = `
                <div class="space-y-6">
                    <div class="app-card p-6 rounded-2xl shadow-lg shadow-slate-500/5">
                        <div class="border-b border-[var(--border)] mb-4">
                            <nav class="-mb-px flex space-x-6" aria-label="Tabs">
                                <button data-tester-tab="rekening" class="tab-button whitespace-nowrap py-3 px-1 text-sm font-semibold ${isRekeningTab ? 'active' : 'text-slate-500 dark:text-slate-400'}">
                                    Validasi Rekening
                                </button>
                                <button data-tester-tab="game" class="tab-button whitespace-nowrap py-3 px-1 text-sm font-semibold ${!isRekeningTab ? 'active' : 'text-slate-500 dark:text-slate-400'}">
                                    Validasi Nick Game
                                </button>
                            </nav>
                        </div>
                        <div id="tester-tab-content">
                            ${isRekeningTab ? this.renderRekeningForm() : this.renderGameForm()}
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-lg font-semibold text-[var(--text)]">Hasil Validasi</h3>
                            <div id="limit-info" class="text-xs text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-3 py-1 rounded-full">${limitInfo}</div>
                        </div>
                        <div id="api-result-container" class="app-card rounded-2xl p-4 min-h-[100px] flex items-center justify-center">
                            <p class="text-slate-500 dark:text-slate-400 text-sm">Hasil akan ditampilkan di sini.</p>
                        </div>
                    </div>
                </div>
            `;
            
            if (isRekeningTab) {
                this.setupRekeningFormListeners();
            } else {
                this.setupGameFormListeners();
            }
        },
        renderRekeningForm() {
            return `
                <form id="api-tester-form" class="space-y-4">
                    <div>
                        <label for="bank_search" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Cari Bank</label>
                        <input type="text" id="bank_search" placeholder="Ketik nama bank..." class="app-input w-full rounded-lg p-3 text-[var(--text)]">
                    </div>
                    <div>
                        <label for="account_type" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Bank / E-Wallet</label>
                        <select id="account_type" name="account_type" required class="app-input w-full rounded-lg p-3 text-[var(--text)]"></select>
                    </div>
                    <div>
                        <label for="account_number" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nomor Rekening / Telepon</label>
                        <input type="number" id="account_number" name="account_number" placeholder="Contoh: 081234567890" required class="app-input w-full rounded-lg p-3 text-[var(--text)]">
                    </div>
                    <button type="submit" class="w-full p-3 font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-lg shadow-indigo-500/20 flex items-center justify-center">
                        <span data-button-text>Validasi Rekening</span>
                        <svg data-button-spinner class="animate-spin h-5 w-5 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </button>
                </form>
            `;
        },
        renderGameForm() {
            return `
                <form id="game-tester-form" class="space-y-4">
                    <div>
                        <label for="game_code" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Pilih Game</label>
                        <select id="game_code" name="game_code" required class="app-input w-full rounded-lg p-3 text-[var(--text)]"></select>
                    </div>
                    <div>
                        <label for="game_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">ID Game</label>
                        <input type="text" id="game_id" name="game_id" placeholder="Masukkan ID Game" required class="app-input w-full rounded-lg p-3 text-[var(--text)]">
                    </div>
                    <div id="zone-id-wrapper" class="hidden">
                        <label for="zone_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Zona ID</label>
                        <input type="text" id="zone_id" name="zone_id" placeholder="Masukkan Zona ID" class="app-input w-full rounded-lg p-3 text-[var(--text)]">
                    </div>
                    <button type="submit" class="w-full p-3 font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-lg shadow-indigo-500/20 flex items-center justify-center">
                        <span data-button-text>Validasi Nickname</span>
                        <svg data-button-spinner class="animate-spin h-5 w-5 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </button>
                </form>
            `;
        },
        setupRekeningFormListeners() {
            const bankSelect = document.getElementById('account_type');
            const bankSearch = document.getElementById('bank_search');
            const testerForm = document.getElementById('api-tester-form');
            
            const populateDropdown = (banks) => {
                bankSelect.innerHTML = '<option value="">-- Pilih Opsi --</option>';
                banks.forEach(b => bankSelect.add(new Option(b.label, b.code)));
            };
            
            populateDropdown(App.state.allBanks);

            bankSearch.addEventListener('input', (e) => {
                const term = e.target.value.toLowerCase();
                const filtered = App.state.allBanks.filter(b => b.label.toLowerCase().includes(term));
                populateDropdown(filtered);
            });

            testerForm.addEventListener('submit', async e => {
                e.preventDefault();
                const button = testerForm.querySelector('button');
                this.setButtonLoading(button, true);
                const formData = new FormData(testerForm);
                try {
                    const data = await App.methods.apiFetch(`${App.config.API_ENDPOINT}?action=validate_account`, {
                        method: 'POST',
                        body: new URLSearchParams(formData)
                    });
                    this.renderResult(data);
                } catch (error) {
                    this.renderResult({ ok: false, msg: error.message });
                } finally {
                    const limitInfoElem = document.getElementById('limit-info');
                    if (limitInfoElem) limitInfoElem.textContent = `Sisa: ${App.state.remain} / ${App.state.limit}`;
                    this.setButtonLoading(button, false);
                }
            });
        },
        setupGameFormListeners() {
            const gameSelect = document.getElementById('game_code');
            const zoneWrapper = document.getElementById('zone-id-wrapper');
            const gameForm = document.getElementById('game-tester-form');

            if (App.state.allGames && App.state.allGames.length > 0) {
                gameSelect.innerHTML = '<option value="">-- Pilih Game --</option>';
                App.state.allGames.forEach(g => gameSelect.add(new Option(g.label, g.code)));
            } else {
                gameSelect.innerHTML = '<option value="">-- Gagal memuat daftar game --</option>';
                gameSelect.disabled = true;
                const errorEl = document.createElement('p');
                errorEl.className = 'text-xs text-red-500 mt-1';
                errorEl.textContent = 'Tidak dapat mengambil daftar game dari server. Coba muat ulang halaman.';
                gameSelect.parentNode.appendChild(errorEl);
                Array.from(gameForm.elements).forEach(el => { if(el.tagName !== 'BUTTON') el.disabled = true; });
                gameForm.querySelector('button').disabled = true;
            }

            gameSelect.addEventListener('change', (e) => {
                const selectedGameCode = e.target.value;
                if (App.config.GAME_CODES_WITH_ZONE.includes(selectedGameCode.toUpperCase())) {
                    zoneWrapper.classList.remove('hidden');
                } else {
                    zoneWrapper.classList.add('hidden');
                }
            });

            gameForm.addEventListener('submit', async e => {
                e.preventDefault();
                const button = gameForm.querySelector('button');
                this.setButtonLoading(button, true);
                const formData = new FormData(gameForm);
                try {
                    const data = await App.methods.apiFetch(`${App.config.API_ENDPOINT}?action=validate_game`, {
                        method: 'POST',
                        body: new URLSearchParams(formData)
                    });
                    this.renderResult(data);
                } catch (error) {
                    this.renderResult({ ok: false, msg: error.message });
                } finally {
                    const limitInfoElem = document.getElementById('limit-info');
                    if (limitInfoElem) limitInfoElem.textContent = `Sisa: ${App.state.remain} / ${App.state.limit}`;
                    this.setButtonLoading(button, false);
                }
            });
        },
        renderResult(data) {
            const container = document.getElementById('api-result-container');
            container.innerHTML = `
                <pre class="w-full text-left text-xs p-4 bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded-lg overflow-x-auto whitespace-pre-wrap break-all">
${JSON.stringify(data, null, 2)}
                </pre>
            `;
        },
        renderHistory(data) {
            let content = `<h2 class="text-xl font-bold text-[var(--text)] mb-4">Riwayat Pengecekan</h2>`;
            content += this.renderHistorySummary(data.summary);
            content += `<div id="history-list-container" class="space-y-3 mt-4">`;
            if (data.data && data.data.length > 0) {
                content += data.data.map(item => this.renderHistoryItem(item)).join('');
            } else {
                content += this.renderEmptyState('Belum ada riwayat pengecekan.');
            }
            content += `</div>`;
            content += `<div id="pagination-container" class="mt-6"></div>`;
            
            App.elements.contentContainer.innerHTML = content;
            this.renderPagination(data.pagination);
        },
        renderHistoryItem(item) {
            const isSuccess = item.status === 'Berhasil';
            return `
                <div class="app-card p-4 rounded-xl flex items-start gap-4">
                    <div class="w-10 h-10 flex-shrink-0 rounded-full flex items-center justify-center ${isSuccess ? 'bg-green-100 dark:bg-green-500/20 text-green-600' : 'bg-red-100 dark:bg-red-500/20 text-red-600'}">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">${isSuccess ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>' : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>'}</svg>
                    </div>
                    <div class="flex-grow">
                        <div class="flex justify-between items-center">
                            <p class="font-semibold text-[var(--text)]">${item.account_name}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">${new Date(item.timestamp).toLocaleDateString('id-ID')}</p>
                        </div>
                        <p class="text-sm text-slate-600 dark:text-slate-400">${item.bank} - ${item.account_number}</p>
                        <p class="text-xs mt-1 text-slate-500 dark:text-slate-400">${item.message}</p>
                    </div>
                </div>
            `;
        },
        renderHistorySummary(summary) {
            return `
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="app-card p-3 rounded-lg">
                        <p class="text-xs text-slate-500 dark:text-slate-400">Total</p>
                        <p class="font-bold text-lg text-[var(--text)]">${summary.total}</p>
                    </div>
                    <div class="app-card p-3 rounded-lg">
                        <p class="text-xs text-green-500">Sukses</p>
                        <p class="font-bold text-lg text-green-500">${summary.success}</p>
                    </div>
                    <div class="app-card p-3 rounded-lg">
                        <p class="text-xs text-red-500">Gagal</p>
                        <p class="font-bold text-lg text-red-500">${summary.failed}</p>
                    </div>
                </div>
            `;
        },
        renderPagination({ currentPage, totalPages }) {
            const container = document.getElementById('pagination-container');
            if (!container || totalPages <= 1) {
                if(container) container.innerHTML = '';
                return;
            }

            let pages = [];
            const pageRange = 2;
            
            pages.push(1);
            if (currentPage > pageRange + 1) pages.push('...');
            
            for (let i = Math.max(2, currentPage - pageRange); i <= Math.min(totalPages - 1, currentPage + pageRange); i++) {
                pages.push(i);
            }

            if (currentPage < totalPages - pageRange) pages.push('...');
            if (totalPages > 1) pages.push(totalPages);

            let html = '<div class="flex items-center justify-center gap-2">';
            pages.forEach(p => {
                if (p === '...') {
                    html += `<span class="px-4 py-2 text-sm text-slate-500 dark:text-slate-400">...</span>`;
                } else {
                    const isActive = p === currentPage;
                    html += `<a href="#" data-page="${p}" class="${isActive ? 'bg-indigo-600 text-white' : 'bg-[var(--card)] text-[var(--text)] hover:bg-slate-200 dark:hover:bg-slate-700'} px-4 py-2 text-sm font-semibold rounded-md transition-colors">${p}</a>`;
                }
            });
            html += '</div>';
            container.innerHTML = html;
        },
        renderHistorySkeleton() {
            App.elements.contentContainer.innerHTML = `
                <h2 class="text-xl font-bold text-[var(--text)] mb-4">Riwayat Pengecekan</h2>
                <div class="grid grid-cols-3 gap-3">
                    <div class="h-16 skeleton rounded-lg"></div>
                    <div class="h-16 skeleton rounded-lg"></div>
                    <div class="h-16 skeleton rounded-lg"></div>
                </div>
                <div class="space-y-3 mt-4">
                    ${Array(5).fill('<div class="h-20 skeleton rounded-xl"></div>').join('')}
                </div>
            `;
        },
        async renderStats() {
            App.elements.contentContainer.innerHTML = `
                <h2 class="text-xl font-bold text-[var(--text)] mb-4">Statistik Penggunaan</h2>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">Grafik ini menampilkan total transaksi berhasil per siklus.</p>
                <div class="app-card p-4 rounded-2xl shadow-lg shadow-slate-500/5 h-64">
                    <canvas id="usageChart"></canvas>
                </div>
            `;
            try {
                const result = await App.methods.apiFetch(`${App.config.API_ENDPOINT}?action=get_stats`);
                if (App.state.chartInstance) App.state.chartInstance.destroy();
                const ctx = document.getElementById('usageChart').getContext('2d');
                App.state.chartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: result.stats.labels,
                        datasets: [{
                            label: 'Penggunaan per Siklus',
                            data: result.stats.data,
                            backgroundColor: 'rgba(79, 70, 229, 0.1)',
                            borderColor: 'rgba(79, 70, 229, 1)',
                            borderWidth: 2, fill: true, tension: 0.4
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
                });
            } catch (error) {
                document.querySelector('.app-card').innerHTML = this.renderEmptyState(error.message, true);
            }
        },
        renderDocs() {
            const endpointUrl = window.location.href.replace(/[^/]*$/, App.config.API_ENDPOINT);
            const apiKey = App.state.currentApiKey || '[API_KEY_ANDA]';

            App.elements.contentContainer.innerHTML = `
                <h2 class="text-xl font-bold text-[var(--text)] mb-4">Dokumentasi API</h2>
                <div class="space-y-4 text-sm">
                    <div class="app-card p-4 rounded-xl">
                        <h3 class="font-semibold text-[var(--text)] mb-2">Endpoint URL</h3>
                        <pre class="bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 p-2 rounded text-xs overflow-x-auto">${endpointUrl}</pre>
                    </div>
                    <div class="app-card p-4 rounded-xl">
                        <h3 class="font-semibold text-[var(--text)] mb-2">Contoh Penggunaan</h3>
                        <div class="border-b border-[var(--border)] mb-2">
                            <nav class="-mb-px flex space-x-4" aria-label="Tabs">
                                <a href="#" data-doc-tab="curl" class="doc-tab active whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm border-indigo-500 text-indigo-600">cURL</a>
                                <a href="#" data-doc-tab="python" class="doc-tab whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:border-slate-300 dark:hover:border-slate-600">Python</a>
                                <a href="#" data-doc-tab="js" class="doc-tab whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:border-slate-300 dark:hover:border-slate-600">JavaScript</a>
                            </nav>
                        </div>
                        <div class="text-xs">
                            <div id="doc-tab-content-curl" class="doc-tab-content">
                                <pre class="bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 p-3 rounded overflow-x-auto"><code>curl -X POST ${endpointUrl} \\
-H "Authorization: Bearer ${apiKey}" \\
-d "action=validate_account" \\
-d "account_type=bca" \\
-d "account_number=1234567890"</code></pre>
                            </div>
                            <div id="doc-tab-content-python" class="doc-tab-content hidden">
                                <pre class="bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 p-3 rounded overflow-x-auto"><code>import requests

url = "${endpointUrl}"
headers = {
    "Authorization": "Bearer ${apiKey}"
}
payload = {
    "action": "validate_account",
    "account_type": "bca",
    "account_number": "1234567890"
}

response = requests.post(url, headers=headers, data=payload)
print(response.json())</code></pre>
                            </div>
                            <div id="doc-tab-content-js" class="doc-tab-content hidden">
                                <pre class="bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 p-3 rounded overflow-x-auto"><code>async function validateAccount() {
    const url = "${endpointUrl}";
    const headers = {
        "Authorization": "Bearer ${apiKey}",
        "Content-Type": "application/x-www-form-urlencoded"
    };
    const body = new URLSearchParams({
        "action": "validate_account",
        "account_type": "bca",
        "account_number": "1234567890"
    });

    try {
        const response = await fetch(url, {
            method: "POST",
            headers: headers,
            body: body
        });
        const data = await response.json();
        console.log(data);
    } catch (error) {
        console.error("Error:", error);
    }
}

validateAccount();</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        },
        updateDocTabs(activeTab) {
            document.querySelectorAll('.doc-tab').forEach(tab => {
                const isSelected = tab.dataset.docTab === activeTab;
                tab.classList.toggle('active', isSelected);
                tab.classList.toggle('border-indigo-500', isSelected);
                tab.classList.toggle('text-indigo-600', isSelected);
                tab.classList.toggle('border-transparent', !isSelected);
                tab.classList.toggle('text-slate-500', !isSelected);
                tab.classList.toggle('dark:text-slate-400', !isSelected);
            });
            document.querySelectorAll('.doc-tab-content').forEach(content => {
                content.classList.toggle('hidden', content.id !== `doc-tab-content-${activeTab}`);
            });
        },
        renderSkeleton() {
            return `
                <div class="space-y-6">
                    <div class="p-6 rounded-2xl app-card space-y-4">
                        <div class="h-6 w-1/2 skeleton rounded"></div>
                        <div class="space-y-4">
                            <div class="h-4 w-1/4 skeleton rounded"></div><div class="h-10 w-full skeleton rounded-lg"></div>
                            <div class="h-4 w-1/4 skeleton rounded"></div><div class="h-10 w-full skeleton rounded-lg"></div>
                            <div class="h-12 w-full skeleton rounded-lg mt-4"></div>
                        </div>
                    </div>
                    <div class="p-6 rounded-2xl app-card h-48"></div>
                </div>
            `;
        },
        renderEmptyState(message, isError = false) {
            const color = isError ? 'text-red-500' : 'text-slate-500';
            return `<div class="text-center p-8 ${color} text-sm">${message}</div>`;
        },
        updateThemeIcons(isDark) {
            App.elements.themeIconLight.classList.toggle('hidden', isDark);
            App.elements.themeIconDark.classList.toggle('hidden', !isDark);
        },
        renderNotificationList(notifications) {
            const listEl = App.elements.notificationList;
            const badgeEl = App.elements.notificationBadge;

            if (notifications === null) {
                listEl.innerHTML = `<div class="p-4 text-center text-sm text-slate-500 dark:text-slate-400">Memuat...</div>`;
                return;
            }

            if (!notifications || notifications.length === 0) {
                listEl.innerHTML = `<div class="p-4 text-center text-sm text-slate-500 dark:text-slate-400">Tidak ada notifikasi.</div>`;
                badgeEl.classList.add('hidden');
                return;
            }

            badgeEl.textContent = notifications.length;
            badgeEl.classList.remove('hidden');

            listEl.innerHTML = notifications.map(notif => {
                const date = new Date(notif.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
                return `
                    <div class="p-4 border-b border-[var(--border)] hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <p class="text-sm text-[var(--text)]">${notif.message}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">${date}</p>
                    </div>
                `;
            }).join('');
        }
    }
};

document.addEventListener('DOMContentLoaded', () => App.init());