<?php $this->layout('layouts.admin', ['title' => 'Kelola Produk']) ?>

<?php $this->section('content') ?>

<div class="min-h-screen bg-gray-50 px-4 py-6 sm:p-6">
    <div class="max-w-6xl mx-auto">

        <!-- Header -->
        <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Produk</h1>
                <p class="text-sm text-gray-400"><?= $total ?> produk total</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="/admin/attributes"
                    class="flex items-center gap-2 border border-gray-300 text-gray-600 px-3 py-2 rounded-xl hover:bg-gray-50 transition text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    Atribut
                </a>
                <a href="/admin/categories"
                    class="flex items-center gap-2 border border-gray-300 text-gray-600 px-3 py-2 rounded-xl hover:bg-gray-50 transition text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                    Kategori
                </a>
                <a href="/admin/products/create"
                    class="flex items-center gap-2 bg-orange-600 text-white px-4 py-2 rounded-xl hover:bg-orange-700 transition text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Produk
                </a>
            </div>
        </div>

        <?php $flashSuccess = \App\Core\Http\Session::getFlash('success'); ?>
        <?php $flashError   = \App\Core\Http\Session::getFlash('error'); ?>
        <?php if ($flashSuccess): ?>
            <div class="mb-4 p-3 bg-green-50 text-green-700 text-sm rounded-xl border border-green-200 flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <?= e($flashSuccess) ?>
            </div>
        <?php endif; ?>
        <?php if ($flashError): ?>
            <div class="mb-4 p-3 bg-red-50 text-red-700 text-sm rounded-xl border border-red-200"><?= e($flashError) ?></div>
        <?php endif; ?>

        <!-- Search + Filter -->
        <form method="GET" action="/admin/products" class="mb-4 flex flex-col sm:flex-row gap-2">
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
            <select name="status" class="w-full sm:w-44 px-3 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">
                <option value="">Semua Status</option>
                <option value="draft" <?= $statusFilter === 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="published" <?= $statusFilter === 'published' ? 'selected' : '' ?>>Published</option>
                <option value="archived" <?= $statusFilter === 'archived' ? 'selected' : '' ?>>Archived</option>
            </select>
            <button type="submit" class="w-full sm:w-auto px-5 py-2 bg-gray-800 text-white text-sm rounded-xl font-medium hover:bg-gray-900 transition">
                Cari
            </button>
        </form>

        <!-- Tabel -->
        <div x-data="bulkAction()" class="space-y-3">

    <!-- Bulk action toolbar (muncul saat ada yang dicentang) -->
    <div x-show="selected.length > 0"
        x-transition
        class="flex items-center gap-3 p-3 bg-orange-50 border border-orange-200 rounded-xl">
        <span class="text-sm font-medium text-orange-700" x-text="selected.length + ' produk dipilih'"></span>
        <div class="flex gap-2 ml-auto">
            <button @click="submitBulk('publish')"
                class="px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition">
                Publish
            </button>
            <button @click="submitBulk('draft')"
                class="px-3 py-1.5 bg-gray-600 text-white text-xs font-medium rounded-lg hover:bg-gray-700 transition">
                Draft
            </button>
            <button @click="submitBulk('delete')"
                class="px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded-lg hover:bg-red-700 transition"
                onclick="return confirm('Hapus ' + selected.length + ' produk yang dipilih?')">
                Hapus
            </button>
        </div>
    </div>

    <!-- Hidden form untuk bulk submit -->
    <form id="bulkForm" method="POST" action="/admin/products/bulk">
        <?= csrf_field() ?>
        <input type="hidden" name="action" id="bulkAction">
        <div id="bulkIds"></div>
    </form>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 w-10">
                            <input type="checkbox" @change="toggleAll($event)"
                                class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Produk</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">SKU</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Harga</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($products as $product): ?>
                        <tr class="hover:bg-gray-50 transition" :class="selected.includes(<?= $product->id ?>) ? 'bg-orange-50' : ''">
                            <td class="px-4 py-3">
                                <input type="checkbox"
                                    :value="<?= $product->id ?>"
                                    @change="toggle(<?= $product->id ?>)"
                                    :checked="selected.includes(<?= $product->id ?>)"
                                    class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gray-100 rounded-lg overflow-hidden shrink-0">
                                        <?php $img = $productImages[$product->id] ?? null; ?>
                                        <?php if ($img): ?>
                                            <img src="<?= e($img) ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center">
                                                <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01"/>
                                                </svg>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-medium text-gray-800 truncate"><?= e($product->name) ?></p>
                                        <p class="text-xs text-gray-400 sm:hidden"><?= e($product->sku) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500 font-mono hidden sm:table-cell"><?= e($product->sku) ?></td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <p class="text-sm font-semibold text-gray-900">Rp <?= number_format($product->price, 0, ',', '.') ?></p>
                                <?php if ($product->isOnSale()): ?>
                                    <p class="text-xs text-gray-400 line-through">Rp <?= number_format($product->comparePrice, 0, ',', '.') ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center hidden sm:table-cell">
                                <span class="px-2.5 py-1 text-xs font-medium rounded-full <?= $product->status === 'published' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
                                    <?= $product->status === 'published' ? 'Published' : 'Draft' ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="/admin/products/<?= $product->id ?>/edit"
                                        class="px-3 py-1.5 text-xs font-medium text-orange-600 bg-orange-50 rounded-lg hover:bg-orange-100 transition">
                                        Edit
                                    </a>
                                    <form method="POST" action="/admin/products/<?= $product->id ?>"
                                        onsubmit="return confirm('Hapus produk ini?')">
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
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center text-gray-400 text-sm">
                                Belum ada produk.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function bulkAction() {
    return {
        selected: [],

        toggle(id) {
            const idx = this.selected.indexOf(id);
            if (idx === -1) {
                this.selected.push(id);
            } else {
                this.selected.splice(idx, 1);
            }
        },

        toggleAll(event) {
            if (event.target.checked) {
                this.selected = <?= json_encode(array_map(fn($p) => $p->id, $products)) ?>;
            } else {
                this.selected = [];
            }
        },

        submitBulk(action) {
            document.getElementById('bulkAction').value = action;
            const container = document.getElementById('bulkIds');
            container.innerHTML = '';
            this.selected.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                container.appendChild(input);
            });
            document.getElementById('bulkForm').submit();
        }
    }
}
</script>

        <!-- Pagination -->
        <?php $totalPages = (int) ceil($total / $perPage); ?>
        <?php if ($totalPages > 1): ?>
            <div class="flex items-center justify-center gap-1.5 mt-5">
                <?php if ($page > 1): ?>
                    <a href="/admin/products?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>"
                        class="px-3 py-1.5 text-sm bg-white border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 transition">
                        &larr;
                    </a>
                <?php endif; ?>

                <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                    <a href="/admin/products?page=<?= $p ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>"
                        class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium
                               <?= $p === $page ? 'bg-orange-600 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' ?>">
                        <?= $p ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="/admin/products?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>"
                        class="px-3 py-1.5 text-sm bg-white border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 transition">
                        &rarr;
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function bulkProduct() {
    return {
        selected: [],

        toggleAll(event) {
            this.selected = event.target.checked
                ? [<?= implode(',', array_map(fn ($p) => $p->id, $products)) ?>]
                : [];
        },

        appendIds(event) {
            this.selected.forEach(id => {
                const input = document.createElement('input');
                input.type  = 'hidden';
                input.name  = 'ids[]';
                input.value = id;
                event.target.appendChild(input);
            });
        }
    }
}
</script>

<?php $this->endSection() ?>