<?php $this->layout('layouts.admin', ['title' => $title]) ?>

<?php $this->section('content') ?>

<div class="p-6 max-w-4xl mx-auto">

    <!-- Warning banner -->
    <div class="mb-6 p-4 bg-yellow-50 border-2 border-yellow-400 border-dashed rounded-xl flex gap-3">
        <svg class="w-5 h-5 text-yellow-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
        </svg>
        <div>
            <p class="font-bold text-yellow-800 text-sm">Development Only</p>
            <p class="text-xs text-yellow-700 mt-0.5">Halaman ini hanya aktif ketika <code class="bg-yellow-100 px-1 rounded">APP_ENV != production</code>. Digunakan untuk mensimulasikan callback/webhook dari iPaymu tanpa perlu expose localhost ke internet.</p>
        </div>
    </div>

    <h1 class="text-xl font-bold text-gray-900 mb-6">iPaymu Payment Simulator</h1>

    <!-- Hasil simulasi -->
    <div id="simResult" class="hidden mb-4 p-4 rounded-xl text-sm"></div>

    <?php if (empty($payments)): ?>
        <div class="text-center py-12 text-gray-400">
            <svg class="w-10 h-10 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
            </svg>
            <p class="font-medium">Belum ada data payment.</p>
            <p class="text-xs mt-1">Buat order terlebih dahulu dari storefront.</p>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($payments as $p): ?>
                <?php
                $statusBadge = match ($p['status']) {
                    'paid'      => 'bg-green-100 text-green-700',
                    'pending'   => 'bg-amber-100 text-amber-700',
                    'cancelled', 'failed' => 'bg-red-100 text-red-700',
                    default     => 'bg-gray-100 text-gray-600',
                };
                $orderStatusBadge = match ($p['order_status']) {
                    'paid', 'completed', 'delivered' => 'bg-green-100 text-green-700',
                    'waiting_payment' => 'bg-amber-100 text-amber-700',
                    'cancelled'       => 'bg-red-100 text-red-700',
                    default           => 'bg-gray-100 text-gray-600',
                };
                ?>
                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <div class="flex items-start justify-between gap-4 flex-wrap">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <p class="font-mono text-sm font-bold text-gray-900"><?= e($p['payment_no']) ?></p>
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $statusBadge ?>">
                                    Payment: <?= e(strtoupper($p['status'])) ?>
                                </span>
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $orderStatusBadge ?>">
                                    Order: <?= e(strtoupper($p['order_status'])) ?>
                                </span>
                            </div>
                            <p class="text-xs text-gray-500">
                                Order: <span class="font-mono"><?= e($p['order_number']) ?></span>
                                &bull; Metode: <?= e(strtoupper($p['payment_method'] ?? '-')) ?> <?= e(strtoupper($p['payment_channel'] ?? '')) ?>
                                &bull; Total: <strong>Rp <?= number_format($p['amount'], 0, ',', '.') ?></strong>
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">Dibuat: <?= e($p['created_at']) ?></p>
                            <?php if ($p['ipaymu_trx_id']): ?>
                                <p class="text-xs text-gray-400">iPaymu Trx ID: <code class="font-mono"><?= e($p['ipaymu_trx_id']) ?></code></p>
                            <?php endif; ?>
                        </div>

                        <!-- Tombol simulasi -->
                        <div class="flex flex-col gap-2 shrink-0">
                            <button
                                onclick="simulate('<?= e($p['payment_no']) ?>', '1')"
                                class="px-3 py-1.5 bg-green-600 text-white text-xs font-semibold rounded-lg hover:bg-green-700 transition flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Simulasi BAYAR
                            </button>
                            <button
                                onclick="simulate('<?= e($p['payment_no']) ?>', '3')"
                                class="px-3 py-1.5 bg-red-600 text-white text-xs font-semibold rounded-lg hover:bg-red-700 transition flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Simulasi CANCEL
                            </button>
                            <button
                                onclick="simulate('<?= e($p['payment_no']) ?>', '2')"
                                class="px-3 py-1.5 bg-gray-500 text-white text-xs font-semibold rounded-lg hover:bg-gray-600 transition flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Simulasi PENDING
                            </button>
                            <?php if ($p['ipaymu_pay_code_url']): ?>
                                <a href="<?= e($p['ipaymu_pay_code_url']) ?>" target="_blank"
                                    class="px-3 py-1.5 bg-orange-600 text-white text-xs font-semibold rounded-lg hover:bg-orange-700 transition flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                                    </svg>
                                    Buka Halaman iPaymu
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
async function simulate(paymentNo, status) {
    const resultEl = document.getElementById('simResult');
    const statusLabel = { '1': 'BAYAR (sukses)', '2': 'PENDING', '3': 'CANCEL' }[status] || status;

    resultEl.className = 'mb-4 p-4 rounded-xl text-sm bg-gray-50 border border-gray-200';
    resultEl.textContent = `Mensimulasikan ${statusLabel} untuk ${paymentNo}...`;
    resultEl.classList.remove('hidden');

    try {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const res = await fetch('/dev/payment-simulator/callback', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrf,
            },
            body: JSON.stringify({ payment_no: paymentNo, status }),
        });

        const data = await res.json();

        if (data.success) {
            resultEl.className = 'mb-4 p-4 rounded-xl text-sm bg-green-50 border border-green-200 text-green-800';
            resultEl.innerHTML = `
                <p class="font-bold mb-1">Berhasil — ${data.message}</p>
                <p class="text-xs text-green-600">Payload yang dikirim ke handleCallback():</p>
                <pre class="text-xs mt-1 bg-white p-2 rounded overflow-auto">${JSON.stringify(data.payload, null, 2)}</pre>
                <button onclick="location.reload()" class="mt-2 px-3 py-1 bg-green-600 text-white text-xs rounded-lg hover:bg-green-700">
                    Refresh halaman
                </button>
            `;
        } else {
            resultEl.className = 'mb-4 p-4 rounded-xl text-sm bg-red-50 border border-red-200 text-red-800';
            resultEl.innerHTML = `
                <p class="font-bold mb-1">Gagal — ${data.message}</p>
                ${data.payload ? `<pre class="text-xs mt-1 bg-white p-2 rounded overflow-auto">${JSON.stringify(data.payload, null, 2)}</pre>` : ''}
            `;
        }
    } catch (e) {
        resultEl.className = 'mb-4 p-4 rounded-xl text-sm bg-red-50 border border-red-200 text-red-800';
        resultEl.textContent = 'Network error: ' + e.message;
    }
}
</script>

<?php $this->endSection() ?>
