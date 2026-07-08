<?php

declare(strict_types=1);

namespace App\Core\Http;

/**
 * Response
 *
 * Membungkus output HTTP: status code, header, dan body.
 * Dipakai Controller untuk return hasil (HTML, JSON, redirect, dll).
 */
class Response
{
    private string $content;
    private int $statusCode;
    private array $headers;

    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content    = $content;
        $this->statusCode = $statusCode;
        $this->headers    = $headers;
    }

    public static function make(string $content = '', int $statusCode = 200, array $headers = []): self
    {
        return new self($content, $statusCode, $headers);
    }

    public static function json(mixed $data, int $statusCode = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json; charset=utf-8';

        return new self(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $statusCode,
            $headers
        );
    }

    public static function redirect(string $url, int $statusCode = 302): self
    {
        return new self('', $statusCode, ['Location' => $url]);
    }

    public static function notFound(string $message = 'Halaman tidak ditemukan.'): self
    {
        $body = view('errors.404', ['message' => $message]);
        return new self($body, 404);
    }

    public static function serverError(string $message = 'Terjadi kesalahan server.'): self
    {
        $body = view('errors.500', ['message' => $message]);
        return new self($body, 500);
    }

    public function withHeader(string $key, string $value): static
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function withStatus(int $code): static
    {
        $this->statusCode = $code;
        return $this;
    }

    public function send(): void
    {
        if (! headers_sent()) {
            http_response_code($this->statusCode);

            foreach ($this->headers as $key => $value) {
                header("{$key}: {$value}");
            }
        }

        echo $this->content;
    }
    
    
}