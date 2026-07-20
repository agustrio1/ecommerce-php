<?php $this->layout('layouts.storefront', ['title' => 'Alamat Saya']) ?>

<?php $this->section('content') ?>

<?php require_once __DIR__ . '/../_brand.php'; ?>
<?php $brand = nexaroBrandTokens(); ?>

<div class="py-4" x-data="{ showForm: false, editingId: null }">
    <div class="flex items-center gap-2 mb-4">
        <a href="/profil" class="hover:opacity-70 transition focus:outline-none focus-visible:ring-2 rounded" style="color: <?= e($brand['ink']) ?>; opacity: 0.5;">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="font-bold text-lg flex-1" style="color: <?= e($brand['ink']) ?>;">Alamat Saya</h1>
        <button @click="showForm = !showForm; editingId = null"
            class="px-3 py-1.5 text-white text-xs font-medium rounded-lg transition focus:outline-none focus-visible:ring-2"
            style="background-color: <?= e($brand['clay']) ?>;">
            + Tambah
        </button>
    </div>

    <?php $flashSuccess = \App\Core\Http\Session::getFlash('success'); ?>
    <?php $flashError   = \App\Core\Http\Session::getFlash('error'); ?>
    <?php if ($flashSuccess): ?>
        <div class="mb-4 p-3 text-sm rounded-xl border" style="background-color: #EEF0EA; color: <?= e($brand['moss']) ?>; border-color: <?= e($brand['moss']) ?>;"><?= e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="mb-4 p-3 text-sm rounded-xl border" style="background-color: #FBEAE6; color: <?= e($brand['urgent']) ?>; border-color: <?= e($brand['urgent']) ?>;"><?= e($flashError) ?></div>
    <?php endif; ?>

    <!-- Form tambah alamat -->
    <div x-show="showForm" x-data="addressFormPage()" class="bg-white rounded-xl border p-4 mb-4" style="border-color: <?= e($brand['line']) ?>;">
        <p class="text-[11px] font-semibold uppercase tracking-[0.15em] mb-1" style="color: <?= e($brand['moss']) ?>;">Baru</p>
        <h2 class="font-semibold mb-3 text-sm" style="color: <?= e($brand['ink']) ?>;">Tambah Alamat Baru</h2>
        <form method="POST" action="/profil/alamat" class="space-y-3">
            <?= csrf_field() ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: <?= e($brand['ink']) ?>;">Label</label>
                    <select name="label" class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2" style="border-color: <?= e($brand['line']) ?>;">
                        <option value="Rumah">Rumah</option>
                        <option value="Kantor">Kantor</option>
                        <option value="Kos/Apartemen">Kos/Apartemen</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: <?= e($brand['ink']) ?>;">Nama Penerima</label>
                    <input type="text" name="recipient_name" required
                        class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2" style="border-color: <?= e($brand['line']) ?>;">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: <?= e($brand['ink']) ?>;">No. HP</label>
                    <input type="text" name="phone" required placeholder="6281234567890"
                        class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2" style="border-color: <?= e($brand['line']) ?>;">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: <?= e($brand['ink']) ?>;">Kode Pos</label>
                    <input type="text" name="postal_code" required
                        class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2" style="border-color: <?= e($brand['line']) ?>;">
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium mb-1" style="color: <?= e($brand['ink']) ?>;">Alamat Lengkap</label>
                <textarea name="address" rows="2" required
                    class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2" style="border-color: <?= e($brand['line']) ?>;"></textarea>
            </div>

            <div>
                <label class="block text-xs font-medium mb-1" style="color: <?= e($brand['ink']) ?>;">Cari Kecamatan / Kota</label>
                <div class="relative">
                    <input type="text"
                        x-model="areaSearch"
                        @input.debounce.500ms="searchArea()"
                        @focus="showDropdown = areaResults.length > 0"
                        placeholder="Ketik nama kecamatan..."
                        class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2" style="border-color: <?= e($brand['line']) ?>;">
                    <div x-show="showDropdown && areaResults.length > 0"
                        @click.outside="showDropdown = false"
                        class="absolute top-full left-0 right-0 bg-white border rounded-lg shadow-lg z-20 max-h-40 overflow-y-auto mt-1" style="border-color: <?= e($brand['line']) ?>;">
                        <template x-for="area in areaResults" :key="area.id">
                            <button type="button" @click="selectArea(area)"
                                class="w-full px-3 py-2.5 text-left text-sm hover:bg-gray-50 border-b last:border-0" style="border-color: <?= e($brand['line']) ?>;">
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
                <input type="checkbox" name="is_primary" value="1" style="accent-color: <?= e($brand['clay']) ?>;">
                <span class="text-xs" style="color: <?= e($brand['ink']) ?>;">Jadikan alamat utama</span>
            </label>

            <button type="submit"
                class="w-full py-2.5 text-white rounded-lg text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                style="background-color: <?= e($brand['clay']) ?>;">
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
                <div class="bg-white rounded-xl border p-4" style="border-color: <?= e($brand['line']) ?>;">
                    <div class="flex items-start justify-between mb-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold" style="color: <?= e($brand['ink']) ?>;"><?= e($address->recipientName) ?></span>
                            <span class="text-xs px-2 py-0.5 rounded-full" style="background-color: <?= e($brand['stone']) ?>; color: <?= e($brand['ink']) ?>;"><?= e($address->label) ?></span>
                            <?php if ($address->isPrimary): ?>
                                <span class="text-xs px-2 py-0.5 rounded-full text-white" style="background-color: <?= e($brand['clay']) ?>;">Utama</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mb-1"><?= e($address->phone) ?></p>
                    <p class="text-sm text-gray-600 leading-relaxed mb-3"><?= e($address->fullAddress()) ?></p>

                    <div class="flex items-center gap-3 text-xs">
                        <?php if (! $address->isPrimary): ?>
                            <form method="POST" action="/profil/alamat/<?= $address->id ?>/utama">
                                <?= csrf_field() ?>
                                <button type="submit" class="hover:underline" style="color: <?= e($brand['clay']) ?>;">Jadikan Utama</button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" action="/profil/alamat/<?= $address->id ?>"
                            onsubmit="return confirm('Hapus alamat ini?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="hover:underline" style="color: <?= e($brand['urgent']) ?>;">Hapus</button>
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