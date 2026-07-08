<?php $this->layout('layouts.storefront', ['title' => 'Checkout']) ?>

<?php $this->section('content') ?>

<?php
if (! function_exists('js_attr')) {
    function js_attr($value): string
    {
        return htmlspecialchars(json_encode($value), ENT_QUOTES, 'UTF-8');
    }
}
?>

<div class="py-4" x-data="checkoutPage()">

    <h1 class="font-bold text-gray-900 text-lg mb-4">Checkout</h1>

    <?php $flashError = \App\Core\Http\Session::getFlash('error'); ?>
    <?php if ($flashError): ?>
        <div class="mb-4 p-3 bg-red-50 text-red-700 text-sm rounded-xl border border-red-200">
            <?= e($flashError) ?>
        </div>
    <?php endif; ?>

    <form id="checkoutForm" method="POST" action="/checkout/submit" class="space-y-4">
        <?= csrf_field() ?>

        <!-- RINGKASAN PRODUK -->
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <h2 class="font-semibold text-gray-800 mb-3 text-sm">Ringkasan Belanja</h2>
            <div class="space-y-3">
                <?php foreach ($items as $item): ?>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-gray-100 rounded-lg overflow-hidden shrink-0">
                            <?php if ($item->productImage): ?>
                                <img src="/storage/<?= e($item->productImage) ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate"><?= e($item->productName) ?></p>
                            <?php if ($item->variantLabel !== 'Default'): ?>
                                <p class="text-xs text-gray-400"><?= e($item->variantLabel) ?></p>
                            <?php endif; ?>
                            <p class="text-xs text-gray-500"><?= $item->quantity ?>x Rp <?= number_format($item->price, 0, ',', '.') ?></p>
                        </div>
                        <p class="text-sm font-bold text-gray-900 shrink-0">
                            Rp <?= number_format($item->subtotal(), 0, ',', '.') ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ALAMAT PENGIRIMAN -->
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <h2 class="font-semibold text-gray-800 mb-3 text-sm">Alamat Pengiriman</h2>

            <?php if (! empty($addresses)): ?>
                <div class="space-y-3 mb-3">
                    <?php foreach ($addresses as $address): ?>
                        <div x-data="addressEditForm(
                            <?= (int) $address->id ?>,
                            {
                                label:          <?= js_attr($address->label) ?>,
                                recipient_name: <?= js_attr($address->recipientName) ?>,
                                phone:          <?= js_attr($address->phone) ?>,
                                address:        <?= js_attr($address->address) ?>,
                                province:       <?= js_attr($address->province) ?>,
                                city:           <?= js_attr($address->city) ?>,
                                district:       <?= js_attr($address->district) ?>,
                                postal_code:    <?= js_attr($address->postalCode) ?>,
                                area_id:        <?= js_attr($address->areaId) ?>,
                                latitude:       <?= js_attr($address->latitude) ?>,
                                longitude:      <?= js_attr($address->longitude) ?>,
                                is_primary:     <?= $address->isPrimary ? 'true' : 'false' ?>
                            }
                        )">
                            <label class="flex items-start gap-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-orange-400 transition has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50">
                                <input type="radio"
                                    name="address_id"
                                    value="<?= (int) $address->id ?>"
                                    <?= $address->isPrimary ? 'checked' : '' ?>
                                    @change="
                                        $dispatch('address-selected', { id: <?= (int) $address->id ?> });
                                        $root.dispatchEvent(new CustomEvent('reset-rates'));
                                    "
                                    class="mt-0.5 text-orange-600 focus:ring-orange-500">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-0.5 flex-wrap">
                                        <span class="text-sm font-semibold text-gray-800">
                                            <?php if ($address->recipientName): ?>
                                                <?= e($address->recipientName) ?>
                                            <?php else: ?>
                                                <span class="text-gray-400 italic">Nama belum diisi</span>
                                            <?php endif; ?>
                                        </span>
                                        <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full"><?= e($address->label) ?></span>
                                        <?php if ($address->isPrimary): ?>
                                            <span class="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full">Utama</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($address->phone): ?>
                                        <p class="text-xs text-gray-500"><?= e($address->phone) ?></p>
                                    <?php endif; ?>
                                    <?php if ($address->address): ?>
                                        <p class="text-xs text-gray-600 leading-relaxed mt-0.5"><?= e($address->fullAddress()) ?></p>
                                    <?php endif; ?>
                                    <?php if (! $address->areaId): ?>
                                        <p class="text-xs text-amber-600 mt-1 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                                            </svg>
                                            Area ID belum diset — ongkir tidak bisa dihitung
                                        </p>
                                    <?php else: ?>
                                        <p class="text-xs text-green-600 mt-1 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            Area ID tersedia
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </label>

                            <button type="button"
                                @click="showEdit = !showEdit"
                                class="mt-1 ml-1 text-xs text-orange-600 hover:underline">
                                <span x-show="!showEdit" class="inline-flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 6.75L18 9.75"/>
                                    </svg>
                                    Edit alamat ini
                                </span>
                                <span x-show="showEdit">— Tutup form edit</span>
                            </button>

                            <div x-show="showEdit" x-cloak class="mt-2 border border-orange-200 rounded-xl p-4 bg-orange-50/40 space-y-3">
                                <p class="text-xs font-semibold text-orange-700 mb-1 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 6.75L18 9.75"/>
                                    </svg>
                                    Edit Alamat
                                </p>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Label</label>
                                        <select x-model="editData.label"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">
                                            <option value="Rumah">Rumah</option>
                                            <option value="Kantor">Kantor</option>
                                            <option value="Kos/Apartemen">Kos/Apartemen</option>
                                            <option value="Lainnya">Lainnya</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Nama Penerima <span class="text-red-500">*</span></label>
                                        <input type="text" x-model="editData.recipient_name"
                                            placeholder="Nama penerima paket"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">No. HP <span class="text-red-500">*</span></label>
                                        <input type="text" x-model="editData.phone"
                                            placeholder="628xxxxxxxxxx"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Kode Pos</label>
                                        <input type="text" x-model="editData.postal_code"
                                            placeholder="12345"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Alamat Lengkap <span class="text-red-500">*</span></label>
                                    <textarea x-model="editData.address" rows="2"
                                        placeholder="Jl. Contoh No. 123, RT 01/RW 02"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white"></textarea>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">
                                        Kecamatan / Kota <span class="text-red-500">*</span>
                                        <span class="text-gray-400 font-normal">(wajib untuk kalkulasi ongkir)</span>
                                    </label>
                                    <div class="relative">
                                        <input type="text"
                                            x-model="areaSearch"
                                            @input.debounce.500ms="searchArea()"
                                            @focus="showDrop = areaResults.length > 0"
                                            @keydown.escape="showDrop = false"
                                            placeholder="Ketik nama kecamatan... (min. 3 huruf)"
                                            autocomplete="off"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white pr-8">
                                        <div x-show="areaLoading" class="absolute right-2 top-1/2 -translate-y-1/2">
                                            <svg class="animate-spin w-4 h-4 text-orange-500" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                                            </svg>
                                        </div>
                                        <div x-show="showDrop && areaResults.length > 0"
                                            @click.outside="showDrop = false"
                                            class="absolute top-full left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-lg z-30 max-h-48 overflow-y-auto mt-1">
                                            <template x-for="area in areaResults" :key="area.id">
                                                <button type="button" @click="selectArea(area)"
                                                    class="w-full px-3 py-2.5 text-left hover:bg-orange-50 border-b border-gray-50 last:border-0">
                                                    <span class="block text-sm text-gray-800" x-text="area.name"></span>
                                                    <span class="block text-xs text-gray-400 font-mono" x-text="area.id"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="mt-1 flex items-center gap-1.5" x-show="editData.area_id">
                                        <svg class="w-3.5 h-3.5 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-xs text-gray-600">Area terpilih: <code class="font-mono text-gray-700 bg-white px-1 rounded text-xs" x-text="editData.area_id"></code></span>
                                    </div>
                                    <p x-show="!editData.area_id" class="text-xs text-amber-600 mt-1 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                                        </svg>
                                        Belum dipilih — ongkir tidak bisa dihitung
                                    </p>
                                </div>

                                <label class="flex items-center gap-2">
                                    <input type="checkbox" x-model="editData.is_primary"
                                        class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                                    <span class="text-xs text-gray-700">Jadikan alamat utama</span>
                                </label>

                                <div x-show="editError" class="p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-600" x-text="editError"></div>

                                <button type="button" @click="saveEdit()" :disabled="editSaving"
                                    class="w-full py-2.5 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700 transition disabled:opacity-50">
                                    <span x-show="!editSaving" class="inline-flex items-center justify-center gap-1.5">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13.5l3 3 3-3m-3-6v9m9 4.5H3a1.5 1.5 0 01-1.5-1.5V4.5A1.5 1.5 0 013 3h12.379a1.5 1.5 0 011.06.44l3.122 3.12a1.5 1.5 0 01.439 1.061V19.5a1.5 1.5 0 01-1.5 1.5z"/>
                                        </svg>
                                        Simpan Perubahan
                                    </span>
                                    <span x-show="editSaving">Menyimpan...</span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div x-data="addressNewForm()">
                <button type="button" @click="showForm = !showForm"
                    class="text-sm text-orange-600 hover:underline font-medium">
                    <span x-show="!showForm">+ Tambah Alamat Baru</span>
                    <span x-show="showForm">— Tutup Form</span>
                </button>

                <div x-show="showForm" x-cloak class="mt-3 border border-gray-200 rounded-xl p-4 space-y-3">
                    <p class="text-xs font-semibold text-gray-700">Alamat Baru</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Label Alamat</label>
                            <select x-model="formData.label"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                                <option value="Rumah">Rumah</option>
                                <option value="Kantor">Kantor</option>
                                <option value="Kos/Apartemen">Kos/Apartemen</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Nama Penerima <span class="text-red-500">*</span></label>
                            <input type="text" x-model="formData.recipient_name" placeholder="Nama penerima paket"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">No. HP <span class="text-red-500">*</span></label>
                            <input type="text" x-model="formData.phone" placeholder="628xxxxxxxxxx"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Kode Pos</label>
                            <input type="text" x-model="formData.postal_code" placeholder="12345"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Alamat Lengkap <span class="text-red-500">*</span></label>
                        <textarea x-model="formData.address" rows="2" placeholder="Jl. Contoh No. 123, RT 01/RW 02"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            Kecamatan / Kota <span class="text-red-500">*</span>
                            <span class="text-gray-400 font-normal">(wajib untuk kalkulasi ongkir)</span>
                        </label>
                        <div class="relative">
                            <input type="text"
                                x-model="areaSearch"
                                @input.debounce.500ms="searchArea()"
                                @focus="showAreaDropdown = areaResults.length > 0"
                                @keydown.escape="showAreaDropdown = false"
                                placeholder="Ketik nama kecamatan... (min. 3 huruf)"
                                autocomplete="off"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 pr-8">
                            <div x-show="areaLoading" class="absolute right-2 top-1/2 -translate-y-1/2">
                                <svg class="animate-spin w-4 h-4 text-orange-500" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                                </svg>
                            </div>
                            <div x-show="showAreaDropdown && areaResults.length > 0"
                                @click.outside="showAreaDropdown = false"
                                class="absolute top-full left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-lg z-30 max-h-48 overflow-y-auto mt-1">
                                <template x-for="area in areaResults" :key="area.id">
                                    <button type="button" @click="selectArea(area)"
                                        class="w-full px-3 py-2.5 text-left hover:bg-orange-50 border-b border-gray-50 last:border-0">
                                        <span class="block text-sm text-gray-800" x-text="area.name"></span>
                                        <span class="block text-xs text-gray-400 font-mono" x-text="area.id"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div class="mt-1 flex items-center gap-1.5" x-show="formData.area_id">
                            <svg class="w-3.5 h-3.5 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-xs text-gray-600">Area terpilih: <code class="font-mono text-gray-700 bg-gray-100 px-1 rounded" x-text="formData.area_id"></code></span>
                        </div>
                        <p x-show="!formData.area_id" class="text-xs text-gray-400 mt-1">Belum dipilih</p>
                    </div>

                    <label class="flex items-center gap-2">
                        <input type="checkbox" x-model="formData.is_primary"
                            class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                        <span class="text-xs text-gray-700">Jadikan alamat utama</span>
                    </label>

                    <div x-show="error" class="p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-600" x-text="error"></div>

                    <button type="button" @click="saveAddress()" :disabled="saving"
                        class="w-full py-2.5 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700 transition disabled:opacity-50">
                        <span x-show="!saving">Simpan Alamat</span>
                        <span x-show="saving">Menyimpan...</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- PILIH KURIR -->
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <h2 class="font-semibold text-gray-800 mb-3 text-sm">Pilih Kurir</h2>

            <button type="button"
                @click="checkRates()"
                :disabled="!selectedAddressId || loadingRates"
                class="w-full py-2.5 border border-orange-600 text-orange-600 rounded-lg text-sm font-medium hover:bg-orange-50 transition disabled:opacity-40 disabled:cursor-not-allowed mb-3">
                <span x-show="!loadingRates">Cek Ongkir</span>
                <span x-show="loadingRates">Menghitung ongkir...</span>
            </button>

            <div id="shippingRates">
                <p class="text-xs text-gray-400 text-center">Pilih alamat lalu klik "Cek Ongkir"</p>
            </div>

            <input type="hidden" name="courier_company" x-model="selectedCourier.company">
            <input type="hidden" name="courier_type" x-model="selectedCourier.type">
            <input type="hidden" name="courier_service_name" x-model="selectedCourier.name">
            <input type="hidden" name="shipping_cost" x-model="selectedCourier.cost">
        </div>

        <!-- CATATAN -->
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <label class="block text-sm font-semibold text-gray-800 mb-2">Catatan (opsional)</label>
            <textarea name="notes" rows="2"
                placeholder="Catatan untuk penjual..."
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"></textarea>
        </div>

        <!-- TOTAL & SUBMIT -->
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="space-y-2 mb-4">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Subtotal Produk</span>
                    <span class="font-medium">Rp <?= number_format($subtotal, 0, ',', '.') ?></span>
                </div>
                <div class="flex justify-between text-sm" x-show="selectedCourier.cost > 0">
                    <span class="text-gray-500">Ongkir (<span x-text="selectedCourier.name"></span>)</span>
                    <span class="font-medium" x-text="'Rp ' + Number(selectedCourier.cost).toLocaleString('id-ID')"></span>
                </div>
                <div class="flex justify-between text-sm" x-show="selectedCourier.cost === 0">
                    <span class="text-gray-500">Ongkir</span>
                    <span class="text-gray-400">Pilih kurir dulu</span>
                </div>
                <div class="border-t pt-2 flex justify-between">
                    <span class="font-bold text-gray-900">Total</span>
                    <span class="font-bold text-orange-600 text-lg"
                        x-text="'Rp ' + Number(<?= (int) $subtotal ?> + (selectedCourier.cost || 0)).toLocaleString('id-ID')">
                    </span>
                </div>
            </div>

            <!-- Info pembayaran -->
            <div class="mb-3 p-3 bg-blue-50 border border-blue-100 rounded-xl flex gap-2.5 items-start">
                <svg class="w-4 h-4 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-xs text-blue-700">
                    Setelah checkout, kamu akan diarahkan ke halaman pembayaran iPaymu untuk memilih metode bayar (VA bank, QRIS, dan lainnya).
                </p>
            </div>

            <button type="submit"
                :disabled="!selectedAddressId || !selectedCourier.company || submitting"
                class="w-full py-3 bg-orange-600 text-white rounded-xl font-bold text-sm hover:bg-orange-700 transition disabled:opacity-40 disabled:cursor-not-allowed">
                <span x-show="!submitting">Lanjutkan ke Pembayaran</span>
                <span x-show="submitting">Memproses...</span>
            </button>
            <p class="text-xs text-gray-400 text-center mt-2">
                Dengan menekan tombol di atas, kamu menyetujui syarat &amp; ketentuan toko.
            </p>
        </div>
    </form>
