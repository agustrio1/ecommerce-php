<?php $this->layout('layouts.storefront', ['title' => $title]) ?>

<?php $this->section('content') ?>

<?php
/**
 * ===== BRAND TOKENS =====
 * Palet diturunkan dari banner yang sudah ada (cream/stone + charcoal +
 * aksen clay/tan) — bukan default oranye Tailwind yang generik.
 * Dipakai lewat inline style (bukan class arbitrary seperti bg-[#A8522E])
 * karena project ini terbukti tidak selalu meng-compile class arbitrary
 * Tailwind dengan benar (lihat riwayat bug banner aspect-ratio) — inline
 * style selalu jalan di browser apa pun, terlepas dari proses build CSS.
 *
 * CATATAN REFACTOR: idealnya token ini + helper function di bawah
 * dipindah ke class terpisah (mis. App\Core\View\BrandTokens atau
 * helpers.php) supaya bisa dipakai ulang di halaman lain (produk,
 * kategori, dll) tanpa copy-paste. Untuk sekarang mengikuti pola yang
 * sudah ada di file ini (categoryIconPath juga didefinisikan inline).
 */
$brand = [
    'ink'    => '#211F1D', // teks utama, charcoal hangat
    'clay'   => '#A8522E', // aksen utama (pengganti oranye default)
    'moss'   => '#6B7156', // aksen sekunder, label/eyebrow
    'stone'  => '#F6F2EA', // background kartu/ikon
    'line'   => '#DED5C4', // garis tipis/border
    'urgent' => '#8C3123', // merah-bata untuk elemen flash sale (urgensi)
];

/**
 * Header section dengan gaya "eyebrow" (label kecil uppercase + judul
 * bold) — dipakai berulang di Kategori & Produk Unggulan supaya bahasa
 * visualnya konsisten satu sama lain.
 */
function sectionHeader(array $brand, string $eyebrow, string $title, ?string $linkText = null, ?string $linkUrl = null): string
{
    $link = '';
    if ($linkText && $linkUrl) {
        $link = '<a href="' . e($linkUrl) . '" class="text-xs font-semibold uppercase tracking-wide hover:opacity-70 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-neutral-800 rounded" style="color: ' . e($brand['clay']) . ';">' . e($linkText) . '</a>';
    }

    return '
    <div class="flex items-end justify-between mb-4">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.15em] mb-0.5" style="color: ' . e($brand['moss']) . ';">' . e($eyebrow) . '</p>
            <h2 class="text-lg font-bold" style="color: ' . e($brand['ink']) . ';">' . e($title) . '</h2>
        </div>
        ' . $link . '
    </div>';
}

/**
 * Badge diskon berbentuk "swing tag" (label gantung khas retail fashion)
 * — signature element brand ini, dipakai terbatas hanya di badge diskon
 * (bukan ditebar ke semua elemen), supaya tetap jadi 1 hal yang diingat
 * bukan dekorasi berlebihan.
 */
function discountTagBadge(int $pct, string $bgColor): string
{
    if ($pct <= 0) {
        return '';
    }

    return '
    <div class="absolute top-2 left-2 flex items-center gap-1 text-white text-[11px] font-bold pl-2.5 pr-2 py-1"
        style="background-color: ' . e($bgColor) . '; clip-path: polygon(0 0, 100% 0, 100% 100%, 10px 100%, 0 55%);">
        <span class="w-1 h-1 rounded-full bg-white/70"></span>-' . $pct . '%
    </div>';
}

