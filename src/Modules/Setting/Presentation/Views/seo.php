<?php $this->layout('layouts.admin', ['title' => 'Pengaturan SEO']) ?>

<?php $this->section('content') ?>

<div class="max-w-2xl space-y-6">

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-800 mb-5">Meta Tags & SEO</h2>

        <form method="POST" action="/admin/settings/seo" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Meta Title <span class="text-gray-400 text-xs">(maks 60 karakter)</span>
                </label>
                <input type="text" name="seo_title" maxlength="60"
                    value="<?= e($seo['seo_title'] ?? '') ?>"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Meta Description <span class="text-gray-400 text-xs">(maks 160 karakter)</span>
                </label>
                <textarea name="seo_description" maxlength="160" rows="2"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"><?= e($seo['seo_description'] ?? '') ?></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Keywords</label>
                <input type="text" name="seo_keywords"
                    value="<?= e($seo['seo_keywords'] ?? '') ?>"
                    placeholder="toko online, belanja, produk, ..."
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Robots</label>
                    <select name="seo_robots"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="index, follow" <?= ($seo['seo_robots'] ?? '') === 'index, follow' ? 'selected' : '' ?>>index, follow</option>
                        <option value="noindex, nofollow" <?= ($seo['seo_robots'] ?? '') === 'noindex, nofollow' ? 'selected' : '' ?>>noindex, nofollow</option>
                        <option value="index, nofollow" <?= ($seo['seo_robots'] ?? '') === 'index, nofollow' ? 'selected' : '' ?>>index, nofollow</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">OG Image URL</label>
                    <input type="text" name="seo_og_image"
                        value="<?= e($seo['seo_og_image'] ?? '') ?>"
                        placeholder="https://..."
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Google Analytics ID</label>
                    <input type="text" name="seo_google_analytics"
                        value="<?= e($seo['seo_google_analytics'] ?? '') ?>"
                        placeholder="G-XXXXXXXXXX"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Google Site Verification</label>
                    <input type="text" name="seo_google_site_verification"
                        value="<?= e($seo['seo_google_site_verification'] ?? '') ?>"
                        placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 font-mono">
                </div>
            </div>

            <!-- Preview JSON-LD -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    JSON-LD Organization Schema
                    <span class="text-gray-400 text-xs font-normal">(otomatis di-generate dari data toko)</span>
                </label>
                <div class="bg-gray-900 text-green-400 text-xs font-mono p-4 rounded-lg overflow-x-auto max-h-48">
                    <pre><?= e(json_encode(json_decode($seo['seo_jsonld_organization'] ?? '{}'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
                </div>
                <p class="text-xs text-gray-400 mt-1">
                    JSON-LD ini otomatis ter-update saat kamu menyimpan pengaturan ini,
                    menggunakan data dari <a href="/admin/settings/store" class="text-orange-600 hover:underline">Informasi Toko</a> dan Social Media.
                </p>
            </div>

            <div class="pt-2">
                <button type="submit"
                    class="px-6 py-2.5 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700 transition">
                    Simpan SEO
                </button>
            </div>
        </form>
    </div>
</div>

<?php $this->endSection() ?>