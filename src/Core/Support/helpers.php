<?php

if (! function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return rtrim(dirname(__DIR__, 3), '/') . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (! function_exists('config_path')) {
    function config_path(string $path = ''): string
    {
        return base_path('config') . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (! function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)'   => true,
            'false', '(false)' => false,
            'null', '(null)'   => null,
            'empty', '(empty)' => '',
            default             => $value,
        };
    }
}

if (! function_exists('config')) {
    function config(?string $key = null, mixed $default = null): mixed
    {
        static $items = [];

        if ($key === null) {
            return $items;
        }

        $segments = explode('.', $key);
        $file     = array_shift($segments);

        if (! array_key_exists($file, $items)) {
            $path = config_path($file . '.php');
            $items[$file] = file_exists($path) ? require $path : [];
        }

        $value = $items[$file];

        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }

        return $value;
    }
}

if (! function_exists('db')) {
    function db(?string $connection = null): \PDO
    {
        return \App\Core\Database\ConnectionManager::getInstance()->connection($connection);
    }
    
    if (! function_exists('view')) {
    /**
     * Render file view dan kembalikan hasilnya sebagai string.
     * Mendukung layout/section lewat App\Core\View\View.
     */
    function view(string $name, array $data = []): string
    {
        return \App\Core\View\View::render($name, $data);
    }
}

if (! function_exists('old')) {
    /**
     * Ambil input lama dari session flash (dipakai setelah validasi gagal,
     * supaya form tidak perlu diisi ulang dari nol).
     */
    function old(string $key, mixed $default = ''): mixed
    {
        $oldData = \App\Core\Http\Session::getFlash('old', []);

        return $oldData[$key] ?? $default;
    }
}

if (! function_exists('errors')) {
    /**
     * Ambil error validasi dari session flash.
     */
    function errors(): array
    {
        return \App\Core\Http\Session::getFlash('errors', []);
    }
}

if (! function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return \App\Core\Http\Csrf::field();
    }
}

if (! function_exists('e')) {
    /**
     * Escape string untuk output HTML aman (cegah XSS).
     * Selalu pakai ini saat menampilkan data dari user/database ke HTML.
     */
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}
}