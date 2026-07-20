<?php $this->layout('layouts.storefront', ['title' => $title]) ?>

<?php $this->section('content') ?>

<?php require_once __DIR__ . '/_brand.php'; ?>
<?php $brand = nexaroBrandTokens(); ?>

<div class="py-4">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-1.5 text-xs text-gray-400 mb-4 overflow-x-auto whitespace-nowrap scrollbar-hide">
        <a href="/" class="hover:opacity-70 transition shrink-0">Home</a>
        <span>›</span>
        <a href="/kategori" class="hover:opacity-70 transition shrink-0">Kategori</a>
        <?php foreach ($breadcrumb as $i => $crumb): ?>
            <span>›</span>
            <?php if ($i < count($breadcrumb) - 1): ?>
                <a href="/kategori/<?= e($crumb['slug']) ?>" class="hover:opacity-70 transition shrink-0">
                    <?= e($crumb['name']) ?>
                </a>
            <?php else: ?>
                <span class="shrink-0" style="color: <?= e($brand['ink']) ?>;"><?= e($crumb['name']) ?></span>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <!-- Header kategori -->
    <div class="mb-4">
        <div class="flex items-center gap-3 mb-2">
            <?php if ($category->image): ?>
                <img src="/storage/<?= e($category->image) ?>" class="w-10 h-10 object-cover rounded-xl">
            <?php else: ?>
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background-color: <?= e($brand['stone']) ?>;">
                    <svg class="w-5 h-5" fill="none" stroke="<?= e($brand['ink']) ?>" viewBox="0 0 24 24">
                        <?= categoryIconPath($category->name) ?>
                    </svg>
                </div>
            <?php endif; ?>
            <div>
                <h1 class="font-bold text-lg" style="color: <?= e($brand['ink']) ?>;"><?= e($category->name) ?></h1>
                <p class="text-xs text-gray-400"><?= $total ?> produk</p>
            </div>
        </div>

        <?php if ($category->description): ?>
            <p class="text-sm text-gray-500 leading-relaxed"><?= e($category->description) ?></p>
        <?php endif; ?>
    </div>

    <!-- Sub-kategori (kalau ada) -->
    <?php if (! empty($children)): ?>
        <div class="flex gap-2 overflow-x-auto pb-2 mb-4 scrollbar-hide -mx-4 px-4">
            <a href="/kategori/<?= e($category->slug) ?>"
                class="shrink-0 px-4 py-2 text-white rounded-full text-sm font-medium whitespace-nowrap"
                style="background-color: <?= e($brand['ink']) ?>;">
                Semua
            </a>
            <?php foreach ($children as $child): ?>
                <a href="/kategori/<?= e($child->slug) ?>"
                    class="shrink-0 px-4 py-2 bg-white rounded-full text-sm font-medium whitespace-nowrap border transition"
                    style="color: <?= e($brand['ink']) ?>; border-color: <?= e($brand['line']) ?>;">
                    <?= e($child->name) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Sort -->
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-400">
            <span class="font-medium" style="color: <?= e($brand['ink']) ?>;"><?= $total ?></span> produk
        </p>
        <form method="GET" action="/kategori/<?= e($category->slug) ?>" id="sortForm">
            <select name="sort" onchange="document.getElementById('sortForm').submit()"
                class="text-sm border rounded-lg px-2 py-1.5 focus:outline-none focus:ring-2 bg-white"
                style="border-color: <?= e($brand['line']) ?>; color: <?= e($brand['ink']) ?>;">
                <option value="terbaru" <?= $sort === 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
                <option value="termurah" <?= $sort === 'termurah' ? 'selected' : '' ?>>Termurah</option>
                <option value="termahal" <?= $sort === 'termahal' ? 'selected' : '' ?>>Termahal</option>
                <option value="terlaris" <?= $sort === 'terlaris' ? 'selected' : '' ?>>Terlaris</option>
                <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Rating</option>
            </select>
        </form>
    </div>

    <!-- Grid produk -->
    <?php if (empty($products)): ?>
        <div class="text-center py-16">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-20" fill="none" stroke="<?= e($brand['ink']) ?>" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <p class="text-gray-400 text-sm">Belum ada produk di kategori ini.</p>
            <a href="/produk" class="text-sm hover:underline mt-2 block" style="color: <?= e($brand['clay']) ?>;">Lihat semua produk</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            <?php foreach ($products as $product): ?>
                <?php
                $fs     = $flashSalePrices[$product->id] ?? null;
                $imgUrl = $images[$product->id] ?? null;
                echo renderProductCard($product, $imgUrl, $fs, $brand);
                ?>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php $totalPages = (int) ceil($total / $perPage); ?>
        <?php if ($totalPages > 1): ?>
            <div class="flex justify-center gap-1.5 mt-6">
                <?php if ($page > 1): ?>
                    <a href="/kategori/<?= e($category->slug) ?>?page=<?= $page - 1 ?>&sort=<?= e($sort) ?>"
                        class="w-9 h-9 flex items-center justify-center bg-white border rounded-lg text-sm hover:bg-gray-50 transition"
                        style="border-color: <?= e($brand['line']) ?>; color: <?= e($brand['ink']) ?>;">←</a>
                <?php endif; ?>

                <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                    <a href="/kategori/<?= e($category->slug) ?>?page=<?= $p ?>&sort=<?= e($sort) ?>"
                        class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium border"
                        style="<?= $p === $page
                            ? 'background-color: ' . e($brand['ink']) . '; color: #fff; border-color: ' . e($brand['ink']) . ';'
                            : 'background-color: #fff; color: ' . e($brand['ink']) . '; border-color: ' . e($brand['line']) . ';' ?>">
                        <?= $p ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="/kategori/<?= e($category->slug) ?>?page=<?= $page + 1 ?>&sort=<?= e($sort) ?>"
                        class="w-9 h-9 flex items-center justify-center bg-white border rounded-lg text-sm hover:bg-gray-50 transition"
                        style="border-color: <?= e($brand['line']) ?>; color: <?= e($brand['ink']) ?>;">→</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php $this->endSection() ?>