<?php $this->layout('layouts.storefront', ['title' => $title]) ?>

<?php $this->section('content') ?>

<div class="py-4">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-1.5 text-xs text-gray-400 mb-4 overflow-x-auto whitespace-nowrap scrollbar-hide">
        <a href="/" class="hover:text-orange-600 transition shrink-0">Home</a>
        <span>›</span>
        <a href="/kategori" class="hover:text-orange-600 transition shrink-0">Kategori</a>
        <?php foreach ($breadcrumb as $i => $crumb): ?>
            <span>›</span>
            <?php if ($i < count($breadcrumb) - 1): ?>
                <a href="/kategori/<?= e($crumb['slug']) ?>" class="hover:text-orange-600 transition shrink-0">
                    <?= e($crumb['name']) ?>
                </a>
            <?php else: ?>
                <span class="text-gray-600 shrink-0"><?= e($crumb['name']) ?></span>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <!-- Header kategori -->
    <div class="mb-4">
        <div class="flex items-center gap-3 mb-2">
            <?php if ($category->image): ?>
                <img src="/storage/<?= e($category->image) ?>" class="w-10 h-10 object-cover rounded-xl">
            <?php else: ?>
                <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                </div>
            <?php endif; ?>
            <div>
                <h1 class="font-bold text-gray-900 text-lg"><?= e($category->name) ?></h1>
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
                class="shrink-0 px-4 py-2 bg-orange-600 text-white rounded-full text-sm font-medium whitespace-nowrap">
                Semua
            </a>
            <?php foreach ($children as $child): ?>
                <a href="/kategori/<?= e($child->slug) ?>"
                    class="shrink-0 px-4 py-2 bg-white text-gray-600 border border-gray-200 rounded-full text-sm font-medium whitespace-nowrap hover:border-orange-400 transition">
                    <?= e($child->name) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Sort -->
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-400">
            <span class="font-medium text-gray-900"><?= $total ?></span> produk
        </p>
        <form method="GET" action="/kategori/<?= e($category->slug) ?>" id="sortForm">
            <select name="sort" onchange="document.getElementById('sortForm').submit()"
                class="text-sm border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">
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
            <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <p class="text-gray-400 text-sm">Belum ada produk di kategori ini.</p>
            <a href="/produk" class="text-orange-600 text-sm hover:underline mt-2 block">Lihat semua produk</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            <?php foreach ($products as $product): ?>
                <?php
                // Logic flash sale diinline (TIDAK pakai $this->include, sama
                // seperti fix di products.php dan home.php).
                $fs            = $flashSalePrices[$product->id] ?? null;
                $isFlashSale    = $fs !== null && ! ($fs['is_exhausted'] ?? false);
                $displayPrice   = $isFlashSale ? $fs['sale_price'] : $product->price;
                $originalPrice  = $isFlashSale ? $product->price : $product->comparePrice;
                $isOnSale       = $isFlashSale || $product->isOnSale();

                $discountPct = 0;
                if ($isFlashSale && $product->price > 0) {
                    $discountPct = round((($product->price - $fs['sale_price']) / $product->price) * 100);
                } elseif ($product->isOnSale()) {
                    $discountPct = $product->discountPercentage();
                }

                $imgUrl = $images[$product->id] ?? null;
                ?>
                <a href="/produk/<?= e($product->slug) ?>"
                    class="bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-md hover:border-orange-200 transition group">

                    <div class="aspect-square bg-gray-100 relative overflow-hidden">
                        <?php if ($imgUrl): ?>
                            <img src="<?= e($imgUrl) ?>"
                                alt="<?= e($product->name) ?>"
                                loading="lazy"
                                class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center">
                                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        <?php endif; ?>

                        <?php if ($discountPct > 0): ?>
                            <div class="absolute top-2 left-2 <?= $isFlashSale ? 'bg-red-500' : 'bg-orange-500' ?> text-white text-xs font-bold px-1.5 py-0.5 rounded">
                                -<?= $discountPct ?>%
                            </div>
                        <?php endif; ?>

                        <?php if ($isFlashSale): ?>
                            <div class="absolute top-2 right-2 bg-red-600 text-white text-xs font-bold px-1.5 py-0.5 rounded flex items-center gap-0.5">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                Sale
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="p-3">
                        <p class="text-sm text-gray-800 font-medium line-clamp-2 mb-1.5 group-hover:text-orange-700 transition leading-tight">
                            <?= e($product->name) ?>
                        </p>
                        <p class="font-bold text-sm <?= $isFlashSale ? 'text-red-600' : 'text-gray-900' ?>">
                            Rp <?= number_format($displayPrice, 0, ',', '.') ?>
                        </p>
                        <?php if ($isOnSale && $originalPrice > 0): ?>
                            <p class="text-xs text-gray-400 line-through">
                                Rp <?= number_format($originalPrice, 0, ',', '.') ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php $totalPages = (int) ceil($total / $perPage); ?>
        <?php if ($totalPages > 1): ?>
            <div class="flex justify-center gap-1.5 mt-6">
                <?php if ($page > 1): ?>
                    <a href="/kategori/<?= e($category->slug) ?>?page=<?= $page - 1 ?>&sort=<?= $sort ?>"
                        class="w-9 h-9 flex items-center justify-center bg-white border border-gray-200 rounded-lg text-sm hover:bg-gray-50 transition">←</a>
                <?php endif; ?>

                <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                    <a href="/kategori/<?= e($category->slug) ?>?page=<?= $p ?>&sort=<?= $sort ?>"
                        class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium
                               <?= $p === $page ? 'bg-orange-600 text-white' : 'bg-white border border-gray-200 hover:bg-gray-50' ?>">
                        <?= $p ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="/kategori/<?= e($category->slug) ?>?page=<?= $page + 1 ?>&sort=<?= $sort ?>"
                        class="w-9 h-9 flex items-center justify-center bg-white border border-gray-200 rounded-lg text-sm hover:bg-gray-50 transition">→</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php $this->endSection() ?>