<?php $this->layout('layouts.admin', ['title' => $title]) ?>

<?php $this->section('content') ?>

<div class="flex items-center gap-3 mb-5">
    <a href="/admin/flash-sales" class="text-gray-400 hover:text-gray-600">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <h1 class="text-xl font-bold text-gray-900"><?= e($title) ?></h1>
</div>

<div class="max-w-lg">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <form method="POST" action="/admin/flash-sales" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Flash Sale <span class="text-red-500">*</span></label>
                <input type="text" name="name" required
                    placeholder="Harbolnas 12.12"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mulai <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="starts_at" required
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Selesai <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="ends_at" required
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
            </div>

            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" checked
                    class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                <span class="text-sm text-gray-700">Aktifkan flash sale</span>
            </label>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="flex-1 py-2.5 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700 transition">
                    Buat & Tambah Produk
                </button>
            </div>
        </form>
    </div>
</div>

<?php $this->endSection() ?>