<?php $this->layout('layouts.storefront', ['title' => $title]) ?>

<?php $this->section('content') ?>

<!-- ===== BANNER SLIDER ===== -->
<?php
$bannerService = new \App\Modules\Banner\Application\Services\BannerService();
$banners = $bannerService->getActive();
if (empty($banners)) {
    // Fallback default banner
    $banners = [[
        'title'       => config('app.name', 'Toko Kami'),
        'subtitle'    => 'Selamat Datang',
        'button_text' => 'Belanja Sekarang',
        'button_url'  => '/produk',
        'bg_color'    => '#f97316',
        'image_path'  => null,
    ]];
}
$bannerCount = count($banners);
?>
<?php
$bannerAspectRatio = '2.5 / 1'; // fallback

foreach ($banners as $banner) {
    if (! empty($banner['image_path'])) {
        $documentRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
        $fullPath     = $documentRoot . '/storage/' . ltrim($banner['image_path'], '/');
        $size         = @getimagesize($fullPath);

        if ($size !== false && $size[0] > 0 && $size[1] > 0) {
            $bannerAspectRatio = $size[0] . ' / ' . $size[1];
        }

        break;
    }
}
?>

<div class="py-3" x-data="bannerSlider(<?= $bannerCount ?>)">
    <div class="relative overflow-hidden rounded-2xl">
        <div class="flex transition-transform duration-500 ease-out"
            :style="'transform: translateX(-' + (current * 100) + '%)'">

            <?php foreach ($banners as $banner): ?>
                <div class="w-full rounded-2xl flex items-center justify-center relative overflow-hidden shrink-0"
                    style="background-color: <?= e($banner['bg_color']) ?>; aspect-ratio: <?= e($bannerAspectRatio) ?>;">

                    <?php if ($banner['image_path']): ?>
                        <img src="/storage/<?= e($banner['image_path']) ?>"
                            alt="<?= e($banner['title'] ?? '') ?>"
                            class="absolute inset-0 w-full h-full object-contain">

                        <?php if ($banner['button_text'] && $banner['button_url']): ?>
                            <a href="<?= e($banner['button_url']) ?>"
                                class="absolute inset-0 z-10"
                                aria-label="<?= e($banner['button_text']) ?>">
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center text-white z-10 px-[4%] relative max-w-full">
                            <?php if ($banner['subtitle']): ?>
                                <p class="font-medium opacity-80 mb-1"
                                    style="font-size: clamp(0.65rem, 2.2vw, 0.95rem);">
                                    <?= e($banner['subtitle']) ?>
                                </p>
                            <?php endif; ?>
                            <h2 class="font-bold mb-2 leading-tight"
                                style="font-size: clamp(0.95rem, 4.5vw, 2rem);">
                                <?= e($banner['title']) ?>
                            </h2>
                            <?php if ($banner['button_text'] && $banner['button_url']): ?>
                                <a href="<?= e($banner['button_url']) ?>"
                                    class="inline-block bg-white text-gray-900 font-semibold rounded-full hover:bg-gray-100 transition"
                                    style="font-size: clamp(0.65rem, 2vw, 0.9rem); padding: clamp(0.3rem, 1.2vw, 0.5rem) clamp(0.9rem, 3vw, 1.5rem);">
                                    <?= e($banner['button_text']) ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="absolute -right-8 -top-8 w-40 h-40 bg-white/10 rounded-full pointer-events-none"></div>
                        <div class="absolute -right-4 -bottom-10 w-56 h-56 bg-white/10 rounded-full pointer-events-none"></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($bannerCount > 1): ?>
            <div class="absolute bottom-3 left-0 right-0 flex justify-center gap-1.5 z-20 pointer-events-none">
                <template x-for="i in total" :key="i">
                    <button @click="current = i - 1"
                        :class="current === i - 1 ? 'bg-white w-4' : 'bg-white/50 w-1.5'"
                        class="h-1.5 rounded-full transition-all duration-300 pointer-events-auto">
                    </button>
                </template>
            </div>

            <button @click="prev()" class="absolute left-2 top-1/2 -translate-y-1/2 w-8 h-8 bg-black/20 hover:bg-black/40 text-white rounded-full hidden sm:flex items-center justify-center transition z-20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <button @click="next()" class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 bg-black/20 hover:bg-black/40 text-white rounded-full hidden sm:flex items-center justify-center transition z-20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- ===== KATEGORI IKON ===== -->
