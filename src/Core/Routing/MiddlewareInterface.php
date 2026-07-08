<?php

declare(strict_types=1);

namespace App\Core\Routing;

use App\Core\Http\Request;
use App\Core\Http\Response;
use Closure;

/**
 * MiddlewareInterface
 *
 * Kontrak yang harus diimplementasikan tiap Middleware.
 * Contoh implementasi ada di Step berikutnya (AuthMiddleware, dll).
 */
interface MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response;
}