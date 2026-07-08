<?php
$seoTitle  = \App\Modules\Setting\Application\Services\SettingService::getInstance()->get('seo_title', config('app.name'));
$seoDesc   = \App\Modules\Setting\Application\Services\SettingService::getInstance()->get('seo_description', '');
$seoKeys   = \App\Modules\Setting\Application\Services\SettingService::getInstance()->get('seo_keywords', '');
$seoRobots = \App\Modules\Setting\Application\Services\SettingService::getInstance()->get('seo_robots', 'index, follow');
$seoOgImg  = \App\Modules\Setting\Application\Services\SettingService::getInstance()->get('seo_og_image', '');
$seoJsonLd = \App\Modules\Setting\Application\Services\SettingService::getInstance()->get('seo_jsonld_organization', '');
$gaId      = \App\Modules\Setting\Application\Services\SettingService::getInstance()->get('seo_google_analytics', '');
$gVerify   = \App\Modules\Setting\Application\Services\SettingService::getInstance()->get('seo_google_site_verification', '');
$currentUser = \App\Modules\Auth\Application\Services\CurrentUserService::user();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? $seoTitle ?? config('app.name')) ?></title>
    <meta name="description" content="<?= e($meta_description ?? $seoDesc) ?>">
    <?php if ($meta_keywords ?? $seoKeys): ?>
        <meta name="keywords" content="<?= e($meta_keywords ?? $seoKeys) ?>">
    <?php endif; ?>
    <meta name="robots" content="<?= e($seoRobots) ?>">

    <meta property="og:title" content="<?= e($title ?? $seoTitle) ?>">
    <meta property="og:description" content="<?= e($meta_description ?? $seoDesc) ?>">
    <meta property="og:type" content="<?= isset($og_image) ? 'product' : 'website' ?>">
    <meta property="og:url" content="<?= e(rtrim(env('APP_URL', ''), '/') . $_SERVER['REQUEST_URI']) ?>">
    <?php $ogImg = $og_image ?? $seoOgImg; ?>
    <?php if ($ogImg): ?>
        <meta property="og:image" content="<?= e(rtrim(env('APP_URL', ''), '/') . $ogImg) ?>">
    <?php endif; ?>

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($title ?? $seoTitle) ?>">
    <meta name="twitter:description" content="<?= e($seoDesc) ?>">

    <?php if ($gVerify): ?>
        <meta name="google-site-verification" content="<?= e($gVerify) ?>">
    <?php endif; ?>

    <?php if ($seoJsonLd): ?>
        <script type="application/ld+json"><?= $seoJsonLd ?></script>
    <?php endif; ?>

    <?php if ($gaId): ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($gaId) ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?= e($gaId) ?>');
        </script>
    <?php endif; ?>

    <link rel="stylesheet" href="/assets/css/app.css">
    <meta name="csrf-token" content="<?= e(\App\Core\Http\Csrf::token()) ?>">
    <style>[x-cloak] { display: none !important; }</style>

    <script>
    function bottomNav() {
        return {
            cartCount: 0,
            cartBump: false,
            wishlistCount: 0,
            wishlistBump: false,

            init() {
                this.fetchCart();
                this.fetchWishlist();

                // Dengarkan event cart-updated dari add to cart
                window.addEventListener('cart-updated', (e) => {
                    const count = e.detail?.count ?? null;
                    if (count !== null) {
                        this.setCartCount(count);
                    } else {
                        this.fetchCart();
                    }
                });

                // Dengarkan event wishlist-updated dari toggle wishlist
                window.addEventListener('wishlist-updated', (e) => {
                    const count = e.detail?.count ?? null;
                    if (count !== null) {
                        this.setWishlistCount(count);
                    } else {
                        this.fetchWishlist();
                    }
                });
            },

            fetchCart() {
                fetch('/cart/count', { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(data => { this.cartCount = data.count ?? 0; })
                    .catch(() => {});
            },

            fetchWishlist() {
                <?php if ($currentUser): ?>
                fetch('/wishlist/count', { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(data => { this.wishlistCount = data.count ?? 0; })
                    .catch(() => {});
                <?php endif; ?>
            },

            setCartCount(n) {
                this.cartCount = n;
                this.cartBump  = true;
                setTimeout(() => this.cartBump = false, 600);
            },

            setWishlistCount(n) {
                this.wishlistCount = n;
                this.wishlistBump  = true;
                setTimeout(() => this.wishlistBump = false, 600);
            },
        }
    }
    </script>
</head>
<body class="bg-gray-50 text-gray-900 flex flex-col min-h-screen">

<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$isActive    = fn(string $path): bool => $currentPath === $path;
$isPrefix    = fn(string $prefix): bool => str_starts_with($currentPath, $prefix);
?>

<!-- ===== NAVBAR ATAS ===== -->
<header class="sticky top-0 z-40 bg-white border-b border-gray-200">
    <div class="max-w-5xl mx-auto px-4">
        <div class="flex items-center gap-3 h-14">

            <!-- Logo -->
            <a href="/" class="flex items-center gap-2 shrink-0">
                <div class="w-7 h-7 bg-orange-600 rounded-md flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <span class="font-bold text-gray-900 text-sm hidden sm:block"><?= e(config('app.name')) ?></span>
            </a>

            <!-- Search bar dengan live search dropdown -->
            <div class="flex-1 relative" x-data="liveSearch()">
                <form action="/produk" method="GET" @submit="showDropdown = false">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="text" name="q"
                            x-model="query"
                            @input.debounce.400ms="search()"
                            @focus="query.length >= 2 && search()"
                            @keydown.escape="showDropdown = false"
                            @click.outside="showDropdown = false"
                            value="<?= e($_GET['q'] ?? '') ?>"
                            placeholder="Cari produk..."
                            autocomplete="off"
                            class="w-full pl-9 pr-4 py-2 bg-gray-100 border border-transparent rounded-xl text-sm focus:outline-none focus:bg-white focus:border-orange-400 transition">
                    </div>
                </form>

                <!-- Dropdown hasil pencarian -->
                <div x-show="showDropdown && (loading || results.length > 0)"
                    x-cloak
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    @click.outside="showDropdown = false"
                    class="absolute top-full left-0 right-0 bg-white border border-gray-200 rounded-xl shadow-lg z-50 mt-1 overflow-hidden">

                    <!-- Loading -->
                    <div x-show="loading" x-cloak class="flex items-center justify-center gap-2 py-4 text-sm text-gray-400">
                        <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Mencari...
                    </div>

                    <!-- Hasil -->
                    <div x-show="!loading && results.length > 0" x-cloak>
                        <template x-for="product in results" :key="product.id">
                            <a :href="'/produk/' + product.slug"
                                @click="showDropdown = false"
                                class="flex items-center gap-3 px-4 py-3 hover:bg-orange-50 transition border-b border-gray-50 last:border-0">
                                <div class="w-10 h-10 bg-gray-100 rounded-lg overflow-hidden shrink-0">
                                    <img x-show="product.image" :src="product.image" :alt="product.name"
                                        class="w-full h-full object-cover">
                                    <div x-show="!product.image" class="w-full h-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800 truncate" x-text="product.name"></p>
                                    <p class="text-xs text-orange-600 font-semibold" x-text="'Rp ' + product.price"></p>
                                </div>
                            </a>
                        </template>

                        <!-- Lihat semua hasil -->
                        <a :href="'/produk?q=' + encodeURIComponent(query)"
                            @click="showDropdown = false"
                            class="flex items-center justify-center gap-1.5 py-3 text-sm text-orange-600 font-medium hover:bg-orange-50 transition border-t border-gray-100">
                            <span x-text="'Lihat semua hasil untuk \'' + query + '\''"></span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>

                    <!-- Tidak ada hasil -->
                    <div x-show="!loading && results.length === 0 && query.length >= 2"
                        x-cloak
                        class="px-4 py-4 text-sm text-gray-400 text-center">
                        Produk "<span x-text="query" class="font-medium text-gray-600"></span>" tidak ditemukan.
                    </div>
                </div>
            </div>

        </div>
    </div>
</header>

<!-- ===== KONTEN HALAMAN ===== -->
<main class="max-w-5xl mx-auto px-4 pb-24 flex-1 w-full">
    <?= $this->yield('content') ?>
</main>

<!-- ===== BOTTOM NAVIGATION ===== -->
<nav class="fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-200 safe-area-pb"
     x-data="bottomNav()"
     x-init="init()">
    <div class="max-w-5xl mx-auto grid grid-cols-5">

        <!-- Home -->
        <a href="/"
            class="flex flex-col items-center justify-center py-2.5 gap-0.5 transition
                   <?= $isActive('/') ? 'text-orange-600' : 'text-gray-400 hover:text-gray-600' ?>">
            <svg class="w-5 h-5" fill="<?= $isActive('/') ? 'currentColor' : 'none' ?>" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="text-[10px] font-medium">Home</span>
        </a>

        <!-- Kategori -->
        <a href="/kategori"
            class="flex flex-col items-center justify-center py-2.5 gap-0.5 transition
                   <?= $isPrefix('/kategori') ? 'text-orange-600' : 'text-gray-400 hover:text-gray-600' ?>">
            <svg class="w-5 h-5" fill="<?= $isPrefix('/kategori') ? 'currentColor' : 'none' ?>" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            <span class="text-[10px] font-medium">Kategori</span>
        </a>

        <!-- Cart -->
        <a href="/cart"
            class="flex flex-col items-center justify-center py-2.5 gap-0.5 transition
                   <?= $isActive('/cart') ? 'text-orange-600' : 'text-gray-400 hover:text-gray-600' ?>">
            <div class="relative">
                <svg class="w-5 h-5" fill="<?= $isActive('/cart') ? 'currentColor' : 'none' ?>" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>

                <!-- Badge cart count -->
                <span x-show="cartCount > 0"
                    x-cloak
                    x-text="cartCount > 99 ? '99+' : cartCount"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-50"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="absolute -top-2 -right-2.5 min-w-[16px] h-4 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center px-0.5 leading-none">
                </span>

                <!-- Ping saat bertambah -->
                <span x-show="cartBump"
                    x-cloak
                    class="absolute -top-2 -right-2.5 w-4 h-4 bg-red-400 rounded-full animate-ping opacity-75">
                </span>
            </div>
            <span class="text-[10px] font-medium">Cart</span>
        </a>

        <!-- Wishlist -->
        <a href="<?= $currentUser ? '/wishlist' : '/login' ?>"
            class="flex flex-col items-center justify-center py-2.5 gap-0.5 transition
                   <?= $isActive('/wishlist') ? 'text-orange-600' : 'text-gray-400 hover:text-gray-600' ?>">
            <div class="relative">
                <svg class="w-5 h-5"
                    fill="<?= $isActive('/wishlist') ? 'currentColor' : 'none' ?>"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>

                <!-- Badge wishlist count -->
                <?php if ($currentUser): ?>
                    <span x-show="wishlistCount > 0"
                        x-cloak
                        x-text="wishlistCount > 99 ? '99+' : wishlistCount"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-50"
                        x-transition:enter-end="opacity-100 scale-100"
                        class="absolute -top-2 -right-2.5 min-w-[16px] h-4 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center px-0.5 leading-none">
                    </span>

                    <!-- Ping saat bertambah -->
                    <span x-show="wishlistBump"
                        x-cloak
                        class="absolute -top-2 -right-2.5 w-4 h-4 bg-red-400 rounded-full animate-ping opacity-75">
                    </span>
                <?php endif; ?>
            </div>
            <span class="text-[10px] font-medium">Wishlist</span>
        </a>

        <!-- Profil -->
        <a href="<?= $currentUser ? '/profil' : '/login' ?>"
            class="flex flex-col items-center justify-center py-2.5 gap-0.5 transition
                   <?= $isActive('/profil') ? 'text-orange-600' : 'text-gray-400 hover:text-gray-600' ?>">
            <?php if ($currentUser): ?>
                <div class="w-5 h-5 bg-orange-100 rounded-full flex items-center justify-center">
                    <span class="text-orange-700 font-bold text-[9px]">
                        <?= strtoupper(substr($currentUser->name, 0, 1)) ?>
                    </span>
                </div>
            <?php else: ?>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            <?php endif; ?>
            <span class="text-[10px] font-medium"><?= $currentUser ? 'Profil' : 'Masuk' ?></span>
        </a>

    </div>
</nav>
<!-- ===== FOOTER ===== -->
<?php
$pageService    = new \App\Modules\Page\Application\Services\PageService();
$footerPages    = $pageService->getPublished();
$settingService = \App\Modules\Setting\Application\Services\SettingService::getInstance();
?>
<footer class="bg-white border-t border-gray-200 pb-24">
    <div class="max-w-5xl mx-auto px-4 py-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <p class="font-bold text-gray-800 text-sm"><?= e($settingService->storeName()) ?></p>
                <p class="text-xs text-gray-400 mt-0.5"><?= e($settingService->storeEmail()) ?></p>
            </div>
            <?php if (!empty($footerPages)): ?>
                <nav class="flex flex-wrap gap-x-4 gap-y-1">
                    <?php foreach ($footerPages as $p): ?>
                        <a href="/p/<?= e($p['slug']) ?>"
                            class="text-xs text-gray-500 hover:text-orange-600 transition">
                            <?= e($p['title']) ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
        </div>
        <div class="border-t border-gray-100 mt-4 pt-4">
            <p class="text-xs text-gray-400 text-center">
                &copy; <?= date('Y') ?> <?= e($settingService->storeName()) ?>. All rights reserved.
            </p>
        </div>
    </div>
</footer>
<script>
  function liveSearch() {
        return {
            query: '<?= e($_GET['q'] ?? '') ?>',
            results: [],
            loading: false,
            showDropdown: false,
            controller: null,

            async search() {
                const q = this.query.trim();

                if (q.length < 2) {
                    this.results      = [];
                    this.showDropdown = false;
                    return;
                }

                // Batalkan request sebelumnya kalau masih loading
                if (this.controller) {
                    this.controller.abort();
                }

                this.controller   = new AbortController();
                this.loading      = true;
                this.showDropdown = true;

                try {
                    const res  = await fetch('/search?q=' + encodeURIComponent(q), {
                        signal: this.controller.signal,
                    });
                    const data = await res.json();
                    this.results = data.products || [];
                } catch (e) {
                    if (e.name !== 'AbortError') {
                        this.results = [];
                    }
                } finally {
                    this.loading = false;
                }
            }
        }
    }</script>
<script src="/assets/js/app.js"></script>
</body>
</html>