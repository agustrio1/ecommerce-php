<?php $this->layout('layouts.admin', ['title' => 'Tambah Produk']) ?>

<?php $this->section('content') ?>

<?php $formErrors = errors(); ?>

<div class="min-h-screen bg-gray-50 px-4 py-6 sm:p-6" x-data="productForm()">
    <div class="max-w-4xl mx-auto">

        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Tambah Produk</h1>
                <p class="text-sm text-gray-400">Isi informasi produk di bawah</p>
            </div>
            <a href="/admin/products"
                class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali
            </a>
        </div>

        <?php if (! empty($formErrors)): ?>
            <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl">
                <p class="text-sm font-medium text-red-700 mb-1">Ada kesalahan pada form:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    <?php foreach ($formErrors as $error): ?>
                        <li class="text-sm text-red-600"><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="/admin/products" enctype="multipart/form-data" class="space-y-5">
            <?= csrf_field() ?>

            <!-- ===== INFORMASI DASAR ===== -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 sm:p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Informasi Dasar</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Produk <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="<?= e(old('name')) ?>" required
                            @input="generateSku($event.target.value)"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <?php if (! empty($formErrors['name'])): ?>
                            <p class="text-red-600 text-xs mt-1"><?= e($formErrors['name']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">SKU <span class="text-red-500">*</span></label>
                            <input type="text" name="sku" x-model="sku" required
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 font-mono">
                            <?php if (! empty($formErrors['sku'])): ?>
                                <p class="text-red-600 text-xs mt-1"><?= e($formErrors['sku']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Singkat</label>
                        <textarea name="short_description" rows="2" placeholder="Ringkasan singkat produk (ditampilkan di listing)"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"><?= e(old('short_description')) ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi Lengkap</label>
                        <?= $this->include('components.rich-editor', [
                            'name'  => 'description',
                            'value' => old('description', ''),
                            'id'    => 'editor_description',
                        ]) ?>
                    </div>
                </div>
            </div>

            <!-- ===== HARGA & STOK ===== -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 sm:p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Harga & Stok</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Harga Jual <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-3 flex items-center text-sm text-gray-500">Rp</span>
                            <input type="number" name="price" value="<?= e(old('price')) ?>" min="0" required
                                class="w-full pl-9 pr-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                        </div>
                        <?php if (! empty($formErrors['price'])): ?>
                            <p class="text-red-600 text-xs mt-1"><?= e($formErrors['price']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Harga Coret <span class="text-gray-400 text-xs">(opsional)</span></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-3 flex items-center text-sm text-gray-500">Rp</span>
                            <input type="number" name="compare_price" value="<?= e(old('compare_price')) ?>" min="0"
                                class="w-full pl-9 pr-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Harga Modal <span class="text-gray-400 text-xs">(opsional)</span></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-3 flex items-center text-sm text-gray-500">Rp</span>
                            <input type="number" name="cost_price" value="<?= e(old('cost_price')) ?>" min="0"
                                class="w-full pl-9 pr-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Berat
                            <span class="text-gray-400 text-xs font-normal">(gram, opsional)</span>
                        </label>
                        <input type="number" name="weight" value="<?= e(old('weight')) ?>" min="0"
                            placeholder="Contoh: 500"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>

                    <!-- Dimensi paket untuk kalkulasi ongkir Biteship -->
                    <div class="sm:col-span-2 lg:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Dimensi Paket
                            <span class="text-gray-400 text-xs font-normal">(cm, untuk kalkulasi ongkir)</span>
                        </label>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <div class="relative">
                                    <input type="number" name="length" value="<?= e(old('length', '0')) ?>" min="0" placeholder="0"
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 pr-10">
                                    <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400 pointer-events-none">cm</span>
                                </div>
                                <p class="text-xs text-gray-400 mt-1 text-center">Panjang</p>
                            </div>
                            <div>
                                <div class="relative">
                                    <input type="number" name="width" value="<?= e(old('width', '0')) ?>" min="0" placeholder="0"
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 pr-10">
                                    <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400 pointer-events-none">cm</span>
                                </div>
                                <p class="text-xs text-gray-400 mt-1 text-center">Lebar</p>
                            </div>
                            <div>
                                <div class="relative">
                                    <input type="number" name="height" value="<?= e(old('height', '0')) ?>" min="0" placeholder="0"
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 pr-10">
                                    <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400 pointer-events-none">cm</span>
                                </div>
                                <p class="text-xs text-gray-400 mt-1 text-center">Tinggi</p>
                            </div>
                        </div>
                        <?php if (! empty($formErrors['length']) || ! empty($formErrors['width']) || ! empty($formErrors['height'])): ?>
                            <p class="text-red-600 text-xs mt-1"><?= e($formErrors['length'] ?? $formErrors['width'] ?? $formErrors['height']) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Stok (hanya tampil kalau variant_mode = single) -->
                    <div x-show="variantMode === 'single'" class="sm:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stok</label>
                        <input type="number" name="stock" value="<?= e(old('stock', '0')) ?>" min="0"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                </div>
            </div>

            <!-- ===== KATEGORI ===== -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 sm:p-6">
                <h2 class="font-semibold text-gray-800 mb-1">Kategori</h2>
                <p class="text-xs text-gray-400 mb-4">Pilih satu atau lebih kategori</p>

                <?php if (empty($categories)): ?>
                    <p class="text-sm text-gray-400">Belum ada kategori. <a href="/admin/categories/create" class="text-orange-600 hover:underline">Buat kategori dulu</a>.</p>
                <?php else: ?>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($categories as $category): ?>
                            <label class="flex items-center gap-2 px-3 py-2 border border-gray-200 rounded-lg cursor-pointer hover:bg-orange-50 hover:border-orange-300 transition has-[:checked]:bg-orange-50 has-[:checked]:border-orange-400">
                                <input type="checkbox" name="category_ids[]" value="<?= $category->id ?>"
                                    class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                                <span class="text-sm text-gray-700"><?= e($category->name) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ===== VARIAN ===== -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 sm:p-6">
                <h2 class="font-semibold text-gray-800 mb-1">Varian Produk</h2>
                <p class="text-xs text-gray-400 mb-4">Pilih "Kombinasi Atribut" jika produk punya ukuran, warna, dll berbeda</p>

                <!-- Toggle mode -->
                <div class="flex gap-2 mb-5">
                    <button type="button"
                        @click="variantMode = 'single'; combinations = []"
                        :class="variantMode === 'single'
                            ? 'bg-orange-600 text-white border-orange-600'
                            : 'bg-white text-gray-600 border-gray-300 hover:border-orange-400'"
                        class="flex-1 sm:flex-none px-4 py-2.5 border rounded-lg text-sm font-medium transition">
                        Produk Tunggal
                    </button>
                    <button type="button"
                        @click="variantMode = 'combination'"
                        :class="variantMode === 'combination'
                            ? 'bg-orange-600 text-white border-orange-600'
                            : 'bg-white text-gray-600 border-gray-300 hover:border-orange-400'"
                        class="flex-1 sm:flex-none px-4 py-2.5 border rounded-lg text-sm font-medium transition">
                        Kombinasi Atribut
                    </button>
                </div>

                <input type="hidden" name="variant_mode" :value="variantMode">

                <!-- Mode: Kombinasi Atribut -->
                <div x-show="variantMode === 'combination'" class="space-y-4">
                    <?php if (empty($attributes)): ?>
                        <p class="text-sm text-gray-400">Belum ada atribut. <a href="/admin/attributes" class="text-orange-600 hover:underline">Tambah atribut dulu</a>.</p>
                    <?php else: ?>
                        <p class="text-xs text-gray-500">Centang value yang ingin dipakai per atribut, lalu klik "Preview Kombinasi":</p>

                        <div class="space-y-4">
                            <?php foreach ($attributes as $attribute): ?>
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <p class="text-sm font-medium text-gray-700 mb-3"><?= e($attribute['name']) ?></p>
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($attribute['values'] as $value): ?>
                                            <label
    class="flex items-center gap-1.5 px-3 py-1.5 border border-gray-200 rounded-full text-sm cursor-pointer hover:border-orange-400 transition has-[:checked]:bg-orange-600 has-[:checked]:text-white has-[:checked]:border-orange-600">
    <input type="checkbox"
        name="attributes[<?= $attribute['id'] ?>][]"
        value="<?= $value['id'] ?>"
        x-model="selectedValues[<?= $attribute['id'] ?>]"
        @change="computeCombinations()"
        class="sr-only">
    <?= e($value['value']) ?>
</label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Preview kombinasi -->
                        <div x-show="combinations.length > 0" class="mt-4">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-sm font-medium text-gray-700">
                                    Preview <span class="text-orange-600 font-bold" x-text="combinations.length"></span> kombinasi varian yang akan dibuat:
                                </p>
                            </div>
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50 border-b border-gray-200">
                                        <tr>
                                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Kombinasi</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">SKU (otomatis)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="(combo, index) in combinations" :key="index">
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-2.5 font-medium text-gray-700" x-text="combo.label"></td>
                                                <td class="px-4 py-2.5 text-gray-500 font-mono text-xs" x-text="combo.sku"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-xs text-gray-400 mt-2">Stok tiap varian bisa diatur setelah produk disimpan.</p>
                        </div>

                        <div x-show="variantMode === 'combination' && combinations.length === 0" class="text-center py-6 border border-dashed border-gray-200 rounded-lg">
                            <p class="text-sm text-gray-400">Centang value atribut di atas untuk melihat preview kombinasi</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Mode: Produk Tunggal (info saja, stok ada di section Harga) -->
                <div x-show="variantMode === 'single'">
                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <p class="text-sm text-gray-600">Produk tanpa varian — stok diisi di section Harga & Stok di atas.</p>
                    </div>
                </div>
            </div>

            <!-- ===== GAMBAR PRODUK ===== -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 sm:p-6">
                <h2 class="font-semibold text-gray-800 mb-1">Gambar Produk</h2>
                <p class="text-xs text-gray-400 mb-4">Maks 2MB per file, format JPG/PNG/WebP. Gambar pertama jadi foto utama.</p>

                <div x-data="imagePreview()"
                    class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-orange-400 transition cursor-pointer"
                    @dragover.prevent @drop.prevent="handleDrop($event)"
                    @click="$refs.fileInput.click()">

                    <input type="file" name="images[]" multiple accept="image/*"
                        x-ref="fileInput"
                        @change="handleFiles($event)"
                        class="hidden">

                    <div x-show="previews.length === 0">
                        <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-sm text-gray-500">Klik atau drag & drop gambar ke sini</p>
                        <p class="text-xs text-gray-400 mt-1">Bisa pilih beberapa sekaligus</p>
                    </div>

                    <div x-show="previews.length > 0" @click.stop class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3 cursor-default">
                        <template x-for="(src, index) in previews" :key="index">
                            <div class="relative group aspect-square">
                                <img :src="src" class="w-full h-full object-cover rounded-lg border border-gray-200">
                                <div x-show="index === 0" class="absolute top-1 left-1 bg-orange-600 text-white text-xs px-1.5 py-0.5 rounded font-medium">
                                    Utama
                                </div>
                                <button type="button" @click="removePreview(index)"
                                    class="absolute top-1 right-1 bg-red-600 text-white rounded-full w-5 h-5 text-xs hidden group-hover:flex items-center justify-center leading-none">
                                    ×
                                </button>
                            </div>
                        </template>
                        <div class="aspect-square border-2 border-dashed border-gray-200 rounded-lg flex items-center justify-center cursor-pointer hover:border-orange-400 transition"
                            @click="$refs.fileInput.click()">
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
                    Simpan Produk
                </button>
                <a href="/admin/products"
                    class="flex-1 sm:flex-none px-8 py-3 bg-white text-gray-600 border border-gray-300 rounded-xl font-medium hover:bg-gray-50 transition text-sm text-center">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function productForm() {
    return {
        sku: '<?= e(old('sku')) ?>',
        variantMode: 'single',
        selectedValues: <?= json_encode(
            array_fill_keys(
                array_column($attributes, 'id'),
                []
            )
        ) ?>,
        combinations: [],

        generateSku(name) {
            const slug = name
                .toUpperCase()
                .replace(/[^A-Z0-9]+/g, '-')
                .replace(/^-|-$/g, '')
                .substring(0, 20);
            this.sku = slug + '-' + Math.random().toString(36).substring(2, 6).toUpperCase();
        },

        computeCombinations() {
            const groups = Object.entries(this.selectedValues)
                .filter(([attrId, values]) => values && values.length > 0)
                .map(([attrId, values]) => values.map(id => ({
                    attrId,
                    valueId: id,
                    label: (() => {
                        const el = document.querySelector(
                            `input[name="attributes[${attrId}][]"][value="${id}"]`
                        );
                        return el ? el.closest('label').textContent.trim() : String(id);
                    })()
                })));

            if (groups.length === 0) {
                this.combinations = [];
                return;
            }

            const cartesian = (arrays) => arrays.reduce(
                (acc, group) => acc.flatMap(existing => group.map(item => [...existing, item])),
                [[]]
            );

            this.combinations = cartesian(groups).map(combo => ({
                label: combo.map(c => c.label).join(' / '),
                sku: this.sku + '-' + combo.map(c =>
                    c.label.substring(0, 4).toUpperCase().replace(/[^A-Z0-9]/g, '')
                ).join('-')
            }));
        }
    }
}

function imagePreview() {
    return {
        previews: [],
        files: [],

        handleFiles(event) {
            const newFiles = Array.from(event.target.files);
            newFiles.forEach(file => {
                const reader = new FileReader();
                reader.onload = e => this.previews.push(e.target.result);
                reader.readAsDataURL(file);
            });
        },

        handleDrop(event) {
            const newFiles = Array.from(event.dataTransfer.files).filter(f => f.type.startsWith('image/'));
            const dt = new DataTransfer();
            newFiles.forEach(f => dt.items.add(f));
            this.$refs.fileInput.files = dt.files;

            newFiles.forEach(file => {
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
</script>

<?php $this->endSection() ?>