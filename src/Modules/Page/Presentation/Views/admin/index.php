<?php $this->layout('layouts.admin', ['title' => 'Halaman Statis']) ?>

<?php $this->section('content') ?>

<?php $flash = \App\Core\Http\Session::getFlash('success'); ?>
<?php if ($flash): ?>
    <div class="mb-4 p-3 bg-green-50 text-green-700 text-sm rounded-xl border border-green-200"><?= e($flash) ?></div>
<?php endif; ?>

<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-900">Halaman Statis</h1>
    <a href="/admin/pages/create"
        class="px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-xl hover:bg-orange-700 transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Buat Halaman
    </a>
</div>

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Judul</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">URL</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Status</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php foreach ($pages as $page): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-800"><?= e($page['title']) ?></p>
                    </td>
                    <td class="px-4 py-3 hidden sm:table-cell">
                        <a href="/p/<?= e($page['slug']) ?>" target="_blank"
                            class="text-xs text-orange-600 hover:underline font-mono">
                            /p/<?= e($page['slug']) ?>
                        </a>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-0.5 text-xs font-medium rounded-full <?= $page['is_published'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
                            <?= $page['is_published'] ? 'Published' : 'Draft' ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-1.5">
                            <a href="/admin/pages/<?= $page['id'] ?>/edit"
                                class="px-3 py-1.5 text-xs font-medium text-orange-600 bg-orange-50 rounded-lg hover:bg-orange-100 transition">
                                Edit
                            </a>
                            <form method="POST" action="/admin/pages/<?= $page['id'] ?>"
                                onsubmit="return confirm('Hapus halaman ini?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit"
                                    class="px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($pages)): ?>
                <tr>
                    <td colspan="4" class="px-4 py-12 text-center text-gray-400 text-sm">
                        Belum ada halaman statis. Buat halaman About, Kontak, dll.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php $this->endSection() ?>