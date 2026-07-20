<?php $this->layout('layouts.storefront', ['title' => 'Semua Kategori']) ?>

<?php $this->section('content') ?>

<?php require_once __DIR__ . '/_brand.php'; ?>
<?php $brand = nexaroBrandTokens(); ?>

<div class="py-4">
    <h1 class="font-bold text-lg mb-4" style="color: <?= e($brand['ink']) ?>;">Semua Kategori</h1>

    <?php if (empty($tree)): ?>
        <p class="text-gray-400 text-sm text-center py-8">Belum ada kategori.</p>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($tree as $node): ?>
                <?php $cat = $node['category']; ?>
                <?php $prodCount = $counts[$cat->id] ?? 0; ?>

                <div>
                    <a href="/kategori/<?= e($cat->slug) ?>"
                        class="flex items-center gap-3 p-4 bg-white border rounded-xl hover:shadow-sm transition group focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                        style="border-color: <?= e($brand['line']) ?>;">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0" style="background-color: <?= e($brand['stone']) ?>;">
                            <?php if ($cat->image): ?>
                                <img src="/storage/<?= e($cat->image) ?>" class="w-8 h-8 object-cover rounded-lg">
                            <?php else: ?>
                                <svg class="w-6 h-6" fill="none" stroke="<?= e($brand['ink']) ?>" viewBox="0 0 24 24">
                                    <?= categoryIconPath($cat->name) ?>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold group-hover:opacity-70 transition" style="color: <?= e($brand['ink']) ?>;"><?= e($cat->name) ?></p>
                            <p class="text-xs text-gray-400 mt-0.5"><?= $prodCount ?> produk</p>
                        </div>
                        <svg class="w-5 h-5 opacity-30 group-hover:opacity-60 transition shrink-0" fill="none" stroke="<?= e($brand['ink']) ?>" viewBox="0 0 24 24">
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
                                    class="flex items-center gap-3 px-4 py-2.5 bg-gray-50 border rounded-xl transition group focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                                    style="border-color: <?= e($brand['line']) ?>;">
                                    <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center shrink-0 border" style="border-color: <?= e($brand['line']) ?>;">
                                        <?php if ($child->image): ?>
                                            <img src="/storage/<?= e($child->image) ?>" class="w-5 h-5 object-cover rounded">
                                        <?php else: ?>
                                            <svg class="w-4 h-4" fill="none" stroke="<?= e($brand['ink']) ?>" viewBox="0 0 24 24">
                                                <?= categoryIconPath($child->name) ?>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm group-hover:opacity-70 transition" style="color: <?= e($brand['ink']) ?>;"><?= e($child->name) ?></p>
                                        <p class="text-xs text-gray-400"><?= $childCount ?> produk</p>
                                    </div>
                                    <svg class="w-4 h-4 opacity-30 group-hover:opacity-60 transition shrink-0" fill="none" stroke="<?= e($brand['ink']) ?>" viewBox="0 0 24 24">
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