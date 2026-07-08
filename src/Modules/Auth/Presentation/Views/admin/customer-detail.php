<?php $this->layout('layouts.admin', ['title' => 'Detail Pelanggan']) ?>

<?php $this->section('content') ?>

<div class="flex items-center gap-3 mb-5">
    <a href="/admin/customers" class="text-gray-400 hover:text-gray-600">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <h1 class="text-xl font-bold text-gray-900">Detail Pelanggan</h1>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    <!-- Info pelanggan -->
    <div class="space-y-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-14 h-14 bg-orange-100 rounded-full flex items-center justify-center shrink-0">
                    <span class="text-orange-700 font-bold text-2xl">
                        <?= strtoupper(substr($customer['name'], 0, 1)) ?>
                    </span>
                </div>
                <div>
                    <p class="font-bold text-gray-900"><?= e($customer['name']) ?></p>
                    <p class="text-sm text-gray-500"><?= e($customer['email']) ?></p>
                </div>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-400">Telepon</span>
                    <span class="text-gray-700"><?= e($customer['phone'] ?? '-') ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Status Email</span>
                    <span class="<?= $customer['email_verified_at'] ? 'text-green-600' : 'text-amber-600' ?> text-xs font-medium">
                        <?= $customer['email_verified_at'] ? 'Terverifikasi' : 'Belum Verifikasi' ?>
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Bergabung</span>
                    <span class="text-gray-700"><?= e(date('d M Y', strtotime($customer['created_at']))) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Role</span>
                    <span class="text-gray-700"><?= e($customer['role_name']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Order history -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Riwayat Order</h2>
                <a href="/admin/orders?customer_id=<?= $customer['id'] ?>" class="text-xs text-orange-600 hover:underline">
                    Lihat semua →
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500">Order</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500">Total</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
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
                        <?php foreach ($orders as $order): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3">
                                    <a href="/admin/orders/<?= $order['id'] ?>" class="font-mono text-xs text-orange-600 hover:underline">
                                        <?= e($order['order_number']) ?>
                                    </a>
                                    <p class="text-xs text-gray-400"><?= e(date('d M Y', strtotime($order['created_at']))) ?></p>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-gray-900">
                                    Rp <?= number_format($order['total'], 0, ',', '.') ?>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full <?= $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-600' ?>">
                                        <?= $statusLabels[$order['status']] ?? ucfirst($order['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="3" class="px-4 py-10 text-center text-gray-400 text-sm">
                                    Belum ada order.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php $this->endSection() ?>