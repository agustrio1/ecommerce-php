<?php $this->layout('layouts.admin', ['title' => 'Order ' . $order['order_number']]) ?>

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

<div class="flex items-center gap-3 mb-5">
    <a href="/admin/orders" class="text-gray-400 hover:text-gray-600 transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <div>
        <h1 class="text-xl font-bold text-gray-900"><?= e($order['order_number']) ?></h1>
        <p class="text-xs text-gray-400"><?= e(date('d M Y H:i', strtotime($order['created_at']))) ?></p>
    </div>
    <span class="ml-auto px-3 py-1.5 text-xs font-semibold rounded-full <?= $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-600' ?>">
        <?= e($statuses[$order['status']] ?? ucfirst($order['status'])) ?>
    </span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    <!-- Kolom kiri: info utama -->
    <div class="lg:col-span-2 space-y-5">

        <!-- Item order -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="font-semibold text-gray-800 mb-4">Produk Dipesan</h2>
            <div class="space-y-3">
                <?php foreach ($order['items'] as $item): ?>
                    <div class="flex gap-3 py-2 border-b border-gray-50 last:border-0">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800"><?= e($item['product_name']) ?></p>
                            <?php if ($item['variant_label']): ?>
                                <p class="text-xs text-gray-400"><?= e($item['variant_label']) ?></p>
                            <?php endif; ?>
                            <p class="text-xs text-gray-500 font-mono"><?= e($item['product_sku']) ?></p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                <?= $item['quantity'] ?>x Rp <?= number_format($item['price'], 0, ',', '.') ?>
                                <?php if ($item['weight']): ?>
                                    · <?= $item['weight'] * $item['quantity'] ?>g
                                <?php endif; ?>
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
                    <span>Ongkir (<?= e($order['courier_company'] ?? '-') ?> <?= e($order['courier_type'] ?? '') ?>)</span>
                    <span>Rp <?= number_format($order['shipping_cost'], 0, ',', '.') ?></span>
                </div>
                <?php if ($order['discount'] > 0): ?>
                    <div class="flex justify-between text-sm text-green-600">
                        <span>Diskon</span>
                        <span>- Rp <?= number_format($order['discount'], 0, ',', '.') ?></span>
                    </div>
                <?php endif; ?>
                <div class="flex justify-between font-bold text-gray-900 text-base pt-2 border-t">
                    <span>Total</span>
                    <span class="text-orange-600">Rp <?= number_format($order['total'], 0, ',', '.') ?></span>
                </div>
            </div>
        </div>

        <!-- Shipment info -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-start justify-between mb-4">
                <h2 class="font-semibold text-gray-800">Pengiriman</h2>
                <?php if (! $shipment && in_array($order['status'], ['paid', 'processing'])): ?>
                    <form method="POST" action="/admin/orders/<?= $order['id'] ?>/shipment"
                        onsubmit="return confirm('Buat shipment Biteship untuk order ini?')">
                        <?= csrf_field() ?>
                        <button type="submit"
                            class="px-4 py-2 bg-orange-600 text-white text-xs font-medium rounded-lg hover:bg-orange-700 transition flex items-center gap-2">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            Buat Shipment via Biteship
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if ($shipment): ?>
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-xs text-gray-400">Kurir</p>
                            <p class="font-medium text-gray-800"><?= e(strtoupper($shipment['courier_company'])) ?> — <?= e($shipment['courier_type']) ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Status Biteship</p>
                            <p class="font-medium text-gray-800"><?= e(ucfirst($shipment['status'])) ?></p>
                        </div>
                        <?php if ($shipment['biteship_order_id']): ?>
                            <div>
                                <p class="text-xs text-gray-400">Biteship Order ID</p>
                                <p class="font-mono text-xs text-gray-700"><?= e($shipment['biteship_order_id']) ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($shipment['waybill_id']): ?>
                            <div>
                                <p class="text-xs text-gray-400">Nomor Resi (Waybill)</p>
                                <p class="font-mono font-bold text-gray-900"><?= e($shipment['waybill_id']) ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($shipment['courier_tracking_link']): ?>
                            <div class="col-span-2">
                                <p class="text-xs text-gray-400">Link Tracking Kurir</p>
                                <a href="<?= e($shipment['courier_tracking_link']) ?>" target="_blank"
                                    class="text-xs text-orange-600 hover:underline break-all">
                                    <?= e($shipment['courier_tracking_link']) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        <div>
                            <p class="text-xs text-gray-400">Biaya Ongkir</p>
                            <p class="font-medium text-gray-800">Rp <?= number_format($shipment['cost'], 0, ',', '.') ?></p>
                        </div>
                        <?php if ($shipment['actual_cost']): ?>
                            <div>
                                <p class="text-xs text-gray-400">Biaya Aktual</p>
                                <p class="font-medium text-gray-800">Rp <?= number_format($shipment['actual_cost'], 0, ',', '.') ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Tracking timeline -->
                    <?php if ($tracking && ! empty($tracking['history'])): ?>
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <p class="text-sm font-semibold text-gray-700 mb-3">Riwayat Tracking</p>
                            <div class="space-y-3">
                                <?php foreach (array_reverse($tracking['history'] ?? []) as $track): ?>
                                    <div class="flex gap-3">
                                        <div class="w-2 h-2 bg-orange-500 rounded-full shrink-0 mt-1.5"></div>
                                        <div>
                                            <p class="text-xs font-medium text-gray-800"><?= e($track['note'] ?? $track['status'] ?? '-') ?></p>
                                            <p class="text-xs text-gray-400"><?= e($track['updated_at'] ?? '') ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php elseif ($shipment['biteship_tracking_id'] && ! $tracking): ?>
                        <p class="text-xs text-gray-400 mt-2">Tracking belum tersedia atau gagal dimuat.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="py-6 text-center border border-dashed border-gray-200 rounded-lg">
                    <p class="text-sm text-gray-400">Belum ada shipment dibuat.</p>
                    <?php if (! in_array($order['status'], ['paid', 'processing'])): ?>
                        <p class="text-xs text-gray-300 mt-1">Shipment bisa dibuat setelah order berstatus "Dibayar".</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Alamat pengiriman -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="font-semibold text-gray-800 mb-3">Alamat Pengiriman</h2>
            <p class="text-sm font-semibold text-gray-800"><?= e($order['recipient_name']) ?></p>
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
            <?php if ($order['shipping_area_id']): ?>
                <p class="text-xs text-gray-400 font-mono mt-1">Area ID: <?= e($order['shipping_area_id']) ?></p>
            <?php endif; ?>
            <?php if ($order['notes']): ?>
                <div class="mt-3 p-3 bg-amber-50 rounded-lg">
                    <p class="text-xs text-amber-700"><strong>Catatan:</strong> <?= e($order['notes']) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Kolom kanan: actions & payment & history -->
    <div class="space-y-5">

        <!-- Update status -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="font-semibold text-gray-800 mb-4">Update Status</h2>
            <form method="POST" action="/admin/orders/<?= $order['id'] ?>/status" class="space-y-3">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Status Baru</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <?php foreach ($statuses as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $order['status'] === $value ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Catatan (opsional)</label>
                    <textarea name="note" rows="2"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
                        placeholder="Catatan update status..."></textarea>
                </div>
                <button type="submit"
                    class="w-full py-2.5 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700 transition">
                    Update Status
                </button>
            </form>
        </div>

        <!-- Payment info -->
        <?php if ($payment): ?>
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h2 class="font-semibold text-gray-800 mb-3">Pembayaran</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Status</span>
                        <?php
                        $payColor = match ($payment['status']) {
                            'paid'      => 'text-green-600 font-semibold',
                            'pending'   => 'text-amber-600',
                            'failed', 'expired' => 'text-red-600',
                            default     => 'text-gray-600',
                        };
                        ?>
                        <span class="<?= $payColor ?>"><?= e(ucfirst($payment['status'])) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Metode</span>
                        <span class="font-medium"><?= e(strtoupper($payment['payment_method'] ?? '-')) ?> <?= e(strtoupper($payment['payment_channel'] ?? '')) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Jumlah</span>
                        <span class="font-bold">Rp <?= number_format($payment['amount'], 0, ',', '.') ?></span>
                    </div>
                    <?php if ($payment['ipaymu_pay_code']): ?>
                        <div>
                            <span class="text-gray-500 block">VA/Kode Bayar</span>
                            <span class="font-mono font-bold text-gray-900 text-base"><?= e($payment['ipaymu_pay_code']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($payment['paid_at']): ?>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Dibayar Pada</span>
                            <span class="text-xs"><?= e($payment['paid_at']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($payment['expired_at'] && $payment['status'] === 'pending'): ?>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Batas Bayar</span>
                            <span class="text-xs text-red-600"><?= e($payment['expired_at']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Status history -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="font-semibold text-gray-800 mb-3">Riwayat Status</h2>
            <div class="space-y-3">
                <?php foreach (array_reverse($history) as $h): ?>
                    <div class="flex gap-2.5">
                        <div class="w-2 h-2 bg-orange-500 rounded-full shrink-0 mt-1.5"></div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-800">
                                <?= e($statuses[$h['status']] ?? ucwords(str_replace('_', ' ', $h['status']))) ?>
                            </p>
                            <?php if ($h['note']): ?>
                                <p class="text-xs text-gray-500 break-words"><?= e($h['note']) ?></p>
                            <?php endif; ?>
                            <p class="text-xs text-gray-300"><?= e($h['created_at']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php $this->endSection() ?>