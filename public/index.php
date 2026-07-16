<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Http\Request;
use App\Core\Routing\Router;

// Load .env
$envPath = dirname(__DIR__) . '/.env';
if (file_exists($envPath)) {
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->load();
}

// DEBUG SEMENTARA — hapus setelah ketauan
$debugLog = dirname(__DIR__) . '/storage/logs/debug.log';
$debugMsg = sprintf(
    "[%s] ENV_PATH=%s | EXISTS=%s | DB_DATABASE=%s\n",
    date('Y-m-d H:i:s'),
    $envPath,
    file_exists($envPath) ? 'YA' : 'TIDAK',
    env('DB_DATABASE', 'FALLBACK_ecommerce_php')
);
@file_put_contents($debugLog, $debugMsg, FILE_APPEND);

// Error reporting sesuai APP_DEBUG
if (env('APP_DEBUG', false)) {
    ini_set('display_errors', '1');
    // Tampilkan semua error KECUALI deprecated (curl_close deprecated di PHP 8.5)
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Jakarta'));

session_start();

$router = new Router();

$router->aliasMiddleware('throttle', \App\Core\Routing\Middleware\ThrottleRequests::class);
$router->aliasMiddleware('csrf', \App\Core\Routing\Middleware\VerifyCsrfToken::class);
$router->aliasMiddleware('auth', \App\Core\Routing\Middleware\Authenticate::class);
$router->aliasMiddleware('guest', \App\Core\Routing\Middleware\RedirectIfAuthenticated::class);
$router->aliasMiddleware('role', \App\Core\Routing\Middleware\CheckRole::class);
$router->aliasMiddleware('permission', \App\Core\Routing\Middleware\CheckPermission::class);

$router->pushGlobalMiddleware('csrf');

// Load semua definisi route
$routesPath = dirname(__DIR__) . '/routes/web.php';
if (file_exists($routesPath)) {
    (require $routesPath)($router);
}

$request  = Request::capture();
try {
    $response = $router->dispatch($request);
} catch (\App\Core\Exceptions\NotFoundException $e) {
    $response = \App\Core\Http\Response::notFound($e->getMessage());
} catch (\Throwable $e) {
    error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
        throw $e; // Development: tampilkan error detail
    }

    $response = \App\Core\Http\Response::serverError();
}

$response->send();