<?php
// admin_pages/dashboard.php
if (!isset($_SESSION['is_admin'])) { die('Akses ditolak'); }

// Menggunakan fungsi statistik baru
$basic_stats = getDashboardStats(); 
$adv_stats = getAdvancedDashboardStats();
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<h1 class="text-3xl font-bold text-slate-800 mb-6">Dashboard Analitik</h1>

<!-- Kartu Statistik Utama -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white p-6 rounded-2xl shadow-lg flex items-center gap-4 card-hover border border-slate-100">
        <div class="bg-gradient-to-br from-blue-400 to-blue-600 p-3 rounded-xl shadow-lg text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.53 0 1.04.21 1.41.59L17 7h3a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h3.172a2 2 0 011.414.586l.828.828A2 2 0 009.828 7h4.344a2 2 0 011.414.586l.828.828A2 2 0 0017.828 9H19a2 2 0 012 2v2"></path></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-slate-500">Total API Keys</p>
            <p class="text-2xl font-bold text-slate-800"><?= e($basic_stats['total_keys']) ?></p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-lg flex items-center gap-4 card-hover border border-slate-100">
        <div class="bg-gradient-to-br from-green-400 to-green-600 p-3 rounded-xl shadow-lg text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-slate-500">Hits Hari Ini</p>
            <p class="text-2xl font-bold text-slate-800"><?= e($adv_stats['hits_today']) ?></p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-lg flex items-center gap-4 card-hover border border-slate-100">
        <div class="bg-gradient-to-br from-yellow-400 to-yellow-600 p-3 rounded-xl shadow-lg text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"></path></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-slate-500">Hits 7 Hari</p>
            <p class="text-2xl font-bold text-slate-800"><?= e($adv_stats['hits_week']) ?></p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-lg flex items-center gap-4 card-hover border border-slate-100">
        <div class="bg-gradient-to-br from-purple-400 to-purple-600 p-3 rounded-xl shadow-lg text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-slate-500">Hits Bulan Ini</p>
            <p class="text-2xl font-bold text-slate-800"><?= e($adv_stats['hits_month']) ?></p>
        </div>
    </div>
</div>

<!-- Grafik dan Pengguna Teratas -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Kolom Utama: Grafik -->
    <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-lg border border-slate-100">
        <h2 class="text-xl font-bold text-slate-800 mb-4">Aktivitas Penggunaan (30 Hari Terakhir)</h2>
        <div class="h-80">
            <canvas id="usageChart"></canvas>
        </div>
    </div>

    <!-- Kolom Samping: Pengguna Teratas -->
    <div class="bg-white p-6 rounded-2xl shadow-lg border border-slate-100">
        <h2 class="text-xl font-bold text-slate-800 mb-4">Pengguna Teratas</h2>
        <div class="space-y-4">
            <?php if (empty($adv_stats['top_keys'])): ?>
                <p class="text-slate-500 text-sm">Belum ada data penggunaan.</p>
            <?php else: 
                $max_hits = max(array_column($adv_stats['top_keys'], 'total_hits'));
                foreach ($adv_stats['top_keys'] as $key_data): 
                    $percentage = $max_hits > 0 ? ($key_data['total_hits'] / $max_hits) * 100 : 0;
            ?>
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <p class="text-sm font-semibold text-slate-700"><?= e($key_data['nama'] ?: 'Tanpa Nama') ?></p>
                        <p class="text-sm font-bold text-slate-600"><?= e($key_data['total_hits']) ?> hits</p>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-2">
                        <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-2 rounded-full" style="width: <?= $percentage ?>%"></div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('usageChart').getContext('2d');
    
    // Membuat gradient untuk area chart
    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(79, 70, 229, 0.4)');
    gradient.addColorStop(1, 'rgba(79, 70, 229, 0)');

    const chartData = {
        labels: <?= json_encode($adv_stats['chart']['labels']) ?>,
        datasets: [{
            label: 'Total Hits',
            data: <?= json_encode($adv_stats['chart']['data']) ?>,
            borderColor: '#4F46E5',
            backgroundColor: gradient,
            borderWidth: 2,
            pointBackgroundColor: '#4F46E5',
            pointBorderColor: '#ffffff',
            pointHoverBackgroundColor: '#ffffff',
            pointHoverBorderColor: '#4F46E5',
            tension: 0.4,
            fill: true,
        }]
    };

    const config = {
        type: 'line',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#e2e8f0',
                        borderDash: [5, 5],
                    },
                    ticks: {
                        color: '#64748b',
                        font: {
                            family: "'Inter', sans-serif",
                        },
                        // Hanya tampilkan integer
                        callback: function(value) {
                            if (Math.floor(value) === value) {
                                return value;
                            }
                        }
                    }
                },
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        color: '#64748b',
                        font: {
                            family: "'Inter', sans-serif",
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { size: 14, family: "'Inter', sans-serif" },
                    bodyFont: { size: 12, family: "'Inter', sans-serif" },
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: false,
                }
            },
            interaction: {
                intersect: false,
                mode: 'index',
            },
        }
    };

    new Chart(ctx, config);
});
</script>
