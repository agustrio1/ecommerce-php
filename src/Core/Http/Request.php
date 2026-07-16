<?php

declare(strict_types=1);

namespace App\Core\Http;

/**
 * Request
 *
 * Membungkus data request HTTP ($_GET, $_POST, $_SERVER, $_FILES, dll)
 * jadi satu object yang gampang dipakai di Controller.
 */
class Request
{
    private array $query;
    private array $body;
    private array $server;
    private array $files;
    private array $cookies;
    private array $routeParams = [];
    private ?array $jsonBody = null;

    public function __construct(
        array $query = [],
        array $body = [],
        array $server = [],
        array $files = [],
        array $cookies = []
    ) {
        $this->query   = $query;
        $this->body    = $body;
        $this->server  = $server;
        $this->files   = $files;
        $this->cookies = $cookies;
    }

    public static function capture(): self
    {
        return new self($_GET, $_POST, $_SERVER, $_FILES, $_COOKIE);
    }

    public function method(): string
    {
        $method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');

        // Dukung method spoofing lewat hidden input _method (PUT/PATCH/DELETE dari form HTML)
        if ($method === 'POST' && isset($this->body['_method'])) {
            $method = strtoupper($this->body['_method']);
        }

        return $method;
    }

    public function path(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        return rtrim($path, '/') ?: '/';
    }

    public function isAjax(): bool
    {
        return strtolower($this->server['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }

    public function isHtmx(): bool
    {
        return isset($this->server['HTTP_HX_REQUEST']);
    }

    public function wantsJson(): bool
    {
        $accept = $this->server['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'application/json') || $this->isAjax();
    }

    /**
     * Ambil satu field dari input.
     *
     * PENTING: ini HANYA membaca dari $_POST (form-urlencoded/multipart)
     * dan $_GET (query string). Untuk request dengan body JSON mentah
     * (Content-Type: application/json), PHP TIDAK PERNAH mengisi $_POST,
     * jadi method ini akan selalu return $default untuk body JSON.
     * Gunakan json() untuk membaca body JSON, atau allInput() kalau mau
     * otomatis mendukung keduanya.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    /**
     * Sama seperti all(), tapi otomatis ikut menggabungkan body JSON kalau
     * Content-Type request ini application/json. Berguna untuk endpoint
     * yang mau menerima baik form-urlencoded maupun JSON tanpa controller
     * harus parsing manual sendiri-sendiri.
     */
    public function allInput(): array
    {
        if ($this->isJsonRequest()) {
            return array_merge($this->query, $this->json());
        }

        return $this->all();
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    public function has(string $key): bool
    {
        return isset($this->body[$key]) || isset($this->query[$key]);
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Decode body request sebagai JSON. Hasilnya di-cache di $jsonBody
     * supaya php://input cuma dibaca sekali per request meski method ini
     * dipanggil berkali-kali dari tempat berbeda.
     */
    public function json(): array
    {
        if ($this->jsonBody === null) {
            $raw = file_get_contents('php://input');
            $this->jsonBody = json_decode($raw, true) ?: [];
        }

        return $this->jsonBody;
    }

    /**
     * Cek apakah Content-Type request ini application/json.
     */
    public function isJsonRequest(): bool
    {
        return str_contains($this->header('Content-Type', ''), 'application/json');
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Ambil header request.
     *
     * FIX: Content-Type dan Content-Length adalah PENGECUALIAN di
     * spek CGI/PHP — PHP menyimpannya di $_SERVER['CONTENT_TYPE'] dan
     * $_SERVER['CONTENT_LENGTH'] TANPA prefix 'HTTP_', beda dari semua
     * header lain yang selalu diberi prefix 'HTTP_' oleh PHP.
     *
     * Sebelumnya method ini selalu menambahkan prefix HTTP_ untuk semua
     * key tanpa pengecualian, jadi header('Content-Type') selalu mencari
     * $_SERVER['HTTP_CONTENT_TYPE'] yang TIDAK PERNAH ADA — hasilnya
     * selalu balik ke $default meski Content-Type sungguhan sudah
     * dikirim client dengan benar. Ini yang bikin semua pengecekan
     * "apakah body ini JSON" berbasis header('Content-Type', ...) di
     * seluruh project selalu gagal (contoh: CartController::parseBody()),
     * sehingga body JSON tidak pernah ke-parse dan field-nya jatuh ke 0/null.
     */
    public function header(string $key, mixed $default = null): mixed
    {
        $normalized = strtoupper(str_replace('-', '_', $key));

        if (in_array($normalized, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
            return $this->server[$normalized] ?? $default;
        }

        return $this->server['HTTP_' . $normalized] ?? $default;
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function route(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function routeParams(): array
    {
        return $this->routeParams;
    }
}