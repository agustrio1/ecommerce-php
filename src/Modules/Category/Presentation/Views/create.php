<?php $this->layout('layouts.admin', ['title' => 'Tambah Kategori']) ?>

<?php $this->section('content') ?>

<div class="min-h-screen p-6">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-xl font-bold text-gray-900 mb-6">Tambah Kategori</h1>

        <?php $formErrors = errors(); ?>

        <div class="bg-white rounded-xl shadow-md p-6">
            <form method="POST" action="/admin/categories" class="space-y-4">
                <?= csrf_field() ?>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kategori</label>
                    <input type="text" name="name" value="<?= e(old('name')) ?>" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <?php if (! empty($formErrors['name'])): ?>
                        <p class="text-red-600 text-xs mt-1"><?= e($formErrors['name']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategori Induk (opsional)</label>
                    <select name="parent_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="">— Tidak ada (kategori utama) —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat->id ?>"><?= e($cat->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi (opsional)</label>
                    <textarea name="description" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"><?= e(old('description')) ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Urutan Tampil</label>
                    <input type="number" name="sort_order" value="<?= e(old('sort_order', '0')) ?>"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>

                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                    <span class="text-sm text-gray-700">Aktif</span>
                </label>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="px-5 py-2.5 bg-orange-600 text-white rounded-lg font-medium hover:bg-orange-700 transition">
                        Simpan
                    </button>
                    <a href="/admin/categories" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $this->endSection() ?>