<?php $flashSuccess ??= null; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? config('app.name')) ?> — Admin</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <meta name="csrf-token" content="<?= e(\App\Core\Http\Csrf::token()) ?>">
</head>
<body class="bg-gray-50 text-gray-900">

<?php
$flashSuccess = \App\Core\Http\Session::getFlash('success');
$flashError   = \App\Core\Http\Session::getFlash('error');
$currentUser  = \App\Modules\Auth\Application\Services\CurrentUserService::user();
$currentRole  = \App\Modules\Auth\Application\Services\CurrentUserService::roleSlug();
$currentPath  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

$isActive = fn(string $path): bool => $currentPath === $path;
$isPrefix = fn(string $prefix): bool => str_starts_with($currentPath, $prefix);
?>

<div x-data="{ sidebarOpen: false }" class="min-h-screen flex">

    <!-- Overlay (mobile) -->
    <div
        x-show="sidebarOpen"
        x-transition:enter="transition-opacity ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="sidebarOpen = false"
        class="fixed inset-0 bg-black/40 z-20 lg:hidden">
    </div>

    <!-- Sidebar -->
    <aside
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        class="fixed top-0 left-0 h-full w-64 bg-white border-r border-gray-200 z-30 flex flex-col transition-transform duration-200 ease-out lg:translate-x-0 lg:static lg:z-auto">

        <!-- Brand -->
        <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
            <div class="w-8 h-8 bg-orange-600 rounded-lg flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="font-bold text-gray-900 text-sm truncate"><?= e(config('app.name')) ?></p>
                <p class="text-xs text-gray-400">Admin Panel</p>
            </div>
            <button @click="sidebarOpen = false" class="ml-auto text-gray-400 hover:text-gray-600 lg:hidden">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Nav -->
        <nav class="flex-1 overflow-y-auto py-4 px-3">

            <!-- Dashboard -->
            <a href="/dashboard" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium mb-1 transition <?= $isActive('/dashboard') ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>

            <!-- Katalog -->
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 mt-4 mb-2">Katalog</p>

            <a href="/admin/products" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium mb-1 transition <?= $isActive('/admin/products') ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                Produk
            </a>

            <a href="/admin/categories" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium mb-1 transition <?= $isActive('/admin/categories') ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                Kategori
            </a>

            <a href="/admin/attributes" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium mb-1 transition <?= $isActive('/admin/attributes') ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                Atribut
            </a>

            <!-- Transaksi -->
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 mt-4 mb-2">Transaksi</p>

            <a href="/admin/orders" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium mb-1 transition <?= $isActive('/admin/orders') ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Pesanan
                <span id="badge-orders"
                    class="ml-auto inline-flex items-center justify-center w-5 h-5 text-xs font-bold bg-orange-600 text-white rounded-full"
                    style="display:none">0</span>
            </a>

            <a href="/admin/customers" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium mb-1 transition <?= $isActive('/admin/customers') ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Pelanggan
            </a>
            
            <a href="/admin/reports"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium mb-1 transition
                       <?= $isPrefix('/admin/reports') ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Laporan
            </a>

            <a href="/admin/reviews" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium mb-1 transition <?= $isActive('/admin/reviews') ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
                Ulasan
            </a>

            <!-- Pengaturan -->
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 mt-4 mb-2">Pengaturan</p>

            <?php if ($currentRole === 'super_admin'): ?>
            <a href="/admin/users" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium mb-1 transition <?= $isActive('/admin/users') ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                Pengguna
            </a>

            <a href="/admin/roles" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium mb-1 transition <?= $isActive('/admin/roles') ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                Role & Akses
            </a>
            <?php endif; ?>

            <a href="/admin/inventory" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium mb-1 transition <?= $isActive('/admin/inventory') ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
                Inventori
                <span id="badge-inventory"
                    class="ml-auto inline-flex items-center justify-center w-5 h-5 text-xs font-bold bg-red-500 text-white rounded-full"
                    style="display:none">0</span>
            </a>
            
            <!-- Promosi -->
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 mt-4 mb-2">Promosi</p>

            <a href="/admin/banners"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium mb-1 transition
                       <?= $isPrefix('/admin/banners') ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Banner
            </a>

            <a href="/admin/coupons"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium mb-1 transition
                       <?= $isPrefix('/admin/coupons') ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                Kupon
            </a>

            <a href="/admin/flash-sales"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium mb-1 transition
                       <?= $isPrefix('/admin/flash-sales') ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Flash Sale
            </a>
          <a href="/admin/pages"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium mb-1 transition
                       <?= $isPrefix('/admin/pages') ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Halaman
            </a>  

            <!-- Settings Dropdown -->
            <div x-data="{ open: <?= $isPrefix('/admin/settings') ? 'true' : 'false' ?> }" class="mb-1">

                <button type="button" @click="open = !open" class="w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition <?= $isPrefix('/admin/settings') ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Pengaturan</span>
                    </div>
                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <a href="/admin/webhook-logs"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium mb-1 transition
                       <?= $isPrefix('/admin/webhook-logs') ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                </svg>
                Webhook Logs
            </a>

                <div x-show="open" x-transition class="mt-1 ml-6 space-y-1">
                    <a href="/admin/settings/general" class="block px-3 py-2 rounded-lg text-sm transition <?= $isActive('/admin/settings/general') ? 'bg-orange-100 text-orange-700' : 'text-gray-600 hover:bg-gray-50' ?>">General</a>
                    <a href="/admin/settings/store" class="block px-3 py-2 rounded-lg text-sm transition <?= $isActive('/admin/settings/store') ? 'bg-orange-100 text-orange-700' : 'text-gray-600 hover:bg-gray-50' ?>">Toko</a>
                    <a href="/admin/settings/payment" class="block px-3 py-2 rounded-lg text-sm transition <?= $isActive('/admin/settings/payment') ? 'bg-orange-100 text-orange-700' : 'text-gray-600 hover:bg-gray-50' ?>">Pembayaran</a>
                    <a href="/admin/settings/shipping" class="block px-3 py-2 rounded-lg text-sm transition <?= $isActive('/admin/settings/shipping') ? 'bg-orange-100 text-orange-700' : 'text-gray-600 hover:bg-gray-50' ?>">Pengiriman</a>
                    <a href="/admin/settings/seo" class="block px-3 py-2 rounded-lg text-sm transition <?= $isActive('/admin/settings/seo') ? 'bg-orange-100 text-orange-700' : 'text-gray-600 hover:bg-gray-50' ?>">SEO</a>
                </div>

            </div>

        </nav>

        <!-- User info + logout -->
        <div class="border-t border-gray-100 p-3">
            <div class="flex items-center gap-3 px-2 py-2 rounded-lg">
                <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center shrink-0">
                    <span class="text-orange-700 font-bold text-sm"><?= strtoupper(substr($currentUser?->name ?? 'A', 0, 1)) ?></span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-800 truncate"><?= e($currentUser?->name ?? '') ?></p>
                    <p class="text-xs text-gray-400 truncate"><?= e($currentRole ?? '') ?></p>
                </div>
                <form method="POST" action="/logout">
                    <?= csrf_field() ?>
                    <button type="submit" title="Logout" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>

    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0">

        <!-- Header -->
        <header class="sticky top-0 z-10 bg-white border-b border-gray-200 px-4 py-3 flex items-center gap-3">
            <button @click="sidebarOpen = true" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition lg:hidden">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <div class="min-w-0 shrink-0 hidden sm:block">
                <h1 class="text-base font-semibold text-gray-900 truncate"><?= e($title ?? 'Dashboard') ?></h1>
                <?php if (isset($breadcrumbs) && !empty($breadcrumbs)): ?>
                <nav class="flex items-center gap-1 text-xs text-gray-400 mt-0.5">
                    <a href="/dashboard" class="hover:text-orange-600 transition">Home</a>
                    <?php foreach ($breadcrumbs as $label => $url): ?>
                    <span>›</span>
                    <?php if ($url): ?>
                    <a href="<?= e($url) ?>" class="hover:text-orange-600 transition"><?= e($label) ?></a>
                    <?php else: ?>
                    <span class="text-gray-600"><?= e($label) ?></span>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </nav>
                <?php endif; ?>
            </div>

            <!-- Global Search -->
            <div x-data="globalSearch()" @click.outside="open = false" class="relative flex-1 max-w-md">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/>
                    </svg>
                    <input
                        type="text"
                        x-model="query"
                        @input.debounce.300ms="doSearch()"
                        @focus="if (query.length >= 2) open = true"
                        @keydown.escape="open = false"
                        placeholder="Cari produk, pesanan, pelanggan, kategori..."
                        autocomplete="off"
                        class="w-full pl-9 pr-8 py-2 text-sm bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    <svg x-show="loading" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </div>

                <div
                    x-show="open && query.length >= 2"
                    x-transition
                    class="absolute mt-2 w-full sm:w-96 bg-white border border-gray-200 rounded-xl shadow-lg max-h-96 overflow-y-auto z-40">

                    <template x-if="!loading && totalCount === 0">
                        <p class="p-4 text-sm text-gray-400 text-center">Tidak ada hasil untuk "<span x-text="query"></span>"</p>
                    </template>

                    <template x-for="group in groups" :key="group.key">
                        <div x-show="group.items.length > 0" class="border-b border-gray-100 last:border-0">
                            <p class="px-4 pt-3 pb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider" x-text="group.label"></p>
                            <template x-for="item in group.items" :key="item.id">
                                <a :href="item.url" class="flex flex-col px-4 py-2 hover:bg-gray-50 transition">
                                    <span class="text-sm font-medium text-gray-800 truncate" x-text="item.title"></span>
                                    <span x-show="item.subtitle" class="text-xs text-gray-400 truncate" x-text="item.subtitle"></span>
                                </a>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <a href="/" target="_blank" class="hidden md:flex items-center gap-1.5 text-xs text-gray-500 hover:text-orange-600 transition px-3 py-1.5 border border-gray-200 rounded-lg hover:border-orange-300">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                Lihat Toko
            </a>
        </header>

        <!-- Flash Messages -->
        <?php if ($flashSuccess): ?>
        <div class="mx-4 mt-4 p-3 bg-green-50 text-green-700 text-sm rounded-xl border border-green-200 flex items-center gap-2" x-data x-init="setTimeout(() => $el.remove(), 4000)">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <?= e($flashSuccess) ?>
        </div>
        <?php endif; ?>

        <?php if ($flashError): ?>
        <div class="mx-4 mt-4 p-3 bg-red-50 text-red-700 text-sm rounded-xl border border-red-200" x-data x-init="setTimeout(() => $el.remove(), 4000)">
            <?= e($flashError) ?>
        </div>
        <?php endif; ?>

        <!-- Content -->
        <main class="flex-1 p-4 sm:p-6">
            <?= $this->yield('content') ?>
        </main>

    </div>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('globalSearch', () => ({
        query: '',
        open: false,
        loading: false,
        groups: [],
        totalCount: 0,
        _controller: null,

        async doSearch() {
            if (this.query.trim().length < 2) {
                this.open = false;
                this.groups = [];
                this.totalCount = 0;
                return;
            }

            if (this._controller) {
                this._controller.abort();
            }
            this._controller = new AbortController();

            this.loading = true;
            this.open = true;

            try {
                const res = await fetch('/admin/search?q=' + encodeURIComponent(this.query), {
                    signal: this._controller.signal,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await res.json();

                if (!res.ok) {
                    console.error('Global search server error:', data);
                    throw new Error(data.message || 'Search request failed');
                }

                const r = data.results || {};

                this.groups = [
                    { key: 'products',   label: 'Produk',    items: r.products   || [] },
                    { key: 'orders',     label: 'Pesanan',   items: r.orders     || [] },
                    { key: 'customers',  label: 'Pelanggan', items: r.customers  || [] },
                    { key: 'categories', label: 'Kategori',  items: r.categories || [] },
                ];
                this.totalCount = this.groups.reduce((sum, g) => sum + g.items.length, 0);
            } catch (e) {
                if (e.name !== 'AbortError') {
                    console.error('Global search error:', e);
                }
            } finally {
                this.loading = false;
            }
        }
    }));
});
(function() {
    // Polling notifikasi setiap 30 detik
    function pollNotifications() {
        fetch('/admin/notifications/poll')
            .then(res => res.json())
            .then(data => {
                // Update badge di link Pesanan
                updateBadge('badge-orders', data.to_process);
                // Update badge di link inventory
                updateBadge('badge-inventory', data.low_stock);
                // Update judul halaman kalau ada alert
                if (data.total_alerts > 0) {
                    document.title = '(' + data.total_alerts + ') ' + document.title.replace(/^\(\d+\)\s*/, '');
                } else {
                    document.title = document.title.replace(/^\(\d+\)\s*/, '');
                }
            })
            .catch(() => {}); // Silent fail
    }

    function updateBadge(id, count) {
        const el = document.getElementById(id);
        if (!el) return;

        if (count > 0) {
            el.textContent = count;
            el.style.display = 'inline-flex';
        } else {
            el.style.display = 'none';
        }
    }

    // Poll pertama kali setelah 5 detik, lalu tiap 30 detik
    setTimeout(() => {
        pollNotifications();
        setInterval(pollNotifications, 30000);
    }, 5000);
})();
</script>
<script src="/assets/js/app.js"></script>
</body>
</html>