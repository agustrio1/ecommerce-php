<?php
/**
 * ===== BRAND TOKENS & HELPERS (storefront) =====
 * Di-require_once dari setiap view storefront yang butuh token warna /
 * helper yang sama (home, products, category-products, categories),
 * supaya tidak copy-paste dan aman kalau satu view sempat ter-render
 * lebih dari sekali dalam request yang sama (function_exists guard).
 *
 * Palet diturunkan dari banner asli Nexaro Studio (cream/stone + charcoal
 * + aksen clay/tan) — bukan default oranye Tailwind yang generik.
 * Dipakai lewat inline style (bukan class arbitrary Tailwind) karena
 * project ini terbukti tidak selalu meng-compile class arbitrary dengan
 * benar (lihat riwayat bug banner aspect-ratio).
 */

if (! function_exists('nexaroBrandTokens')) {
    function nexaroBrandTokens(): array
    {
        return [
            'ink'     => '#211F1D',
            'clay'    => '#A8522E',
            'moss'    => '#6B7156', // juga dipakai sebagai warna "sukses" (paid/completed)
            'stone'   => '#F6F2EA',
            'line'    => '#DED5C4',
            'urgent'  => '#8C3123', // danger (cancelled/refunded)
            'info'    => '#4A6670', // status netral (processing/shipped)
            'warning' => '#B8813B', // status menunggu (waiting_payment/pending)
        ];
    }
}

if (! function_exists('sectionHeader')) {
    function sectionHeader(array $brand, string $eyebrow, string $title, ?string $linkText = null, ?string $linkUrl = null): string
    {
        $link = '';
        if ($linkText && $linkUrl) {
            $link = '<a href="' . e($linkUrl) . '" class="text-xs font-semibold uppercase tracking-wide hover:opacity-70 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 rounded" style="color: ' . e($brand['clay']) . ';">' . e($linkText) . '</a>';
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
}

if (! function_exists('discountTagBadge')) {
    /**
     * Badge diskon berbentuk "swing tag" (label gantung khas retail
     * fashion) — signature element brand ini, dipakai konsisten di semua
     * kartu produk di seluruh situs (home, listing, kategori).
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
}

if (! function_exists('categoryIconPath')) {
    /**
     * Ikon SVG spesifik per kategori — supaya kategori tanpa foto tidak
     * semua jatuh ke satu ikon generik yang sama.
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
}

if (! function_exists('renderProductCard')) {
    function renderProductCard(object $product, ?string $imgUrl, ?array $fs, array $brand): string
    {
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

        $imageHtml = $imgUrl
            ? '<img src="' . e($imgUrl) . '" alt="' . e($product->name) . '" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition duration-300 motion-reduce:transition-none motion-reduce:transform-none">'
            : '<div class="w-full h-full flex items-center justify-center"><svg class="w-12 h-12 opacity-30" fill="none" stroke="' . e($brand['ink']) . '" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>';

        $badge = discountTagBadge($discountPct, $badgeColor);

        $saleFlag = $isFlashSale
            ? '<div class="absolute top-2 right-2 text-white text-[10px] font-bold px-1.5 py-0.5 rounded flex items-center gap-0.5" style="background-color: ' . e($brand['urgent']) . ';"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>Sale</div>'
            : '';

        $strikethrough = ($isOnSale && $originalPrice > 0)
            ? '<p class="text-xs text-gray-400 line-through">Rp ' . number_format($originalPrice, 0, ',', '.') . '</p>'
            : '';

        return '
        <a href="/produk/' . e($product->slug) . '" class="bg-white rounded-lg border overflow-hidden hover:shadow-md transition group focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2" style="border-color: ' . e($brand['line']) . ';">
            <div class="aspect-square relative overflow-hidden" style="background-color: ' . e($brand['stone']) . ';">
                ' . $imageHtml . $badge . $saleFlag . '
            </div>
            <div class="p-3">
                <p class="text-sm font-medium line-clamp-2 mb-1.5 leading-tight" style="color: ' . e($brand['ink']) . ';">' . e($product->name) . '</p>
                <p class="font-bold text-sm" style="color: ' . e($priceColor) . ';">Rp ' . number_format($displayPrice, 0, ',', '.') . '</p>
                ' . $strikethrough . '
            </div>
        </a>';
    }
}

if (! function_exists('orderStatusMeta')) {
    /**
     * @return array{label: string, color: string, bg: string}
     */
    function orderStatusMeta(string $status, array $brand): array
    {
        $label = [
            'pending'         => 'Menunggu',
            'waiting_payment' => 'Menunggu Pembayaran',
            'paid'            => 'Sudah Dibayar',
            'processing'      => 'Diproses',
            'shipped'         => 'Dikirim',
            'delivered'       => 'Terkirim',
            'completed'       => 'Selesai',
            'cancelled'       => 'Dibatalkan',
            'refunded'        => 'Direfund',
        ][$status] ?? ucfirst($status);

        $color = match ($status) {
            'paid', 'delivered', 'completed' => $brand['moss'],
            'shipped', 'processing'          => $brand['info'],
            'cancelled', 'refunded'          => $brand['urgent'],
            'waiting_payment', 'pending'     => $brand['warning'],
            default                          => $brand['ink'],
        };

        return ['label' => $label, 'color' => $color];
    }
}