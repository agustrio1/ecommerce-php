<?php $this->layout('layouts.admin', ['title' => $title]) ?>

<?php $this->section('content') ?>

<div class="flex items-center gap-3 mb-5">
    <a href="/admin/banners" class="text-gray-400 hover:text-gray-600">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <h1 class="text-xl font-bold text-gray-900"><?= e($title) ?></h1>
</div>

<div class="max-w-2xl">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <form method="POST"
            action="<?= $banner ? '/admin/banners/' . $banner['id'] : '/admin/banners' ?>"
            enctype="multipart/form-data"
            class="space-y-4">
            <?= csrf_field() ?>
            <?php if ($banner): ?>
                <input type="hidden" name="_method" value="PUT">
            <?php endif; ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Judul Banner <span class="text-red-500">*</span></label>
                    <input type="text" name="title" required
                        value="<?= e($banner['title'] ?? '') ?>"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subtitle</label>
                    <input type="text" name="subtitle"
                        value="<?= e($banner['subtitle'] ?? '') ?>"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teks Tombol</label>
                    <input type="text" name="button_text"
                        value="<?= e($banner['button_text'] ?? '') ?>"
                        placeholder="Belanja Sekarang"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">URL Tombol</label>
                    <input type="text" name="button_url"
                        value="<?= e($banner['button_url'] ?? '') ?>"
                        placeholder="/produk"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Warna Background</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="bg_color"
                            value="<?= e($banner['bg_color'] ?? '#f97316') ?>"
                            class="h-10 w-16 cursor-pointer rounded-lg border border-gray-300">
                        <input type="text" id="bgColorText"
                            value="<?= e($banner['bg_color'] ?? '#f97316') ?>"
                            class="flex-1 px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 font-mono"
                            oninput="document.querySelector('input[type=color][name=bg_color]').value = this.value">
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Digunakan jika tidak ada gambar.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Urutan</label>
                    <input type="number" name="sort_order" min="0"
                        value="<?= e($banner['sort_order'] ?? 0) ?>"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <p class="text-xs text-gray-400 mt-1">Angka kecil tampil lebih dulu.</p>
                </div>
            </div>

            <!-- Upload gambar -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Gambar Banner
                    <span class="text-gray-400 text-xs font-normal">(JPG/PNG/WebP, maks 3MB, rasio 16:6 disarankan)</span>
                </label>

                <?php if ($banner && $banner['image_path']): ?>
                    <div class="mb-2 rounded-lg overflow-hidden border border-gray-200">
                        <img src="/storage/<?= e($banner['image_path']) ?>"
                            class="w-full h-32 object-cover">
                    </div>
                    <p class="text-xs text-gray-400 mb-2">Upload gambar baru untuk mengganti gambar di atas.</p>
                <?php endif; ?>

                <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                    class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100">
            </div>

            <!-- Status aktif -->
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="is_active" value="1"
                    <?= ($banner['is_active'] ?? 1) ? 'checked' : '' ?>
                    class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                <div>
                    <p class="text-sm font-medium text-gray-700">Banner aktif</p>
                    <p class="text-xs text-gray-400">Banner akan ditampilkan di halaman utama storefront</p>
                </div>
            </label>

            <!-- Preview -->
            <div id="bannerPreview"
                class="rounded-xl overflow-hidden h-32 flex items-center justify-center relative transition-colors duration-300"
                style="background-color: <?= e($banner['bg_color'] ?? '#f97316') ?>">
                <div class="text-center text-white z-10 px-6">
                    <p class="text-xs opacity-80 mb-1" id="previewSubtitle"><?= e($banner['subtitle'] ?? 'Subtitle') ?></p>
                    <p class="text-lg font-bold" id="previewTitle"><?= e($banner['title'] ?? 'Judul Banner') ?></p>
                    <span class="inline-block mt-2 px-3 py-1 bg-white/20 text-white text-xs rounded-full" id="previewBtn">
                        <?= e($banner['button_text'] ?? 'Tombol') ?>
                    </span>
                </div>
                <div class="absolute -right-6 -top-6 w-32 h-32 bg-white/10 rounded-full"></div>
            </div>
            <p class="text-xs text-gray-400">Preview banner (tidak termasuk gambar upload)</p>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="flex-1 py-2.5 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700 transition">
                    <?= $banner ? 'Simpan Perubahan' : 'Tambah Banner' ?>
                </button>
                <a href="/admin/banners"
                    class="px-5 py-2.5 border border-gray-300 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Live preview
const titleInput    = document.querySelector('input[name="title"]');
const subtitleInput = document.querySelector('input[name="subtitle"]');
const btnInput      = document.querySelector('input[name="button_text"]');
const colorInput    = document.querySelector('input[type="color"][name="bg_color"]');
const colorText     = document.getElementById('bgColorText');
const preview       = document.getElementById('bannerPreview');

function updatePreview() {
    document.getElementById('previewTitle').textContent    = titleInput.value || 'Judul Banner';
    document.getElementById('previewSubtitle').textContent = subtitleInput.value || 'Subtitle';
    document.getElementById('previewBtn').textContent      = btnInput.value || 'Tombol';
    preview.style.backgroundColor = colorInput.value;
    colorText.value = colorInput.value;
}

titleInput?.addEventListener('input', updatePreview);
subtitleInput?.addEventListener('input', updatePreview);
btnInput?.addEventListener('input', updatePreview);
colorInput?.addEventListener('input', updatePreview);
</script>

<?php $this->endSection() ?>