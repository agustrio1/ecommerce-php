<?php $this->layout('layouts.admin', ['title' => 'Riwayat Stok']) ?>

<?php $this->section('content') ?>

<div class="flex items-center gap-3 mb-5">
    <a href="/admin/inventory" class="text-gray-400 hover:text-gray-600">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <h1 class="text-xl font-bold text-gray-900">Riwayat Pergerakan Stok</h1>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tipe</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Jumlah</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">Sebelum → Sesudah</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Alasan</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Oleh</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($movements as $m): ?>
                    <?php
                    $typeColor = match ($m['type']) {
                        'in'         => 'bg-green-100 text-green-700',
                        'out'        => 'bg-red-100 text-red-700',
                        'adjustment' => 'bg-blue-100 text-blue-700',
                        default      => 'bg-gray-100 text-gray-600',
                    };
                    $typeLabel = match ($m['type']) {
                        'in'         => 'Masuk',
                        'out'        => 'Keluar',
                        'adjustment' => 'Penyesuaian',
                        default      => $m['type'],
                    };
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-xs text-gray-500"><?= e($m['created_at']) ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full <?= $typeColor ?>"><?= $typeLabel ?></span>
                        </td>
                        <td class="px-4 py-3 text-center font-semibold <?= $m['quantity'] >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                            <?= $m['quantity'] >= 0 ? '+' : '' ?><?= $m['quantity'] ?>
                        </td>
                        <td class="px-4 py-3 text-center text-xs text-gray-500 hidden sm:table-cell">
                            <?= $m['stock_before'] ?> → <?= $m['stock_after'] ?>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-xs text-gray-600"><?= e(ucwords(str_replace('_', ' ', $m['reason']))) ?></p>
                            <?php if ($m['order_number']): ?>
                                <p class="text-xs text-orange-600"><?= e($m['order_number']) ?></p>
                            <?php endif; ?>
                            <?php if ($m['note']): ?>
                                <p class="text-xs text-gray-400"><?= e($m['note']) ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500 hidden md:table-cell">
                            <?= e($m['created_by_name'] ?? 'Sistem') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($movements)): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-16 text-center text-gray-400 text-sm">
                            Belum ada riwayat pergerakan stok.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $this->endSection() ?>