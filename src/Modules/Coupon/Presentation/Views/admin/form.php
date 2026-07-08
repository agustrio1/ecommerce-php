<?php $this->layout('layouts.admin', ['title' => $title]) ?>

<?php $this->section('content') ?>

<div class="flex items-center gap-3 mb-5">
    <a href="/admin/coupons" class="text-gray-400 hover:text-gray-600">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <h1 class="text-xl font-bold text-gray-900"><?= e($title) ?></h1>
</div>

<div class="max-w-2xl">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <form method="POST"
            action="<?= $coupon ? '/admin/coupons/' . $coupon['id'] : '/admin/coupons' ?>"
            class="space-y-4">
            <?= csrf_field() ?>
            <?php if ($coupon): ?>
                <input type="hidden" name="_method" value="PUT">
            <?php endif; ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Kode Kupon <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="code" required
                        value="<?= e($coupon['code'] ?? '') ?>"
                        placeholder="DISKON10"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-orange-500 uppercase">
                    <p class="text-xs text-gray-400 mt-1">Otomatis diubah ke huruf kapital</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                    <input type="text" name="description"
                        value="<?= e($coupon['description'] ?? '') ?>"
                        placeholder="Diskon 10% untuk semua produk"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Diskon <span class="text-red-500">*</span></label>
                    <select name="type" required
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="percentage" <?= ($coupon['type'] ?? '') === 'percentage' ? 'selected' : '' ?>>Persentase (%)</option>
                        <option value="fixed" <?= ($coupon['type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Nominal Tetap (Rp)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nilai Diskon <span class="text-red-500">*</span></label>
                    <input type="number" name="value" required min="1" step="0.01"
                        value="<?= e($coupon['value'] ?? '') ?>"
                        placeholder="10"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <p class="text-xs text-gray-400 mt-1">Masukkan angka (10 = 10% atau Rp 10.000)</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Pembelian</label>
                    <input type="number" name="min_purchase" min="0"
                        value="<?= e($coupon['min_purchase'] ?? 0) ?>"
                        placeholder="0"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Maksimum Diskon (Rp)</label>
                    <input type="number" name="max_discount" min="0"
                        value="<?= e($coupon['max_discount'] ?? '') ?>"
                        placeholder="Kosongkan = tidak terbatas"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <p class="text-xs text-gray-400 mt-1">Hanya berlaku untuk diskon persentase</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Batas Penggunaan</label>
                    <input type="number" name="usage_limit" min="1"
                        value="<?= e($coupon['usage_limit'] ?? '') ?>"
                        placeholder="Kosongkan = tidak terbatas"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                    <input type="datetime-local" name="starts_at"
                        value="<?= e($coupon['starts_at'] ? date('Y-m-d\TH:i', strtotime($coupon['starts_at'])) : '') ?>"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Kadaluarsa</label>
                    <input type="datetime-local" name="expires_at"
                        value="<?= e($coupon['expires_at'] ? date('Y-m-d\TH:i', strtotime($coupon['expires_at'])) : '') ?>"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
            </div>

            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="is_active" value="1"
                    <?= ($coupon['is_active'] ?? 1) ? 'checked' : '' ?>
                    class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                <span class="text-sm font-medium text-gray-700">Kupon aktif</span>
            </label>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="flex-1 py-2.5 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700 transition">
                    <?= $coupon ? 'Simpan Perubahan' : 'Buat Kupon' ?>
                </button>
                <a href="/admin/coupons"
                    class="px-5 py-2.5 border border-gray-300 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

<?php $this->endSection() ?>