<?php if (! empty($categories)): ?>
<?php
/**
 * Pilih ikon SVG yang sesuai dengan nama kategori, supaya tidak semua
 * kategori tanpa foto jatuh ke satu ikon generik yang sama (kotak grid
 * 2x2) — itu yang bikin tampilan terkesan template kosong/asal-jadi.
 * Cocokkan berdasarkan kata kunci di nama kategori (case-insensitive),
 * dengan fallback ikon "tag" generik kalau tidak ada yang cocok.
 */
function categoryIconPath(string $name): string
{
    $key = strtolower($name);

    return match (true) {
        str_contains($key, 'tas') =>
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7V6a4 4 0 00-8 0v1M5 7h14l-1.2 12.1a2 2 0 01-2 1.9H8.2a2 2 0 01-2-1.9L5 7z"/>',
        str_contains($key, 'pakaian') || str_contains($key, 'baju') || str_contains($key, 'kaos') =>
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 4L4.5 6.5 6 10h2v10h8V10h2l1.5-3.5L16 4l-2 2h-4L8 4z"/>',
        str_contains($key, 'celana') =>
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7.5 3h9l.8 8-1 9h-2.6l-.7-8-.7 8H9.9l-1-9 .6-8z"/>',
        str_contains($key, 'jam') =>
            '<circle cx="12" cy="13" r="7" stroke-width="1.5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 10v3l2 2M9.5 3h5"/>',
        str_contains($key, 'ikat') || str_contains($key, 'pinggang') || str_contains($key, 'sabuk') =>
            '<rect x="3" y="10" width="18" height="4" rx="1" stroke-width="1.5"/><circle cx="12" cy="12" r="1.3" stroke-width="1.5" fill="currentColor"/>',
        str_contains($key, 'sepatu') =>
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 17.5h17a1 1 0 001-1c0-1.8-1.8-2.8-3.7-3.3-1.8-.5-2.8-1.4-3.6-2.7-.5-.8-1.1-1.5-2.1-1.5H8a1 1 0 00-1 1v3.3L3 14.5v3z"/>',
        default =>
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>',
    };
}

// Variasi warna background per kategori supaya tidak semua oranye monoton.
$categoryColors = [
    'bg-orange-50 text-orange-500',
    'bg-sky-50 text-sky-500',
    'bg-emerald-50 text-emerald-500',
    'bg-rose-50 text-rose-500',
    'bg-violet-50 text-violet-500',
    'bg-amber-50 text-amber-600',
    'bg-teal-50 text-teal-500',
    'bg-fuchsia-50 text-fuchsia-500',
];
?>
<div class="py-4">
    <div class="flex items-center justify-between mb-3">
        <h2 class="font-bold text-gray-900">Kategori</h2>
        <a href="/kategori" class="text-sm text-orange-600 hover:underline">Lihat semua</a>
    </div>
    <div class="grid grid-cols-4 sm:grid-cols-6 gap-3">
        <?php foreach (array_slice($categories, 0, 8) as $i => $category): ?>
            <?php $colorClass = $categoryColors[$i % count($categoryColors)]; ?>
            <a href="/produk?kategori=<?= $category->id ?>"
                class="flex flex-col items-center gap-2 group">
                <div class="w-14 h-14 sm:w-16 sm:h-16 <?= $colorClass ?> rounded-2xl flex items-center justify-center group-hover:scale-105 transition-transform duration-200">
                    <?php if ($category->image): ?>
                        <img src="/storage/<?= e($category->image) ?>" alt="<?= e($category->name) ?>"
                            class="w-8 h-8 object-cover rounded-lg">
                    <?php else: ?>
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <?= categoryIconPath($category->name) ?>
                        </svg>
                    <?php endif; ?>
                </div>
                <span class="text-xs text-gray-600 text-center leading-tight group-hover:text-orange-600 transition line-clamp-2">
                    <?= e($category->name) ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ===== FLASH SALE ===== -->
