<?php $this->layout('layouts.storefront', ['title' => 'Alamat Saya']) ?>

<?php $this->section('content') ?>

<div class="py-4" x-data="{ showForm: false, editingId: null }">
    <div class="flex items-center gap-2 mb-4">
        <a href="/profil" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="font-bold text-gray-900 text-lg flex-1">Alamat Saya</h1>
        <button @click="showForm = !showForm; editingId = null"
            class="px-3 py-1.5 bg-orange-600 text-white text-xs font-medium rounded-lg hover:bg-orange-700 transition">
            + Tambah
        </button>
    </div>

    <?php $flashSuccess = \App\Core\Http\Session::getFlash('success'); ?>
    <?php $flashError   = \App\Core\Http\Session::getFlash('error'); ?>
    <?php if ($flashSuccess): ?>
        <div class="mb-4 p-3 bg-green-50 text-green-700 text-sm rounded-xl border border-green-200"><?= e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="mb-4 p-3 bg-red-50 text-red-700 text-sm rounded-xl border border-red-200"><?= e($flashError) ?></div>
    <?php endif; ?>

    <!-- Form tambah alamat -->
    <div x-show="showForm" x-data="addressFormPage()" class="bg-white rounded-xl border border-gray-200 p-4 mb-4">
        <h2 class="font-semibold text-gray-800 mb-3 text-sm">Tambah Alamat Baru</h2>
        <form method="POST" action="/profil/alamat" class="space-y-3">
            <?= csrf_field() ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Label</label>
                    <select name="label" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="Rumah">Rumah</option>
                        <option value="Kantor">Kantor</option>
                        <option value="Kos/Apartemen">Kos/Apartemen</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Nama Penerima</label>
                    <input type="text" name="recipient_name" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">No. HP</label>
                    <input type="text" name="phone" required placeholder="6281234567890"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Kode Pos</label>
                    <input type="text" name="postal_code" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Alamat Lengkap</label>
                <textarea name="address" rows="2" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"></textarea>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Cari Kecamatan / Kota</label>
                <div class="relative">
                    <input type="text"
                        x-model="areaSearch"
                        @input.debounce.500ms="searchArea()"
                        @focus="showDropdown = areaResults.length > 0"
                        placeholder="Ketik nama kecamatan..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <div x-show="showDropdown && areaResults.length > 0"
                        @click.outside="showDropdown = false"
                        class="absolute top-full left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-lg z-20 max-h-40 overflow-y-auto mt-1">
                        <template x-for="area in areaResults" :key="area.id">
                            <button type="button" @click="selectArea(area)"
                                class="w-full px-3 py-2.5 text-left text-sm hover:bg-orange-50 border-b border-gray-50 last:border-0">
                                <span x-text="area.name"></span>
                            </button>
                        </template>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-1">Area: <span x-text="selectedAreaId || 'belum dipilih'"></span></p>
            </div>

            <input type="hidden" name="area_id" x-model="selectedAreaId">
            <input type="hidden" name="province" x-model="province">
            <input type="hidden" name="city" x-model="city">
            <input type="hidden" name="district" x-model="district">

            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_primary" value="1"
                    class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                <span class="text-xs text-gray-700">Jadikan alamat utama</span>
            </label>

            <button type="submit"
                class="w-full py-2.5 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700 transition">
                Simpan Alamat
            </button>
        </form>
    </div>

    <!-- List alamat -->
    <?php if (empty($addresses)): ?>
        <div class="text-center py-12">
            <p class="text-gray-400 text-sm">Belum ada alamat tersimpan.</p>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($addresses as $address): ?>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="flex items-start justify-between mb-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-gray-800"><?= e($address->recipientName) ?></span>
                            <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full"><?= e($address->label) ?></span>
                            <?php if ($address->isPrimary): ?>
                                <span class="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full">Utama</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mb-1"><?= e($address->phone) ?></p>
                    <p class="text-sm text-gray-600 leading-relaxed mb-3"><?= e($address->fullAddress()) ?></p>

                    <div class="flex items-center gap-3 text-xs">
                        <?php if (! $address->isPrimary): ?>
                            <form method="POST" action="/profil/alamat/<?= $address->id ?>/utama">
                                <?= csrf_field() ?>
                                <button type="submit" class="text-orange-600 hover:underline">Jadikan Utama</button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" action="/profil/alamat/<?= $address->id ?>"
                            onsubmit="return confirm('Hapus alamat ini?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="text-red-600 hover:underline">Hapus</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function addressFormPage() {
    return {
        areaSearch: '',
        areaResults: [],
        showDropdown: false,
        selectedAreaId: '',
        province: '',
        city: '',
        district: '',

        async searchArea() {
            if (this.areaSearch.length < 3) {
                this.areaResults = [];
                return;
            }
            try {
                const res = await fetch('/admin/settings/search-area?input=' + encodeURIComponent(this.areaSearch));
                const data = await res.json();
                this.areaResults = data.areas || [];
                this.showDropdown = this.areaResults.length > 0;
            } catch (e) {
                this.areaResults = [];
            }
        },

        selectArea(area) {
            this.selectedAreaId = area.id;
            this.areaSearch = area.name;
            this.showDropdown = false;
            this.district = area.administrative_division_level_3_name || '';
            this.city = area.administrative_division_level_2_name || '';
            this.province = area.administrative_division_level_1_name || '';
        }
    }
}
</script>

<?php $this->endSection() ?>