</div>

<script>
function checkoutPage() {
    return {
        selectedAddressId: <?php
            $defaultAddressId = 0;
            if (! empty($addresses)) {
                foreach ($addresses as $addr) {
                    if ($addr->isPrimary) { $defaultAddressId = $addr->id; break; }
                }
                if (! $defaultAddressId) $defaultAddressId = $addresses[0]->id;
            }
            echo (int) $defaultAddressId;
        ?>,
        loadingRates: false,
        submitting:   false,
        selectedCourier: { company: '', type: '', name: '', cost: 0 },

        init() {
            const checkedRadio = document.querySelector('input[name="address_id"]:checked');
            if (checkedRadio) this.selectedAddressId = parseInt(checkedRadio.value);

            this.$el.addEventListener('reset-rates', () => this.resetRates());

            window.addEventListener('address-selected', (e) => {
                this.selectedAddressId = e.detail.id;
                this.resetRates();
            });

            document.getElementById('checkoutForm').addEventListener('submit', () => {
                this.submitting = true;
            });
        },

        resetRates() {
            document.getElementById('shippingRates').innerHTML =
                '<p class="text-xs text-gray-400 text-center">Pilih alamat lalu klik "Cek Ongkir"</p>';
            this.selectedCourier = { company: '', type: '', name: '', cost: 0 };
        },

        async checkRates() {
            if (! this.selectedAddressId) { alert('Pilih alamat dulu'); return; }

            this.loadingRates = true;
            document.getElementById('shippingRates').innerHTML =
                '<p class="text-xs text-gray-400 text-center py-3">Menghitung ongkir...</p>';

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch('/checkout/rates', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'text/html',
                        'X-CSRF-Token': csrfToken,
                        'HX-Request': 'true',
                    },
                    body: JSON.stringify({ address_id: this.selectedAddressId }),
                });

                const ratesEl = document.getElementById('shippingRates');
                ratesEl.innerHTML = await res.text();

                if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                    window.Alpine.initTree(ratesEl);
                }

                setTimeout(() => {
                    const firstRadio = ratesEl.querySelector('input[name="_courier_radio"][value="0"]');
                    if (firstRadio) {
                        firstRadio.checked = true;
                        firstRadio.dispatchEvent(new Event('change'));
                        firstRadio.dispatchEvent(new Event('click'));
                    }
                }, 50);
            } catch (e) {
                document.getElementById('shippingRates').innerHTML =
                    '<p class="text-red-600 text-xs p-3 bg-red-50 rounded-lg">Gagal mengambil data ongkir: ' + e.message + '</p>';
            } finally {
                this.loadingRates = false;
            }
        },

        selectCourier(company, type, name, cost) {
            this.selectedCourier = { company, type, name, cost: parseInt(cost) || 0 };
        }
    }
}

