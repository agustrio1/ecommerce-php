<?php

declare(strict_types=1);

namespace App\Core\Routing\Middleware;

use App\Core\Http\Csrf;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Routing\MiddlewareInterface;
use Closure;

/**
 * VerifyCsrfToken
 *
 * Cek token CSRF untuk semua request yang mengubah data (POST, PUT, PATCH, DELETE).
 * Token diambil dari input '_csrf_token' (form biasa) atau header 'X-CSRF-Token' (HTMX/AJAX).
 *
 * PENGECUALIAN: webhook dari pihak ketiga (iPaymu, Biteship) TIDAK BISA
 * mengirim CSRF token — mereka bukan browser yang render form/HTMX kita,
 * jadi tidak pernah tahu token itu ada. Middleware ini terdaftar sebagai
 * GLOBAL middleware (bootstrap: $router->pushGlobalMiddleware('csrf')),
 * yang berarti SEMUA route kena termasuk webhook, sehingga tanpa
 * pengecualian ini setiap webhook masuk selalu ditolak 419 sebelum
 * sempat diproses controller sama sekali. Keamanan endpoint webhook
 * diamankan dengan cara lain (verifikasi status via API server-to-server
 * ke iPaymu, dan idempotency check di WebhookLogger), bukan lewat CSRF.
 */
class VerifyCsrfToken implements MiddlewareInterface
{
    private array $methodsToCheck = ['POST', 'PUT', 'PATCH', 'DELETE'];

    private array $exceptPaths = [
        '/webhooks/ipaymu',
        '/webhooks/biteship',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $path = rtrim($request->path(), '/') ?: '/';

        if (in_array($path, $this->exceptPaths, true)) {
            return $next($request);
        }

        if (in_array($request->method(), $this->methodsToCheck, true)) {
            $token = $request->input('_csrf_token') ?? $request->header('X-CSRF-Token');

            if (! Csrf::verify($token)) {
                if ($request->wantsJson() || $request->isHtmx()) {
                    return Response::json(['message' => 'CSRF token tidak valid atau kadaluarsa.'], 419);
                }

                return Response::make('419 - CSRF token tidak valid atau kadaluarsa. Silakan refresh halaman dan coba lagi.', 419);
            }
        }

        return $next($request);
    }
}