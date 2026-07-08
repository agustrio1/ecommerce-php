<?php $this->layout('layouts.admin', ['title' => 'Ulasan Produk']) ?>

<?php $this->section('content') ?>

<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-xl font-bold text-gray-900">Ulasan Produk</h1>
        <p class="text-sm text-gray-400"><?= $total ?> ulasan total</p>
    </div>
</div>

<form method="GET" action="/admin/reviews" class="flex flex-col sm:flex-row gap-2 mb-5">
    <div class="relative flex-1">
        <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <input type="text" name="search" value="<?= e($search) ?>"
            placeholder="Cari produk atau nama reviewer..."
            class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">
    </div>
    <select name="rating" class="w-full sm:w-40 px-3 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">
        <option value="">Semua Rating</option>
        <?php for ($i = 5; $i >= 1; $i--): ?>
            <option value="<?= $i ?>" <?= $ratingFilter == $i ? 'selected' : '' ?>>
                <?= $i ?> Bintang
            </option>
        <?php endfor; ?>
    </select>
    <button type="submit" class="px-5 py-2 bg-gray-800 text-white text-sm rounded-xl font-medium hover:bg-gray-900 transition">
        Filter
    </button>
</form>

<div class="space-y-3">
    <?php foreach ($reviews as $review): ?>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3 flex-1 min-w-0">

                    <!-- Gambar produk -->
                    <div class="w-12 h-12 bg-gray-100 rounded-lg overflow-hidden shrink-0">
                        <?php if ($review['product_image']): ?>
                            <img src="/storage/<?= e($review['product_image']) ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01"/>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <p class="text-sm font-semibold text-gray-800 truncate"><?= e($review['product_name']) ?></p>
                            <!-- Bintang rating -->
                            <div class="flex text-amber-400 text-sm">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span><?= $i <= $review['rating'] ? '★' : '☆' ?></span>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <p class="text-xs text-gray-400 mb-1.5">
                            oleh <span class="font-medium text-gray-600"><?= e($review['user_name']) ?></span>
                            · <?= e(date('d M Y', strtotime($review['created_at']))) ?>
                        </p>

                        <?php if ($review['comment']): ?>
                            <p class="text-sm text-gray-600 leading-relaxed"><?= e($review['comment']) ?></p>
                        <?php endif; ?>

                        <?php if (! empty($review['images'])): ?>
                            <div class="flex gap-2 mt-2">
                                <?php foreach ($review['images'] as $imgPath): ?>
                                    <img src="/storage/<?= e($imgPath) ?>"
                                        class="w-12 h-12 object-cover rounded-lg border border-gray-100">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tombol hapus -->
                <form method="POST" action="/admin/reviews/<?= $review['id'] ?>"
                    onsubmit="return confirm('Hapus ulasan ini?')" class="shrink-0">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit"
                        class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($reviews)): ?>
        <div class="text-center py-16 bg-white rounded-xl border border-gray-200">
            <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
            </svg>
            <p class="text-gray-400 text-sm">Belum ada ulasan.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php $totalPages = (int) ceil($total / $perPage); ?>
<?php if ($totalPages > 1): ?>
    <div class="flex items-center justify-center gap-1.5 mt-5">
        <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
            <a href="/admin/reviews?page=<?= $p ?>&search=<?= urlencode($search) ?>&rating=<?= urlencode($ratingFilter) ?>"
                class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium
                       <?= $p === $page ? 'bg-orange-600 text-white' : 'bg-white border border-gray-200 hover:bg-gray-50' ?>">
                <?= $p ?>
            </a>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<?php $this->endSection() ?>