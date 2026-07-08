<?php $this->layout('layouts.admin', ['title' => 'Pengaturan Umum']) ?>

<?php $this->section('content') ?>

<div class="max-w-2xl space-y-6">

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-800 mb-5">Informasi Aplikasi</h2>

        <form method="POST" action="/admin/settings/general" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Toko / Aplikasi</label>
                <input type="text" name="app_name"
                    value="<?= e($settings['app_name'] ?? '') ?>"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tagline</label>
                <input type="text" name="app_tagline"
                    value="<?= e($settings['app_tagline'] ?? '') ?>"
                    placeholder="Toko Online Terpercaya"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>

            <div class="pt-2">
                <button type="submit"
                    class="px-6 py-2.5 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700 transition">
                    Simpan
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-800 mb-5">Media Sosial & Marketplace</h2>

        <form method="POST" action="/admin/settings/general" class="space-y-4">
            <?= csrf_field() ?>
            <!-- Re-send general fields supaya tidak ter-reset -->
            <input type="hidden" name="app_name" value="<?= e($settings['app_name'] ?? '') ?>">
            <input type="hidden" name="app_tagline" value="<?= e($settings['app_tagline'] ?? '') ?>">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php
                $socials = [
                    'social_instagram' => ['label' => 'Instagram', 'placeholder' => 'https://instagram.com/username'],
                    'social_facebook'  => ['label' => 'Facebook', 'placeholder' => 'https://facebook.com/page'],
                    'social_tiktok'    => ['label' => 'TikTok', 'placeholder' => 'https://tiktok.com/@username'],
                    'social_twitter'   => ['label' => 'Twitter / X', 'placeholder' => 'https://twitter.com/username'],
                    'social_youtube'   => ['label' => 'YouTube', 'placeholder' => 'https://youtube.com/channel'],
                    'social_whatsapp'  => ['label' => 'WhatsApp', 'placeholder' => '6281234567890'],
                    'social_shopee'    => ['label' => 'Shopee', 'placeholder' => 'https://shopee.co.id/toko'],
                    'social_tokopedia' => ['label' => 'Tokopedia', 'placeholder' => 'https://tokopedia.com/toko'],
                ];
                foreach ($socials as $key => $meta): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"><?= e($meta['label']) ?></label>
                        <input type="text" name="<?= $key ?>"
                            value="<?= e($social[$key] ?? '') ?>"
                            placeholder="<?= e($meta['placeholder']) ?>"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="pt-2">
                <button type="submit"
                    class="px-6 py-2.5 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700 transition">
                    Simpan Media Sosial
                </button>
            </div>
        </form>
    </div>
</div>

<?php $this->endSection() ?>