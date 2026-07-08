<?php

declare(strict_types=1);

use App\Core\Routing\Router;
use App\Modules\Auth\Presentation\Controllers\AuthController;
use App\Modules\Category\Presentation\Controllers\CategoryController;
use App\Modules\Product\Presentation\Controllers\AttributeController;
use App\Modules\Product\Presentation\Controllers\ProductController;
use App\Core\Http\StorageController;
use App\Modules\Product\Presentation\Controllers\StorefrontController;
use App\Modules\Setting\Presentation\Controllers\SettingController;
use App\Modules\Cart\Presentation\Controllers\CartController;
use App\Modules\Order\Presentation\Controllers\CheckoutController;
use App\Modules\Order\Presentation\Controllers\OrderController;
use App\Modules\Order\Presentation\Controllers\AdminOrderController;
use App\Modules\Dev\Presentation\Controllers\DevController;
use App\Modules\Auth\Presentation\Controllers\ProfileController;
use App\Modules\Review\Presentation\Controllers\ReviewController;
use App\Modules\Order\Presentation\Controllers\WebhookLogController;
use App\Modules\Inventory\Presentation\Controllers\InventoryController;
use App\Modules\Dashboard\Presentation\Controllers\DashboardController;
use App\Modules\Dashboard\Presentation\Controllers\ExportController;
use App\Modules\Auth\Presentation\Controllers\AdminCustomerController;
use App\Modules\Auth\Presentation\Controllers\AdminUserController;
use App\Modules\Auth\Presentation\Controllers\AdminRoleController;
use App\Modules\Review\Presentation\Controllers\AdminReviewController;
use App\Modules\Search\Presentation\Controllers\GlobalSearchController;
use App\Modules\Dashboard\Presentation\Controllers\NotificationController;
use App\Modules\Banner\Presentation\Controllers\BannerController;
use App\Modules\Wishlist\Presentation\Controllers\WishlistController;
use App\Modules\Coupon\Presentation\Controllers\CouponController;
use App\Modules\FlashSale\Presentation\Controllers\FlashSaleController;
use App\Modules\Dashboard\Presentation\Controllers\ReportController;
use App\Modules\Page\Presentation\Controllers\PageController;
use App\Modules\Page\Presentation\Controllers\AdminPageController;

