<?php $this->layout('layouts.admin', ['title' => 'Inventori']) ?>

<?php $this->section('content') ?>

<div class="flex flex-wrap justify-between items-center gap-3 mb-5">
    <div>
        <h1 class="text-xl font-bold text-gray-900">Inventori</h1>
        <p class="text-sm text-gray-400"><?= $total ?> varian produk</p>
        <a href="/admin/export/inventory?date_from=<?= date('Y-m-01') ?>&date_to=<?= date('Y-m-d') ?>"
        class="px-3 py-2 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-200 transition flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        Export CSV
    </a>
    </div>
    <?php if ($lowStockCount > 0): ?>
        <a href="/admin/inventory?low_stock=1"
            class="flex items-center gap-2 px-3 py-2 bg-red-50 text-red-700 text-xs font-medium rounded-xl hover:bg-red-100 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <?= $lowStockCount ?> stok menipis
        </a>
    <?php endif; ?>
</div>

<!-- Search & filter -->
<form method="GET" action="/admin/inventory" class="flex flex-col sm:flex-row gap-2 mb-5">
    <div class="relative flex-1">
        <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <input type="text" name="search" value="<?= e($search) ?>"
            placeholder="Cari nama produk atau SKU..."
            class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">
    </div>
    <label class="flex items-center gap-2 px-3 py-2 border border-gray-300 rounded-xl bg-white cursor-pointer">
        <input type="checkbox" name="low_stock" value="1" <?= $lowStockOnly ? 'checked' : '' ?>
            class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
        <span class="text-sm text-gray-700">Stok menipis saja</span>
    </label>
    <button type="submit" class="px-5 py-2 bg-gray-800 text-white text-sm rounded-xl font-medium hover:bg-gray-900 transition">
        Filter
    </button>
</form>

<!-- Tabel inventory -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Produk</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">SKU</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Stok</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($variants as $variant): ?>
                    <tr class="hover:bg-gray-50 transition" x-data="{ showRestock: false }">
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-800"><?= e($variant['product_name']) ?></p>
                            <?php if ($variant['variant_label'] !== 'Default'): ?>
                                <p class="text-xs text-gray-400"><?= e($variant['variant_label']) ?></p>
                            <?php endif; ?>
                            <p class="sm:hidden text-xs text-gray-400 font-mono mt-0.5"><?= e($variant['sku']) ?></p>
                        </td>
                        <td class="px-4 py-3 hidden sm:table-cell text-gray-500 text-xs font-mono">
                            <?= e($variant['sku']) ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2.5 py-1 text-xs font-bold rounded-full <?= $variant['stock'] <= 5 ? 'bg-red-100 text-red-700' : ($variant['stock'] <= 20 ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700') ?>">
                                <?= $variant['stock'] ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button @click="showRestock = !showRestock"
                                    class="px-3 py-1.5 text-xs font-medium text-orange-600 bg-orange-50 rounded-lg hover:bg-orange-100 transition">
                                    + Restock
                                </button>
                                <a href="/admin/inventory/<?= $variant['variant_id'] ?>/history"
                                    class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                                    Riwayat
                                </a>
                            </div>
                        </td>
                    </tr>
                    <tr x-show="showRestock" x-cloak>
                        <td colspan="4" class="px-4 py-3 bg-gray-50">
                            <form
                                hx-post="/admin/inventory/<?= $variant['variant_id'] ?>/restock"
                                hx-target="closest tr"
                                hx-swap="none"
                                hx-headers='<?= json_encode(['X-CSRF-Token' => \App\Core\Http\Csrf::token()]) ?>'
                                class="flex flex-wrap items-end gap-2"
                                @htmx:after-request="showRestock = false; window.location.reload()">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Jumlah Tambah</label>
                                    <input type="number" name="quantity" min="1" required
                                        class="w-28 px-2 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                                </div>
                                <div class="flex-1 min-w-[150px]">
                                    <label class="block text-xs text-gray-500 mb-1">Catatan</label>
                                    <input type="text" name="note" placeholder="Mis: Restock dari supplier"
                                        class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                                </div>
                                <button type="submit"
                                    class="px-4 py-1.5 bg-orange-600 text-white text-sm rounded-lg font-medium hover:bg-orange-700 transition">
                                    Simpan
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($variants)): ?>
                    <tr>
                        <td colspan="4" class="px-4 py-16 text-center text-gray-400 text-sm">
                            Tidak ada data inventori.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php $totalPages = (int) ceil($total / $perPage); ?>
<?php if ($totalPages > 1): ?>
    <div class="flex items-center justify-center gap-1.5 mt-5">
        <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
            <a href="/admin/inventory?page=<?= $p ?>&search=<?= urlencode($search) ?>"
                class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium
                       <?= $p === $page ? 'bg-orange-600 text-white' : 'bg-white border border-gray-200 hover:bg-gray-50' ?>">
                <?= $p ?>
            </a>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<?php $this->endSection() ?>