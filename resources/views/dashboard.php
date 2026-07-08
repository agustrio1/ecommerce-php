<?php $this->layout('layouts.admin', ['title' => 'Dashboard']) ?>

<?php $this->section('content') ?>

<?php
$dashboardService = new \App\Modules\Dashboard\Application\Services\DashboardService();
$stats     = $dashboardService->getSummaryStats();
$chart     = $dashboardService->getRevenueChart(30);
$topProds  = $dashboardService->getTopProducts(5);
$recent    = $dashboardService->getRecentOrders(8);
$custStats = $dashboardService->getCustomerStats();

$statusColors = [
    'pending'         => 'bg-gray-100 text-gray-600',
    'waiting_payment' => 'bg-amber-100 text-amber-700',
    'paid'            => 'bg-green-100 text-green-700',
    'processing'      => 'bg-blue-100 text-blue-700',
    'shipped'         => 'bg-indigo-100 text-indigo-700',
    'delivered'       => 'bg-teal-100 text-teal-700',
    'completed'       => 'bg-emerald-100 text-emerald-700',
    'cancelled'       => 'bg-red-100 text-red-700',
    'refunded'        => 'bg-pink-100 text-pink-700',
];
$statusLabels = [
    'pending'         => 'Menunggu',
    'waiting_payment' => 'Belum Bayar',
    'paid'            => 'Dibayar',
    'processing'      => 'Diproses',
    'shipped'         => 'Dikirim',
    'delivered'       => 'Terkirim',
    'completed'       => 'Selesai',
    'cancelled'       => 'Dibatalkan',
    'refunded'        => 'Direfund',
];
?>

<!-- ===== STAT CARDS ===== -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <!-- Revenue bulan ini -->
    <div class="bg-white rounded-xl border border-gray-200 p-5 col-span-2 lg:col-span-1">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Revenue Bulan Ini</p>
            <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= $stats['revenue_growth'] >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                <?= $stats['revenue_growth'] >= 0 ? '+' : '' ?><?= $stats['revenue_growth'] ?>%
            </span>
        </div>
        <p class="text-2xl font-bold text-gray-900">
            Rp <?= number_format($stats['revenue_this_month'], 0, ',', '.') ?>
        </p>
        <p class="text-xs text-gray-400 mt-1">
            Bulan lalu: Rp <?= number_format($stats['revenue_last_month'], 0, ',', '.') ?>
        </p>
    </div>

    <!-- Order bulan ini -->
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-3">Order Bulan Ini</p>
        <p class="text-2xl font-bold text-gray-900"><?= $stats['orders_this_month'] ?></p>
        <p class="text-xs text-gray-400 mt-1">Total: <?= $stats['total_orders'] ?></p>
    </div>

    <!-- Perlu diproses -->
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-3">Perlu Diproses</p>
        <p class="text-2xl font-bold <?= $stats['to_process'] > 0 ? 'text-orange-600' : 'text-gray-900' ?>">
            <?= $stats['to_process'] ?>
        </p>
        <a href="/admin/orders?status=paid" class="text-xs text-orange-600 hover:underline mt-1 block">Lihat order →</a>
    </div>

    <!-- Stok menipis -->
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-3">Stok Menipis</p>
        <p class="text-2xl font-bold <?= $stats['low_stock_count'] > 0 ? 'text-red-600' : 'text-gray-900' ?>">
            <?= $stats['low_stock_count'] ?>
        </p>
        <a href="/admin/inventory?low_stock=1" class="text-xs text-orange-600 hover:underline mt-1 block">Cek inventori →</a>
    </div>
</div>