function addressEditForm(id, initial) {
    return {
        addressId: id, showEdit: false, editData: { ...initial },
        areaSearch: '', areaResults: [], showDrop: false,
        areaLoading: false, editSaving: false, editError: '',

        async searchArea() {
            if (this.areaSearch.length < 3) { this.areaResults = []; this.showDrop = false; return; }
            this.areaLoading = true;
            try {
                const data = await (await fetch('/api/search-area?input=' + encodeURIComponent(this.areaSearch))).json();
                this.areaResults = data.areas || [];
                this.showDrop    = this.areaResults.length > 0;
            } catch (e) { this.areaResults = []; }
            finally { this.areaLoading = false; }
        },

        selectArea(area) {
            this.editData.area_id  = area.id;
            this.areaSearch        = area.name;
            this.showDrop          = false;
            this.areaResults       = [];
            this.editData.district = area.administrative_division_level_3_name || '';
            this.editData.city     = area.administrative_division_level_2_name || '';
            this.editData.province = area.administrative_division_level_1_name || '';
            if (area.postal_code && ! this.editData.postal_code)
                this.editData.postal_code = String(area.postal_code);
        },

        async saveEdit() {
            this.editError = '';
            if (! this.editData.recipient_name?.trim()) { this.editError = 'Nama penerima wajib diisi.'; return; }
            if (! this.editData.phone?.trim())          { this.editError = 'Nomor HP wajib diisi.'; return; }
            if (! this.editData.address?.trim())        { this.editError = 'Alamat lengkap wajib diisi.'; return; }
            if (! this.editData.area_id)                { this.editError = 'Pilih kecamatan/kota untuk kalkulasi ongkir.'; return; }
            this.editSaving = true;
            try {
                const res  = await fetch('/checkout/address/update', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' },
                    body: JSON.stringify({ id: this.addressId, ...this.editData, is_primary: this.editData.is_primary ? 1 : 0 }),
                });
                const data = await res.json();
                if (data.success) window.location.reload();
                else this.editError = data.message || 'Gagal menyimpan alamat.';
            } catch (e) { this.editError = 'Terjadi kesalahan: ' + e.message; }
            finally { this.editSaving = false; }
        }
    }
}

