<?php $this->layout('layouts.admin', ['title' => 'Kelola Atribut']) ?>

<?php $this->section('content') ?>

<div class="min-h-screen bg-gray-50 px-4 py-6 sm:p-6">
    <div class="max-w-3xl mx-auto">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mb-1">Kelola Atribut Produk</h1>
        <p class="text-sm text-gray-500 mb-6">Atribut dipakai untuk membuat varian produk (mis. Ukuran, Warna)</p>

        <?php $flashSuccess = \App\Core\Http\Session::getFlash('success'); ?>
        <?php $flashError = \App\Core\Http\Session::getFlash('error'); ?>
        <?php if ($flashSuccess): ?>
            <div class="mb-4 p-3 bg-green-50 text-green-700 text-sm rounded-lg border border-green-200"><?= e($flashSuccess) ?></div>
        <?php endif; ?>
        <?php if ($flashError): ?>
            <div class="mb-4 p-3 bg-red-50 text-red-700 text-sm rounded-lg border border-red-200"><?= e($flashError) ?></div>
        <?php endif; ?>

        <!-- Form tambah attribute baru -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 sm:p-6 mb-6">
            <h2 class="font-semibold text-gray-800 mb-3">Tambah Atribut Baru</h2>
            <form method="POST" action="/admin/attributes" class="flex flex-col sm:flex-row gap-3">
                <?= csrf_field() ?>
                <input type="text" name="name" placeholder="Contoh: Ukuran, Warna, Material" required
                    class="w-full sm:flex-1 px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                <button type="submit"
                    class="w-full sm:w-auto px-5 py-2.5 bg-orange-600 text-white text-sm rounded-lg font-medium hover:bg-orange-700 active:bg-orange-800 transition whitespace-nowrap">
                    + Tambah Atribut
                </button>
            </form>
        </div>

        <!-- Daftar attribute + value -->
        <div class="space-y-4">
            <?php foreach ($attributes as $attribute): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-900"><?= e($attribute['name']) ?></h3>
                        <span class="text-xs text-gray-400"><?= count($attribute['values']) ?> value</span>
                    </div>

                    <div class="flex flex-wrap gap-2 mb-4">
                        <?php if (empty($attribute['values'])): ?>
                            <span class="text-sm text-gray-400 italic">Belum ada value</span>
                        <?php else: ?>
                            <?php foreach ($attribute['values'] as $value): ?>
                                <span class="px-3 py-1.5 bg-orange-50 text-orange-700 text-sm font-medium rounded-full border border-orange-100">
                                    <?= e($value['value']) ?>
                                </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <form method="POST" action="/admin/attribute-values" class="flex flex-col sm:flex-row gap-2 pt-3 border-t border-gray-100">
                        <?= csrf_field() ?>
                        <input type="hidden" name="attribute_id" value="<?= $attribute['id'] ?>">
                        <input type="text" name="value" placeholder="Tambah value baru..." required
                            class="w-full sm:flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        <button type="submit"
                            class="w-full sm:w-auto px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg font-medium hover:bg-gray-200 active:bg-gray-300 transition whitespace-nowrap">
                            + Value
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($attributes)): ?>
            <div class="text-center py-12">
                <p class="text-gray-400 text-sm">Belum ada atribut. Tambahkan atribut pertama di atas.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php $this->endSection() ?>