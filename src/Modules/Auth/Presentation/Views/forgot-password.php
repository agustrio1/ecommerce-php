<?php $this->layout('layouts.app', ['title' => 'Lupa Password']) ?>

<?php $this->section('content') ?>

<div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-1">Lupa Password</h1>
        <p class="text-gray-500 text-sm mb-6">Masukkan email Anda, kami akan kirim link reset password</p>

        <form method="POST" action="/forgot-password" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" required
                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 transition"
                    style="border-color: #D1D5DB; --tw-ring-color: #A8522E;"
                    @focus="$el.style.borderColor = '#A8522E'"
                    @blur="$el.style.borderColor = '#D1D5DB'">
            </div>

            <button type="submit"
                class="w-full py-2.5 text-white rounded-lg font-medium transition"
                style="background-color: #A8522E;"
                onmouseover="this.style.backgroundColor='#8E3F22'"
                onmouseout="this.style.backgroundColor='#A8522E'">
                Kirim Link Reset
            </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-6">
            Ingat password Anda?
            <a href="/login" class="font-medium hover:underline" style="color: #A8522E;">Kembali ke login</a>
        </p>
    </div>
</div>

<?php $this->endSection() ?>
