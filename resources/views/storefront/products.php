<?php $this->layout('layouts.storefront', ['title' => $title]) ?>

<?php $this->section('content') ?>

<div class="py-4">

    <!-- Filter kategori (horizontal scroll) -->
    <div class="flex gap-2 overflow-x-auto pb-2 mb-4 scrollbar-hide -mx-4 px-4">
        <a href="/produk<?= $search ? '?q=' . urlencode($search) : '' ?>"
            class="shrink-0 px-4 py-2 rounded-full text-sm font-medium transition
                   <?= $activeCategoryId === null ? 'bg-orange-600 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:border-orange-400' ?>">
            Semua
        </a>
        <?php foreach ($categories as $cat): ?>
            <a href="/produk?kategori=<?= $cat->id ?><?= $search ? '&q=' . urlencode($search) : '' ?>"
                class="shrink-0 px-4 py-2 rounded-full text-sm font-medium transition whitespace-nowrap
                       <?= $activeCategoryId === $cat->id ? 'bg-orange-600 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:border-orange-400' ?>">
                <?= e($cat->name) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Filter lanjutan (collapsible) -->
    <div x-data="{ showFilter: false }" class="mb-4">
        <button @click="showFilter = !showFilter"
            class="flex items-center gap-2 text-sm text-gray-600 hover:text-orange-600 transition mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
            </svg>
            Filter
            <?php if ($minPrice || $maxPrice || $minRating): ?>
                <span class="w-2 h-2 bg-orange-500 rounded-full"></span>
            <?php endif; ?>
        </button>

        <div x-show="showFilter" x-collapse class="bg-white rounded-xl border border-gray-200 p-4">
            <form method="GET" action="/produk" class="space-y-4">
                <?php if ($search): ?>
                    <input type="hidden" name="q" value="<?= e($search) ?>">
                <?php endif; ?>
                <?php if ($activeCategoryId): ?>
                    <input type="hidden" name="kategori" value="<?= $activeCategoryId ?>">
                <?php endif; ?>
                <input type="hidden" name="sort" value="<?= e($sort) ?>">

                <!-- Filter harga -->
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Rentang Harga</p>
                    <div class="flex items-center gap-2">
                        <input type="number" name="harga_min"
                            value="<?= e($minPrice ?? '') ?>"
                            placeholder="Min"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <span class="text-gray-400 text-sm">—</span>
                        <input type="number" name="harga_max"
                            value="<?= e($maxPrice ?? '') ?>"
                            placeholder="Max"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                </div>

                <!-- Filter rating -->
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Rating Minimum</p>
                    <div class="flex gap-2">
                        <?php foreach ([4, 3, 2, 1] as $star): ?>
                            <label class="flex items-center gap-1 cursor-pointer">
                                <input type="radio" name="rating" value="<?= $star ?>"
                                    <?= $minRating == $star ? 'checked' : '' ?>
                                    class="text-orange-600 focus:ring-orange-500">
                                <span class="text-sm text-amber-400">
                                    <?= str_repeat('★', $star) ?>
                                </span>
                                <span class="text-xs text-gray-400">+</span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                        class="flex-1 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700 transition">
                        Terapkan
                    </button>
                    <a href="/produk<?= $search ? '?q=' . urlencode($search) : '' ?>"
                        class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Info hasil -->
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-500">
            <?php if ($search): ?>
                <span class="font-medium text-gray-900"><?= $total ?></span> hasil untuk
                "<span class="text-orange-600"><?= e($search) ?></span>"
            <?php elseif ($activeCategory): ?>
                Kategori: <span class="font-medium text-gray-900"><?= e($activeCategory->name) ?></span>
                <span class="text-gray-400">(<?= $total ?> produk)</span>
            <?php else: ?>
                <span class="font-medium text-gray-900"><?= $total ?></span> produk
            <?php endif; ?>
        </p>

        <!-- Sort -->
        <form method="GET" action="/produk" id="sortForm">
            <?php if ($search): ?>
                <input type="hidden" name="q" value="<?= e($search) ?>">
            <?php endif; ?>
            <?php if ($activeCategoryId): ?>
                <input type="hidden" name="kategori" value="<?= $activeCategoryId ?>">
            <?php endif; ?>
            <select name="sort" onchange="document.getElementById('sortForm').submit()"
                class="text-sm border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">
                <option value="terbaru" <?= $sort === 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
                <option value="termurah" <?= $sort === 'termurah' ? 'selected' : '' ?>>Termurah</option>
                <option value="termahal" <?= $sort === 'termahal' ? 'selected' : '' ?>>Termahal</option>
            </select>
        </form>
    </div>

    <!-- Grid produk -->
    <?php if (empty($products)): ?>
        <div class="text-center py-16">
            <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <p class="text-gray-400 text-sm">Tidak ada produk ditemukan.</p>
            <?php if ($search): ?>
                <a href="/produk" class="text-orange-600 text-sm hover:underline mt-2 block">
                    Lihat semua produk
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            <?php foreach ($products as $product): ?>
                <?php
                // Logic flash sale — diinline langsung di sini, TIDAK pakai
                // $this->include() karena method itu tidak didukung oleh
                // custom template engine ini (menyebabkan fatal error diam-diam
                // yang bikin seluruh grid produk gak muncul).
                $fs            = $flashSalePrices[$product->id] ?? null;
                $isFlashSale   = $fs !== null && ! ($fs['is_exhausted'] ?? false);
                $displayPrice  = $isFlashSale ? $fs['sale_price'] : $product->price;
                $originalPrice = $isFlashSale ? $product->price : $product->comparePrice;
                $isOnSale      = $isFlashSale || $product->isOnSale();

                $discountPct = 0;
                if ($isFlashSale && $product->price > 0) {
                    $discountPct = round((($product->price - $fs['sale_price']) / $product->price) * 100);
                } elseif ($product->isOnSale()) {
                    $discountPct = $product->discountPercentage();
                }

                $imgUrl = $productImages[$product->id] ?? null;
                ?>
                <a href="/produk/<?= e($product->slug) ?>"
                    class="bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-md hover:border-orange-200 transition group">

                    <!-- Foto produk -->
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

                    <!-- Info produk -->
                    <div class="p-3">
                        <p class="text-sm text-gray-800 font-medium line-clamp-2 mb-1.5 group-hover:text-orange-700 transition leading-tight">
                            <?= e($product->name) ?>
                        </p>
                        <p class="font-bold text-sm <?= $isFlashSale ? 'text-red-600' : 'text-gray-900' ?>">
                            Rp <?= number_format($displayPrice, 0, ',', '.') ?>
                        </p>
                        <?php if ($isOnSale && $originalPrice > 0): ?>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="text-xs text-gray-400 line-through">
                                    Rp <?= number_format($originalPrice, 0, ',', '.') ?>
                                </span>
                                <?php if ($isFlashSale && isset($fs['stock_limit']) && $fs['stock_limit']): ?>
                                    <span class="text-xs text-red-500">
                                        Sisa <?= max(0, $fs['stock_limit'] - ($fs['sold_count'] ?? 0)) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
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
                    <a href="/produk?page=<?= $page - 1 ?><?= $search ? '&q=' . urlencode($search) : '' ?><?= $activeCategoryId ? '&kategori=' . $activeCategoryId : '' ?>"
                        class="w-9 h-9 flex items-center justify-center bg-white border border-gray-200 rounded-lg text-sm hover:bg-gray-50 transition">
                        ←
                    </a>
                <?php endif; ?>

                <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                    <a href="/produk?page=<?= $p ?><?= $search ? '&q=' . urlencode($search) : '' ?><?= $activeCategoryId ? '&kategori=' . $activeCategoryId : '' ?>"
                        class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium transition
                               <?= $p === $page ? 'bg-orange-600 text-white' : 'bg-white border border-gray-200 hover:bg-gray-50' ?>">
                        <?= $p ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="/produk?page=<?= $page + 1 ?><?= $search ? '&q=' . urlencode($search) : '' ?><?= $activeCategoryId ? '&kategori=' . $activeCategoryId : '' ?>"
                        class="w-9 h-9 flex items-center justify-center bg-white border border-gray-200 rounded-lg text-sm hover:bg-gray-50 transition">
                        →
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php $this->endSection() ?>