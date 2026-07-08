<?php $this->layout('layouts.admin', ['title' => 'Laporan Penjualan']) ?>

<?php $this->section('content') ?>

<!-- Filter periode -->
<div class="bg-white rounded-xl border border-gray-200 p-4 mb-5">
    <form method="GET" action="/admin/reports" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Dari Tanggal</label>
            <input type="date" name="from" value="<?= e($from) ?>"
                class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Sampai Tanggal</label>
            <input type="date" name="to" value="<?= e($to) ?>"
                class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Kelompokkan per</label>
            <select name="group_by"
                class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                <option value="day" <?= $groupBy === 'day' ? 'selected' : '' ?>>Hari</option>
                <option value="week" <?= $groupBy === 'week' ? 'selected' : '' ?>>Minggu</option>
                <option value="month" <?= $groupBy === 'month' ? 'selected' : '' ?>>Bulan</option>
            </select>
        </div>
        <button type="submit"
            class="px-5 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 transition">
            Tampilkan
        </button>

        <!-- Shortcut periode -->
        <div class="flex gap-2 ml-auto">
            <?php
            $shortcuts = [
                'Hari ini'    => [date('Y-m-d'), date('Y-m-d')],
                '7 hari'      => [date('Y-m-d', strtotime('-6 days')), date('Y-m-d')],
                'Bulan ini'   => [date('Y-m-01'), date('Y-m-d')],
                'Bulan lalu'  => [date('Y-m-01', strtotime('last month')), date('Y-m-t', strtotime('last month'))],
            ];
            foreach ($shortcuts as $label => [$f, $t]):
            ?>
                <a href="/admin/reports?from=<?= $f ?>&to=<?= $t ?>&group_by=<?= e($groupBy) ?>"
                    class="px-3 py-2 text-xs font-medium <?= $from === $f && $to === $t ? 'bg-orange-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> rounded-lg transition">
                    <?= e($label) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </form>
</div>

<!-- Summary cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <p class="text-xs text-gray-400 mb-1">Total Order</p>
        <p class="text-2xl font-bold text-gray-900"><?= $summary['total_orders'] ?></p>
        <p class="text-xs text-gray-400 mt-1">Valid: <?= $summary['valid_orders'] ?> · Batal: <?= $summary['cancelled_orders'] ?></p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 col-span-2 lg:col-span-1">
        <p class="text-xs text-gray-400 mb-1">Total Revenue</p>
        <p class="text-2xl font-bold text-gray-900">Rp <?= number_format($summary['total_revenue'], 0, ',', '.') ?></p>
        <p class="text-xs text-gray-400 mt-1">Avg: Rp <?= number_format($summary['avg_order_value'], 0, ',', '.') ?></p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <p class="text-xs text-gray-400 mb-1">Total Ongkir</p>
        <p class="text-2xl font-bold text-gray-900">Rp <?= number_format($summary['total_shipping'], 0, ',', '.') ?></p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <p class="text-xs text-gray-400 mb-1">Total Diskon</p>
        <p class="text-2xl font-bold text-gray-900">Rp <?= number_format($summary['total_discount'], 0, ',', '.') ?></p>
    </div>
</div>

