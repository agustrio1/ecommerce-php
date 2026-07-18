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
<div class="py-3" x-data="bannerSlider(<?= $bannerCount ?>)">
    <div class="relative overflow-hidden rounded-2xl">
        <div class="flex transition-transform duration-500 ease-out"
            :style="'transform: translateX(-' + (current * 100) + '%)'">

            <?php foreach ($banners as $banner): ?>
                <div class="w-full aspect-[16/9] sm:aspect-[21/9] rounded-2xl flex items-center justify-center relative overflow-hidden shrink-0"
                    style="background-color: <?= e($banner['bg_color']) ?>">

                    <?php if ($banner['image_path']): ?>
                        <!-- Banner dengan gambar: teks & tombol promosi SUDAH jadi
                             bagian dari desain gambar itu sendiri, jadi tidak perlu
                             overlay teks lagi di sini — cukup tampilkan gambarnya
                             utuh (object-contain, tidak dipotong). -->
                        <img src="/storage/<?= e($banner['image_path']) ?>"
                            alt="<?= e($banner['title'] ?? '') ?>"
                            class="absolute inset-0 w-full h-full object-contain">

                        <?php if ($banner['button_text'] && $banner['button_url']): ?>
                            <!-- Link tetap perlu ada supaya banner tetap bisa
                                 diklik/ditap (untuk navigasi), tapi dibuat transparan
                                 dan menutup seluruh area gambar — bukan kotak putih
                                 di tengah yang menutupi tombol asli di gambar. -->
                            <a href="<?= e($banner['button_url']) ?>"
                                class="absolute inset-0 z-10"
                                aria-label="<?= e($banner['button_text']) ?>">
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Banner fallback TANPA gambar: di sinilah overlay teks
                             & tombol dari kode dipakai, karena tidak ada gambar
                             yang membawa teks/tombol sendiri. -->
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
            <!-- Dots -->
            <div class="absolute bottom-3 left-0 right-0 flex justify-center gap-1.5 z-20 pointer-events-none">
                <template x-for="i in total" :key="i">
                    <button @click="current = i - 1"
                        :class="current === i - 1 ? 'bg-white w-4' : 'bg-white/50 w-1.5'"
                        class="h-1.5 rounded-full transition-all duration-300 pointer-events-auto">
                    </button>
                </template>
            </div>

            <!-- Prev/Next -->
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
<div class="py-4">
    <div class="flex items-center justify-between mb-3">
        <h2 class="font-bold text-gray-900">Kategori</h2>
        <a href="/kategori" class="text-sm text-orange-600 hover:underline">Lihat semua</a>
    </div>
    <div class="grid grid-cols-4 sm:grid-cols-6 gap-3">
        <?php foreach (array_slice($categories, 0, 8) as $category): ?>
            <a href="/produk?kategori=<?= $category->id ?>"
                class="flex flex-col items-center gap-2 group">
                <div class="w-14 h-14 sm:w-16 sm:h-16 bg-orange-50 rounded-2xl flex items-center justify-center group-hover:bg-orange-100 transition">
                    <?php if ($category->image): ?>
                        <img src="/storage/<?= e($category->image) ?>" alt="<?= e($category->name) ?>"
                            class="w-8 h-8 object-cover rounded-lg">
                    <?php else: ?>
                        <svg class="w-7 h-7 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
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