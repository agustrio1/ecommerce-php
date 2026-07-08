<?php $this->layout('layouts.admin', ['title' => 'Kelola Flash Sale']) ?>

<?php $this->section('content') ?>

<?php $flash = \App\Core\Http\Session::getFlash('success'); ?>
<?php if ($flash): ?>
    <div class="mb-4 p-3 bg-green-50 text-green-700 text-sm rounded-xl border border-green-200"><?= e($flash) ?></div>
<?php endif; ?>

<div class="flex items-center gap-3 mb-5">
    <a href="/admin/flash-sales" class="text-gray-400 hover:text-gray-600">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <div>
        <h1 class="text-xl font-bold text-gray-900"><?= e($flashSale['name']) ?></h1>
        <p class="text-xs text-gray-400">
            <?= e(date('d M Y H:i', strtotime($flashSale['starts_at']))) ?>
            — <?= e(date('d M Y H:i', strtotime($flashSale['ends_at']))) ?>
        </p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

    <!-- Produk aktif di flash sale -->
    <div>
        <h2 class="font-semibold text-gray-800 mb-3">Produk dalam Flash Sale</h2>

        <?php if (empty($flashSale['products'])): ?>
            <div class="text-center py-10 bg-white rounded-xl border border-gray-200">
                <p class="text-gray-400 text-sm">Belum ada produk. Tambahkan dari kanan.</p>
            </div>
        <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($flashSale['products'] as $fsp): ?>
                    <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-800 truncate"><?= e($fsp['product_name']) ?></p>
                            <div class="flex items-center gap-3 mt-0.5">
                                <span class="text-sm font-bold text-red-600">Rp <?= number_format($fsp['sale_price'], 0, ',', '.') ?></span>
                                <span class="text-xs text-gray-400 line-through">Rp <?= number_format($fsp['original_price'], 0, ',', '.') ?></span>
                            </div>
                            <?php if ($fsp['stock_limit']): ?>
                                <p class="text-xs text-gray-400">Stok: <?= $fsp['sold_count'] ?>/<?= $fsp['stock_limit'] ?></p>
                            <?php endif; ?>
                        </div>
                        <form method="POST" action="/admin/flash-sales/<?= $flashSale['id'] ?>/products/<?= $fsp['product_id'] ?>"
                            onsubmit="return confirm('Hapus produk ini dari flash sale?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit"
                                class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Form tambah produk -->
    <div>
        <h2 class="font-semibold text-gray-800 mb-3">Tambah Produk</h2>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <form method="POST" action="/admin/flash-sales/<?= $flashSale['id'] ?>/products" class="space-y-3">
                <?= csrf_field() ?>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Pilih Produk</label>
                    <select name="product_id" required
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="">-- Pilih produk --</option>
                        <?php
                        $existingIds = array_column($flashSale['products'], 'product_id');
                        foreach ($allProducts as $product):
                            if (in_array($product->id, $existingIds)) continue;
                        ?>
                            <option value="<?= $product->id ?>">
                                <?= e($product->name) ?> — Rp <?= number_format($product->price, 0, ',', '.') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Harga Flash Sale <span class="text-red-500">*</span></label>
                    <input type="number" name="sale_price" required min="1"
                        placeholder="75000"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Batas Stok Flash Sale</label>
                    <input type="number" name="stock_limit" min="1"
                        placeholder="Kosongkan = tidak terbatas"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>

                <button type="submit"
                    class="w-full py-2.5 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700 transition">
                    Tambah ke Flash Sale
                </button>
            </form>
        </div>
    </div>
</div>

<?php $this->endSection() ?>