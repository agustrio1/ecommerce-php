<?php

declare(strict_types=1);

namespace App\Core\Routing\Middleware;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Core\Routing\MiddlewareInterface;
use Closure;

/**
 * RedirectIfAuthenticated
 *
 * Kebalikan dari Authenticate — dipasang di halaman login/register,
 * supaya user yang SUDAH login tidak bisa akses halaman itu lagi
 * (otomatis dilempar ke dashboard).
 */
class RedirectIfAuthenticated implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Session::isLoggedIn()) {
            return Response::redirect('/dashboard');
        }

        return $next($request);
    }
}