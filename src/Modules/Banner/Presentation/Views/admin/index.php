<?php $this->layout('layouts.admin', ['title' => 'Kelola Banner']) ?>

<?php $this->section('content') ?>

<?php $flash = \App\Core\Http\Session::getFlash('success'); ?>
<?php if ($flash): ?>
    <div class="mb-4 p-3 bg-green-50 text-green-700 text-sm rounded-xl border border-green-200"><?= e($flash) ?></div>
<?php endif; ?>

<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-900">Kelola Banner</h1>
    <a href="/admin/banners/create"
        class="px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-xl hover:bg-orange-700 transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Tambah Banner
    </a>
</div>

<div class="space-y-3">
    <?php foreach ($banners as $banner): ?>
        <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-4">

            <!-- Preview -->
            <div class="w-24 h-14 rounded-lg overflow-hidden shrink-0"
                style="background-color: <?= e($banner['bg_color']) ?>">
                <?php if ($banner['image_path']): ?>
                    <img src="/storage/<?= e($banner['image_path']) ?>"
                        class="w-full h-full object-cover">
                <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center">
                        <span class="text-white text-xs font-bold text-center px-1 leading-tight">
                            <?= e($banner['title']) ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Info -->
            <div class="flex-1 min-w-0">
                <p class="font-medium text-gray-800 truncate"><?= e($banner['title']) ?></p>
                <?php if ($banner['subtitle']): ?>
                    <p class="text-xs text-gray-400 truncate"><?= e($banner['subtitle']) ?></p>
                <?php endif; ?>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-xs <?= $banner['is_active'] ? 'text-green-600' : 'text-gray-400' ?>">
                        <?= $banner['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                    </span>
                    <span class="text-gray-300">·</span>
                    <span class="text-xs text-gray-400">Urutan: <?= $banner['sort_order'] ?></span>
                    <?php if ($banner['button_url']): ?>
                        <span class="text-gray-300">·</span>
                        <span class="text-xs text-gray-400 truncate"><?= e($banner['button_url']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Aksi -->
            <div class="flex items-center gap-2 shrink-0">
                <a href="/admin/banners/<?= $banner['id'] ?>/edit"
                    class="px-3 py-1.5 text-xs font-medium text-orange-600 bg-orange-50 rounded-lg hover:bg-orange-100 transition">
                    Edit
                </a>
                <form method="POST" action="/admin/banners/<?= $banner['id'] ?>"
                    onsubmit="return confirm('Hapus banner ini?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit"
                        class="px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition">
                        Hapus
                    </button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($banners)): ?>
        <div class="text-center py-16 bg-white rounded-xl border border-gray-200">
            <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="text-gray-400 text-sm">Belum ada banner. Tambahkan banner pertama kamu.</p>
        </div>
    <?php endif; ?>
</div>

<?php $this->endSection() ?>