<?php
$flashSaleService = new \App\Modules\FlashSale\Application\Services\FlashSaleService();
$activeFlashSale  = $flashSaleService->getActive();
?>
<?php if ($activeFlashSale && !empty($activeFlashSale['products'])): ?>
<div class="py-4" x-data="flashSaleTimer('<?= $activeFlashSale['ends_at'] ?>')">
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <h2 class="font-bold text-gray-900"><?= e($activeFlashSale['name']) ?></h2>
        </div>
        <!-- Countdown timer -->
        <div class="flex items-center gap-1 text-xs font-mono">
            <span class="px-2 py-1 bg-red-600 text-white rounded" x-text="hours">00</span>
            <span class="text-red-600 font-bold">:</span>
            <span class="px-2 py-1 bg-red-600 text-white rounded" x-text="minutes">00</span>
            <span class="text-red-600 font-bold">:</span>
            <span class="px-2 py-1 bg-red-600 text-white rounded" x-text="seconds">00</span>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
        <?php foreach ($activeFlashSale['products'] as $fsp): ?>
            <?php
            $discount = $fsp['original_price'] > 0
                ? round((($fsp['original_price'] - $fsp['sale_price']) / $fsp['original_price']) * 100)
                : 0;
            $soldPct = $fsp['stock_limit']
                ? min(100, round(($fsp['sold_count'] / $fsp['stock_limit']) * 100))
                : 0;
            ?>
            <a href="/produk/<?= e($fsp['slug']) ?>"
                class="bg-white rounded-xl border border-red-100 overflow-hidden hover:shadow-md hover:border-red-300 transition group">
                <div class="aspect-square bg-gray-100 relative overflow-hidden">
                    <?php if ($fsp['product_image']): ?>
                        <img src="/storage/<?= e($fsp['product_image']) ?>"
                            class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center">
                            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                    <?php if ($discount > 0): ?>
                        <div class="absolute top-2 left-2 bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded">
                            -<?= $discount ?>%
                        </div>
                    <?php endif; ?>
                </div>
                <div class="p-3">
                    <p class="text-sm font-medium text-gray-800 line-clamp-2 mb-1.5 leading-tight"><?= e($fsp['product_name']) ?></p>
                    <p class="font-bold text-red-600 text-sm">Rp <?= number_format($fsp['sale_price'], 0, ',', '.') ?></p>
                    <p class="text-xs text-gray-400 line-through">Rp <?= number_format($fsp['original_price'], 0, ',', '.') ?></p>

                    <?php if ($fsp['stock_limit']): ?>
                        <div class="mt-2">
                            <div class="flex justify-between text-xs text-gray-400 mb-1">
                                <span>Terjual <?= $fsp['sold_count'] ?>/<?= $fsp['stock_limit'] ?></span>
                                <span><?= $soldPct ?>%</span>
                            </div>
                            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-red-500 rounded-full transition-all"
                                    style="width: <?= $soldPct ?>%"></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<script>
function flashSaleTimer(endTime) {
    return {
        hours: '00',
        minutes: '00',
        seconds: '00',
        endTimestamp: new Date(endTime).getTime(),

        init() {
            this.update();
            setInterval(() => this.update(), 1000);
        },

        update() {
            const now  = Date.now();
            const diff = Math.max(0, this.endTimestamp - now);

            const h = Math.floor(diff / 3600000);
            const m = Math.floor((diff % 3600000) / 60000);
            const s = Math.floor((diff % 60000) / 1000);

            this.hours   = String(h).padStart(2, '0');
            this.minutes = String(m).padStart(2, '0');
            this.seconds = String(s).padStart(2, '0');
        }
    }
}
</script>
<?php endif; ?>

<!-- ===== PRODUK UNGGULAN ===== -->
<div class="py-4">
    <div class="flex items-center justify-between mb-3">
        <h2 class="font-bold text-gray-900">Produk Unggulan</h2>
        <a href="/produk" class="text-sm text-orange-600 hover:underline">Lihat semua</a>
    </div>

    <?php if (empty($products)): ?>
        <div class="text-center py-12 text-gray-400 text-sm">
            Belum ada produk yang dipublikasikan.
        </div>
    <?php else: ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            <?php foreach ($products as $product): ?>
                <?php
                // Logic flash sale diinline langsung di sini (TIDAK pakai
                // $this->include, karena method itu tidak didukung template
                // engine ini dan menyebabkan fatal error diam-diam yang
                // bikin seluruh grid produk gak muncul sama sekali).
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

                $imgUrl = $productImages[$product->id] ?? null;
                ?>
                <a href="/produk/<?= e($product->slug) ?>"
                    class="bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-md hover:border-orange-200 transition group">

                    <!-- Foto produk -->
                    <div class="aspect-square bg-gray-100 relative overflow-hidden">
                        <?php if ($imgUrl): ?>
                            <img src="<?= e($imgUrl) ?>"
                                alt="<?= e($product->name) ?>"
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
                            <p class="text-xs text-gray-400 line-through">
                                Rp <?= number_format($originalPrice, 0, ',', '.') ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function bannerSlider(total) {
    return {
        current: 0,
        total: total,
        timer: null,

        init() {
            if (this.total > 1) this.startAuto();
        },

        startAuto() {
            this.timer = setInterval(() => this.next(), 4000);
        },

        next() {
            this.current = (this.current + 1) % this.total;
        },

        prev() {
            this.current = (this.current - 1 + this.total) % this.total;
        }
    }
}
</script>

<?php $this->endSection() ?>