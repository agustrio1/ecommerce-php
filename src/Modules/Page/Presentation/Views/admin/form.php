<?php $this->layout('layouts.admin', ['title' => $title]) ?>

<?php $this->section('content') ?>

<div class="flex items-center gap-3 mb-5">
    <a href="/admin/pages" class="text-gray-400 hover:text-gray-600">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <h1 class="text-xl font-bold text-gray-900"><?= e($title) ?></h1>
</div>

<div class="max-w-3xl space-y-4">
    <form method="POST"
        action="<?= $page ? '/admin/pages/' . $page['id'] : '/admin/pages' ?>"
        class="space-y-4">
        <?= csrf_field() ?>
        <?php if ($page): ?>
            <input type="hidden" name="_method" value="PUT">
        <?php endif; ?>

        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Judul Halaman <span class="text-red-500">*</span></label>
                <input type="text" name="title" required
                    value="<?= e($page['title'] ?? '') ?>"
                    placeholder="Tentang Kami"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>

            <?php if (!$page): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Slug URL <span class="text-gray-400 text-xs font-normal">(kosongkan = auto dari judul)</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-400">/p/</span>
                        <input type="text" name="slug"
                            placeholder="tentang-kami"
                            class="flex-1 px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 font-mono">
                    </div>
                </div>
            <?php else: ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">URL</label>
                    <div class="flex items-center gap-2 px-3 py-2.5 border border-gray-200 bg-gray-50 rounded-lg">
                        <span class="text-sm text-gray-400">/p/</span>
                        <span class="text-sm font-mono text-gray-700"><?= e($page['slug']) ?></span>
                        <a href="/p/<?= e($page['slug']) ?>" target="_blank"
                            class="ml-auto text-xs text-orange-600 hover:underline">Lihat →</a>
                    </div>
                </div>
            <?php endif; ?>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Konten Halaman <span class="text-red-500">*</span></label>
                <?= $this->include('components.rich-editor', [
                    'name'  => 'content',
                    'value' => $page['content'] ?? '',
                    'id'    => 'page_content_editor',
                ]) ?>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-gray-100">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Meta Title SEO</label>
                    <input type="text" name="meta_title" maxlength="100"
                        value="<?= e($page['meta_title'] ?? '') ?>"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Meta Description SEO</label>
                    <input type="text" name="meta_description" maxlength="200"
                        value="<?= e($page['meta_description'] ?? '') ?>"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
            </div>

            <div class="flex items-center gap-4 pt-2 border-t border-gray-100">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_published" value="1"
                        <?= ($page['is_published'] ?? 1) ? 'checked' : '' ?>
                        class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                    <span class="text-sm text-gray-700">Publish (tampil di publik)</span>
                </label>

                <div class="flex items-center gap-2">
                    <label class="text-xs text-gray-500">Urutan:</label>
                    <input type="number" name="sort_order" min="0"
                        value="<?= e($page['sort_order'] ?? 0) ?>"
                        class="w-16 px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit"
                class="flex-1 py-2.5 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700 transition">
                <?= $page ? 'Simpan Perubahan' : 'Buat Halaman' ?>
            </button>
            <a href="/admin/pages"
                class="px-5 py-2.5 border border-gray-300 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                Batal
            </a>
        </div>
    </form>
</div>

<?php $this->endSection() ?>