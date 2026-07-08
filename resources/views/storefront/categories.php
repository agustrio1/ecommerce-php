<?php $this->layout('layouts.storefront', ['title' => 'Semua Kategori']) ?>

<?php $this->section('content') ?>

<div class="py-4">
    <h1 class="font-bold text-gray-900 text-lg mb-4">Semua Kategori</h1>

    <?php if (empty($tree)): ?>
        <p class="text-gray-400 text-sm text-center py-8">Belum ada kategori.</p>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($tree as $node): ?>
                <?php $cat = $node['category']; ?>
                <?php $prodCount = $counts[$cat->id] ?? 0; ?>

                <!-- Kategori utama -->
                <div>
                    <a href="/kategori/<?= e($cat->slug) ?>"
                        class="flex items-center gap-3 p-4 bg-white border border-gray-100 rounded-xl hover:border-orange-300 hover:shadow-sm transition group">
                        <div class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center shrink-0 group-hover:bg-orange-100 transition">
                            <?php if ($cat->image): ?>
                                <img src="/storage/<?= e($cat->image) ?>" class="w-8 h-8 object-cover rounded-lg">
                            <?php else: ?>
                                <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-800 group-hover:text-orange-700 transition"><?= e($cat->name) ?></p>
                            <p class="text-xs text-gray-400 mt-0.5"><?= $prodCount ?> produk</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-300 group-hover:text-orange-400 transition shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    <!-- Sub-kategori -->
                    <?php if (! empty($node['children'])): ?>
                        <div class="ml-4 mt-1.5 space-y-1">
                            <?php foreach ($node['children'] as $childNode): ?>
                                <?php $child = $childNode['category']; ?>
                                <?php $childCount = $counts[$child->id] ?? 0; ?>
                                <a href="/kategori/<?= e($child->slug) ?>"
                                    class="flex items-center gap-3 px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl hover:border-orange-200 hover:bg-orange-50 transition group">
                                    <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center shrink-0 border border-gray-100">
                                        <?php if ($child->image): ?>
                                            <img src="/storage/<?= e($child->image) ?>" class="w-5 h-5 object-cover rounded">
                                        <?php else: ?>
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-700 group-hover:text-orange-600 transition"><?= e($child->name) ?></p>
                                        <p class="text-xs text-gray-400"><?= $childCount ?> produk</p>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-300 group-hover:text-orange-400 transition shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php $this->endSection() ?>