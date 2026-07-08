<?php

declare(strict_types=1);

namespace App\Core\Routing\Middleware;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Routing\MiddlewareInterface;
use App\Modules\Auth\Application\Services\CurrentUserService;
use Closure;

/**
 * CheckRole
 *
 * Dipasang di route dengan parameter role yang diizinkan, contoh:
 *   $router->get('/admin', [...])->middleware('role:super_admin,admin');
 */
class CheckRole implements MiddlewareInterface
{
    private array $allowedRoles;

    public function __construct(string ...$allowedRoles)
    {
        $this->allowedRoles = $allowedRoles;
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! CurrentUserService::hasRole($this->allowedRoles)) {
            if ($request->wantsJson() || $request->isHtmx()) {
                return Response::json(['message' => 'Anda tidak memiliki akses ke halaman ini.'], 403);
            }

            return Response::make('403 - Anda tidak memiliki akses ke halaman ini.', 403);
        }

        return $next($request);
    }
}