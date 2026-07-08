<?php $this->layout('layouts.admin', ['title' => 'Kelola Kategori']) ?>

<?php $this->section('content') ?>

<div class="min-h-screen p-6">
    <div class="max-w-4xl mx-auto">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Kelola Kategori</h1>
                <p class="text-sm text-gray-500">Atur kategori dan sub-kategori produk</p>
            </div>
            <a href="/admin/categories/create"
                class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700 transition">
                + Tambah Kategori
            </a>
        </div>

        <?php $flashSuccess = \App\Core\Http\Session::getFlash('success'); ?>
        <?php $flashError = \App\Core\Http\Session::getFlash('error'); ?>
        <?php if ($flashSuccess): ?>
            <div class="mb-4 p-3 bg-green-50 text-green-700 text-sm rounded-lg border border-green-200"><?= e($flashSuccess) ?></div>
        <?php endif; ?>
        <?php if ($flashError): ?>
            <div class="mb-4 p-3 bg-red-50 text-red-700 text-sm rounded-lg border border-red-200"><?= e($flashError) ?></div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-md p-6">
            <?php if (empty($tree)): ?>
                <p class="text-gray-500 text-sm">Belum ada kategori. Klik "Tambah Kategori" untuk membuat yang pertama.</p>
            <?php else: ?>
                <?php
                function renderCategoryTree(array $nodes, int $depth = 0): void {
                    foreach ($nodes as $node) {
                        $category = $node['category'];
                        $indent = $depth * 24;
                        ?>
                        <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0" style="padding-left: <?= $indent ?>px">
                            <div class="flex items-center gap-2">
                                <?php if ($depth > 0): ?><span class="text-gray-300">└</span><?php endif; ?>
                                <span class="font-medium text-gray-800"><?= e($category->name) ?></span>
                                <?php if (! $category->isActive): ?>
                                    <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">Nonaktif</span>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center gap-3 text-sm">
                                <a href="/admin/categories/<?= $category->id ?>/edit" class="text-orange-600 hover:underline">Edit</a>
                                <form method="POST" action="/admin/categories/<?= $category->id ?>" onsubmit="return confirm('Hapus kategori ini?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="text-red-600 hover:underline">Hapus</button>
                                </form>
                            </div>
                        </div>
                        <?php
                        if (! empty($node['children'])) {
                            renderCategoryTree($node['children'], $depth + 1);
                        }
                    }
                }
                renderCategoryTree($tree);
                ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $this->endSection() ?>