<?php $this->layout('layouts.storefront', ['title' => $title]) ?>

<?php $this->section('content') ?>

<?php require_once __DIR__ . '/_brand.php'; ?>
<?php $brand = nexaroBrandTokens(); ?>

<div class="py-4 space-y-4">

    <?php if ($isNew): ?>
        <div class="p-4 rounded-xl text-center border" style="background-color: #EEF0EA; border-color: <?= e($brand['moss']) ?>;">
            <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="<?= e($brand['moss']) ?>" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="font-bold" style="color: <?= e($brand['ink']) ?>;">Order Berhasil Dibuat!</p>
            <p class="text-sm mt-1" style="color: <?= e($brand['ink']) ?>;">Segera lakukan pembayaran sebelum batas waktu habis.</p>
        </div>
    <?php endif; ?>

    <?php $paymentError = \App\Core\Http\Session::getFlash('payment_error'); ?>
    <?php if ($paymentError): ?>
        <div class="p-4 rounded-xl border" style="background-color: #F7EEDF; border-color: <?= e($brand['warning']) ?>;">
            <p class="font-bold text-sm mb-1" style="color: <?= e($brand['warning']) ?>;">Pembayaran Otomatis Gagal</p>
            <p class="text-sm" style="color: <?= e($brand['ink']) ?>;"><?= e($paymentError) ?></p>
            <?php if (env('APP_ENV', 'production') !== 'production'): ?>
                <a href="/dev/payment-simulator"
                    class="mt-3 inline-block px-4 py-2 text-white text-sm font-semibold rounded-lg transition"
                    style="background-color: <?= e($brand['warning']) ?>;">
                    Buka Payment Simulator
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Header order -->
    <div class="bg-white rounded-xl border p-4" style="border-color: <?= e($brand['line']) ?>;">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.15em]" style="color: <?= e($brand['moss']) ?>;">Nomor Order</p>
                <p class="font-bold" style="color: <?= e($brand['ink']) ?>;"><?= e($order['order_number']) ?></p>
            </div>
            <?php $statusMeta = orderStatusMeta($order['status'], $brand); ?>
            <span class="px-3 py-1 text-xs font-semibold rounded-full text-white" style="background-color: <?= e($statusMeta['color']) ?>;">
                <?= e($statusMeta['label']) ?>
            </span>
        </div>
        <p class="text-xs text-gray-400 mt-2"><?= e($order['created_at']) ?></p>
    </div>

    <!-- Instruksi pembayaran -->
    <?php if ($payment && $payment['status'] === 'pending'): ?>

        <?php if (($payment['payment_method'] ?? '') === 'redirect'): ?>
            <!-- Pembayaran via halaman resmi iPaymu (Redirect Payment) -->
            <div class="rounded-xl p-4 border" style="background-color: #F7EEDF; border-color: <?= e($brand['warning']) ?>;">
                <p class="text-[11px] font-semibold uppercase tracking-[0.15em] mb-1" style="color: <?= e($brand['warning']) ?>;">Aksi Diperlukan</p>
                <h2 class="font-bold mb-3" style="color: <?= e($brand['ink']) ?>;">Selesaikan Pembayaran</h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs" style="color: <?= e($brand['warning']) ?>;">Total yang Harus Dibayar</p>
                        <p class="font-bold text-xl" style="color: <?= e($brand['ink']) ?>;">Rp <?= number_format($payment['amount'], 0, ',', '.') ?></p>
                    </div>
                    <?php if ($payment['expired_at']): ?>
                        <div>
                            <p class="text-xs" style="color: <?= e($brand['warning']) ?>;">Batas Waktu Pembayaran</p>
                            <p class="font-semibold text-sm" style="color: <?= e($brand['urgent']) ?>;"><?= e($payment['expired_at']) ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (! empty($payment['ipaymu_pay_code_url'])): ?>
                        <a href="<?= e($payment['ipaymu_pay_code_url']) ?>"
                            class="block w-full text-center py-3 text-white rounded-xl font-bold text-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                            style="background-color: <?= e($brand['clay']) ?>;">
                            Lanjutkan ke Pembayaran
                        </a>
                        <p class="text-xs text-center" style="color: <?= e($brand['warning']) ?>;">
                            Kamu akan diarahkan ke halaman pembayaran resmi iPaymu.
                        </p>
                    <?php else: ?>
                        <div class="p-3 rounded-lg border" style="background-color: #FBEAE6; border-color: <?= e($brand['urgent']) ?>;">
                            <p class="text-sm font-medium" style="color: <?= e($brand['urgent']) ?>;">Link pembayaran tidak tersedia.</p>
                            <p class="text-xs mt-1" style="color: <?= e($brand['ink']) ?>;">Kemungkinan terjadi gangguan saat menghubungi iPaymu. Silakan gunakan payment simulator (development) atau hubungi admin.</p>
                        </div>
                        <?php if (env('APP_ENV', 'production') !== 'production'): ?>
                            <a href="/dev/payment-simulator"
                                class="block w-full text-center py-3 text-white rounded-xl font-bold text-sm transition"
                                style="background-color: <?= e($brand['ink']) ?>;">
                                Buka Payment Simulator (DEV)
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- Direct Payment (VA langsung) -->
            <div class="rounded-xl p-4 border" style="background-color: #F7EEDF; border-color: <?= e($brand['warning']) ?>;">
                <p class="text-[11px] font-semibold uppercase tracking-[0.15em] mb-1" style="color: <?= e($brand['warning']) ?>;">Aksi Diperlukan</p>
                <h2 class="font-bold mb-3" style="color: <?= e($brand['ink']) ?>;">Instruksi Pembayaran</h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs" style="color: <?= e($brand['warning']) ?>;">Metode Pembayaran</p>
                        <p class="font-semibold text-sm" style="color: <?= e($brand['ink']) ?>;">
                            <?= e(strtoupper($payment['payment_method'] ?? '')) ?>
                            <?= e(strtoupper($payment['payment_channel'] ?? '')) ?>
                        </p>
                    </div>
                    <?php if ($payment['ipaymu_pay_code']): ?>
                        <div>
                            <p class="text-xs" style="color: <?= e($brand['warning']) ?>;">
                                <?= $payment['payment_method'] === 'va' ? 'Nomor Virtual Account' : 'Kode Pembayaran' ?>
                            </p>
                            <div class="flex items-center gap-2 mt-1">
                                <p class="font-bold text-xl font-mono tracking-wider" style="color: <?= e($brand['ink']) ?>;">
                                    <?= e($payment['ipaymu_pay_code']) ?>
                                </p>
                                <button type="button"
                                    onclick="navigator.clipboard.writeText('<?= e($payment['ipaymu_pay_code']) ?>').then(() => { this.textContent = 'Disalin'; setTimeout(() => this.textContent = 'Salin', 2000) })"
                                    class="px-2.5 py-1 text-white text-xs rounded-lg transition focus:outline-none focus-visible:ring-2"
                                    style="background-color: <?= e($brand['clay']) ?>;">
                                    Salin
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div>
                        <p class="text-xs" style="color: <?= e($brand['warning']) ?>;">Total yang Harus Dibayar</p>
                        <p class="font-bold text-xl" style="color: <?= e($brand['ink']) ?>;">Rp <?= number_format($payment['amount'], 0, ',', '.') ?></p>
                    </div>
                    <?php if ($payment['expired_at']): ?>
                        <div>
                            <p class="text-xs" style="color: <?= e($brand['warning']) ?>;">Batas Waktu Pembayaran</p>
                            <p class="font-semibold text-sm" style="color: <?= e($brand['urgent']) ?>;"><?= e($payment['expired_at']) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php elseif (! $payment && in_array($order['status'], ['pending', 'waiting_payment'])): ?>
        <div class="rounded-xl p-4 border" style="background-color: #FBEAE6; border-color: <?= e($brand['urgent']) ?>;">
            <p class="font-bold text-sm mb-1" style="color: <?= e($brand['urgent']) ?>;">Pembayaran Belum Dibuat</p>
            <p class="text-sm" style="color: <?= e($brand['ink']) ?>;">Terjadi kesalahan saat menghubungi gateway pembayaran. Order kamu sudah tersimpan.</p>
            <?php if (env('APP_ENV', 'production') !== 'production'): ?>
                <a href="/dev/payment-simulator"
                    class="mt-3 inline-block px-4 py-2 text-white text-sm font-semibold rounded-lg transition"
                    style="background-color: <?= e($brand['ink']) ?>;">
                    Buka Payment Simulator (DEV)
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Item order -->
    <div class="bg-white rounded-xl border p-4" style="border-color: <?= e($brand['line']) ?>;">
        <p class="text-[11px] font-semibold uppercase tracking-[0.15em] mb-1" style="color: <?= e($brand['moss']) ?>;">Pesanan</p>
        <h2 class="font-semibold mb-3 text-sm" style="color: <?= e($brand['ink']) ?>;">Produk Dipesan</h2>
        <div class="space-y-3">
            <?php foreach ($order['items'] as $item): ?>
                <div class="flex gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium" style="color: <?= e($brand['ink']) ?>;"><?= e($item['product_name']) ?></p>
                        <?php if ($item['variant_label']): ?>
                            <p class="text-xs text-gray-400"><?= e($item['variant_label']) ?></p>
                        <?php endif; ?>
                        <p class="text-xs text-gray-500">
                            <?= $item['quantity'] ?>x Rp <?= number_format($item['price'], 0, ',', '.') ?>
                        </p>
                    </div>
                    <p class="text-sm font-bold shrink-0" style="color: <?= e($brand['ink']) ?>;">
                        Rp <?= number_format($item['subtotal'], 0, ',', '.') ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="border-t pt-3 mt-3 space-y-1.5" style="border-color: <?= e($brand['line']) ?>;">
            <div class="flex justify-between text-sm text-gray-500">
                <span>Subtotal Produk</span>
                <span>Rp <?= number_format($order['subtotal'], 0, ',', '.') ?></span>
            </div>
            <div class="flex justify-between text-sm text-gray-500">
                <span>Ongkir (<?= e($order['courier_company'] ?? '') ?> <?= e($order['courier_type'] ?? '') ?>)</span>
                <span>Rp <?= number_format($order['shipping_cost'], 0, ',', '.') ?></span>
            </div>
            <?php if (! empty($order['discount']) && $order['discount'] > 0): ?>
                <div class="flex justify-between text-sm" style="color: <?= e($brand['moss']) ?>;">
                    <span>Diskon<?= ! empty($order['coupon_code']) ? ' (' . e($order['coupon_code']) . ')' : '' ?></span>
                    <span>- Rp <?= number_format($order['discount'], 0, ',', '.') ?></span>
                </div>
            <?php endif; ?>
            <div class="flex justify-between font-bold text-base pt-1 border-t" style="border-color: <?= e($brand['line']) ?>; color: <?= e($brand['ink']) ?>;">
                <span>Total</span>
                <span style="color: <?= e($brand['clay']) ?>;">Rp <?= number_format($order['total'], 0, ',', '.') ?></span>
            </div>
        </div>
    </div>

    <!-- Alamat pengiriman -->
    <div class="bg-white rounded-xl border p-4" style="border-color: <?= e($brand['line']) ?>;">
        <p class="text-[11px] font-semibold uppercase tracking-[0.15em] mb-1" style="color: <?= e($brand['moss']) ?>;">Kirim Ke</p>
        <h2 class="font-semibold mb-2 text-sm" style="color: <?= e($brand['ink']) ?>;">Alamat Pengiriman</h2>
        <p class="text-sm font-medium" style="color: <?= e($brand['ink']) ?>;"><?= e($order['recipient_name']) ?></p>
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
        <div class="bg-white rounded-xl border p-4" style="border-color: <?= e($brand['line']) ?>;">
            <p class="text-[11px] font-semibold uppercase tracking-[0.15em] mb-1" style="color: <?= e($brand['moss']) ?>;">Riwayat</p>
            <h2 class="font-semibold mb-3 text-sm" style="color: <?= e($brand['ink']) ?>;">Riwayat Status</h2>
            <div class="space-y-3">
                <?php foreach (array_reverse($history) as $h): ?>
                    <?php $hMeta = orderStatusMeta($h['status'], $brand); ?>
                    <div class="flex gap-3">
                        <div class="w-2 h-2 rounded-full shrink-0 mt-1.5" style="background-color: <?= e($hMeta['color']) ?>;"></div>
                        <div>
                            <p class="text-sm font-medium" style="color: <?= e($brand['ink']) ?>;">
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

    <a href="/orders" class="block text-center text-sm hover:underline py-2" style="color: <?= e($brand['clay']) ?>;">
        ← Lihat semua pesanan
    </a>
</div>

<?php $this->endSection() ?>