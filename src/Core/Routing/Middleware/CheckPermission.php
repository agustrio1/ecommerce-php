<?php

declare(strict_types=1);

namespace App\Core\Routing\Middleware;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Routing\MiddlewareInterface;
use App\Modules\Auth\Application\Services\CurrentUserService;
use Closure;

/**
 * CheckPermission
 *
 * Dipasang di route dengan parameter slug permission spesifik, contoh:
 *   $router->post('/products', [...])->middleware('permission:products.create');
 */
class CheckPermission implements MiddlewareInterface
{
    private string $permissionSlug;

    public function __construct(string $permissionSlug)
    {
        $this->permissionSlug = $permissionSlug;
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! CurrentUserService::hasPermission($this->permissionSlug)) {
            if ($request->wantsJson() || $request->isHtmx()) {
                return Response::json(['message' => 'Anda tidak memiliki izin untuk aksi ini.'], 403);
            }

            return Response::make('403 - Anda tidak memiliki izin untuk aksi ini.', 403);
        }

        return $next($request);
    }
}