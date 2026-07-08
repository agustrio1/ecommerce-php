<?php $this->layout('layouts.admin', ['title' => 'Flash Sale']) ?>

<?php $this->section('content') ?>

<?php $flash = \App\Core\Http\Session::getFlash('success'); ?>
<?php if ($flash): ?>
    <div class="mb-4 p-3 bg-green-50 text-green-700 text-sm rounded-xl border border-green-200"><?= e($flash) ?></div>
<?php endif; ?>

<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-900">Flash Sale</h1>
    <a href="/admin/flash-sales/create"
        class="px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-xl hover:bg-orange-700 transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Buat Flash Sale
    </a>
</div>

<div class="space-y-3">
    <?php foreach ($flashSales as $fs): ?>
        <?php
        $now       = time();
        $started   = strtotime($fs['starts_at']) <= $now;
        $ended     = strtotime($fs['ends_at']) < $now;
        $isRunning = $fs['is_active'] && $started && !$ended;

        $statusLabel = !$fs['is_active'] ? 'Nonaktif' : ($ended ? 'Selesai' : ($started ? 'Berlangsung' : 'Akan Datang'));
        $statusColor = !$fs['is_active'] ? 'bg-gray-100 text-gray-600' : ($ended ? 'bg-gray-100 text-gray-600' : ($started ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'));
        ?>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <p class="font-semibold text-gray-800"><?= e($fs['name']) ?></p>
                        <span class="px-2 py-0.5 text-xs font-medium rounded-full <?= $statusColor ?>"><?= $statusLabel ?></span>
                        <?php if ($isRunning): ?>
                            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-gray-400">
                        <?= e(date('d M Y H:i', strtotime($fs['starts_at']))) ?>
                        —
                        <?= e(date('d M Y H:i', strtotime($fs['ends_at']))) ?>
                    </p>
                    <p class="text-xs text-gray-400"><?= $fs['product_count'] ?> produk</p>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <a href="/admin/flash-sales/<?= $fs['id'] ?>"
                        class="px-3 py-1.5 text-xs font-medium text-orange-600 bg-orange-50 rounded-lg hover:bg-orange-100 transition">
                        Kelola
                    </a>
                    <form method="POST" action="/admin/flash-sales/<?= $fs['id'] ?>"
                        onsubmit="return confirm('Hapus flash sale ini?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit"
                            class="px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition">
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($flashSales)): ?>
        <div class="text-center py-16 bg-white rounded-xl border border-gray-200">
            <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <p class="text-gray-400 text-sm">Belum ada flash sale.</p>
        </div>
    <?php endif; ?>
</div>

<?php $this->endSection() ?>