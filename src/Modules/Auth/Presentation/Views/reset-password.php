<?php $this->layout('layouts.app', ['title' => 'Reset Password']) ?>

<?php $this->section('content') ?>

<div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-1">Reset Password</h1>
        <p class="text-gray-500 text-sm mb-6">Masukkan password baru Anda</p>

        <?php $formErrors = errors(); ?>
        <?php if (! empty($formErrors['general'])): ?>
            <div class="mb-4 p-3 bg-red-50 text-red-700 text-sm rounded-lg border border-red-200">
                <?= e($formErrors['general']) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($token)): ?>
            <div class="p-3 bg-amber-50 text-amber-700 text-sm rounded-lg border border-amber-200">
                Token reset password tidak ditemukan. Silakan minta link baru lewat halaman
                <a href="/forgot-password" class="underline font-medium">lupa password</a>.
            </div>
        <?php else: ?>
            <form method="POST" action="/reset-password" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">

                <div x-data="{ show: false }">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                    <div class="relative">
                        <input :type="show ? 'text' : 'password'" name="password" required
                            class="w-full px-3 py-2 pr-10 border rounded-lg focus:outline-none focus:ring-2 transition"
                            style="border-color: #D1D5DB; --tw-ring-color: #A8522E;"
                            @focus="$el.style.borderColor = '#A8522E'"
                            @blur="$el.style.borderColor = '#D1D5DB'">
                        <button type="button" @click="show = !show"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                            <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div x-data="{ show: false }">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru</label>
                    <div class="relative">
                        <input :type="show ? 'text' : 'password'" name="password_confirmation" required
                            class="w-full px-3 py-2 pr-10 border rounded-lg focus:outline-none focus:ring-2 transition"
                            style="border-color: #D1D5DB; --tw-ring-color: #A8522E;"
                            @focus="$el.style.borderColor = '#A8522E'"
                            @blur="$el.style.borderColor = '#D1D5DB'">
                        <button type="button" @click="show = !show"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                            <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit"
                    class="w-full py-2.5 text-white rounded-lg font-medium transition"
                    style="background-color: #A8522E;"
                    onmouseover="this.style.backgroundColor='#8E3F22'"
                    onmouseout="this.style.backgroundColor='#A8522E'">
                    Reset Password
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php $this->endSection() ?>
