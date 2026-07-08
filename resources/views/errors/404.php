<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Halaman Tidak Ditemukan</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center px-4">
    <div class="text-center max-w-md">
        <div class="mb-6">
            <svg class="w-24 h-24 text-gray-200 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h1 class="text-6xl font-bold text-gray-200 mb-2">404</h1>
        <h2 class="text-xl font-bold text-gray-800 mb-2">Halaman Tidak Ditemukan</h2>
        <p class="text-sm text-gray-500 mb-8">
            <?= e($message ?? 'Halaman yang kamu cari tidak ada atau sudah dipindahkan.') ?>
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="/"
                class="px-6 py-2.5 bg-orange-600 text-white rounded-xl text-sm font-medium hover:bg-orange-700 transition">
                Ke Halaman Utama
            </a>
            <button onclick="history.back()"
                class="px-6 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-50 transition">
                Kembali
            </button>
        </div>
    </div>
</body>
</html>