function addressNewForm() {
    return {
        showForm: <?= empty($addresses) ? 'true' : 'false' ?>,
        formData: { label: 'Rumah', recipient_name: '', phone: '', address: '', postal_code: '', area_id: '', province: '', city: '', district: '', is_primary: false, latitude: null, longitude: null },
        areaSearch: '', areaResults: [], showAreaDropdown: false,
        areaLoading: false, saving: false, error: '',

        async searchArea() {
            if (this.areaSearch.length < 3) { this.areaResults = []; this.showAreaDropdown = false; return; }
            this.areaLoading = true;
            try {
                const data = await (await fetch('/api/search-area?input=' + encodeURIComponent(this.areaSearch))).json();
                this.areaResults      = data.areas || [];
                this.showAreaDropdown = this.areaResults.length > 0;
            } catch (e) { this.areaResults = []; }
            finally { this.areaLoading = false; }
        },

        selectArea(area) {
            this.formData.area_id  = area.id;
            this.areaSearch        = area.name;
            this.showAreaDropdown  = false;
            this.areaResults       = [];
            this.formData.district = area.administrative_division_level_3_name || '';
            this.formData.city     = area.administrative_division_level_2_name || '';
            this.formData.province = area.administrative_division_level_1_name || '';
            if (area.postal_code && ! this.formData.postal_code)
                this.formData.postal_code = String(area.postal_code);
        },

        async saveAddress() {
            this.error = '';
            if (! this.formData.recipient_name.trim()) { this.error = 'Nama penerima wajib diisi.'; return; }
            if (! this.formData.phone.trim())          { this.error = 'Nomor HP wajib diisi.'; return; }
            if (! this.formData.address.trim())        { this.error = 'Alamat lengkap wajib diisi.'; return; }
            if (! this.formData.area_id)               { this.error = 'Pilih area kecamatan/kota untuk kalkulasi ongkir.'; return; }
            this.saving = true;
            try {
                const res  = await fetch('/checkout/address', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' },
                    body: JSON.stringify({ ...this.formData, is_primary: this.formData.is_primary ? 1 : 0 }),
                });
                const data = await res.json();
                if (data.success) window.location.reload();
                else this.error = data.message || 'Gagal menyimpan alamat.';
            } catch (e) { this.error = 'Terjadi kesalahan: ' + e.message; }
            finally { this.saving = false; }
        }
    }
}
</script>

<?php $this->endSection() ?>
