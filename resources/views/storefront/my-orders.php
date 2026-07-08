<?php $this->layout('layouts.storefront', ['title' => 'Pesanan Saya']) ?>

<?php $this->section('content') ?>

<div class="py-4">
    <h1 class="font-bold text-gray-900 text-lg mb-4">Pesanan Saya</h1>

    <?php if (empty($orders)): ?>
        <div class="text-center py-16">
            <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-gray-400 text-sm mb-3">Belum ada pesanan</p>
            <a href="/produk" class="px-5 py-2 bg-orange-600 text-white rounded-xl text-sm font-medium hover:bg-orange-700 transition">
                Mulai Belanja
            </a>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($orders as $order): ?>
                <?php
                $statusColor = match ($order['status']) {
                    'paid', 'delivered', 'completed' => 'bg-green-100 text-green-700',
                    'shipped', 'processing'           => 'bg-blue-100 text-blue-700',
                    'cancelled', 'refunded'           => 'bg-red-100 text-red-700',
                    'waiting_payment'                 => 'bg-amber-100 text-amber-700',
                    default                           => 'bg-gray-100 text-gray-600',
                };
                $statusLabel = [
                    'pending'         => 'Menunggu',
                    'waiting_payment' => 'Belum Dibayar',
                    'paid'            => 'Dibayar',
                    'processing'      => 'Diproses',
                    'shipped'         => 'Dikirim',
                    'delivered'       => 'Terkirim',
                    'completed'       => 'Selesai',
                    'cancelled'       => 'Dibatalkan',
                    'refunded'        => 'Direfund',
                ][$order['status']] ?? ucfirst($order['status']);
                ?>
                <a href="/orders/<?= e($order['order_number']) ?>/payment"
                    class="block bg-white rounded-xl border border-gray-100 p-4 hover:shadow-md hover:border-orange-200 transition">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <p class="text-sm font-bold text-gray-900"><?= e($order['order_number']) ?></p>
                            <p class="text-xs text-gray-400"><?= e($order['created_at']) ?></p>
                        </div>
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full <?= $statusColor ?>">
                            <?= $statusLabel ?>
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <p class="text-xs text-gray-500">
                            <?= e($order['courier_company'] ?? '-') ?> · <?= e($order['courier_type'] ?? '') ?>
                        </p>
                        <p class="font-bold text-orange-600 text-sm">
                            Rp <?= number_format($order['total'], 0, ',', '.') ?>
                        </p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php $this->endSection() ?>