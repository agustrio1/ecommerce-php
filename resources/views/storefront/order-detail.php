<?php $this->layout('layouts.storefront', ['title' => $title]) ?>

<?php $this->section('content') ?>

<div class="py-4 space-y-4">

    <?php if ($isNew): ?>
        <div class="p-4 bg-green-50 border border-green-200 rounded-xl text-center">
            <svg class="w-8 h-8 text-green-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="font-bold text-green-800">Order Berhasil Dibuat!</p>
            <p class="text-sm text-green-700 mt-1">Segera lakukan pembayaran sebelum batas waktu habis.</p>
        </div>
    <?php endif; ?>

    <?php $paymentError = \App\Core\Http\Session::getFlash('payment_error'); ?>
    <?php if ($paymentError): ?>
        <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl">
            <p class="font-bold text-amber-800 text-sm mb-1">Pembayaran Otomatis Gagal</p>
            <p class="text-sm text-amber-700"><?= e($paymentError) ?></p>
            <?php if (env('APP_ENV', 'production') !== 'production'): ?>
                <a href="/dev/payment-simulator"
                    class="mt-3 inline-block px-4 py-2 bg-amber-600 text-white text-sm font-semibold rounded-lg hover:bg-amber-700 transition">
                    Buka Payment Simulator
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Header order -->
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs text-gray-400">Nomor Order</p>
                <p class="font-bold text-gray-900"><?= e($order['order_number']) ?></p>
            </div>
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
                'waiting_payment' => 'Menunggu Pembayaran',
                'paid'            => 'Sudah Dibayar',
                'processing'      => 'Diproses',
                'shipped'         => 'Dikirim',
                'delivered'       => 'Terkirim',
                'completed'       => 'Selesai',
                'cancelled'       => 'Dibatalkan',
                'refunded'        => 'Direfund',
            ][$order['status']] ?? ucfirst($order['status']);
            ?>
            <span class="px-3 py-1 text-xs font-semibold rounded-full <?= $statusColor ?>">
                <?= $statusLabel ?>
            </span>
        </div>
        <p class="text-xs text-gray-400 mt-2"><?= e($order['created_at']) ?></p>
    </div>

    <!-- Instruksi pembayaran -->
    <?php if ($payment && $payment['status'] === 'pending'): ?>

        <?php if (($payment['payment_method'] ?? '') === 'redirect'): ?>
            <!-- Pembayaran via halaman resmi iPaymu (Redirect Payment) -->
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                <h2 class="font-bold text-amber-800 mb-3">Selesaikan Pembayaran</h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-amber-600">Total yang Harus Dibayar</p>
                        <p class="font-bold text-amber-900 text-xl">Rp <?= number_format($payment['amount'], 0, ',', '.') ?></p>
                    </div>
                    <?php if ($payment['expired_at']): ?>
                        <div>
                            <p class="text-xs text-amber-600">Batas Waktu Pembayaran</p>
                            <p class="font-semibold text-red-700 text-sm"><?= e($payment['expired_at']) ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (! empty($payment['ipaymu_pay_code_url'])): ?>
                        <a href="<?= e($payment['ipaymu_pay_code_url']) ?>"
                            class="block w-full text-center py-3 bg-amber-600 text-white rounded-xl font-bold text-sm hover:bg-amber-700 transition">
                            Lanjutkan ke Pembayaran
                        </a>
                        <p class="text-xs text-amber-600 text-center">
                            Kamu akan diarahkan ke halaman pembayaran resmi iPaymu.
                        </p>
                    <?php else: ?>
                        <!-- URL tidak ada (payment gagal dibuat), tampilkan opsi -->
                        <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-sm text-red-700 font-medium">Link pembayaran tidak tersedia.</p>
                            <p class="text-xs text-red-600 mt-1">Kemungkinan terjadi gangguan saat menghubungi iPaymu. Silakan gunakan payment simulator (development) atau hubungi admin.</p>
                        </div>
                        <?php if (env('APP_ENV', 'production') !== 'production'): ?>
                            <a href="/dev/payment-simulator"
                                class="block w-full text-center py-3 bg-gray-800 text-white rounded-xl font-bold text-sm hover:bg-gray-900 transition">
                                Buka Payment Simulator (DEV)
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- Direct Payment (VA langsung) -->
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                <h2 class="font-bold text-amber-800 mb-3">Instruksi Pembayaran</h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-amber-600">Metode Pembayaran</p>
                        <p class="font-semibold text-amber-900 text-sm">
                            <?= e(strtoupper($payment['payment_method'] ?? '')) ?>
                            <?= e(strtoupper($payment['payment_channel'] ?? '')) ?>
                        </p>
                    </div>
                    <?php if ($payment['ipaymu_pay_code']): ?>
                        <div>
                            <p class="text-xs text-amber-600">
                                <?= $payment['payment_method'] === 'va' ? 'Nomor Virtual Account' : 'Kode Pembayaran' ?>
                            </p>
                            <div class="flex items-center gap-2 mt-1">
                                <p class="font-bold text-amber-900 text-xl font-mono tracking-wider">
                                    <?= e($payment['ipaymu_pay_code']) ?>
                                </p>
                                <button type="button"
                                    onclick="navigator.clipboard.writeText('<?= e($payment['ipaymu_pay_code']) ?>').then(() => { this.textContent = 'Disalin'; setTimeout(() => this.textContent = 'Salin', 2000) })"
                                    class="px-2.5 py-1 bg-amber-600 text-white text-xs rounded-lg hover:bg-amber-700 transition">
                                    Salin
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div>
                        <p class="text-xs text-amber-600">Total yang Harus Dibayar</p>
                        <p class="font-bold text-amber-900 text-xl">Rp <?= number_format($payment['amount'], 0, ',', '.') ?></p>
                    </div>
                    <?php if ($payment['expired_at']): ?>
                        <div>
                            <p class="text-xs text-amber-600">Batas Waktu Pembayaran</p>
                            <p class="font-semibold text-red-700 text-sm"><?= e($payment['expired_at']) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php elseif (! $payment && in_array($order['status'], ['pending', 'waiting_payment'])): ?>
        <!-- Order ada tapi payment record tidak ada sama sekali (gagal dibuat) -->
        <div class="bg-red-50 border border-red-200 rounded-xl p-4">
            <p class="font-bold text-red-800 text-sm mb-1">Pembayaran Belum Dibuat</p>
            <p class="text-sm text-red-700">Terjadi kesalahan saat menghubungi gateway pembayaran. Order kamu sudah tersimpan.</p>
            <?php if (env('APP_ENV', 'production') !== 'production'): ?>
                <a href="/dev/payment-simulator"
                    class="mt-3 inline-block px-4 py-2 bg-gray-800 text-white text-sm font-semibold rounded-lg hover:bg-gray-900 transition">
                    Buka Payment Simulator (DEV)
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Item order -->
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <h2 class="font-semibold text-gray-800 mb-3 text-sm">Produk Dipesan</h2>
        <div class="space-y-3">
            <?php foreach ($order['items'] as $item): ?>
                <div class="flex gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800"><?= e($item['product_name']) ?></p>
                        <?php if ($item['variant_label']): ?>
                            <p class="text-xs text-gray-400"><?= e($item['variant_label']) ?></p>
                        <?php endif; ?>
                        <p class="text-xs text-gray-500">
                            <?= $item['quantity'] ?>x Rp <?= number_format($item['price'], 0, ',', '.') ?>
                        </p>
                    </div>
                    <p class="text-sm font-bold text-gray-900 shrink-0">
                        Rp <?= number_format($item['subtotal'], 0, ',', '.') ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="border-t border-gray-100 pt-3 mt-3 space-y-1.5">
            <div class="flex justify-between text-sm text-gray-500">
                <span>Subtotal Produk</span>
                <span>Rp <?= number_format($order['subtotal'], 0, ',', '.') ?></span>
            </div>
            <div class="flex justify-between text-sm text-gray-500">
                <span>Ongkir (<?= e($order['courier_company'] ?? '') ?> <?= e($order['courier_type'] ?? '') ?>)</span>
                <span>Rp <?= number_format($order['shipping_cost'], 0, ',', '.') ?></span>
            </div>
            <?php if (! empty($order['discount']) && $order['discount'] > 0): ?>
                <div class="flex justify-between text-sm text-green-600">
                    <span>Diskon<?= ! empty($order['coupon_code']) ? ' (' . e($order['coupon_code']) . ')' : '' ?></span>
                    <span>- Rp <?= number_format($order['discount'], 0, ',', '.') ?></span>
                </div>
            <?php endif; ?>
            <div class="flex justify-between font-bold text-gray-900 text-base pt-1 border-t">
                <span>Total</span>
                <span class="text-orange-600">Rp <?= number_format($order['total'], 0, ',', '.') ?></span>
            </div>
        </div>
    </div>

    <!-- Alamat pengiriman -->
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <h2 class="font-semibold text-gray-800 mb-2 text-sm">Alamat Pengiriman</h2>
        <p class="text-sm font-medium text-gray-800"><?= e($order['recipient_name']) ?></p>
        <p class="text-sm text-gray-500"><?= e($order['recipient_phone']) ?></p>
        <p class="text-sm text-gray-600 leading-relaxed mt-1">
            <?= e(implode(', ', array_filter([
                $order['shipping_address'],
                $order['shipping_district'],
                $order['shipping_city'],
                $order['shipping_province'],
                $order['shipping_postal_code'],
            ]))) ?>
        </p>
    </div>

    <!-- Status history -->
    <?php if (! empty($history)): ?>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <h2 class="font-semibold text-gray-800 mb-3 text-sm">Riwayat Status</h2>
            <div class="space-y-3">
                <?php foreach (array_reverse($history) as $h): ?>
                    <div class="flex gap-3">
                        <div class="w-2 h-2 bg-orange-500 rounded-full shrink-0 mt-1.5"></div>
                        <div>
                            <p class="text-sm font-medium text-gray-800">
                                <?= e(ucwords(str_replace('_', ' ', $h['status']))) ?>
                            </p>
                            <?php if ($h['note']): ?>
                                <p class="text-xs text-gray-400"><?= e($h['note']) ?></p>
                            <?php endif; ?>
                            <p class="text-xs text-gray-300"><?= e($h['created_at']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <a href="/orders" class="block text-center text-sm text-orange-600 hover:underline py-2">
        ← Lihat semua pesanan
    </a>
</div>

<?php $this->endSection() ?>
