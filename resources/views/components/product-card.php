<?php
/**
 * Product card component (partial).
 *
 * PENTING: struktur ini identik dengan renderProductCard() di _brand.php,
 * yang sudah dipakai konsisten di home.php, products.php, dan
 * category-products.php. Kalau file ini benar-benar di-include lewat
 * mekanisme yang didukung template engine project (BUKAN $this->include,
 * yang tercatat tidak didukung dan menyebabkan fatal error diam-diam),
 * pertimbangkan untuk memanggil renderProductCard() saja dari sini agar
 * tidak ada 2 sumber kebenaran untuk markup yang sama. Kalau file ini
 * sebenarnya sudah tidak dipakai di mana pun, sebaiknya dihapus.
 *
 * Variables yang dibutuhkan:
 * @var \App\Modules\Product\Domain\Entities\Product $product
 * @var string|null $imgUrl          — URL gambar primary
 * @var array|null  $flashSalePrice  — ['sale_price' => float, 'is_exhausted' => bool] atau null
 */

require_once __DIR__ . '/_brand.php';
$brand = nexaroBrandTokens();

$fs            = $flashSalePrice ?? null;
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

$badgeColor = $isFlashSale ? $brand['urgent'] : $brand['clay'];
$priceColor = $isFlashSale ? $brand['urgent'] : $brand['ink'];
?>
<a href="/produk/<?= e($product->slug) ?>"
    class="bg-white rounded-lg border overflow-hidden hover:shadow-md transition group focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
    style="border-color: <?= e($brand['line']) ?>;">

    <!-- Foto produk -->
    <div class="aspect-square relative overflow-hidden" style="background-color: <?= e($brand['stone']) ?>;">
        <?php if ($imgUrl ?? null): ?>
            <img src="<?= e($imgUrl) ?>"
                alt="<?= e($product->name) ?>"
                loading="lazy"
                class="w-full h-full object-cover group-hover:scale-105 transition duration-300 motion-reduce:transition-none motion-reduce:transform-none">
        <?php else: ?>
            <div class="w-full h-full flex items-center justify-center">
                <svg class="w-12 h-12 opacity-30" fill="none" stroke="<?= e($brand['ink']) ?>" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        <?php endif; ?>

        <!-- Badge diskon (swing tag) -->
        <?php if ($discountPct > 0): ?>
            <div class="absolute top-2 left-2 flex items-center gap-1 text-white text-[11px] font-bold pl-2.5 pr-2 py-1"
                style="background-color: <?= e($badgeColor) ?>; clip-path: polygon(0 0, 100% 0, 100% 100%, 10px 100%, 0 55%);">
                <span class="w-1 h-1 rounded-full bg-white/70"></span>-<?= $discountPct ?>%
            </div>
        <?php endif; ?>

        <!-- Badge flash sale -->
        <?php if ($isFlashSale): ?>
            <div class="absolute top-2 right-2 text-white text-[10px] font-bold px-1.5 py-0.5 rounded flex items-center gap-0.5" style="background-color: <?= e($brand['urgent']) ?>;">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Sale
            </div>
        <?php endif; ?>
    </div>

    <!-- Info produk -->
    <div class="p-3">
        <p class="text-sm font-medium line-clamp-2 mb-1.5 leading-tight" style="color: <?= e($brand['ink']) ?>;">
            <?= e($product->name) ?>
        </p>

        <p class="font-bold text-sm" style="color: <?= e($priceColor) ?>;">
            Rp <?= number_format($displayPrice, 0, ',', '.') ?>
        </p>

        <?php if ($isOnSale && $originalPrice > 0): ?>
            <div class="flex items-center gap-1.5 mt-0.5">
                <span class="text-xs text-gray-400 line-through">
                    Rp <?= number_format($originalPrice, 0, ',', '.') ?>
                </span>
                <?php if ($isFlashSale && isset($fs['stock_limit']) && $fs['stock_limit']): ?>
                    <span class="text-xs" style="color: <?= e($brand['urgent']) ?>;">
                        Sisa <?= max(0, $fs['stock_limit'] - ($fs['sold_count'] ?? 0)) ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</a>