<?php

declare(strict_types=1);

namespace App\Core\Routing\Middleware;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Core\Routing\MiddlewareInterface;
use Closure;

/**
 * Authenticate
 *
 * Memastikan user sudah login sebelum mengakses route yang dilindungi.
 * Jika belum login, redirect ke /login (atau JSON 401 untuk request AJAX/HTMX).
 */
class Authenticate implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Session::isLoggedIn()) {
            if ($request->wantsJson() || $request->isHtmx()) {
                return Response::json(['message' => 'Anda harus login terlebih dahulu.'], 401);
            }

            Session::flash('error', 'Silakan login terlebih dahulu.');

            return Response::redirect('/login');
        }

        return $next($request);
    }
}