return function (Router $router) {

    // ===================== STORAGE FILE SERVING =====================
    $router->get('/storage/{path}', [StorageController::class, 'serve']);

    // ===================== AUTH: GUEST ONLY =====================
    $router->group(['middleware' => ['guest']], function (Router $router) {
        $router->get('/register', [AuthController::class, 'showRegister']);
        $router->post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');

        $router->get('/login', [AuthController::class, 'showLogin']);
        $router->post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

        $router->get('/forgot-password', [AuthController::class, 'showForgotPassword']);
        $router->post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,5');

        $router->get('/reset-password', [AuthController::class, 'showResetPassword']);
        $router->post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');

        $router->get('/resend-verification', [AuthController::class, 'showResendVerification']);
        $router->post('/resend-verification', [AuthController::class, 'resendVerification'])->middleware('throttle:3,5');
    });

    $router->get('/verify-email', [AuthController::class, 'verifyEmail']);

    // ===================== AUTH: REQUIRES LOGIN =====================
    $router->group(['middleware' => ['auth']], function (Router $router) {
        $router->post('/logout', [AuthController::class, 'logout']);

        $router->get('/dashboard', [DashboardController::class, 'index']);
        
        $router->get('/profil', [ProfileController::class, 'index']);
        $router->post('/profil', [ProfileController::class, 'update']);

        $router->get('/profil/ubah-password', [ProfileController::class, 'showChangePassword']);
        $router->post('/profil/ubah-password', [ProfileController::class, 'updatePassword']);

        $router->get('/profil/alamat', [ProfileController::class, 'addresses']);
        $router->post('/profil/alamat', [ProfileController::class, 'storeAddress']);
        $router->put('/profil/alamat/{id}', [ProfileController::class, 'updateAddress']);
        $router->delete('/profil/alamat/{id}', [ProfileController::class, 'deleteAddress']);
        $router->post('/profil/alamat/{id}/utama', [ProfileController::class, 'setPrimaryAddress']);
        $router->get('/ulasan', [ReviewController::class, 'index']);
        $router->get('/ulasan/tulis/{orderItemId}', [ReviewController::class, 'create']);
        $router->post('/ulasan', [ReviewController::class, 'store']);
        // Wishlist
        $router->get('/wishlist', [WishlistController::class, 'index']);
        $router->post('/wishlist/toggle', [WishlistController::class, 'toggle']);
        $router->post('/wishlist/{productId}/remove', [WishlistController::class, 'remove']);
        $router->get('/wishlist/count', [WishlistController::class, 'count']);

        // Kupon validate (dari checkout)
        $router->post('/coupon/validate', [CouponController::class, 'validate']);
    });

    // ===================== ADMIN =====================
    $router->group(['prefix' => '/admin', 'middleware' => ['auth', 'role:super_admin,admin']], function (Router $router) {
        $router->get('/categories', [CategoryController::class, 'index']);
        $router->get('/categories/create', [CategoryController::class, 'create']);
        $router->post('/categories', [CategoryController::class, 'store']);
        $router->get('/categories/{id}/edit', [CategoryController::class, 'edit']);
        $router->put('/categories/{id}', [CategoryController::class, 'update']);
        $router->delete('/categories/{id}', [CategoryController::class, 'destroy']);

        $router->get('/attributes', [AttributeController::class, 'index']);
        $router->post('/attributes', [AttributeController::class, 'storeAttribute']);
        $router->post('/attribute-values', [AttributeController::class, 'storeValue']);

        $router->get('/products', [ProductController::class, 'index']);
        $router->get('/products/create', [ProductController::class, 'create']);
        $router->post('/products', [ProductController::class, 'store']);
        $router->get('/products/{id}/edit', [ProductController::class, 'edit']);
        $router->put('/products/{id}', [ProductController::class, 'update']);
        $router->delete('/products/{id}', [ProductController::class, 'destroy']);
        $router->patch('/product-variants/{variantId}/stock', [ProductController::class, 'updateVariantStock']);
        $router->delete('/product-images/{imageId}', [ProductController::class, 'deleteImage']);
        $router->post('/products/bulk', [ProductController::class, 'bulkAction']);

        $router->get('/settings/general', [SettingController::class, 'general']);
        $router->post('/settings/general', [SettingController::class, 'saveGeneral']);
        $router->get('/settings/store', [SettingController::class, 'store']);
        $router->post('/settings/store', [SettingController::class, 'saveStore']);
        $router->get('/settings/payment', [SettingController::class, 'payment']);
        $router->post('/settings/payment', [SettingController::class, 'savePayment']);
        $router->get('/settings/shipping', [SettingController::class, 'shipping']);
        $router->post('/settings/shipping', [SettingController::class, 'saveShipping']);
        $router->get('/settings/seo', [SettingController::class, 'seo']);
        $router->post('/settings/seo', [SettingController::class, 'saveSeo']);
        $router->get('/settings/search-area', [SettingController::class, 'searchArea']);
        // Order management
        $router->get('/orders', [AdminOrderController::class, 'index']);
        $router->get('/orders/{id}', [AdminOrderController::class, 'show']);
        $router->post('/orders/{id}/status', [AdminOrderController::class, 'updateStatus']);
        $router->post('/orders/{id}/shipment', [AdminOrderController::class, 'createShipment']);
        if (env('APP_ENV', 'production') !== 'production') {
    $router->get('/dev/payment-simulator', [DevController::class, 'paymentSimulator']);
    $router->post('/dev/payment-simulator/callback', [DevController::class, 'simulateCallback']);
    $router->get('/webhook-logs', [WebhookLogController::class, 'index']);
    $router->get('/inventory', [InventoryController::class, 'index']);
        $router->get('/inventory/{variantId}/history', [InventoryController::class, 'history']);
        $router->post('/inventory/{variantId}/restock', [InventoryController::class, 'restock']);
        $router->patch('/inventory/{variantId}/adjust', [InventoryController::class, 'adjust']);
        // Export
        $router->get('/export/orders', [ExportController::class, 'orders']);
        $router->get('/export/products', [ExportController::class, 'products']);
        $router->get('/export/inventory', [ExportController::class, 'inventory']);
        // Pelanggan
        $router->get('/customers', [AdminCustomerController::class, 'index']);
        $router->get('/customers/{id}', [AdminCustomerController::class, 'show']);

        // Ulasan
        $router->get('/reviews', [AdminReviewController::class, 'index']);
        $router->delete('/reviews/{id}', [AdminReviewController::class, 'destroy']);

        // Pengguna
        $router->get('/users', [AdminUserController::class, 'index']);
        $router->get('/users/create', [AdminUserController::class, 'create']);
        $router->post('/users', [AdminUserController::class, 'store']);
        $router->get('/users/{id}/edit', [AdminUserController::class, 'edit']);
        $router->put('/users/{id}', [AdminUserController::class, 'update']);
        $router->delete('/users/{id}', [AdminUserController::class, 'destroy']);
        
        $router->get('/search', [GlobalSearchController::class, 'search']);

        // Role & Akses
        $router->get('/roles', [AdminRoleController::class, 'index']);
        $router->post('/roles/{roleId}/permissions', [AdminRoleController::class, 'updatePermissions']);
}

       $router->get('/notifications/poll', [NotificationController::class, 'poll']);
       // Banner
        $router->get('/banners', [BannerController::class, 'index']);
        $router->get('/banners/create', [BannerController::class, 'create']);
        $router->post('/banners', [BannerController::class, 'store']);
        $router->get('/banners/{id}/edit', [BannerController::class, 'edit']);
        $router->put('/banners/{id}', [BannerController::class, 'update']);
        $router->delete('/banners/{id}', [BannerController::class, 'destroy']);

        // Kupon
        $router->get('/coupons', [CouponController::class, 'index']);
        $router->get('/coupons/create', [CouponController::class, 'create']);
        $router->post('/coupons', [CouponController::class, 'store']);
        $router->get('/coupons/{id}/edit', [CouponController::class, 'edit']);
        $router->put('/coupons/{id}', [CouponController::class, 'update']);
        $router->delete('/coupons/{id}', [CouponController::class, 'destroy']);

        // Flash Sale
        $router->get('/flash-sales', [FlashSaleController::class, 'index']);
        $router->get('/flash-sales/create', [FlashSaleController::class, 'create']);
        $router->post('/flash-sales', [FlashSaleController::class, 'store']);
        $router->get('/flash-sales/{id}', [FlashSaleController::class, 'show']);
        $router->post('/flash-sales/{id}/products', [FlashSaleController::class, 'addProduct']);
        $router->delete('/flash-sales/{id}/products/{productId}', [FlashSaleController::class, 'removeProduct']);
        $router->delete('/flash-sales/{id}', [FlashSaleController::class, 'destroy']);
        $router->get('/reports', [ReportController::class, 'index']);
        $router->get('/reports/export', [ReportController::class, 'export']);
        $router->get('/pages', [AdminPageController::class, 'index']);
        $router->get('/pages/create', [AdminPageController::class, 'create']);
        $router->post('/pages', [AdminPageController::class, 'store']);
        $router->get('/pages/{id}/edit', [AdminPageController::class, 'edit']);
        $router->put('/pages/{id}', [AdminPageController::class, 'update']);
        $router->delete('/pages/{id}', [AdminPageController::class, 'destroy']);
    });

    // ===================== STOREFRONT =====================
    $router->get('/', [StorefrontController::class, 'home']);
    $router->get('/produk', [StorefrontController::class, 'products']);
    $router->get('/produk/{slug}', [StorefrontController::class, 'productDetail']);
    $router->get('/kategori', [StorefrontController::class, 'categories']);
    // Live search
    $router->get('/search', [StorefrontController::class, 'liveSearch']);

    // Halaman kategori produk
    $router->get('/kategori/{slug}', [StorefrontController::class, 'categoryProducts']);

    // API public — search area Biteship (dipakai storefront checkout, tidak butuh auth admin)
    $router->get('/api/search-area', [SettingController::class, 'searchArea']);

    // ===================== CART =====================
    $router->get('/cart', [CartController::class, 'index']);
    $router->post('/cart/add', [CartController::class, 'add']);
    $router->post('/cart/update', [CartController::class, 'update']);
    $router->post('/cart/remove', [CartController::class, 'remove']);
    $router->get('/cart/count', [CartController::class, 'count']);

    // ===================== CHECKOUT =====================
    $router->group(['middleware' => ['auth']], function (Router $router) {
    $router->get('/checkout', [CheckoutController::class, 'index']);
    $router->post('/checkout/address', [CheckoutController::class, 'storeAddress'])->middleware('throttle:10,1');
    $router->post('/checkout/address/update', [CheckoutController::class, 'updateAddress'])->middleware('throttle:10,1');
    $router->post('/checkout/rates', [CheckoutController::class, 'getRates'])->middleware('throttle:15,1');
    $router->post('/checkout/submit', [CheckoutController::class, 'submit'])->middleware('throttle:5,1');

    $router->get('/orders', [OrderController::class, 'myOrders']);
    $router->get('/orders/{orderNumber}/payment', [OrderController::class, 'show']);
});
    
    $router->get('/p/{slug}', [PageController::class, 'show']);

    // ===================== WEBHOOKS =====================
    $router->post('/webhooks/ipaymu', [OrderController::class, 'ipaymuCallback']);
    $router->post('/webhooks/biteship', [AdminOrderController::class, 'biteshipWebhook']);
};
