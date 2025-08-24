<?php global $csrf_token; ?>

<!-- Sertakan Chart.js dari CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-slate-800">Dashboard Monitoring</h1>
        <div class="flex items-center gap-3">
            <div id="loading-indicator" class="hidden">
                <svg class="animate-spin h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <span id="last-updated" class="text-sm text-slate-500"></span>
            <button id="refresh-btn" title="Refresh Data" class="p-2 bg-white border border-slate-300 text-slate-600 rounded-lg hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M20 4v5h-5M4 20v-5h5M4 4l1.5 1.5M20 20l-1.5-1.5M4 20l1.5-1.5M20 4l-1.5 1.5" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 6.343A8 8 0 105.636 18.364m12.021-12.021A8 8 0 006.343 17.657" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Grid Utama -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Kolom Kiri -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Grafik Penggunaan API -->
            <div class="card-hover bg-white p-6 rounded-2xl shadow-lg">
                <h2 class="text-lg font-semibold text-slate-700 mb-4">Penggunaan API (30 Hari Terakhir)</h2>
                <div class="h-80"><canvas id="apiUsageChart"></canvas></div>
            </div>

            <!-- Analitik API -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="card-hover bg-white p-6 rounded-2xl shadow-lg">
                    <h2 class="text-lg font-semibold text-slate-700 mb-4">Top Pengguna API (24 Jam)</h2>
                    <div id="api-top-consumers" class="h-48 overflow-y-auto"></div>
                </div>
                <div class="card-hover bg-white p-6 rounded-2xl shadow-lg">
                    <h2 class="text-lg font-semibold text-slate-700 mb-4">Key Mendekati Limit (>80%)</h2>
                    <div id="api-nearing-limit" class="h-48 overflow-y-auto"></div>
                </div>
            </div>

            <!-- Slow Query Log -->
            <div class="card-hover bg-white p-6 rounded-2xl shadow-lg">
                <h2 class="text-lg font-semibold text-slate-700 mb-4">Slow Query Log</h2>
                <pre id="slow-query-log" class="text-xs bg-slate-900 text-green-400 font-mono p-4 rounded-lg h-64 overflow-y-auto"></pre>
            </div>
        </div>

        <!-- Kolom Kanan (Sidebar) -->
        <div class="space-y-6">
            <!-- Info Sistem & DB -->
            <div class="card-hover bg-white p-6 rounded-2xl shadow-lg">
                <h2 class="text-lg font-semibold text-slate-700 mb-4">Info Sistem & DB</h2>
                <div id="system-info-content" class="space-y-3 text-sm mb-4"></div>
                <div id="database-info-content" class="space-y-3 text-sm"></div>
            </div>

            <!-- Manajemen Cache -->
            <div class="card-hover bg-white p-6 rounded-2xl shadow-lg">
                <h2 class="text-lg font-semibold text-slate-700 mb-4">Manajemen Cache</h2>
                <div id="cache-info" class="text-sm space-y-2 mb-4"></div>
                <button id="clear-cache-btn" class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all duration-200 text-sm font-semibold">
                    Bersihkan Cache
                </button>
            </div>

            <!-- Live Error Log -->
            <div class="card-hover bg-white p-6 rounded-2xl shadow-lg">
                <h2 class="text-lg font-semibold text-slate-700 mb-4">Live Error Log (20 baris terakhir)</h2>
                <pre id="error-log-content" class="text-xs bg-slate-100 text-red-700 p-4 rounded-lg h-48 overflow-y-auto"></pre>
            </div>

            <!-- Feeds -->
            <div class="card-hover bg-white p-6 rounded-2xl shadow-lg">
                <h2 class="text-lg font-semibold text-slate-700 mb-4">Feed Aktivitas</h2>
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <h3 class="font-semibold text-sm mb-2">Aktivitas Admin</h3>
                        <div id="feed-admin-activity" class="h-32 overflow-y-auto space-y-2 text-xs"></div>
                    </div>
                    <div>
                        <h3 class="font-semibold text-sm mb-2">Aktivitas Keamanan</h3>
                        <div id="feed-security" class="h-32 overflow-y-auto space-y-2 text-xs"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '<?= $csrf_token ?>';
    const loadingIndicator = document.getElementById('loading-indicator');
    const lastUpdatedEl = document.getElementById('last-updated');
    const refreshBtn = document.getElementById('refresh-btn');
    const clearCacheBtn = document.getElementById('clear-cache-btn');

    // Element containers
    const systemInfoEl = document.getElementById('system-info-content');
    const dbInfoEl = document.getElementById('database-info-content');
    const errorLogEl = document.getElementById('error-log-content');
    const slowQueryLogEl = document.getElementById('slow-query-log');
    const topConsumersEl = document.getElementById('api-top-consumers');
    const nearingLimitEl = document.getElementById('api-nearing-limit');
    const adminFeedEl = document.getElementById('feed-admin-activity');
    const securityFeedEl = document.getElementById('feed-security');
    const cacheInfoEl = document.getElementById('cache-info');
    const chartCanvas = document.getElementById('apiUsageChart');

    let apiUsageChart = null;
    let autoRefreshInterval;

    const showLoading = (isLoading) => {
        loadingIndicator.classList.toggle('hidden', !isLoading);
    };

    const formatBytes = (bytes, decimals = 2) => {
        if (!bytes || bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    };

    const escapeHTML = (str) => String(str ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[m]);

    const fetchData = async () => {
        showLoading(true);
        try {
            const response = await fetch('?api=true&action=get_monitoring_data');
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const result = await response.json();
            if (result.success) {
                updateDashboard(result.data);
                lastUpdatedEl.textContent = `Diperbarui: ${new Date().toLocaleTimeString()}`;
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Gagal mengambil data monitoring:', error);
            document.body.innerHTML = `<div class="p-8 text-red-500">Gagal memuat data monitoring: ${error.message}</div>`;
        } finally {
            showLoading(false);
        }
    };

    const updateDashboard = (data) => {
        renderSystemInfo(data.system_info);
        renderDatabaseInfo(data.database_info);
        renderLogs(data.error_log, data.slow_query_log);
        renderApiAnalytics(data.api_analytics);
        renderFeeds(data.feeds);
        renderCacheInfo(data.cache_info);
        renderChart(data.chart_data);
    };

    const renderSystemInfo = (sysInfo) => {
        const usagePercent = parseFloat(sysInfo.disk_usage_percentage);
        systemInfoEl.innerHTML = `
            <div class="flex justify-between"><span>Versi PHP:</span> <span class="font-semibold">${escapeHTML(sysInfo.php_version)}</span></div>
            <div class="flex justify-between"><span>Web Server:</span> <span class="font-semibold">${escapeHTML(sysInfo.web_server)}</span></div>
            <div class="flex justify-between"><span>Sistem Operasi:</span> <span class="font-semibold">${escapeHTML(sysInfo.os)}</span></div>
            <hr class="my-2 border-slate-200">
            <p class="font-semibold">Penggunaan Disk</p>
            <div class="w-full bg-slate-200 rounded-full h-2.5">
                <div class="bg-indigo-600 h-2.5 rounded-full" style="width: ${usagePercent}%"></div>
            </div>
            <div class="flex justify-between text-xs">
                <span>${formatBytes(sysInfo.disk_used)} / ${formatBytes(sysInfo.disk_total)}</span>
                <span>${usagePercent}% digunakan</span>
            </div>
        `;
    };

    const renderDatabaseInfo = (dbInfo) => {
        let tablesHtml = dbInfo.tables.map(table => `
            <tr class="hover:bg-slate-50">
                <td class="p-1.5">${escapeHTML(table.name)}</td>
                <td class="p-1.5 text-right">${escapeHTML(table.rows)}</td>
                <td class="p-1.5 text-right">${formatBytes(table.size_bytes)}</td>
            </tr>`).join('');

        dbInfoEl.innerHTML = `
            <div class="flex justify-between p-2 bg-slate-50 rounded-lg"><span>Versi DB:</span> <span class="font-semibold">${escapeHTML(dbInfo.version)}</span></div>
            <div class="flex justify-between p-2 bg-slate-50 rounded-lg mt-2"><span>Total Ukuran:</span> <span class="font-semibold">${formatBytes(dbInfo.total_size_bytes)}</span></div>
            <div class="max-h-40 overflow-y-auto mt-3">
                <table class="w-full text-xs">
                    <thead class="bg-slate-100 sticky top-0"><tr>
                        <th class="p-1.5 text-left font-semibold">Tabel</th>
                        <th class="p-1.5 text-right font-semibold">Baris</th>
                        <th class="p-1.5 text-right font-semibold">Ukuran</th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-100">${tablesHtml}</tbody>
                </table>
            </div>
        `;
    };

    const renderLogs = (errorLog, slowQueryLog) => {
        errorLogEl.textContent = errorLog.content || 'Tidak ada log.';

        if (slowQueryLog && slowQueryLog.length > 0 && !slowQueryLog[0].error) {
            slowQueryLogEl.textContent = slowQueryLog.map(log =>
`-- ${new Date(log.start_time).toLocaleString()} | Query Time: ${log.query_time}s
${log.sql_text}`
            ).join('\n\n');
        } else {
            slowQueryLogEl.textContent = slowQueryLog[0]?.error || 'Tidak ada kueri lambat yang tercatat.';
            slowQueryLogEl.classList.remove('text-green-400');
            slowQueryLogEl.classList.add('text-slate-400');
        }
    };

    const renderApiAnalytics = (analytics) => {
        topConsumersEl.innerHTML = analytics.top_consumers_today.length > 0 ? `
            <table class="w-full text-xs">
                <tbody class="divide-y divide-slate-100">${analytics.top_consumers_today.map(c => `
                    <tr>
                        <td class="p-1.5">${escapeHTML(c.nama)}</td>
                        <td class="p-1.5 text-right font-mono">${escapeHTML(c.api_key)}</td>
                        <td class="p-1.5 text-right font-semibold">${c.total_hits} hits</td>
                    </tr>`).join('')}
                </tbody>
            </table>` : `<p class="text-center text-sm text-slate-500 p-4">Tidak ada data.</p>`;

        nearingLimitEl.innerHTML = analytics.nearing_limit.length > 0 ? `
            <table class="w-full text-xs">
                <tbody class="divide-y divide-slate-100">${analytics.nearing_limit.map(k => `
                    <tr>
                        <td class="p-1.5">${escapeHTML(k.nama)}</td>
                        <td class="p-1.5 text-right">${k.usage} / ${k.limit}</td>
                        <td class="p-1.5 text-right"><span class="font-semibold text-orange-600">${k.percentage}%</span></td>
                    </tr>`).join('')}
                </tbody>
            </table>` : `<p class="text-center text-sm text-slate-500 p-4">Semua kunci aman.</p>`;
    };

    const renderFeeds = (feeds) => {
        adminFeedEl.innerHTML = feeds.admin_activity.length > 0 ? feeds.admin_activity.map(a => `
            <p><span class="font-semibold text-blue-600">${escapeHTML(a.action)}</span> - ${escapeHTML(a.details)} <span class="text-slate-500">(${new Date(a.timestamp).toLocaleTimeString()})</span></p>
        `).join('') : `<p class="text-slate-500">Tidak ada aktivitas.</p>`;

        securityFeedEl.innerHTML = feeds.security.length > 0 ? feeds.security.map(s => `
            <p><span class="font-semibold text-red-600">${escapeHTML(s.api_key)}</span> - ${s.request_count} requests from ${escapeHTML(s.ip_address)}</p>
        `).join('') : `<p class="text-slate-500">Tidak ada aktivitas.</p>`;
    };

    const renderCacheInfo = (cacheInfo) => {
        cacheInfoEl.innerHTML = `
            <div class="flex justify-between"><span>Jumlah File:</span> <span class="font-semibold">${cacheInfo.file_count}</span></div>
            <div class="flex justify-between"><span>Total Ukuran:</span> <span class="font-semibold">${formatBytes(cacheInfo.total_size)}</span></div>
        `;
    };

    const renderChart = (chartData) => {
        if (apiUsageChart) {
            apiUsageChart.data.labels = chartData.labels;
            apiUsageChart.data.datasets[0].data = chartData.data;
            apiUsageChart.update();
        } else {
            apiUsageChart = new Chart(chartCanvas, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'API Requests',
                        data: chartData.data,
                        fill: true,
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        borderColor: 'rgba(79, 70, 229, 1)',
                        tension: 0.3,
                        pointBackgroundColor: 'rgba(79, 70, 229, 1)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true } },
                    plugins: { legend: { display: false } }
                }
            });
        }
    };

    clearCacheBtn.addEventListener('click', async () => {
        showConfirmModal({
            title: 'Konfirmasi Hapus Cache',
            message: 'Anda yakin ingin membersihkan semua file cache? Tindakan ini tidak dapat diurungkan.',
            type: 'danger',
            confirmText: 'Ya, Hapus',
            onConfirm: async () => {
                try {
                    const result = await fetch('?api=true&action=clear_cache', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ csrf_token: csrfToken })
                    });
                    const data = await result.json();
                    if (!data.success) throw new Error(data.message);
                    showNotification(data.message, 'success');
                    fetchData(); // Refresh data untuk melihat perubahan
                } catch (error) {
                    showNotification(error.message, 'error');
                }
            }
        });
    });

    refreshBtn.addEventListener('click', fetchData);
    fetchData();
    autoRefreshInterval = setInterval(fetchData, 60000); // Refresh every 60 seconds
});
</script>
