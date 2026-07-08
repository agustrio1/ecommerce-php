<?php $this->layout('layouts.admin', ['title' => 'Kelola Order']) ?>

<?php $this->section('content') ?>

<?php
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
?>

<div class="flex flex-wrap justify-between items-center gap-3 mb-5">
    <h1 class="text-xl font-bold text-gray-900">Kelola Order</h1>
    <p class="text-sm text-gray-400"><?= $total ?> order total</p>
    <a href="/admin/export/inventory?date_from=<?= date('Y-m-01') ?>&date_to=<?= date('Y-m-d') ?>"
        class="px-3 py-2 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-200 transition flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        Export CSV
    </a>
</div>


<!-- Filter & Search -->
<form method="GET" action="/admin/orders" class="flex flex-col sm:flex-row gap-2 mb-5">
    <div class="relative flex-1">
        <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <input type="text" name="search" value="<?= e($search) ?>"
            placeholder="Cari nomor order, nama, telepon..."
            class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">
    </div>
    <select name="status" class="w-full sm:w-48 px-3 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">
        <option value="">Semua Status</option>
        <?php foreach ($statuses as $value => $label): ?>
            <option value="<?= e($value) ?>" <?= $statusFilter === $value ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="w-full sm:w-auto px-5 py-2 bg-gray-800 text-white text-sm rounded-xl font-medium hover:bg-gray-900 transition">
        Filter
    </button>
</form>

<!-- Tabel order -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Order</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Penerima</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">Kurir</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Total</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($orders as $order): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3">
                            <p class="font-mono font-semibold text-gray-800 text-xs"><?= e($order['order_number']) ?></p>
                            <p class="text-xs text-gray-400"><?= e(date('d M Y H:i', strtotime($order['created_at']))) ?></p>
                            <p class="text-xs text-gray-400"><?= $order['item_count'] ?> item</p>
                            <!-- Mobile: info yang hidden di desktop -->
                            <p class="text-xs text-gray-500 md:hidden mt-0.5"><?= e($order['recipient_name']) ?></p>
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell">
                            <p class="text-sm text-gray-800"><?= e($order['recipient_name']) ?></p>
                            <p class="text-xs text-gray-400"><?= e($order['recipient_phone']) ?></p>
                        </td>
                        <td class="px-4 py-3 hidden sm:table-cell">
                            <p class="text-sm text-gray-700"><?= e(strtoupper($order['courier_company'] ?? '-')) ?></p>
                            <p class="text-xs text-gray-400"><?= e($order['courier_type'] ?? '') ?></p>
                            <?php if ($order['waybill_id']): ?>
                                <p class="text-xs font-mono text-green-700 mt-0.5"><?= e($order['waybill_id']) ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-bold text-gray-900">Rp <?= number_format($order['total'], 0, ',', '.') ?></p>
                            <?php if ($order['payment_status']): ?>
                                <span class="text-xs <?= $order['payment_status'] === 'paid' ? 'text-green-600' : 'text-amber-600' ?>">
                                    <?= e(ucfirst($order['payment_status'])) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full <?= $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-600' ?>">
                                <?= e($statuses[$order['status']] ?? ucfirst($order['status'])) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="/admin/orders/<?= $order['id'] ?>"
                                class="px-3 py-1.5 text-xs font-medium text-orange-600 bg-orange-50 rounded-lg hover:bg-orange-100 transition">
                                Detail
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-16 text-center">
                            <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p class="text-gray-400 text-sm">Belum ada order</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (! empty($orders)): ?>
        <div class="px-5 py-3 border-t bg-gray-50 text-xs text-gray-400 flex justify-between items-center">
            <span><?= count($orders) ?> dari <?= $total ?> order</span>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php $totalPages = (int) ceil($total / $perPage); ?>
<?php if ($totalPages > 1): ?>
    <div class="flex items-center justify-center gap-1.5 mt-5">
        <?php if ($page > 1): ?>
            <a href="/admin/orders?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>"
                class="px-3 py-1.5 text-sm bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition">←</a>
        <?php endif; ?>

        <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
            <a href="/admin/orders?page=<?= $p ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>"
                class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium
                       <?= $p === $page ? 'bg-orange-600 text-white' : 'bg-white border border-gray-200 hover:bg-gray-50' ?>">
                <?= $p ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="/admin/orders?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>"
                class="px-3 py-1.5 text-sm bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition">→</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php $this->endSection() ?>