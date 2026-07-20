<?php $this->layout('layouts.storefront', ['title' => $title]) ?>

<?php $this->section('content') ?>

<?php require_once __DIR__ . '/_brand.php'; ?>
<?php $brand = nexaroBrandTokens(); ?>

<div class="py-4">

    <!-- Filter kategori (horizontal scroll) -->
    <div class="flex gap-2 overflow-x-auto pb-2 mb-4 scrollbar-hide -mx-4 px-4">
        <a href="/produk<?= $search ? '?q=' . urlencode($search) : '' ?>"
            class="shrink-0 px-4 py-2 rounded-full text-sm font-medium transition border"
            style="<?= $activeCategorySlug === null
                ? 'background-color: ' . e($brand['ink']) . '; color: #fff; border-color: ' . e($brand['ink']) . ';'
                : 'background-color: #fff; color: ' . e($brand['ink']) . '; border-color: ' . e($brand['line']) . ';' ?>">
            Semua
        </a>
        <?php foreach ($categories as $cat): ?>
            <a href="/produk?kategori=<?= e($cat->slug) ?><?= $search ? '&q=' . urlencode($search) : '' ?>"
                class="shrink-0 px-4 py-2 rounded-full text-sm font-medium transition whitespace-nowrap border"
                style="<?= $activeCategorySlug === $cat->slug
                    ? 'background-color: ' . e($brand['ink']) . '; color: #fff; border-color: ' . e($brand['ink']) . ';'
                    : 'background-color: #fff; color: ' . e($brand['ink']) . '; border-color: ' . e($brand['line']) . ';' ?>">
                <?= e($cat->name) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Filter lanjutan (collapsible) -->
    <div x-data="{ showFilter: false }" class="mb-4">
        <button @click="showFilter = !showFilter"
            class="flex items-center gap-2 text-sm hover:opacity-70 transition mb-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 rounded"
            style="color: <?= e($brand['ink']) ?>;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
            </svg>
            Filter
            <?php if ($minPrice || $maxPrice || $minRating): ?>
                <span class="w-2 h-2 rounded-full" style="background-color: <?= e($brand['clay']) ?>;"></span>
            <?php endif; ?>
        </button>

        <div x-show="showFilter" x-collapse class="bg-white rounded-xl border p-4" style="border-color: <?= e($brand['line']) ?>;">
            <form method="GET" action="/produk" class="space-y-4">
                <?php if ($search): ?>
                    <input type="hidden" name="q" value="<?= e($search) ?>">
                <?php endif; ?>
                <?php if ($activeCategorySlug): ?>
                    <input type="hidden" name="kategori" value="<?= e($activeCategorySlug) ?>">
                <?php endif; ?>
                <input type="hidden" name="sort" value="<?= e($sort) ?>">

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider mb-2" style="color: <?= e($brand['moss']) ?>;">Rentang Harga</p>
                    <div class="flex items-center gap-2">
                        <input type="number" name="harga_min"
                            value="<?= e($minPrice ?? '') ?>"
                            placeholder="Min"
                            class="flex-1 px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2"
                            style="border-color: <?= e($brand['line']) ?>;">
                        <span class="text-gray-400 text-sm">—</span>
                        <input type="number" name="harga_max"
                            value="<?= e($maxPrice ?? '') ?>"
                            placeholder="Max"
                            class="flex-1 px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2"
                            style="border-color: <?= e($brand['line']) ?>;">
                    </div>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider mb-2" style="color: <?= e($brand['moss']) ?>;">Rating Minimum</p>
                    <div class="flex gap-2">
                        <?php foreach ([4, 3, 2, 1] as $star): ?>
                            <label class="flex items-center gap-1 cursor-pointer">
                                <input type="radio" name="rating" value="<?= $star ?>"
                                    <?= $minRating == $star ? 'checked' : '' ?>>
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
                        class="flex-1 py-2 text-white rounded-lg text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                        style="background-color: <?= e($brand['clay']) ?>;">
                        Terapkan
                    </button>
                    <a href="/produk<?= $search ? '?q=' . urlencode($search) : '' ?>"
                        class="px-4 py-2 border rounded-lg text-sm hover:bg-gray-50 transition"
                        style="border-color: <?= e($brand['line']) ?>; color: <?= e($brand['ink']) ?>;">
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
                <span class="font-medium" style="color: <?= e($brand['ink']) ?>;"><?= $total ?></span> hasil untuk
                "<span style="color: <?= e($brand['clay']) ?>;"><?= e($search) ?></span>"
            <?php elseif ($activeCategory): ?>
                Kategori: <span class="font-medium" style="color: <?= e($brand['ink']) ?>;"><?= e($activeCategory->name) ?></span>
                <span class="text-gray-400">(<?= $total ?> produk)</span>
            <?php else: ?>
                <span class="font-medium" style="color: <?= e($brand['ink']) ?>;"><?= $total ?></span> produk
            <?php endif; ?>
        </p>

        <form method="GET" action="/produk" id="sortForm">
            <?php if ($search): ?>
                <input type="hidden" name="q" value="<?= e($search) ?>">
            <?php endif; ?>
            <?php if ($activeCategorySlug): ?>
                <input type="hidden" name="kategori" value="<?= e($activeCategorySlug) ?>">
            <?php endif; ?>
            <select name="sort" onchange="document.getElementById('sortForm').submit()"
                class="text-sm border rounded-lg px-2 py-1.5 focus:outline-none focus:ring-2 bg-white"
                style="border-color: <?= e($brand['line']) ?>; color: <?= e($brand['ink']) ?>;">
                <option value="terbaru" <?= $sort === 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
                <option value="termurah" <?= $sort === 'termurah' ? 'selected' : '' ?>>Termurah</option>
                <option value="termahal" <?= $sort === 'termahal' ? 'selected' : '' ?>>Termahal</option>
            </select>
        </form>
    </div>

    <!-- Grid produk -->
    <?php if (empty($products)): ?>
        <div class="text-center py-16">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-20" fill="none" stroke="<?= e($brand['ink']) ?>" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <p class="text-gray-400 text-sm">Tidak ada produk ditemukan.</p>
            <?php if ($search): ?>
                <a href="/produk" class="text-sm hover:underline mt-2 block" style="color: <?= e($brand['clay']) ?>;">
                    Lihat semua produk
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            <?php foreach ($products as $product): ?>
                <?php
                $fs     = $flashSalePrices[$product->id] ?? null;
                $imgUrl = $productImages[$product->id] ?? null;
                echo renderProductCard($product, $imgUrl, $fs, $brand);
                ?>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php $totalPages = (int) ceil($total / $perPage); ?>
        <?php if ($totalPages > 1): ?>
            <?php $catQuery = $activeCategorySlug ? '&kategori=' . urlencode($activeCategorySlug) : ''; ?>
            <div class="flex justify-center gap-1.5 mt-6">
                <?php if ($page > 1): ?>
                    <a href="/produk?page=<?= $page - 1 ?><?= $search ? '&q=' . urlencode($search) : '' ?><?= $catQuery ?>"
                        class="w-9 h-9 flex items-center justify-center bg-white border rounded-lg text-sm hover:bg-gray-50 transition"
                        style="border-color: <?= e($brand['line']) ?>; color: <?= e($brand['ink']) ?>;">←</a>
                <?php endif; ?>

                <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                    <a href="/produk?page=<?= $p ?><?= $search ? '&q=' . urlencode($search) : '' ?><?= $catQuery ?>"
                        class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium transition border"
                        style="<?= $p === $page
                            ? 'background-color: ' . e($brand['ink']) . '; color: #fff; border-color: ' . e($brand['ink']) . ';'
                            : 'background-color: #fff; color: ' . e($brand['ink']) . '; border-color: ' . e($brand['line']) . ';' ?>">
                        <?= $p ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="/produk?page=<?= $page + 1 ?><?= $search ? '&q=' . urlencode($search) : '' ?><?= $catQuery ?>"
                        class="w-9 h-9 flex items-center justify-center bg-white border rounded-lg text-sm hover:bg-gray-50 transition"
                        style="border-color: <?= e($brand['line']) ?>; color: <?= e($brand['ink']) ?>;">→</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php $this->endSection() ?>