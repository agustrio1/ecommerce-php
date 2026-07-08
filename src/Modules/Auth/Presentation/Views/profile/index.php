<?php $this->layout('layouts.storefront', ['title' => 'Profil Saya']) ?>

<?php $this->section('content') ?>

<div class="py-4">
    <h1 class="font-bold text-gray-900 text-lg mb-4">Profil Saya</h1>

    <?php $flashSuccess = \App\Core\Http\Session::getFlash('success'); ?>
    <?php $flashError   = \App\Core\Http\Session::getFlash('error'); ?>
    <?php if ($flashSuccess): ?>
        <div class="mb-4 p-3 bg-green-50 text-green-700 text-sm rounded-xl border border-green-200"><?= e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="mb-4 p-3 bg-red-50 text-red-700 text-sm rounded-xl border border-red-200"><?= e($flashError) ?></div>
    <?php endif; ?>

    <!-- Avatar & info ringkas -->
    <div class="bg-white rounded-xl border border-gray-200 p-5 mb-4 flex items-center gap-4">
        <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center shrink-0">
            <span class="text-orange-700 font-bold text-2xl"><?= strtoupper(substr($user->name, 0, 1)) ?></span>
        </div>
        <div class="min-w-0">
            <p class="font-bold text-gray-900 truncate"><?= e($user->name) ?></p>
            <p class="text-sm text-gray-500 truncate"><?= e($user->email) ?></p>
            <?php if ($user->isEmailVerified()): ?>
                <span class="inline-flex items-center gap-1 text-xs text-green-600 mt-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Email terverifikasi
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Menu navigasi -->
    <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100 mb-4">
        <a href="/orders" class="flex items-center justify-between px-4 py-3.5 hover:bg-gray-50 transition">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <span class="text-sm text-gray-700">Pesanan Saya</span>
            </div>
            <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        <a href="/profil/alamat" class="flex items-center justify-between px-4 py-3.5 hover:bg-gray-50 transition">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="text-sm text-gray-700">Alamat Saya</span>
            </div>
            <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        <a href="/profil/ubah-password" class="flex items-center justify-between px-4 py-3.5 hover:bg-gray-50 transition">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <span class="text-sm text-gray-700">Ubah Password</span>
            </div>
            <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>

    <!-- Edit profil -->
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="font-semibold text-gray-800 mb-4 text-sm">Edit Informasi</h2>
        <form method="POST" action="/profil" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" name="name" value="<?= e($user->name) ?>" required
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" value="<?= e($user->email) ?>" disabled
                    class="w-full px-3 py-2.5 border border-gray-200 bg-gray-50 rounded-lg text-sm text-gray-500">
                <p class="text-xs text-gray-400 mt-1">Email tidak bisa diubah</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">No. HP</label>
                <input type="text" name="phone" value="<?= e($user->phone ?? '') ?>"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>
            <button type="submit"
                class="w-full py-2.5 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700 transition">
                Simpan Perubahan
            </button>
        </form>
    </div>

    <!-- Logout -->
    <form method="POST" action="/logout" class="mt-4">
        <?= csrf_field() ?>
        <button type="submit"
            class="w-full py-2.5 bg-red-50 text-red-600 rounded-xl text-sm font-medium hover:bg-red-100 transition">
            Keluar
        </button>
    </form>
</div>

<?php $this->endSection() ?>