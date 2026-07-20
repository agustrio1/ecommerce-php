<?php
$metaTitle = $product->metaTitle
    ? $product->metaTitle . ' — ' . config('app.name')
    : $product->name . ' — ' . config('app.name');

$metaDesc = $product->metaDescription
    ?: ($product->shortDescription
        ?: 'Beli ' . $product->name . ' dengan harga terbaik di ' . config('app.name'));

$metaKeywords = $product->metaKeywords ?: '';
?>
<?php $this->layout('layouts.storefront', [
    'title'            => $metaTitle,
    'meta_description' => $metaDesc,
    'meta_keywords'    => $metaKeywords,
    'og_image'         => !empty($images) ? '/storage/' . $images[0]['path'] : null,
]) ?>

<?php $this->section('content') ?>

<?php require_once __DIR__ . '/_brand.php'; ?>
<?php $brand = nexaroBrandTokens(); ?>

<div class="py-4">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-1.5 text-xs text-gray-400 mb-4">
        <a href="/" class="hover:opacity-70 transition">Home</a>
        <span>›</span>
        <a href="/produk" class="hover:opacity-70 transition">Produk</a>
        <span>›</span>
        <span class="line-clamp-1" style="color: <?= e($brand['ink']) ?>;"><?= e($product->name) ?></span>
    </nav>

    <div x-data="productPage(<?= $product->id ?>, <?= $product->hasVariants ? 'true' : 'false' ?>)" class="lg:grid lg:grid-cols-2 lg:gap-8">

        <!-- GAMBAR PRODUK -->
        <div x-data="{ activeImage: 0 }">
            <!-- Main image -->
            <div class="aspect-square rounded-2xl overflow-hidden mb-3 relative" style="background-color: <?= e($brand['stone']) ?>;">
                <?php if (!empty($images)): ?>
                    <?php foreach ($images as $i => $image): ?>
                        <img src="/storage/<?= e($image['path']) ?>"
                            alt="<?= e($image['alt_text'] ?? $product->name) ?>"
                            x-show="activeImage === <?= $i ?>"
                            class="w-full h-full object-cover">
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center">
                        <svg class="w-16 h-16 opacity-20" fill="none" stroke="<?= e($brand['ink']) ?>" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                <?php endif; ?>

                <?php if ($product->isOnSale()): ?>
                    <div class="absolute top-3 left-3 flex items-center gap-1 text-white text-sm font-bold pl-3 pr-2.5 py-1.5"
                        style="background-color: <?= e($brand['clay']) ?>; clip-path: polygon(0 0, 100% 0, 100% 100%, 12px 100%, 0 55%);">
                        <span class="w-1.5 h-1.5 rounded-full bg-white/70"></span>
                        -<?= $product->discountPercentage() ?>%
                    </div>
                <?php endif; ?>
            </div>

            <!-- Thumbnail -->
            <?php if (count($images) > 1): ?>
                <div class="flex gap-2 overflow-x-auto pb-1">
                    <?php foreach ($images as $i => $image): ?>
                        <button type="button"
                            @click="activeImage = <?= $i ?>"
                            :style="activeImage === <?= $i ?> ? 'border-color: <?= e($brand['ink']) ?>' : 'border-color: transparent'"
                            class="w-16 h-16 shrink-0 rounded-xl overflow-hidden border-2 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2">
                            <img src="/storage/<?= e($image['path']) ?>" alt="Thumbnail <?= $i + 1 ?> — <?= e($product->name) ?>" class="w-full h-full object-cover">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- INFO PRODUK -->
        <div class="mt-4 lg:mt-0">
            <h1 class="text-xl font-bold mb-2" style="color: <?= e($brand['ink']) ?>;"><?= e($product->name) ?></h1>

            <!-- Harga -->
            <?php
            $fs            = $flashSalePrice ?? null;
            $isFlashSale   = $fs !== null && ! ($fs['is_exhausted'] ?? false);
            $displayPrice  = $isFlashSale ? $fs['sale_price'] : $product->price;
            $originalPrice = $isFlashSale ? $product->price : $product->comparePrice;
            $discountPct   = 0;
            if ($isFlashSale && $product->price > 0) {
                $discountPct = round((($product->price - $fs['sale_price']) / $product->price) * 100);
            } elseif ($product->isOnSale()) {
                $discountPct = $product->discountPercentage();
                $originalPrice = $product->comparePrice;
            }
            $priceColor = $isFlashSale ? $brand['urgent'] : $brand['ink'];
            ?>

            <?php if ($isFlashSale): ?>
                <div class="flex items-center gap-2 mb-2">
                    <span class="flex items-center gap-1 px-2 py-1 text-white text-xs font-bold rounded-lg" style="background-color: <?= e($brand['urgent']) ?>;">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Flash Sale
                    </span>
                    <?php if (isset($fs['stock_limit']) && $fs['stock_limit']): ?>
                        <span class="text-xs" style="color: <?= e($brand['urgent']) ?>;">
                            Sisa <?= max(0, $fs['stock_limit'] - ($fs['sold_count'] ?? 0)) ?> item
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="flex items-baseline gap-2 mb-4">
                <span class="text-2xl font-bold" style="color: <?= e($priceColor) ?>;">
                    Rp <?= number_format($displayPrice, 0, ',', '.') ?>
                </span>
                <?php if ($originalPrice > 0 && $originalPrice > $displayPrice): ?>
                    <span class="text-sm text-gray-400 line-through">
                        Rp <?= number_format($originalPrice, 0, ',', '.') ?>
                    </span>
                    <?php if ($discountPct > 0): ?>
                        <span class="text-xs font-semibold px-1.5 py-0.5 rounded" style="color: <?= e($brand['clay']) ?>; background-color: <?= e($brand['stone']) ?>;">
                            -<?= $discountPct ?>%
                        </span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <?php if ($product->shortDescription): ?>
                <p class="text-sm text-gray-600 mb-4 leading-relaxed"><?= e($product->shortDescription) ?></p>
            <?php endif; ?>

            <!-- Varian -->
            <?php if ($product->hasVariants && !empty($variants)): ?>
                <?php
                $attrGroups = [];
                foreach ($variants as $variant) {
                    foreach ($variant['attribute_values'] as $av) {
                        $attr = $av['attribute'];
                        if (!isset($attrGroups[$attr])) $attrGroups[$attr] = [];
                        if (!in_array($av['value'], $attrGroups[$attr])) $attrGroups[$attr][] = $av['value'];
                    }
                }
                ?>
                <div class="space-y-4 mb-5">
                    <?php foreach ($attrGroups as $attrName => $values): ?>
                        <div>
                            <p class="text-sm font-semibold mb-2" style="color: <?= e($brand['ink']) ?>;"><?= e($attrName) ?></p>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($values as $value): ?>
                                    <button type="button"
                                        @click="selectAttr('<?= e($attrName) ?>', '<?= e($value) ?>')"
                                        :style="selected['<?= e($attrName) ?>'] === '<?= e($value) ?>'
                                            ? 'background-color: <?= e($brand['ink']) ?>; color: #fff; border-color: <?= e($brand['ink']) ?>;'
                                            : 'background-color: #fff; color: <?= e($brand['ink']) ?>; border-color: <?= e($brand['line']) ?>;'"
                                        class="px-4 py-2 border rounded-lg text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2">
                                        <?= e($value) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <p class="text-sm text-gray-500 mb-4">
                    Stok: <span class="font-semibold" style="color: <?= e($brand['ink']) ?>;" x-text="selectedStock !== null ? selectedStock : '—'"></span>
                </p>
            <?php else: ?>
                <?php $defaultVariant = $variants[0] ?? null; ?>
                <p class="text-sm text-gray-500 mb-4">
                    Stok: <span class="font-semibold" style="color: <?= e($brand['ink']) ?>;"><?= $defaultVariant ? (int)$defaultVariant['stock'] : 0 ?></span>
                </p>
            <?php endif; ?>

            <!-- Wishlist toggle -->
            <?php
            $currentUser = \App\Modules\Auth\Application\Services\CurrentUserService::user();
            $isWishlisted = false;
            if ($currentUser) {
                $wlService = new \App\Modules\Wishlist\Application\Services\WishlistService();
                $isWishlisted = $wlService->isWishlisted($currentUser->id, $product->id);
            }
            ?>
            <div x-data="wishlistBtn(<?= $product->id ?>, <?= $isWishlisted ? 'true' : 'false' ?>)" class="mb-3">
                <button @click="toggle()"
                    :disabled="loading"
                    :aria-pressed="wishlisted"
                    :style="wishlisted
                        ? 'background-color: #FBEAE6; color: <?= e($brand['urgent']) ?>; border-color: <?= e($brand['urgent']) ?>;'
                        : 'background-color: <?= e($brand['stone']) ?>; color: <?= e($brand['ink']) ?>; border-color: <?= e($brand['line']) ?>;'"
                    class="w-full py-2.5 border rounded-xl text-sm font-medium transition flex items-center justify-center gap-2 disabled:opacity-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2">
                    <svg class="w-4 h-4" :fill="wishlisted ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                    <span x-text="wishlisted ? 'Hapus dari Wishlist' : 'Tambah ke Wishlist'"></span>
                </button>
            </div>

            <!-- Tombol aksi -->
            <div class="flex gap-3 mb-6">
                <button type="button"
                    @click="addToCart()"
                    :disabled="loading"
                    class="flex-1 py-3 text-white rounded-xl font-semibold transition text-sm disabled:opacity-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                    style="background-color: <?= e($brand['ink']) ?>;">
                    <span x-show="!loading">+ Keranjang</span>
                    <span x-show="loading">Menambahkan...</span>
                </button>
                <a href="/checkout"
                    class="flex-1 py-3 border rounded-xl font-semibold transition text-sm text-center focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                    style="background-color: <?= e($brand['stone']) ?>; color: <?= e($brand['clay']) ?>; border-color: <?= e($brand['clay']) ?>;">
                    Beli Sekarang
                </a>
            </div>

            <!-- Toast -->
            <div x-show="toast"
                x-transition
                role="status"
                aria-live="polite"
                :style="toastSuccess ? 'background-color: <?= e($brand['ink']) ?>' : 'background-color: <?= e($brand['urgent']) ?>'"
                class="fixed bottom-24 left-4 right-4 text-white text-sm px-4 py-3 rounded-xl shadow-lg z-50 text-center"
                x-text="toastMsg">
            </div>

            <!-- Info pengiriman -->
            <div class="border rounded-xl p-4 space-y-2.5" style="border-color: <?= e($brand['line']) ?>;">
                <div class="flex items-center gap-3 text-sm text-gray-600">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="<?= e($brand['clay']) ?>" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                    <span>Berat: <strong style="color: <?= e($brand['ink']) ?>;"><?= $product->weight ? number_format($product->weight) . ' gram' : 'Tidak diketahui' ?></strong></span>
                </div>
                <?php if ($product->length > 0): ?>
                    <div class="flex items-center gap-3 text-sm text-gray-600">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="<?= e($brand['clay']) ?>" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                        </svg>
                        <span>Dimensi: <strong style="color: <?= e($brand['ink']) ?>;"><?= $product->length ?> × <?= $product->width ?> × <?= $product->height ?> cm</strong></span>
                    </div>
                <?php endif; ?>
                <div class="flex items-center gap-3 text-sm text-gray-600">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="<?= e($brand['moss']) ?>" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span>Pengiriman via Biteship — ongkir dihitung saat checkout</span>
                </div>
            </div>
        </div>
    </div>

    <!-- DESKRIPSI LENGKAP -->
    <?php if ($product->description): ?>
        <div class="mt-8 bg-white rounded-xl border p-5" style="border-color: <?= e($brand['line']) ?>;">
            <p class="text-[11px] font-semibold uppercase tracking-[0.15em] mb-1" style="color: <?= e($brand['moss']) ?>;">Detail</p>
            <h2 class="font-bold mb-3" style="color: <?= e($brand['ink']) ?>;">Deskripsi Produk</h2>
            <div class="text-sm text-gray-700 leading-relaxed prose prose-sm max-w-none
                        [&_h1]:text-xl [&_h1]:font-bold [&_h1]:mb-2
                        [&_h2]:text-lg [&_h2]:font-bold [&_h2]:mb-2
                        [&_h3]:text-base [&_h3]:font-semibold [&_h3]:mb-1
                        [&_p]:mb-2 [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:mb-2
                        [&_ol]:list-decimal [&_ol]:pl-5 [&_ol]:mb-2"
                style="--tw-prose-quote-borders: <?= e($brand['clay']) ?>;">
                <?= $product->description ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function productPage(productId, hasVariants) {
    return {
        productId,
        hasVariants,
        selected: {},
        selectedStock: null,
        selectedVariantId: null,
        variants: <?= json_encode(array_map(fn($v) => [
            'id'    => $v['id'],
            'stock' => (int) $v['stock'],
            'attrs' => array_map(fn($av) => ['attr' => $av['attribute'], 'value' => $av['value']], $v['attribute_values']),
        ], $variants)) ?>,

        flashSalePrice: <?= ($isFlashSale ?? false) ? json_encode($fs['sale_price']) : 'null' ?>,

        loading: false,
        toast: false,
        toastSuccess: true,
        toastMsg: '',
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',

        init() {
            if (!this.hasVariants && this.variants.length > 0) {
                this.selectedVariantId = this.variants[0].id;
            }
        },

        selectAttr(attr, value) {
            this.selected[attr] = value;
            this.updateStock();
        },

        updateStock() {
            const selectedKeys = Object.keys(this.selected);
            if (selectedKeys.length === 0) {
                this.selectedStock = null;
                this.selectedVariantId = null;
                return;
            }

            const match = this.variants.find(v =>
                selectedKeys.every(attr =>
                    v.attrs.some(a => a.attr === attr && a.value === this.selected[attr])
                )
            );

            this.selectedStock = match ? match.stock : null;
            this.selectedVariantId = match ? match.id : null;
        },

        async addToCart() {
            if (!this.selectedVariantId) {
                this.showToast('Pilih varian produk terlebih dahulu.', false);
                return;
            }

            this.loading = true;

            try {
                const res = await fetch('/cart/add', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-Token': this.csrfToken,
                    },
                    body: JSON.stringify({
                        product_id: this.productId,
                        variant_id: this.selectedVariantId,
                        quantity: 1,
                        price_override: this.flashSalePrice,
                    }),
                });

                let data;
                try { data = await res.json(); } catch {
                    this.showToast('Terjadi kesalahan server.', false);
                    return;
                }

                this.showToast(data.message ?? 'Terjadi kesalahan.', data.success ?? false);

                if (data.success) {
                    window.dispatchEvent(new CustomEvent('cart-updated', {
                        detail: { count: data.count }
                    }));
                }

            } catch (e) {
                this.showToast('Gagal menambahkan ke keranjang.', false);
            } finally {
                this.loading = false;
            }
        },

        showToast(msg, success) {
            this.toastMsg = msg;
            this.toastSuccess = success;
            this.toast = true;
            setTimeout(() => this.toast = false, 3000);
        }
    }
}

