<!-- Diperlukan untuk token CSRF dan fungsi global -->
<?php global $csrf_token; ?>

<!-- Sertakan Chart.js dari CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-slate-800">System Monitoring</h1>
        <div class="flex items-center gap-2">
            <div id="loading-indicator" class="hidden">
                <svg class="animate-spin h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <span id="last-updated" class="text-sm text-slate-500"></span>
            <button id="refresh-btn" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5M20 4v5h-5M4 20v-5h5" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 6.343A8 8 0 105.636 18.364m12.021-12.021A8 8 0 006.343 17.657" />
                </svg>
                Refresh
            </button>
        </div>
    </div>

    <!-- Grid untuk Widget Monitoring -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

        <!-- Widget: Informasi Sistem -->
        <div class="card-hover bg-white p-6 rounded-2xl shadow-lg col-span-1 md:col-span-2 lg:col-span-1">
            <h2 class="text-lg font-semibold text-slate-700 mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"/></svg>
                Informasi Sistem
            </h2>
            <div id="system-info-content" class="space-y-3 text-sm">
                <p class="text-center text-slate-500 py-4">Memuat data...</p>
            </div>
        </div>

        <!-- Widget: Status Database -->
        <div class="card-hover bg-white p-6 rounded-2xl shadow-lg col-span-1 md:col-span-2 lg:col-span-2">
            <h2 class="text-lg font-semibold text-slate-700 mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10m16-10v10M4 11h16M4 15h16M4 7a2 2 0 012-2h12a2 2 0 012 2m-2 10a2 2 0 01-2 2H6a2 2 0 01-2-2"/></svg>
                Status Database
            </h2>
            <div id="database-info-content" class="space-y-3">
                <p class="text-center text-slate-500 py-4">Memuat data...</p>
            </div>
        </div>

        <!-- Widget: Statistik API -->
        <div class="card-hover bg-white p-6 rounded-2xl shadow-lg col-span-1 md:col-span-2 lg:col-span-1">
            <h2 class="text-lg font-semibold text-slate-700 mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M12 6a2 2 0 100-4 2 2 0 000 4zm0 14a2 2 0 100-4 2 2 0 000 4zm6-8a2 2 0 100-4 2 2 0 000 4zm-12 0a2 2 0 100-4 2 2 0 000 4z"/></svg>
                Statistik API
            </h2>
            <div id="api-stats-content" class="space-y-3 text-sm">
                <p class="text-center text-slate-500 py-4">Memuat data...</p>
            </div>
        </div>

        <!-- Widget: Grafik Penggunaan API -->
        <div class="card-hover bg-white p-6 rounded-2xl shadow-lg col-span-1 md:col-span-2 lg:col-span-3">
            <h2 class="text-lg font-semibold text-slate-700 mb-4">Penggunaan API (30 Hari Terakhir)</h2>
            <div class="h-80">
                <canvas id="apiUsageChart"></canvas>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loadingIndicator = document.getElementById('loading-indicator');
    const lastUpdatedEl = document.getElementById('last-updated');
    const refreshBtn = document.getElementById('refresh-btn');
    const systemInfoEl = document.getElementById('system-info-content');
    const dbInfoEl = document.getElementById('database-info-content');
    const apiStatsEl = document.getElementById('api-stats-content');
    const chartCanvas = document.getElementById('apiUsageChart');
    let apiUsageChart = null;
    let autoRefreshInterval;

    const showLoading = (isLoading) => {
        loadingIndicator.classList.toggle('hidden', !isLoading);
    };

    const formatBytes = (bytes, decimals = 2) => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    };

    const fetchData = async () => {
        showLoading(true);
        try {
            const response = await fetch('?api=true&action=get_monitoring_data');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();
            if (result.success) {
                updateDashboard(result.data);
                lastUpdatedEl.textContent = `Last updated: ${new Date().toLocaleTimeString()}`;
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Failed to fetch monitoring data:', error);
            systemInfoEl.innerHTML = `<p class="text-red-500">Gagal memuat data: ${error.message}</p>`;
            dbInfoEl.innerHTML = `<p class="text-red-500">Gagal memuat data.</p>`;
            apiStatsEl.innerHTML = `<p class="text-red-500">Gagal memuat data.</p>`;
        } finally {
            showLoading(false);
        }
    };

    const updateDashboard = (data) => {
        // Render System Info
        const sysInfo = data.system_info;
        systemInfoEl.innerHTML = `
            <div class="flex justify-between"><span>Versi PHP:</span> <span class="font-semibold">${sysInfo.php_version}</span></div>
            <div class="flex justify-between"><span>Web Server:</span> <span class="font-semibold">${sysInfo.web_server}</span></div>
            <div class="flex justify-between"><span>Sistem Operasi:</span> <span class="font-semibold">${sysInfo.os}</span></div>
            <hr class="my-2">
            <p class="font-semibold">Disk Space</p>
            <div class="w-full bg-slate-200 rounded-full h-2.5">
                <div class="bg-indigo-600 h-2.5 rounded-full" style="width: ${sysInfo.disk_usage_percentage}%"></div>
            </div>
            <div class="flex justify-between text-xs">
                <span>${formatBytes(sysInfo.disk_used)} / ${formatBytes(sysInfo.disk_total)}</span>
                <span>${sysInfo.disk_usage_percentage}% used</span>
            </div>
        `;

        // Render Database Info
        const dbInfo = data.database_info;
        let tablesHtml = dbInfo.tables.map(table => `
            <tr class="hover:bg-slate-50">
                <td class="p-2">${table.name}</td>
                <td class="p-2 text-right">${table.rows}</td>
                <td class="p-2 text-right">${formatBytes(table.size_bytes)}</td>
            </tr>
        `).join('');

        dbInfoEl.innerHTML = `
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div class="flex justify-between p-3 bg-slate-50 rounded-lg"><span>Versi DB:</span> <span class="font-semibold">${dbInfo.version}</span></div>
                <div class="flex justify-between p-3 bg-slate-50 rounded-lg"><span>Total Size:</span> <span class="font-semibold">${formatBytes(dbInfo.total_size_bytes)}</span></div>
            </div>
            <div class="max-h-48 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-100 sticky top-0"><tr>
                        <th class="p-2 text-left font-semibold">Tabel</th>
                        <th class="p-2 text-right font-semibold">Baris</th>
                        <th class="p-2 text-right font-semibold">Ukuran</th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-100">${tablesHtml}</tbody>
                </table>
            </div>
        `;

        // Render API Stats
        const apiStats = data.api_stats;
        apiStatsEl.innerHTML = `
            <div class="flex justify-between"><span>Total Request (30d):</span> <span class="font-semibold">${apiStats.total_requests}</span></div>
            <div class="flex justify-between"><span>Request Berhasil:</span> <span class="font-semibold text-green-600">${apiStats.successful_requests}</span></div>
            <div class="flex justify-between"><span>Request Gagal:</span> <span class="font-semibold text-red-600">${apiStats.failed_requests}</span></div>
            <hr class="my-2">
            <div class="flex justify-between"><span>Success Rate:</span> <span class="font-semibold">${apiStats.success_rate}%</span></div>
        `;

        // Render Chart
        if (apiUsageChart) {
            apiUsageChart.data.labels = data.chart_data.labels;
            apiUsageChart.data.datasets[0].data = data.chart_data.data;
            apiUsageChart.update();
        } else {
            const ctx = chartCanvas.getContext('2d');
            apiUsageChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.chart_data.labels,
                    datasets: [{
                        label: 'API Requests',
                        data: data.chart_data.data,
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
                    scales: {
                        x: {
                            grid: { display: false }
                        },
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }
    };

    refreshBtn.addEventListener('click', fetchData);

    // Initial fetch and start auto-refresh
    fetchData();
    autoRefreshInterval = setInterval(fetchData, 30000); // Refresh every 30 seconds

    // Clear interval when the user navigates away (optional, for single-page app behavior)
    // window.addEventListener('beforeunload', () => {
    //     clearInterval(autoRefreshInterval);
    // });
});
</script>
