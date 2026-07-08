<?php $this->layout('layouts.app', ['title' => 'Beranda']) ?>

<?php $this->section('content') ?>

<div class="min-h-screen flex items-center justify-center px-4">
    <div class="text-center max-w-xl">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">
            <?= e(config('app.name')) ?>
        </h1>
        <p class="text-gray-600 mb-8">
            Setup project berhasil. Router, Middleware, CSRF, View Engine, dan Module Auth sudah aktif.
        </p>

        <div class="flex items-center justify-center gap-4">
            <a href="/login" class="px-6 py-3 bg-orange-600 text-white rounded-lg font-medium hover:bg-orange-700 transition">
                Login
            </a>
            <a href="/register" class="px-6 py-3 bg-white text-orange-600 border border-orange-600 rounded-lg font-medium hover:bg-orange-50 transition">
                Daftar
            </a>
        </div>
    </div>
</div>

<?php $this->endSection() ?>