/**
 * Ikon SVG spesifik per kategori, supaya kategori tanpa foto tidak semua
 * jatuh ke satu ikon generik yang sama (kotak grid 2x2) — itu yang bikin
 * tampilan terkesan template kosong/asal-jadi. Cocokkan berdasarkan kata
 * kunci di nama kategori (case-insensitive), fallback ikon "tag" generik
 * kalau tidak ada yang cocok.
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
?>

<!-- ===== BANNER SLIDER ===== -->
<?php
$bannerService = new \App\Modules\Banner\Application\Services\BannerService();
$banners = $bannerService->getActive();
if (empty($banners)) {
    $banners = [[
        'title'       => config('app.name', 'Toko Kami'),
        'subtitle'    => 'Selamat Datang',
        'button_text' => 'Belanja Sekarang',
        'button_url'  => '/produk',
        'bg_color'    => $brand['stone'],
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
        <div class="flex transition-transform duration-500 ease-out motion-reduce:transition-none"
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
                                class="absolute inset-0 z-10 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-white"
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
                                    class="inline-block font-semibold rounded-full transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-white"
                                    style="font-size: clamp(0.65rem, 2vw, 0.9rem); padding: clamp(0.3rem, 1.2vw, 0.5rem) clamp(0.9rem, 3vw, 1.5rem); background-color: #fff; color: <?= e($brand['ink']) ?>;">
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
                        class="h-1.5 rounded-full transition-all duration-300 motion-reduce:transition-none pointer-events-auto focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
                    </button>
                </template>
            </div>

            <button @click="prev()" class="absolute left-2 top-1/2 -translate-y-1/2 w-8 h-8 bg-black/20 hover:bg-black/40 text-white rounded-full hidden sm:flex items-center justify-center transition z-20 focus:outline-none focus-visible:ring-2 focus-visible:ring-white" aria-label="Banner sebelumnya">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <button @click="next()" class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 bg-black/20 hover:bg-black/40 text-white rounded-full hidden sm:flex items-center justify-center transition z-20 focus:outline-none focus-visible:ring-2 focus-visible:ring-white" aria-label="Banner berikutnya">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- ===== KATEGORI IKON ===== -->
<?php if (! empty($categories)): ?>
<?php $accentCycle = [$brand['clay'], $brand['moss']]; ?>
<div class="py-5">
    <?= sectionHeader($brand, 'Jelajahi', 'Kategori', 'Lihat semua', '/kategori') ?>
    <div class="grid grid-cols-4 sm:grid-cols-6 gap-3">
        <?php foreach (array_slice($categories, 0, 8) as $i => $category): ?>
            <?php $accent = $accentCycle[$i % count($accentCycle)]; ?>
            <a href="/produk?kategori=<?= $category->id ?>"
                class="flex flex-col items-center gap-2 group focus:outline-none">
                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-xl flex items-center justify-center border transition-transform duration-200 motion-reduce:transition-none group-hover:-translate-y-0.5 group-focus-visible:ring-2 group-focus-visible:ring-offset-2"
                    style="background-color: <?= e($brand['stone']) ?>; border-color: <?= e($brand['line']) ?>;">
                    <?php if ($category->image): ?>
                        <img src="/storage/<?= e($category->image) ?>" alt="<?= e($category->name) ?>"
                            class="w-8 h-8 object-cover rounded-lg">
                    <?php else: ?>
                        <svg class="w-6 h-6" fill="none" stroke="<?= e($brand['ink']) ?>" viewBox="0 0 24 24">
                            <?= categoryIconPath($category->name) ?>
                        </svg>
                    <?php endif; ?>
                </div>
                <span class="text-[11px] font-medium text-center leading-tight line-clamp-2" style="color: <?= e($brand['ink']) ?>;">
                    <?= e($category->name) ?>
                </span>
                <span class="w-3 h-[2px] rounded-full -mt-1" style="background-color: <?= e($accent) ?>;"></span>
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
<div class="py-5" x-data="flashSaleTimer('<?= $activeFlashSale['ends_at'] ?>')">
    <div class="flex items-end justify-between mb-4">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.15em] mb-0.5" style="color: <?= e($brand['urgent']) ?>;">
                Waktu Terbatas
            </p>
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="<?= e($brand['urgent']) ?>" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <h2 class="text-lg font-bold" style="color: <?= e($brand['ink']) ?>;"><?= e($activeFlashSale['name']) ?></h2>
            </div>
        </div>
        <!-- Countdown timer -->
        <div class="flex items-center gap-1 text-xs font-mono" role="timer" aria-label="Waktu tersisa flash sale">
            <span class="px-2 py-1 text-white rounded" style="background-color: <?= e($brand['urgent']) ?>;" x-text="hours">00</span>
            <span class="font-bold" style="color: <?= e($brand['urgent']) ?>;">:</span>
            <span class="px-2 py-1 text-white rounded" style="background-color: <?= e($brand['urgent']) ?>;" x-text="minutes">00</span>
            <span class="font-bold" style="color: <?= e($brand['urgent']) ?>;">:</span>
            <span class="px-2 py-1 text-white rounded" style="background-color: <?= e($brand['urgent']) ?>;" x-text="seconds">00</span>
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
                class="bg-white rounded-lg border overflow-hidden hover:shadow-md transition group focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                style="border-color: <?= e($brand['line']) ?>;">
                <div class="aspect-square relative overflow-hidden" style="background-color: <?= e($brand['stone']) ?>;">
                    <?php if ($fsp['product_image']): ?>
                        <img src="/storage/<?= e($fsp['product_image']) ?>"
                            alt="<?= e($fsp['product_name']) ?>"
                            class="w-full h-full object-cover group-hover:scale-105 transition duration-300 motion-reduce:transition-none motion-reduce:transform-none">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center">
                            <svg class="w-10 h-10 opacity-30" fill="none" stroke="<?= e($brand['ink']) ?>" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                    <?= discountTagBadge($discount, $brand['urgent']) ?>
                </div>
                <div class="p-3">
                    <p class="text-sm font-medium line-clamp-2 mb-1.5 leading-tight" style="color: <?= e($brand['ink']) ?>;"><?= e($fsp['product_name']) ?></p>
                    <p class="font-bold text-sm" style="color: <?= e($brand['urgent']) ?>;">Rp <?= number_format($fsp['sale_price'], 0, ',', '.') ?></p>
                    <p class="text-xs text-gray-400 line-through">Rp <?= number_format($fsp['original_price'], 0, ',', '.') ?></p>

                    <?php if ($fsp['stock_limit']): ?>
                        <div class="mt-2">
                            <div class="flex justify-between text-xs text-gray-400 mb-1">
                                <span>Terjual <?= $fsp['sold_count'] ?>/<?= $fsp['stock_limit'] ?></span>
                                <span><?= $soldPct ?>%</span>
                            </div>
                            <div class="h-1.5 rounded-full overflow-hidden" style="background-color: <?= e($brand['line']) ?>;">
                                <div class="h-full rounded-full transition-all" style="width: <?= $soldPct ?>%; background-color: <?= e($brand['urgent']) ?>;"></div>
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
<div class="py-5">
    <?= sectionHeader($brand, 'Pilihan Kami', 'Produk Unggulan', 'Lihat semua', '/produk') ?>

    <?php if (empty($products)): ?>
        <div class="text-center py-12 text-sm" style="color: <?= e($brand['ink']) ?>; opacity: 0.5;">
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

                $imgUrl      = $productImages[$product->id] ?? null;
                $badgeColor  = $isFlashSale ? $brand['urgent'] : $brand['clay'];
                ?>
                <a href="/produk/<?= e($product->slug) ?>"
                    class="bg-white rounded-lg border overflow-hidden hover:shadow-md transition group focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                    style="border-color: <?= e($brand['line']) ?>;">

                    <!-- Foto produk -->
                    <div class="aspect-square relative overflow-hidden" style="background-color: <?= e($brand['stone']) ?>;">
                        <?php if ($imgUrl): ?>
                            <img src="<?= e($imgUrl) ?>"
                                alt="<?= e($product->name) ?>"
                                class="w-full h-full object-cover group-hover:scale-105 transition duration-300 motion-reduce:transition-none motion-reduce:transform-none">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center">
                                <svg class="w-12 h-12 opacity-30" fill="none" stroke="<?= e($brand['ink']) ?>" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        <?php endif; ?>

                        <?= discountTagBadge($discountPct, $badgeColor) ?>

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
                        <p class="font-bold text-sm" style="color: <?= e($isFlashSale ? $brand['urgent'] : $brand['ink']) ?>;">
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