<!-- Grafik penjualan -->
<div class="bg-white rounded-xl border border-gray-200 p-5 mb-5">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-gray-800">Grafik Penjualan</h2>
        <a href="/admin/reports/export?from=<?= e($from) ?>&to=<?= e($to) ?>&type=sales"
            class="text-xs text-orange-600 hover:underline flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Export CSV
        </a>
    </div>
    <div class="h-56 sm:h-72">
        <canvas id="salesCanvas"></canvas>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">

    <!-- Produk terlaris -->
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-800">Produk Terlaris</h2>
            <a href="/admin/reports/export?from=<?= e($from) ?>&to=<?= e($to) ?>&type=products"
                class="text-xs text-orange-600 hover:underline">Export CSV</a>
        </div>
        <?php if (empty($productReport)): ?>
            <p class="text-sm text-gray-400 text-center py-6">Belum ada data penjualan.</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($productReport as $i => $prod): ?>
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-bold text-gray-300 w-5 shrink-0"><?= $i + 1 ?></span>
                        <div class="w-9 h-9 bg-gray-100 rounded-lg overflow-hidden shrink-0">
                            <?php if ($prod['product_image']): ?>
                                <img src="/storage/<?= e($prod['product_image']) ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

    <!-- Per kategori -->
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-800">Per Kategori</h2>
            <a href="/admin/reports/export?from=<?= e($from) ?>&to=<?= e($to) ?>&type=categories"
                class="text-xs text-orange-600 hover:underline">Export CSV</a>
        </div>
        <?php if (empty($categoryReport)): ?>
            <p class="text-sm text-gray-400 text-center py-6">Belum ada data.</p>
        <?php else: ?>
            <?php
            $maxRevenue = max(array_column($categoryReport, 'total_revenue') ?: [1]);
            ?>
            <div class="space-y-3">
                <?php foreach ($categoryReport as $cat): ?>
                    <?php $pct = $maxRevenue > 0 ? ($cat['total_revenue'] / $maxRevenue) * 100 : 0; ?>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-700 font-medium"><?= e($cat['category_name']) ?></span>
                            <span class="text-gray-900 font-bold">Rp <?= number_format($cat['total_revenue'], 0, ',', '.') ?></span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-orange-500 rounded-full transition-all"
                                style="width: <?= $pct ?>%"></div>
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5"><?= $cat['total_qty'] ?> terjual · <?= $cat['total_orders'] ?> order</p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Tabel detail penjualan -->
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">Detail per <?= $groupBy === 'month' ? 'Bulan' : ($groupBy === 'week' ? 'Minggu' : 'Hari') ?></h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Periode</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Total Order</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Order Valid</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 hidden sm:table-cell">Ongkir</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 hidden sm:table-cell">Diskon</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Revenue</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($salesChart as $row): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 font-mono text-sm text-gray-700"><?= e($row['period']) ?></td>
                        <td class="px-4 py-3 text-right text-gray-600"><?= $row['total_orders'] ?></td>
                        <td class="px-4 py-3 text-right text-green-600 font-medium"><?= $row['valid_orders'] ?></td>
                        <td class="px-4 py-3 text-right text-gray-500 hidden sm:table-cell">Rp <?= number_format($row['shipping'], 0, ',', '.') ?></td>
                        <td class="px-4 py-3 text-right text-orange-600 hidden sm:table-cell">Rp <?= number_format($row['discount'], 0, ',', '.') ?></td>
                        <td class="px-4 py-3 text-right font-bold text-gray-900">Rp <?= number_format($row['revenue'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($salesChart)): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-gray-400 text-sm">
                            Belum ada data untuk periode ini.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
(function() {
    const data    = <?= json_encode($salesChart) ?>;
    const labels  = data.map(d => d.period);
    const revenue = data.map(d => parseFloat(d.revenue));
    const orders  = data.map(d => parseInt(d.valid_orders));

    const canvas = document.getElementById('salesCanvas');
    if (!canvas) return;

    canvas.style.height = '100%';
    canvas.style.width  = '100%';

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Revenue',
                    data: revenue,
                    backgroundColor: 'rgba(249, 115, 22, 0.8)',
                    borderRadius: 4,
                    yAxisID: 'y',
                },
                {
                    label: 'Order',
                    data: orders,
                    type: 'line',
                    borderColor: '#94a3b8',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    tension: 0.4,
                    pointRadius: 3,
                    yAxisID: 'y1',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { labels: { font: { size: 11 }, boxWidth: 16 } },
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
                x: { grid: { display: false }, ticks: { font: { size: 10 }, maxTicksLimit: 12 } },
                y: {
                    position: 'left',
                    grid: { color: 'rgba(0,0,0,0.04)' },
                    ticks: {
                        font: { size: 10 },
                        callback: val => 'Rp ' + (val >= 1000000 ? (val/1000000).toFixed(1)+'jt' : val.toLocaleString('id-ID'))
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