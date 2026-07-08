<?php

declare(strict_types=1);

namespace App\Core\Routing;

use App\Core\Http\Request;
use App\Core\Http\Response;
use RuntimeException;

/**
 * Router
 *
 * Mendaftarkan route (GET, POST, PUT, PATCH, DELETE), mencocokkan
 * request masuk ke route yang sesuai, menjalankan middleware pipeline,
 * lalu memanggil handler (Controller@method atau Closure).
 *
 * Pemakaian di routes/web.php:
 *
 *   $router->get('/', [HomeController::class, 'index']);
 *   $router->get('/product/{id}', [ProductController::class, 'show']);
 *   $router->post('/cart/add', [CartController::class, 'add'])->middleware('auth');
 *
 *   $router->group(['prefix' => '/admin', 'middleware' => 'auth'], function ($router) {
 *       $router->get('/dashboard', [AdminController::class, 'dashboard']);
 *   });
 */
class Router
{
    /** @var Route[] */
    private array $routes = [];

    /** @var array<string, class-string> nama alias middleware => class */
    private array $middlewareAliases = [];

    private string $groupPrefix = '';
    private array $groupMiddleware = [];
    private array $globalMiddleware = [];

    public function get(string $path, mixed $handler): Route
    {
        return $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, mixed $handler): Route
    {
        return $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, mixed $handler): Route
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    public function patch(string $path, mixed $handler): Route
    {
        return $this->addRoute('PATCH', $path, $handler);
    }

    public function delete(string $path, mixed $handler): Route
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, mixed $handler): Route
    {
        $fullPath = rtrim($this->groupPrefix . $path, '/') ?: '/';

        $route = new Route($method, $fullPath, $handler);
        $route->middleware($this->groupMiddleware);

        $this->routes[] = $route;

        return $route;
    }

    /**
     * Group route dengan prefix path dan/atau middleware bersama.
     *
     * @param array{prefix?: string, middleware?: string|array} $attributes
     */
    public function group(array $attributes, callable $callback): void
    {
        $previousPrefix     = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->groupPrefix     = $previousPrefix . ($attributes['prefix'] ?? '');
        $this->groupMiddleware = array_merge($previousMiddleware, (array) ($attributes['middleware'] ?? []));

        $callback($this);

        $this->groupPrefix     = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }
    
    /**
     * Daftarkan middleware yang otomatis jalan di SEMUA route, tanpa perlu
     * ditempel manual satu-satu. Contoh: CSRF protection.
     */
    public function pushGlobalMiddleware(string $middleware): void
    {
        $this->globalMiddleware[] = $middleware;
    }

    /**
     * Daftarkan alias middleware, contoh: 'auth' => AuthMiddleware::class
     */
    public function aliasMiddleware(string $alias, string $class): void
    {
        $this->middlewareAliases[$alias] = $class;
    }

    /**
     * Cari route yang cocok dengan request, jalankan middleware pipeline,
     * lalu panggil handler-nya. Return Response object.
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path   = $request->path();

        foreach ($this->routes as $route) {
            if ($route->method !== $method) {
                continue;
            }

            if (! $route->matches($path)) {
                continue;
            }

            $params = $route->extractParams($path);
            $request->setRouteParams($params);

            return $this->runWithMiddleware($route, $request);
        }

        return Response::notFound('404 - Halaman tidak ditemukan');
    }

    /**
     * Jalankan middleware pipeline (urut sesuai didaftarkan), lalu handler.
     * Tiap middleware harus punya method handle(Request $request, Closure $next): Response
     */
    private function runWithMiddleware(Route $route, Request $request): Response
    {
        $pipeline = array_reduce(
            array_reverse(array_merge($this->globalMiddleware, $route->middleware)),
            function (\Closure $next, string $middlewareEntry) {
                return function (Request $request) use ($next, $middlewareEntry) {
                    [$middlewareName, $params] = $this->parseMiddlewareEntry($middlewareEntry);

                    $class = $this->middlewareAliases[$middlewareName] ?? $middlewareName;

                    if (! class_exists($class)) {
                        throw new RuntimeException("Middleware [{$middlewareName}] tidak ditemukan.");
                    }

                    $middleware = empty($params) ? new $class() : new $class(...$params);

                    return $middleware->handle($request, $next);
                };
            },
            function (Request $request) use ($route) {
                return $this->callHandler($route->handler, $request);
            }
        );

        return $pipeline($request);
    }

    /**
     * Parse format middleware "nama:param1,param2" menjadi nama dan array parameter.
     * Parameter numerik otomatis dikonversi ke int.
     *
     * Contoh: "throttle:5,1" -> ['throttle', [5, 1]]
     *         "auth"         -> ['auth', []]
     */
    private function parseMiddlewareEntry(string $entry): array
    {
        if (! str_contains($entry, ':')) {
            return [$entry, []];
        }

        [$name, $paramString] = explode(':', $entry, 2);

        $params = array_map(function ($value) {
            return is_numeric($value) ? (int) $value : $value;
        }, explode(',', $paramString));

        return [$name, $params];
    }

    /**
     * Panggil handler: bisa berupa Closure, atau [ControllerClass::class, 'method'].
     */
    private function callHandler(mixed $handler, Request $request): Response
    {
        if ($handler instanceof \Closure) {
            $result = $handler($request);
            return $this->normalizeResponse($result);
        }

        if (is_array($handler) && count($handler) === 2) {
            [$controllerClass, $methodName] = $handler;

            if (! class_exists($controllerClass)) {
                throw new RuntimeException("Controller [{$controllerClass}] tidak ditemukan.");
            }

            $controller = new $controllerClass();

            if (! method_exists($controller, $methodName)) {
                throw new RuntimeException("Method [{$methodName}] tidak ditemukan di [{$controllerClass}].");
            }

            $params = array_values($request->routeParams());
            $result = $controller->{$methodName}($request, ...$params);

            return $this->normalizeResponse($result);
        }

        throw new RuntimeException('Handler route tidak valid.');
    }

    /**
     * Pastikan hasil handler selalu jadi Response object.
     * Controller boleh return string (HTML) atau array (otomatis jadi JSON).
     */
    private function normalizeResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result)) {
            return Response::json($result);
        }

        return Response::make((string) $result);
    }
}