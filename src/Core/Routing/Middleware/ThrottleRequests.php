<?php

declare(strict_types=1);

namespace App\Core\Routing\Middleware;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Routing\MiddlewareInterface;
use App\Core\Support\RateLimiter;
use Closure;

/**
 * ThrottleRequests
 *
 * Middleware generik untuk rate limiting berbasis IP. Dipasang per-route
 * dengan parameter "maxAttempts,decayMinutes" lewat alias, contoh:
 *
 *   $router->post('/login', [...])->middleware('throttle:5,1');
 *   // maksimal 5 percobaan per 1 menit, key dibedakan per route + IP
 */
class ThrottleRequests implements MiddlewareInterface
{
    private int $maxAttempts;
    private int $decayMinutes;

    public function __construct(int $maxAttempts = 5, int $decayMinutes = 1)
    {
        $this->maxAttempts  = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $limiter = new RateLimiter();
        $key = 'throttle:' . $request->path() . ':' . $request->ip();

        if ($limiter->tooManyAttempts($key, $this->maxAttempts)) {
            $retryAfter = $limiter->availableInSeconds($key);

            $message = "Terlalu banyak percobaan. Coba lagi dalam {$retryAfter} detik.";

            if ($request->wantsJson() || $request->isHtmx()) {
                return Response::json(['message' => $message], 429)
                    ->withHeader('Retry-After', (string) $retryAfter);
            }

            return Response::make($message, 429)
                ->withHeader('Retry-After', (string) $retryAfter);
        }

        $limiter->hit($key, $this->decayMinutes);

        return $next($request);
    }
}