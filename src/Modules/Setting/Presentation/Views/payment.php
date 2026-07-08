<?php $this->layout('layouts.admin', ['title' => 'Pengaturan Payment']) ?>

<?php $this->section('content') ?>

<div class="max-w-2xl">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-start gap-4 mb-6">
            <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
            </div>
            <div>
                <h2 class="font-semibold text-gray-800">iPaymu Payment Gateway</h2>
                <p class="text-xs text-gray-400">Konfigurasi VA dan API Key dari dashboard iPaymu</p>
            </div>
        </div>

        <form method="POST" action="/admin/settings/payment" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mode</label>
                <div class="flex gap-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="ipaymu_mode" value="sandbox"
                            <?= ($ipaymu['ipaymu_mode'] ?? 'sandbox') === 'sandbox' ? 'checked' : '' ?>
                            class="text-orange-600 focus:ring-orange-500">
                        <span class="text-sm text-gray-700">Sandbox (Testing)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="ipaymu_mode" value="production"
                            <?= ($ipaymu['ipaymu_mode'] ?? '') === 'production' ? 'checked' : '' ?>
                            class="text-orange-600 focus:ring-orange-500">
                        <span class="text-sm text-gray-700">Production</span>
                    </label>
                </div>
                <p class="text-xs text-gray-400 mt-1">
                    Sandbox: <code class="bg-gray-100 px-1 rounded">https://sandbox.ipaymu.com/api/v2</code><br>
                    Production: <code class="bg-gray-100 px-1 rounded">https://my.ipaymu.com/api/v2</code>
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">VA (Virtual Account)</label>
                <input type="text" name="ipaymu_va"
                    value="<?= e($ipaymu['ipaymu_va'] ?? '') ?>"
                    placeholder="1179000899"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 font-mono">
                <p class="text-xs text-gray-400 mt-1">Dapatkan dari dashboard iPaymu → My Account</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
                <input type="text" name="ipaymu_api_key"
                    value="<?= e($ipaymu['ipaymu_api_key'] ?? '') ?>"
                    placeholder="Your-API-Key"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 font-mono">
                <p class="text-xs text-gray-400 mt-1">Dapatkan dari dashboard iPaymu → API Key</p>
            </div>

            <div class="bg-gray-50 rounded-lg p-4 text-xs text-gray-500 space-y-1.5">
                <p class="font-semibold text-gray-700">URL Callback (otomatis di-set):</p>
                <p>Notify URL: <code class="bg-white px-1.5 py-0.5 rounded border border-gray-200"><?= e(rtrim(env('APP_URL', ''), '/')) ?>/webhooks/ipaymu</code></p>
                <p>Return URL: <code class="bg-white px-1.5 py-0.5 rounded border border-gray-200"><?= e(rtrim(env('APP_URL', ''), '/')) ?>/orders/callback/ipaymu</code></p>
                <p>Cancel URL: <code class="bg-white px-1.5 py-0.5 rounded border border-gray-200"><?= e(rtrim(env('APP_URL', ''), '/')) ?>/checkout</code></p>
                <p class="text-orange-600">Pastikan URL ini bisa diakses publik dari internet (bukan localhost) saat production.</p>
            </div>

            <div class="pt-2">
                <button type="submit"
                    class="px-6 py-2.5 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700 transition">
                    Simpan Konfigurasi iPaymu
                </button>
            </div>
        </form>
    </div>
</div>

<?php $this->endSection() ?>