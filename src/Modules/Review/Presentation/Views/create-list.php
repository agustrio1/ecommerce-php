<?php $this->layout('layouts.storefront', ['title' => 'Beri Ulasan']) ?>

<?php $this->section('content') ?>

<div class="py-4">
    <h1 class="font-bold text-gray-900 text-lg mb-4">Beri Ulasan</h1>

    <?php $flashSuccess = \App\Core\Http\Session::getFlash('success'); ?>
    <?php $flashError   = \App\Core\Http\Session::getFlash('error'); ?>
    <?php if ($flashSuccess): ?>
        <div class="mb-4 p-3 bg-green-50 text-green-700 text-sm rounded-xl border border-green-200"><?= e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="mb-4 p-3 bg-red-50 text-red-700 text-sm rounded-xl border border-red-200"><?= e($flashError) ?></div>
    <?php endif; ?>

    <?php if (empty($items)): ?>
        <div class="text-center py-16">
            <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
            </svg>
            <p class="text-gray-400 text-sm">Belum ada produk yang bisa diulas.</p>
            <p class="text-xs text-gray-300 mt-1">Ulasan bisa diberikan setelah pesanan berstatus "Selesai".</p>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($items as $item): ?>
                <a href="/ulasan/tulis/<?= $item['order_item_id'] ?>"
                    class="block bg-white rounded-xl border border-gray-100 p-4 hover:border-orange-200 hover:shadow-md transition">
                    <div class="flex items-center gap-3">
                        <div class="w-14 h-14 bg-gray-100 rounded-lg overflow-hidden shrink-0">
                            <?php if ($item['product_image']): ?>
                                <img src="/storage/<?= e($item['product_image']) ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate"><?= e($item['product_name']) ?></p>
                            <?php if ($item['variant_label']): ?>
                                <p class="text-xs text-gray-400"><?= e($item['variant_label']) ?></p>
                            <?php endif; ?>
                            <p class="text-xs text-gray-400 mt-0.5">Order <?= e($item['order_number']) ?></p>
                        </div>
                        <span class="px-3 py-1.5 bg-orange-600 text-white text-xs font-medium rounded-lg shrink-0">
                            Beri Ulasan
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php $this->endSection() ?>