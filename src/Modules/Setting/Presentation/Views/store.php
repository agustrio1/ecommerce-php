<?php $this->layout('layouts.admin', ['title' => 'Informasi Toko']) ?>

<?php $this->section('content') ?>

<div class="max-w-2xl" x-data="storeSettings()">

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-800 mb-1">Informasi Toko</h2>
        <p class="text-xs text-gray-400 mb-5">Digunakan sebagai alamat origin pengiriman di Biteship</p>

        <form method="POST" action="/admin/settings/store" class="space-y-4">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Toko</label>
                    <input type="text" name="store_name"
                        value="<?= e($settings['store_name'] ?? '') ?>"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Toko</label>
                    <input type="email" name="store_email"
                        value="<?= e($settings['store_email'] ?? '') ?>"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">No. HP / WhatsApp</label>
                    <input type="text" name="store_phone"
                        value="<?= e($settings['store_phone'] ?? '') ?>"
                        placeholder="6281234567890"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kode Pos</label>
                    <input type="text" name="store_postal_code"
                        value="<?= e($settings['store_postal_code'] ?? '') ?>"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Lengkap</label>
                <textarea name="store_address" rows="2"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"><?= e($settings['store_address'] ?? '') ?></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kecamatan</label>
                    <input type="text" name="store_district"
                        value="<?= e($settings['store_district'] ?? '') ?>"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kota / Kabupaten</label>
                    <input type="text" name="store_city"
                        value="<?= e($settings['store_city'] ?? '') ?>"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Provinsi</label>
                    <input type="text" name="store_province"
                        value="<?= e($settings['store_province'] ?? '') ?>"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Biteship Area ID
                    <span class="text-gray-400 text-xs font-normal">(akurasi tinggi, wajib diisi)</span>
                </label>

                <div class="relative">
                    <input
                        type="text"
                        x-ref="areaSearch"
                        x-model="areaQuery"
                        @input.debounce.600ms="searchArea()"
                        @focus="if (results.length > 0) showDropdown = true"
                        @keydown.escape="showDropdown = false"
                        placeholder="Ketik nama kecamatan atau kota... (min. 3 huruf)"
                        autocomplete="off"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 pr-10">

                    <div x-show="loading" class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
                        <svg class="animate-spin w-4 h-4 text-orange-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                        </svg>
                    </div>

                    <button type="button"
                        x-show="areaId && !loading"
                        @click="clearArea()"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>

                    <div
                        x-show="showDropdown && results.length > 0"
                        @click.outside="showDropdown = false"
                        class="absolute top-full left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-lg z-20 max-h-56 overflow-y-auto mt-1">
                        <template x-for="area in results" :key="area.id">
                            <button
                                type="button"
                                @click="selectArea(area)"
                                class="w-full px-4 py-3 text-left hover:bg-orange-50 hover:text-orange-700 transition-colors border-b border-gray-50 last:border-0">
                                <span class="block text-sm font-medium" x-text="area.name"></span>
                                <span class="block text-xs text-gray-400 font-mono mt-0.5" x-text="area.id"></span>
                            </button>
                        </template>
                    </div>

                    <div
                        x-show="!loading && searchDone && results.length === 0 && !errorMsg && areaQuery.length >= 3"
                        class="absolute top-full left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-lg z-20 mt-1 px-4 py-3 text-sm text-gray-400">
                        Tidak ada area ditemukan untuk "<span x-text="areaQuery"></span>"
                    </div>

                    <div
                        x-show="errorMsg"
                        class="absolute top-full left-0 right-0 bg-red-50 border border-red-200 rounded-lg shadow-lg z-20 mt-1 px-4 py-3 text-sm text-red-600"
                        x-text="errorMsg">
                    </div>
                </div>

                <input type="hidden" name="store_area_id" x-model="areaId">
                <input type="hidden" name="store_area_name" x-model="areaQuery">

                <div class="mt-1.5 flex items-center gap-2" x-show="areaId">
                    <svg class="w-3.5 h-3.5 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-xs text-gray-500">Area ID: <code class="font-mono text-gray-700 bg-gray-100 px-1 rounded" x-text="areaId"></code></span>
                </div>
                <p x-show="!areaId" class="text-xs text-gray-400 mt-1">
                    Belum dipilih — ketik nama kecamatan/kota untuk mencari via Biteship Maps API
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Latitude <span class="text-gray-400 text-xs">(opsional, untuk instant courier)</span>
                    </label>
                    <input type="text" name="store_latitude"
                        value="<?= e($settings['store_latitude'] ?? '') ?>"
                        placeholder="-6.2088"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Longitude</label>
                    <input type="text" name="store_longitude"
                        value="<?= e($settings['store_longitude'] ?? '') ?>"
                        placeholder="106.8456"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
            </div>

            <div class="pt-2">
                <button type="submit"
                    class="px-6 py-2.5 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700 transition">
                    Simpan Informasi Toko
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function storeSettings() {
    return {
        areaQuery:    '<?= e($settings['store_area_name'] ?? '') ?>',
        areaId:       '<?= e($settings['store_area_id'] ?? '') ?>',
        results:      [],
        showDropdown: false,
        loading:      false,
        searchDone:   false,
        errorMsg:     '',

        async searchArea() {
            if (this.areaQuery.length < 3) {
                this.results      = [];
                this.showDropdown = false;
                this.searchDone   = false;
                this.errorMsg     = '';
                return;
            }

            this.loading      = true;
            this.errorMsg     = '';
            this.showDropdown = false;
            this.searchDone   = false;

            try {
                const res  = await fetch('/admin/settings/search-area?input=' + encodeURIComponent(this.areaQuery), {
                    headers: { 'Accept': 'application/json' }
                });

                const text = await res.text();

                let data;
                try {
                    data = JSON.parse(text);
                } catch (parseErr) {
                    this.errorMsg = 'Server error: ' + text.substring(0, 200);
                    return;
                }

                if (data.success === false) {
                    this.errorMsg     = data.error || 'Terjadi kesalahan';
                    this.results      = [];
                    this.showDropdown = false;
                } else {
                    this.results      = data.areas || [];
                    this.showDropdown = this.results.length > 0;
                    this.errorMsg     = '';
                }
            } catch (e) {
                this.errorMsg = 'Network error: ' + e.message;
            } finally {
                this.loading    = false;
                this.searchDone = true;
            }
        },

        selectArea(area) {
            this.areaId       = area.id;
            this.areaQuery    = area.name;
            this.showDropdown = false;
            this.results      = [];
            this.errorMsg     = '';
        },

        clearArea() {
            this.areaId       = '';
            this.areaQuery    = '';
            this.results      = [];
            this.showDropdown = false;
            this.searchDone   = false;
            this.errorMsg     = '';
            this.$refs.areaSearch.focus();
        },
    }
}
</script>

<?php $this->endSection() ?>