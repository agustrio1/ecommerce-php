<?php $this->layout('layouts.storefront', ['title' => 'Ubah Password']) ?>

<?php $this->section('content') ?>

<?php require_once __DIR__ . '/../_brand.php'; ?>
<?php $brand = nexaroBrandTokens(); ?>

<div class="py-4">
    <div class="flex items-center gap-2 mb-4">
        <a href="/profil" class="hover:opacity-70 transition focus:outline-none focus-visible:ring-2 rounded" style="color: <?= e($brand['ink']) ?>; opacity: 0.5;">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="font-bold text-lg" style="color: <?= e($brand['ink']) ?>;">Ubah Password</h1>
    </div>

    <?php $flashError = \App\Core\Http\Session::getFlash('error'); ?>
    <?php if ($flashError): ?>
        <div class="mb-4 p-3 text-sm rounded-xl border" style="background-color: #FBEAE6; color: <?= e($brand['urgent']) ?>; border-color: <?= e($brand['urgent']) ?>;"><?= e($flashError) ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-xl border p-5" style="border-color: <?= e($brand['line']) ?>;">
        <form method="POST" action="/profil/ubah-password" class="space-y-4">
            <?= csrf_field() ?>

            <div x-data="{ show: false }">
                <label class="block text-sm font-medium mb-1" style="color: <?= e($brand['ink']) ?>;">Password Saat Ini</label>
                <div class="relative">
                    <input :type="show ? 'text' : 'password'" name="current_password" required
                        class="w-full px-3 py-2.5 pr-10 border rounded-lg text-sm focus:outline-none focus:ring-2" style="border-color: <?= e($brand['line']) ?>;">
                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 opacity-40 focus:outline-none focus-visible:ring-2 rounded" style="color: <?= e($brand['ink']) ?>;">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div x-data="{ show: false }">
                <label class="block text-sm font-medium mb-1" style="color: <?= e($brand['ink']) ?>;">Password Baru</label>
                <div class="relative">
                    <input :type="show ? 'text' : 'password'" name="new_password" required
                        class="w-full px-3 py-2.5 pr-10 border rounded-lg text-sm focus:outline-none focus:ring-2" style="border-color: <?= e($brand['line']) ?>;">
                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 opacity-40 focus:outline-none focus-visible:ring-2 rounded" style="color: <?= e($brand['ink']) ?>;">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div x-data="{ show: false }">
    <label class="block text-sm font-medium mb-1" style="color: <?= e($brand['ink']) ?>;">Password Saat Ini</label>
    <div class="relative">
        <input :type="show ? 'text' : 'password'" name="current_password" required autocomplete="current-password"
            class="w-full px-3 py-2.5 pr-10 border rounded-lg text-sm focus:outline-none focus:ring-2" style="border-color: <?= e($brand['line']) ?>;">
        <button type="button" @click.prevent="show = !show"
            class="absolute inset-y-0 right-0 flex items-center pr-3 opacity-40 hover:opacity-70 transition focus:outline-none focus-visible:ring-2 rounded"
            style="color: <?= e($brand['ink']) ?>;"
            :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'">
            <!-- Mata terbuka (password tersembunyi) -->
            <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <!-- Mata dicoret (password terlihat) -->
            <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
            </svg>
        </button>
    </div>
</div>

            <button type="submit"
                class="w-full py-2.5 text-white rounded-lg text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                style="background-color: <?= e($brand['clay']) ?>;">
                Ubah Password
            </button>
        </form>
    </div>
</div>

<?php $this->endSection() ?>