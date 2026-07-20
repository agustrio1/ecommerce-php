<?php $this->layout('layouts.storefront', ['title' => 'Pesanan Saya']) ?>

<?php $this->section('content') ?>

<?php require_once __DIR__ . '/_brand.php'; ?>
<?php $brand = nexaroBrandTokens(); ?>

<div class="py-4">
    <h1 class="font-bold text-lg mb-4" style="color: <?= e($brand['ink']) ?>;">Pesanan Saya</h1>

    <?php if (empty($orders)): ?>
        <div class="text-center py-16">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-20" fill="none" stroke="<?= e($brand['ink']) ?>" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-gray-400 text-sm mb-3">Belum ada pesanan</p>
            <a href="/produk" class="px-5 py-2 text-white rounded-xl text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                style="background-color: <?= e($brand['clay']) ?>;">
                Mulai Belanja
            </a>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($orders as $order): ?>
                <?php $statusMeta = orderStatusMeta($order['status'], $brand); ?>
                <a href="/orders/<?= e($order['order_number']) ?>/payment"
                    class="block bg-white rounded-xl border p-4 hover:shadow-md transition"
                    style="border-color: <?= e($brand['line']) ?>;">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <p class="text-sm font-bold" style="color: <?= e($brand['ink']) ?>;"><?= e($order['order_number']) ?></p>
                            <p class="text-xs text-gray-400"><?= e($order['created_at']) ?></p>
                        </div>
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full text-white" style="background-color: <?= e($statusMeta['color']) ?>;">
                            <?= e($statusMeta['label']) ?>
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <p class="text-xs text-gray-500">
                            <?= e($order['courier_company'] ?? '-') ?> · <?= e($order['courier_type'] ?? '') ?>
                        </p>
                        <p class="font-bold text-sm" style="color: <?= e($brand['clay']) ?>;">
                            Rp <?= number_format($order['total'], 0, ',', '.') ?>
                        </p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php $this->endSection() ?>