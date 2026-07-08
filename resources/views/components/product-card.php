<?php
/**
 * Product card component.
 *
 * Variables yang dibutuhkan:
 * @var \App\Modules\Product\Domain\Entities\Product $product
 * @var string|null $imgUrl          — URL gambar primary
 * @var array|null  $flashSalePrice  — ['sale_price' => float, 'is_exhausted' => bool] atau null
 */

$fs          = $flashSalePrice ?? null;
$isFlashSale = $fs !== null && ! ($fs['is_exhausted'] ?? false);
$displayPrice = $isFlashSale ? $fs['sale_price'] : $product->price;
$originalPrice = $isFlashSale ? $product->price : $product->comparePrice;
$isOnSale    = $isFlashSale || $product->isOnSale();

$discountPct = 0;
if ($isFlashSale && $product->price > 0) {
    $discountPct = round((($product->price - $fs['sale_price']) / $product->price) * 100);
} elseif ($product->isOnSale()) {
    $discountPct = $product->discountPercentage();
}
?>
<a href="/produk/<?= e($product->slug) ?>"
    class="bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-md hover:border-orange-200 transition group">

    <!-- Foto produk -->
    <div class="aspect-square bg-gray-100 relative overflow-hidden">
        <?php if ($imgUrl ?? null): ?>
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

        <!-- Badge diskon -->
        <?php if ($discountPct > 0): ?>
            <div class="absolute top-2 left-2 <?= $isFlashSale ? 'bg-red-500' : 'bg-orange-500' ?> text-white text-xs font-bold px-1.5 py-0.5 rounded">
                -<?= $discountPct ?>%
            </div>
        <?php endif; ?>

        <!-- Badge flash sale -->
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