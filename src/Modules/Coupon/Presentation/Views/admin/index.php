<?php $this->layout('layouts.admin', ['title' => 'Kupon Diskon']) ?>

<?php $this->section('content') ?>

<?php $flash = \App\Core\Http\Session::getFlash('success'); ?>
<?php if ($flash): ?>
    <div class="mb-4 p-3 bg-green-50 text-green-700 text-sm rounded-xl border border-green-200"><?= e($flash) ?></div>
<?php endif; ?>

<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-900">Kupon Diskon</h1>
    <a href="/admin/coupons/create"
        class="px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-xl hover:bg-orange-700 transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Buat Kupon
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kode</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">Tipe & Nilai</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Penggunaan</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">Berlaku</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($coupons as $coupon): ?>
                    <?php
                    $isExpired = $coupon['expires_at'] && strtotime($coupon['expires_at']) < time();
                    $isExhausted = $coupon['usage_limit'] && $coupon['used_count'] >= $coupon['usage_limit'];
                    ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3">
                            <p class="font-mono font-bold text-gray-900"><?= e($coupon['code']) ?></p>
                            <?php if ($coupon['description']): ?>
                                <p class="text-xs text-gray-400"><?= e($coupon['description']) ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 hidden sm:table-cell">
                            <span class="font-semibold text-gray-800">
                                <?php if ($coupon['type'] === 'percentage'): ?>
                                    <?= $coupon['value'] ?>%
                                    <?php if ($coupon['max_discount']): ?>
                                        <span class="text-xs text-gray-400">(maks Rp <?= number_format($coupon['max_discount'], 0, ',', '.') ?>)</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    Rp <?= number_format($coupon['value'], 0, ',', '.') ?>
                                <?php endif; ?>
                            </span>
                            <?php if ($coupon['min_purchase'] > 0): ?>
                                <p class="text-xs text-gray-400">Min. Rp <?= number_format($coupon['min_purchase'], 0, ',', '.') ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell">
                            <p class="text-sm text-gray-700"><?= $coupon['used_count'] ?> / <?= $coupon['usage_limit'] ?? '∞' ?></p>
                        </td>
                        <td class="px-4 py-3 hidden lg:table-cell text-xs text-gray-500">
                            <?php if ($coupon['starts_at']): ?>
                                <p><?= e(date('d M Y', strtotime($coupon['starts_at']))) ?></p>
                            <?php endif; ?>
                            <?php if ($coupon['expires_at']): ?>
                                <p class="<?= $isExpired ? 'text-red-600' : '' ?>">
                                    s/d <?= e(date('d M Y', strtotime($coupon['expires_at']))) ?>
                                </p>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if (!$coupon['is_active'] || $isExpired || $isExhausted): ?>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-700">
                                    <?= !$coupon['is_active'] ? 'Nonaktif' : ($isExpired ? 'Kadaluarsa' : 'Habis') ?>
                                </span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700">Aktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="/admin/coupons/<?= $coupon['id'] ?>/edit"
                                    class="px-3 py-1.5 text-xs font-medium text-orange-600 bg-orange-50 rounded-lg hover:bg-orange-100 transition">
                                    Edit
                                </a>
                                <form method="POST" action="/admin/coupons/<?= $coupon['id'] ?>"
                                    onsubmit="return confirm('Hapus kupon ini?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit"
                                        class="px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($coupons)): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-16 text-center text-gray-400 text-sm">Belum ada kupon.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $this->endSection() ?>