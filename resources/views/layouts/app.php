<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? config('app.name', 'Ecommerce PHP')) ?></title>

    <link rel="stylesheet" href="/assets/css/app.css">
    <meta name="csrf-token" content="<?= e(\App\Core\Http\Csrf::token()) ?>">
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen">

    <?php
    $flashSuccess = \App\Core\Http\Session::getFlash('success');
    $flashError   = \App\Core\Http\Session::getFlash('error');
    ?>

    <?php if ($flashSuccess): ?>
        <div class="fixed top-4 right-4 z-50 bg-green-600 text-white px-4 py-3 rounded-lg shadow-lg" x-data x-init="setTimeout(() => $el.remove(), 4000)">
            <?= e($flashSuccess) ?>
        </div>
    <?php endif; ?>

    <?php if ($flashError): ?>
        <div class="fixed top-4 right-4 z-50 bg-red-600 text-white px-4 py-3 rounded-lg shadow-lg" x-data x-init="setTimeout(() => $el.remove(), 4000)">
            <?= e($flashError) ?>
        </div>
    <?php endif; ?>

    <?= $this->yield('content') ?>

    <script src="/assets/js/app.js"></script>
</body>
</html>