<!-- ===== GRAFIK REVENUE ===== -->
<div class="bg-white rounded-xl border border-gray-200 p-5 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-gray-800">Revenue 30 Hari Terakhir</h2>
        <p class="text-xs text-gray-400">Total: Rp <?= number_format($stats['total_revenue'], 0, ',', '.') ?></p>
    </div>
    <div class="w-full h-48 sm:h-64 relative">
        <canvas id="revenueCanvas"></canvas>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    <!-- Produk terlaris -->
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="font-semibold text-gray-800 mb-4">Produk Terlaris</h2>
        <?php if (empty($topProds)): ?>
            <p class="text-sm text-gray-400 text-center py-6">Belum ada data penjualan.</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($topProds as $i => $prod): ?>
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-bold text-gray-300 w-5 shrink-0"><?= $i + 1 ?></span>
                        <div class="w-10 h-10 bg-gray-100 rounded-lg overflow-hidden shrink-0">
                            <?php if ($prod['product_image']): ?>
                                <img src="/storage/<?= e($prod['product_image']) ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate"><?= e($prod['product_name']) ?></p>
                            <p class="text-xs text-gray-400"><?= $prod['total_qty'] ?> terjual · <?= $prod['total_orders'] ?> order</p>
                        </div>
                        <p class="text-sm font-bold text-gray-900 shrink-0">
                            Rp <?= number_format($prod['total_revenue'], 0, ',', '.') ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Kanan: customer + perlu perhatian -->
    <div class="space-y-5">

        <!-- Customer stats -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="font-semibold text-gray-800 mb-4">Pelanggan</h2>
            <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-3 bg-gray-50 rounded-xl">
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['total_customers'] ?></p>
                    <p class="text-xs text-gray-400 mt-1">Total Pelanggan</p>
                </div>
                <div class="text-center p-3 bg-orange-50 rounded-xl">
                    <p class="text-2xl font-bold text-orange-600"><?= $custStats['new_this_month'] ?></p>
                    <p class="text-xs text-gray-400 mt-1">Baru Bulan Ini</p>
                </div>
            </div>
        </div>

        <!-- Perlu perhatian -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="font-semibold text-gray-800 mb-3">Perlu Perhatian</h2>
            <div class="space-y-2">

                <?php if ($stats['pending_payment'] > 0): ?>
                    <a href="/admin/orders?status=waiting_payment"
                        class="flex items-center justify-between p-3 bg-amber-50 border border-amber-100 rounded-lg hover:border-amber-300 transition">
                        <span class="flex items-center gap-2 text-sm text-amber-700">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Menunggu Pembayaran
                        </span>
                        <span class="font-bold text-amber-700"><?= $stats['pending_payment'] ?></span>
                    </a>
                <?php endif; ?>

                <?php if ($stats['to_process'] > 0): ?>
                    <a href="/admin/orders?status=paid"
                        class="flex items-center justify-between p-3 bg-green-50 border border-green-100 rounded-lg hover:border-green-300 transition">
                        <span class="flex items-center gap-2 text-sm text-green-700">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                            </svg>
                            Siap Diproses
                        </span>
                        <span class="font-bold text-green-700"><?= $stats['to_process'] ?></span>
                    </a>
                <?php endif; ?>

                <?php if ($stats['low_stock_count'] > 0): ?>
                    <a href="/admin/inventory?low_stock=1"
                        class="flex items-center justify-between p-3 bg-red-50 border border-red-100 rounded-lg hover:border-red-300 transition">
                        <span class="flex items-center gap-2 text-sm text-red-700">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            Stok Menipis
                        </span>
                        <span class="font-bold text-red-700"><?= $stats['low_stock_count'] ?></span>
                    </a>
                <?php endif; ?>

                <?php if ($stats['pending_payment'] === 0 && $stats['to_process'] === 0 && $stats['low_stock_count'] === 0): ?>
                    <div class="flex items-center justify-center gap-2 py-4 text-sm text-gray-400">
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Semua beres!
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<!-- Order terbaru -->
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">Order Terbaru</h2>
        <a href="/admin/orders" class="text-xs text-orange-600 hover:underline">Lihat semua →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500">Order</th>
                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 hidden sm:table-cell">Penerima</th>
                    <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500">Total</th>
                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($recent as $order): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3">
                            <a href="/admin/orders/<?= $order['id'] ?>" class="font-mono text-xs text-orange-600 hover:underline">
                                <?= e($order['order_number']) ?>
                            </a>
                            <p class="text-xs text-gray-400"><?= e(date('d M H:i', strtotime($order['created_at']))) ?></p>
                        </td>
                        <td class="px-4 py-3 hidden sm:table-cell text-sm text-gray-700">
                            <?= e($order['recipient_name'] ?? '-') ?>
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-gray-900 text-sm">
                            Rp <?= number_format($order['total'], 0, ',', '.') ?>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full <?= $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-600' ?>">
                                <?= $statusLabels[$order['status']] ?? ucfirst($order['status']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($recent)): ?>
                    <tr>
                        <td colspan="4" class="px-4 py-12 text-center text-gray-400 text-sm">Belum ada order.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
(function() {
    const chartData = <?= json_encode($chart) ?>;

    const labels  = chartData.map(d => d.date_label);
    const revenue = chartData.map(d => d.revenue);
    const orders  = chartData.map(d => d.order_count);

    const canvas = document.getElementById('revenueCanvas');
    if (! canvas) return;

    canvas.height = null;
    canvas.style.height = '100%';
    canvas.style.width = '100%';

    new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Revenue',
                    data: revenue,
                    borderColor: '#f97316',
                    backgroundColor: 'rgba(249, 115, 22, 0.08)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 2,
                    pointHoverRadius: 5,
                    yAxisID: 'y',
                },
                {
                    label: 'Order',
                    data: orders,
                    borderColor: '#94a3b8',
                    backgroundColor: 'transparent',
                    borderWidth: 1.5,
                    borderDash: [4, 4],
                    tension: 0.4,
                    fill: false,
                    pointRadius: 2,
                    pointHoverRadius: 4,
                    yAxisID: 'y1',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    labels: { font: { size: 11 }, boxWidth: 20 }
                },
                tooltip: {
                    callbacks: {
                        label(ctx) {
                            if (ctx.datasetIndex === 0) {
                                return 'Revenue: Rp ' + ctx.parsed.y.toLocaleString('id-ID');
                            }
                            return 'Order: ' + ctx.parsed.y;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 10 }, maxTicksLimit: 10 }
                },
                y: {
                    position: 'left',
                    grid: { color: 'rgba(0,0,0,0.04)' },
                    ticks: {
                        font: { size: 10 },
                        callback: val => 'Rp ' + (val >= 1000000
                            ? (val / 1000000).toFixed(1) + 'jt'
                            : val.toLocaleString('id-ID'))
                    }
                },
                y1: {
                    position: 'right',
                    grid: { display: false },
                    ticks: { font: { size: 10 } }
                }
            }
        }
    });
})();
</script>

<?php $this->endSection() ?>