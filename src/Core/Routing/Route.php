<?php

declare(strict_types=1);

namespace App\Core\Routing;

/**
 * Route
 *
 * Representasi satu definisi route: method, path pattern, handler, middleware.
 */
class Route
{
    public string $method;
    public string $path;
    public mixed $handler;
    public array $middleware = [];
    public string $pattern;
    public array $paramNames = [];

    public function __construct(string $method, string $path, mixed $handler)
    {
        $this->method  = strtoupper($method);
        $this->path    = $path;
        $this->handler = $handler;

        [$this->pattern, $this->paramNames] = $this->compile($path);
    }

    /**
     * Ubah path seperti /product/{id}/review/{reviewId}
     * menjadi regex pattern dan daftar nama parameter.
     */
    private function compile(string $path): array
    {
        $paramNames = [];

        $pattern = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', function ($matches) use (&$paramNames) {
            $paramNames[] = $matches[1];

            // {path} khusus: match semua karakter termasuk slash
            // berguna untuk route seperti /storage/{path}
            if ($matches[1] === 'path') {
                return '(.+)';
            }

            return '([^/]+)';
        }, $path);

        $trimmed = rtrim($pattern, '/');
        $trimmed = $trimmed === '' ? '/' : $trimmed;

        $pattern = '#^' . $trimmed . '$#';

        return [$pattern, $paramNames];
    }

    public function matches(string $requestPath): bool
    {
        return (bool) preg_match($this->pattern, $requestPath);
    }

    /**
     * Ambil array asosiatif parameter dari URL aktual.
     * Contoh: /product/15 -> ['id' => '15']
     */
    public function extractParams(string $requestPath): array
    {
        preg_match($this->pattern, $requestPath, $matches);
        array_shift($matches);

        return array_combine($this->paramNames, $matches) ?: [];
    }

    public function middleware(string|array $middleware): static
    {
        $this->middleware = array_merge($this->middleware, (array) $middleware);
        return $this;
    }

    public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }
}