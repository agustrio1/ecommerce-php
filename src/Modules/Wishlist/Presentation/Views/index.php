<?php $this->layout('layouts.storefront', ['title' => 'Wishlist Saya']) ?>

<?php $this->section('content') ?>

<div class="py-4">
    <h1 class="font-bold text-gray-900 text-lg mb-4">Wishlist Saya</h1>

    <?php $flash = \App\Core\Http\Session::getFlash('success'); ?>
    <?php if ($flash): ?>
        <div class="mb-4 p-3 bg-green-50 text-green-700 text-sm rounded-xl border border-green-200"><?= e($flash) ?></div>
    <?php endif; ?>

    <?php if (empty($items)): ?>
        <div class="text-center py-16">
            <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
            </svg>
            <p class="text-gray-400 text-sm mb-3">Wishlist masih kosong.</p>
            <a href="/produk" class="px-5 py-2 bg-orange-600 text-white rounded-xl text-sm font-medium hover:bg-orange-700 transition">
                Jelajahi Produk
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <?php foreach ($items as $item): ?>
                <div class="bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-md transition group">
                    <a href="/produk/<?= e($item['slug']) ?>">
                        <div class="aspect-square bg-gray-100 overflow-hidden">
                            <?php if ($item['product_image']): ?>
                                <img src="/storage/<?= e($item['product_image']) ?>"
                                    alt="<?= e($item['product_name']) ?>"
                                    class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-3">
                            <p class="text-sm font-medium text-gray-800 line-clamp-2 mb-1"><?= e($item['product_name']) ?></p>
                            <p class="font-bold text-orange-600 text-sm">Rp <?= number_format($item['price'], 0, ',', '.') ?></p>
                        </div>
                    </a>
                    <div class="px-3 pb-3">
                        <form method="POST" action="/wishlist/<?= $item['product_id'] ?>/remove">
                            <?= csrf_field() ?>
                            <button type="submit"
                                class="w-full py-1.5 text-xs font-medium text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition">
                                Hapus dari Wishlist
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php $this->endSection() ?>