function wishlistBtn(productId, initialState) {
    return {
        productId,
        wishlisted: initialState,
        loading: false,
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',

        async toggle() {
            if (this.loading) return;
            this.loading = true;

            try {
                const res = await fetch('/wishlist/toggle', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Accept': 'application/json',
                        'X-CSRF-Token': this.csrfToken,
                    },
                    body: new URLSearchParams({ product_id: this.productId }),
                });

                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch {
                    console.error('Non-JSON response dari /wishlist/toggle:', text);
                    return;
                }

                if (res.status === 401 && data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }

                if (data.success) {
                    this.wishlisted = data.added;
                    window.dispatchEvent(new CustomEvent('wishlist-updated', {
                        detail: { count: data.count }
                    }));
                } else {
                    console.error('Wishlist toggle gagal:', data);
                }
            } catch (e) {
                console.error('toggle wishlist error:', e);
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>

<!-- ===== RATING & ULASAN ===== -->
<?php
$reviewService = new \App\Modules\Review\Application\Services\ReviewService();
$reviewSummary = $reviewService->getSummary($product->id);
$productReviews = $reviewService->getByProduct($product->id, 1, 10);
?>
<div class="mt-8 bg-white rounded-xl border p-5" style="border-color: <?= e($brand['line']) ?>;">
    <p class="text-[11px] font-semibold uppercase tracking-[0.15em] mb-1" style="color: <?= e($brand['moss']) ?>;">Kata Pembeli</p>
    <h2 class="font-bold mb-4" style="color: <?= e($brand['ink']) ?>;">Ulasan Pembeli</h2>

    <?php if ($reviewSummary['total'] > 0): ?>
        <div class="flex items-center gap-6 mb-5 pb-5 border-b" style="border-color: <?= e($brand['line']) ?>;">
            <div class="text-center shrink-0">
                <p class="text-3xl font-bold" style="color: <?= e($brand['ink']) ?>;"><?= $reviewSummary['average'] ?></p>
                <div class="flex text-amber-400 text-sm">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span><?= $i <= round($reviewSummary['average']) ? '★' : '☆' ?></span>
                    <?php endfor; ?>
                </div>
                <p class="text-xs text-gray-400 mt-1"><?= $reviewSummary['total'] ?> ulasan</p>
            </div>
            <div class="flex-1 space-y-1">
                <?php for ($star = 5; $star >= 1; $star--): ?>
                    <?php
                    $count = $reviewSummary['distribution'][$star];
                    $pct = $reviewSummary['total'] > 0 ? ($count / $reviewSummary['total']) * 100 : 0;
                    ?>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="text-gray-500 w-3"><?= $star ?></span>
                        <div class="flex-1 h-1.5 rounded-full overflow-hidden" style="background-color: <?= e($brand['stone']) ?>;">
                            <div class="h-full bg-amber-400" style="width: <?= $pct ?>%"></div>
                        </div>
                        <span class="text-gray-400 w-6 text-right"><?= $count ?></span>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <div class="space-y-4">
            <?php foreach ($productReviews as $review): ?>
                <div class="border-b pb-4 last:border-0" style="border-color: <?= e($brand['line']) ?>;">
                    <div class="flex items-center gap-2 mb-1.5">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center shrink-0" style="background-color: <?= e($brand['stone']) ?>;">
                            <span class="font-bold text-xs" style="color: <?= e($brand['ink']) ?>;"><?= strtoupper(substr($review['user_name'], 0, 1)) ?></span>
                        </div>
                        <div>
                            <p class="text-xs font-medium" style="color: <?= e($brand['ink']) ?>;"><?= e($review['user_name']) ?></p>
                            <div class="flex text-amber-400 text-xs">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span><?= $i <= $review['rating'] ? '★' : '☆' ?></span>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <span class="text-xs text-gray-300 ml-auto"><?= e(date('d M Y', strtotime($review['created_at']))) ?></span>
                    </div>
                    <?php if ($review['comment']): ?>
                        <p class="text-sm text-gray-600 leading-relaxed"><?= e($review['comment']) ?></p>
                    <?php endif; ?>
                    <?php if (! empty($review['images'])): ?>
                        <div class="flex gap-2 mt-2">
                            <?php foreach ($review['images'] as $imgPath): ?>
                                <img src="/storage/<?= e($imgPath) ?>" alt="Foto ulasan dari <?= e($review['user_name']) ?>" class="w-14 h-14 object-cover rounded-lg border" style="border-color: <?= e($brand['line']) ?>;">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-sm text-gray-400 text-center py-6">Belum ada ulasan untuk produk ini.</p>
    <?php endif; ?>
</div>

<?php $this->endSection() ?>