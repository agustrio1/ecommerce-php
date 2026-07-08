<?php $this->layout('layouts.admin', ['title' => 'Pengaturan Shipping']) ?>

<?php $this->section('content') ?>

<div class="max-w-2xl">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-start gap-4 mb-6">
            <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
            </div>
            <div>
                <h2 class="font-semibold text-gray-800">Biteship Shipping</h2>
                <p class="text-xs text-gray-400">Konfigurasi API Key dan origin pengiriman</p>
            </div>
        </div>

        <form method="POST" action="/admin/settings/shipping" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">API Key Biteship</label>
                <input type="text" name="biteship_api_key"
                    value="<?= e($biteship['biteship_api_key'] ?? '') ?>"
                    placeholder="biteship_test.xxxxxxx atau biteship_live.xxxxxxx"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 font-mono">
                <p class="text-xs text-gray-400 mt-1">
                    Dapatkan dari <a href="https://dashboard.biteship.com/integrations" target="_blank" class="text-orange-600 hover:underline">dashboard.biteship.com → Integrations</a>.
                    API key test diawali <code class="bg-gray-100 px-1 rounded">biteship_test.</code>, production diawali <code class="bg-gray-100 px-1 rounded">biteship_live.</code>
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Origin Area ID
                    <span class="text-gray-400 text-xs font-normal">(dari Biteship Maps API)</span>
                </label>
                <input type="text" name="biteship_origin_area_id"
                    value="<?= e($biteship['biteship_origin_area_id'] ?? '') ?>"
                    placeholder="IDNP6IDNC148IDND836IDZ12410"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 font-mono">
                <p class="text-xs text-gray-400 mt-1">
                    Isi dari halaman <a href="/admin/settings/store" class="text-orange-600 hover:underline">Informasi Toko</a> — sudah ada live search area di sana.
                    Format: <code class="bg-gray-100 px-1 rounded">IDNP[province]IDNC[city]IDND[district]IDZ[postal]</code>
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Origin Location ID
                    <span class="text-gray-400 text-xs font-normal">(opsional, dari Biteship Locations API)</span>
                </label>
                <input type="text" name="biteship_origin_location_id"
                    value="<?= e($biteship['biteship_origin_location_id'] ?? '') ?>"
                    placeholder="5dad2bf246d52d72b87378f6"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 font-mono">
                <p class="text-xs text-gray-400 mt-1">Isi jika menggunakan Biteship Locations API (multi-gudang).</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kurir yang Aktif</label>
                <input type="text" name="biteship_couriers"
                    value="<?= e($biteship['biteship_couriers'] ?? 'jne,sicepat,anteraja,jnt,tiki') ?>"
                    placeholder="jne,sicepat,anteraja,jnt,tiki"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 font-mono">
                <p class="text-xs text-gray-400 mt-1">
                    Pisahkan dengan koma. Tersedia: jne, sicepat, anteraja, jnt, tiki, wahana, paxel, ninja, grab, gojek, dll.
                    Lihat list lengkap di <a href="https://biteship.com/id/docs/api/couriers/overview" target="_blank" class="text-orange-600 hover:underline">Biteship Couriers API</a>
                </p>
            </div>

            <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 text-xs text-blue-700 space-y-1">
                <p class="font-semibold">Webhook URL Biteship:</p>
                <p><code class="bg-white px-1.5 py-0.5 rounded border border-blue-200"><?= e(rtrim(env('APP_URL', ''), '/')) ?>/webhooks/biteship</code></p>
                <p>Daftarkan URL ini di dashboard Biteship → Settings → Webhooks untuk update status otomatis.</p>
                <p>Events yang perlu diaktifkan: <strong>order.status</strong>, <strong>order.price</strong>, <strong>order.waybill_id</strong></p>
            </div>

            <div class="pt-2">
                <button type="submit"
                    class="px-6 py-2.5 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700 transition">
                    Simpan Konfigurasi Biteship
                </button>
            </div>
        </form>
    </div>
</div>

<?php $this->endSection() ?>a