<?php $this->layout('layouts.admin', ['title' => 'Edit Produk']) ?>

<?php $this->section('content') ?>

<?php $formErrors = errors(); ?>

<div class="min-h-screen bg-gray-50 px-4 py-6 sm:p-6">
    <div class="max-w-4xl mx-auto">

        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Edit Produk</h1>
                <p class="text-sm text-gray-400"><?= e($product->name) ?></p>
            </div>
            <a href="/admin/products"
                class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali
            </a>
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

        <?php if (! empty($formErrors)): ?>
            <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl">
                <p class="text-sm font-medium text-red-700 mb-1">Ada kesalahan:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    <?php foreach ($formErrors as $error): ?>
                        <li class="text-sm text-red-600"><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form id="editProductForm" method="POST" action="/admin/products/<?= $product->id ?>"
            enctype="multipart/form-data" class="space-y-5">
            <?= csrf_field() ?>
            <input type="hidden" name="_method" value="PUT">

            <!-- ===== INFORMASI DASAR ===== -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 sm:p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Informasi Dasar</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Nama Produk <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name"
                            value="<?= e(old('name', $product->name)) ?>" required
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <?php if (! empty($formErrors['name'])): ?>
                            <p class="text-red-600 text-xs mt-1"><?= e($formErrors['name']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                SKU <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="sku"
                                value="<?= e(old('sku', $product->sku)) ?>" required
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 font-mono">
                            <?php if (! empty($formErrors['sku'])): ?>
                                <p class="text-red-600 text-xs mt-1"><?= e($formErrors['sku']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                                <option value="draft" <?= $product->status === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="published" <?= $product->status === 'published' ? 'selected' : '' ?>>Published</option>
                                <option value="archived" <?= $product->status === 'archived' ? 'selected' : '' ?>>Archived</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Singkat</label>
                        <textarea name="short_description" rows="2"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"><?= e(old('short_description', $product->shortDescription)) ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi Lengkap</label>
                        <?= $this->include('components.rich-editor', [
                            'name'  => 'description',
                            'value' => old('description', $product->description ?? ''),
                            'id'    => 'editor_description',
                        ]) ?>
                    </div>
                </div>
            </div>

            <!-- ===== HARGA & PENGIRIMAN ===== -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 sm:p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Harga & Pengiriman</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Harga Jual <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-3 flex items-center text-sm text-gray-500">Rp</span>
                            <input type="number" name="price"
                                value="<?= e(old('price', (string) $product->price)) ?>"
                                min="0" required
                                class="w-full pl-9 pr-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                        </div>
                        <?php if (! empty($formErrors['price'])): ?>
                            <p class="text-red-600 text-xs mt-1"><?= e($formErrors['price']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Harga Coret <span class="text-gray-400 text-xs">(opsional)</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-3 flex items-center text-sm text-gray-500">Rp</span>
                            <input type="number" name="compare_price"
                                value="<?= e(old('compare_price', (string) ($product->comparePrice ?? ''))) ?>"
                                min="0"
                                class="w-full pl-9 pr-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Harga Modal <span class="text-gray-400 text-xs">(opsional)</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-3 flex items-center text-sm text-gray-500">Rp</span>
                            <input type="number" name="cost_price"
                                value="<?= e(old('cost_price', (string) ($product->costPrice ?? ''))) ?>"
                                min="0"
                                class="w-full pl-9 pr-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Berat <span class="text-gray-400 text-xs">(gram)</span>
                        </label>
                        <input type="number" name="weight"
                            value="<?= e(old('weight', (string) ($product->weight ?? ''))) ?>"
                            min="0" placeholder="0"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>

                    <?php if (! $product->hasVariants): ?>
                        <?php
                        $defaultVariant = $variants[0] ?? null;
                        $currentStock   = $defaultVariant ? (int) $defaultVariant['stock'] : 0;
                        ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Stok</label>
                            <input type="number" name="stock"
                                value="<?= e(old('stock', (string) $currentStock)) ?>"
                                min="0"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                        </div>
                    <?php endif; ?>

                    <!-- Dimensi -->
                    <div class="sm:col-span-2 lg:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Dimensi Paket
                            <span class="text-gray-400 text-xs font-normal">(cm, untuk kalkulasi ongkir)</span>
                        </label>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <div class="relative">
                                    <input type="number" name="length"
                                        value="<?= e(old('length', (string) $product->length)) ?>"
                                        min="0" placeholder="0"
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 pr-10">
                                    <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400 pointer-events-none">cm</span>
                                </div>
                                <p class="text-xs text-gray-400 mt-1 text-center">Panjang</p>
                            </div>
                            <div>
                                <div class="relative">
                                    <input type="number" name="width"
                                        value="<?= e(old('width', (string) $product->width)) ?>"
                                        min="0" placeholder="0"
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 pr-10">
                                    <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400 pointer-events-none">cm</span>
                                </div>
                                <p class="text-xs text-gray-400 mt-1 text-center">Lebar</p>
                            </div>
                            <div>
                                <div class="relative">
                                    <input type="number" name="height"
                                        value="<?= e(old('height', (string) $product->height)) ?>"
                                        min="0" placeholder="0"
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 pr-10">
                                    <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400 pointer-events-none">cm</span>
                                </div>
                                <p class="text-xs text-gray-400 mt-1 text-center">Tinggi</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== KATEGORI ===== -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 sm:p-6">
                <h2 class="font-semibold text-gray-800 mb-1">Kategori</h2>
                <p class="text-xs text-gray-400 mb-4">Pilih satu atau lebih kategori</p>

                <?php if (empty($categories)): ?>
                    <p class="text-sm text-gray-400">Belum ada kategori.</p>
                <?php else: ?>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($categories as $category): ?>
                            <label class="flex items-center gap-2 px-3 py-2 border border-gray-200 rounded-lg cursor-pointer hover:bg-orange-50 hover:border-orange-300 transition has-[:checked]:bg-orange-50 has-[:checked]:border-orange-400">
                                <input type="checkbox" name="category_ids[]"
                                    value="<?= $category->id ?>"
                                    <?= in_array($category->id, $selectedCategoryIds, true) ? 'checked' : '' ?>
                                    class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                                <span class="text-sm text-gray-700"><?= e($category->name) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ===== VARIAN & STOK ===== -->
            <?php if ($product->hasVariants && ! empty($variants)): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 sm:p-6">
                    <div class="mb-4">
                        <h2 class="font-semibold text-gray-800">Stok per Varian</h2>
                        <p class="text-xs text-gray-400"><?= count($variants) ?> varian — edit stok langsung, auto-tersimpan</p>
                    </div>

                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Kombinasi</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">SKU</th>
                                    <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase w-32">Stok</th>
                                    <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase w-20">Aktif</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($variants as $variant): ?>
                                    <?php
                                    $label = empty($variant['attribute_values'])
                                        ? 'Default'
                                        : implode(' / ', array_column($variant['attribute_values'], 'value'));
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-700">
                                            <?= e($label) ?>
                                            <div class="sm:hidden text-xs text-gray-400 font-mono mt-0.5"><?= e($variant['sku']) ?></div>
                                        </td>
                                        <td class="px-4 py-3 text-gray-500 font-mono text-xs hidden sm:table-cell">
                                            <?= e($variant['sku']) ?>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <div
                                                hx-patch="/admin/product-variants/<?= $variant['id'] ?>/stock"
                                                hx-trigger="change from:find input[name='stock']"
                                                hx-include="find input[name='stock']"
                                                hx-swap="none"
                                                hx-headers='<?= json_encode(['X-CSRF-Token' => \App\Core\Http\Csrf::token()]) ?>'
                                                hx-on::after-request="showStockSaved()">
                                                <input type="number"
                                                    name="stock"
                                                    value="<?= (int) $variant['stock'] ?>"
                                                    min="0"
                                                    class="w-24 px-2 py-1.5 text-center border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full <?= $variant['is_active'] ? 'bg-green-100' : 'bg-gray-100' ?>">
                                                <span class="w-2 h-2 rounded-full <?= $variant['is_active'] ? 'bg-green-500' : 'bg-gray-400' ?>"></span>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ===== GAMBAR PRODUK ===== -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 sm:p-6">
                <h2 class="font-semibold text-gray-800 mb-1">Gambar Produk</h2>
                <p class="text-xs text-gray-400 mb-4">Maks 2MB per file, format JPG/PNG/WebP</p>

                <!-- Gambar existing — hapus via HTMX langsung di button, TANPA nested form -->
                <?php if (! empty($images)): ?>
                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3 mb-4" id="imageGrid">
                        <?php foreach ($images as $image): ?>
                            <div class="relative group aspect-square" id="img-<?= $image['id'] ?>">
                                <img src="/storage/<?= e($image['path']) ?>"
                                    alt="<?= e($image['alt_text'] ?? $product->name) ?>"
                                    class="w-full h-full object-cover rounded-lg border border-gray-200">
                                <?php if ($image['is_primary']): ?>
                                    <div class="absolute top-1 left-1 bg-orange-600 text-white text-xs px-1.5 py-0.5 rounded font-medium pointer-events-none">
                                        Utama
                                    </div>
                                <?php endif; ?>
                                <!-- Tombol hapus: type="button" + HTMX, BUKAN nested form -->
                                <button
                                    type="button"
                                    hx-delete="/admin/product-images/<?= $image['id'] ?>"
                                    hx-target="#img-<?= $image['id'] ?>"
                                    hx-swap="outerHTML"
                                    hx-headers='<?= json_encode(['X-CSRF-Token' => \App\Core\Http\Csrf::token()]) ?>'
                                    hx-confirm="Hapus gambar ini?"
                                    class="absolute top-1 right-1 bg-red-600 text-white rounded-full w-6 h-6 text-xs hidden group-hover:flex items-center justify-center shadow hover:bg-red-700 transition">
                                    ×
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Upload gambar baru -->
                <div x-data="imagePreview()"
                    class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-orange-400 transition cursor-pointer"
                    @dragover.prevent
                    @drop.prevent="handleDrop($event)"
                    @click="$refs.fileInput.click()">

                    <input type="file" name="images[]" multiple accept="image/*"
                        x-ref="fileInput"
                        @change="handleFiles($event)"
                        class="hidden">

                    <div x-show="previews.length === 0">
                        <svg class="w-8 h-8 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-sm text-gray-500">Klik atau drag & drop untuk tambah gambar baru</p>
                    </div>

                    <div x-show="previews.length > 0" @click.stop
                        class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3 cursor-default">
                        <template x-for="(src, index) in previews" :key="index">
                            <div class="relative group aspect-square">
                                <img :src="src" class="w-full h-full object-cover rounded-lg border border-gray-200">
                                <button type="button" @click.stop="removePreview(index)"
                                    class="absolute top-1 right-1 bg-red-600 text-white rounded-full w-5 h-5 text-xs hidden group-hover:flex items-center justify-center">
                                    ×
                                </button>
                            </div>
                        </template>
                        <div class="aspect-square border-2 border-dashed border-gray-200 rounded-lg flex items-center justify-center cursor-pointer hover:border-orange-400 transition"
                            @click.stop="$refs.fileInput.click()">
                            <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- SEO -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h2 class="font-semibold text-gray-800 mb-4 text-sm">SEO (Opsional)</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            Meta Title <span class="text-gray-400">(maks 100 karakter, default = nama produk)</span>
                        </label>
                        <input type="text" name="meta_title" maxlength="100"
                            value="<?= e(old('meta_title', $product->metaTitle ?? '')) ?>"
                            placeholder="<?= e($product->name ?? 'Nama Produk') ?>"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            Meta Description <span class="text-gray-400">(maks 200 karakter)</span>
                        </label>
                        <textarea name="meta_description" maxlength="200" rows="2"
                            placeholder="Deskripsi singkat produk untuk mesin pencari..."
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"><?= e(old('meta_description', $product->metaDescription ?? '')) ?></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Keywords</label>
                        <input type="text" name="meta_keywords"
                            value="<?= e(old('meta_keywords', $product->metaKeywords ?? '')) ?>"
                            placeholder="kata kunci, dipisah koma"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                </div>
            </div>

            <!-- ===== TOMBOL SIMPAN ===== -->
            <div class="flex flex-col sm:flex-row gap-3 pb-6">
                <button type="submit"
                    class="flex-1 sm:flex-none px-8 py-3 bg-orange-600 text-white rounded-xl font-medium hover:bg-orange-700 active:bg-orange-800 transition text-sm">
                    Simpan Perubahan
                </button>
                <a href="/admin/products"
                    class="flex-1 sm:flex-none px-8 py-3 bg-white text-gray-600 border border-gray-300 rounded-xl font-medium hover:bg-gray-50 transition text-sm text-center">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Toast stok tersimpan -->
<div id="stockSavedToast"
    class="hidden fixed bottom-6 right-6 bg-green-600 text-white text-sm px-4 py-2.5 rounded-xl shadow-lg items-center gap-2 z-50">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
    </svg>
    Stok tersimpan
</div>

<script>
function imagePreview() {
    return {
        previews: [],

        handleFiles(event) {
            Array.from(event.target.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = e => this.previews.push(e.target.result);
                reader.readAsDataURL(file);
            });
        },

        handleDrop(event) {
            const files = Array.from(event.dataTransfer.files).filter(f => f.type.startsWith('image/'));
            const dt = new DataTransfer();
            files.forEach(f => dt.items.add(f));
            this.$refs.fileInput.files = dt.files;

            files.forEach(file => {
                const reader = new FileReader();
                reader.onload = e => this.previews.push(e.target.result);
                reader.readAsDataURL(file);
            });
        },

        removePreview(index) {
            this.previews.splice(index, 1);
        }
    }
}

function showStockSaved() {
    const toast = document.getElementById('stockSavedToast');
    if (!toast) return;

    toast.classList.remove('hidden');
    toast.classList.add('flex');

    clearTimeout(window._stockToastTimer);
    window._stockToastTimer = setTimeout(() => {
        toast.classList.add('hidden');
        toast.classList.remove('flex');
    }, 2500);
}
</script>

<?php $this->endSection() ?>