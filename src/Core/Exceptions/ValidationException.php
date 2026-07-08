<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use RuntimeException;

/**
 * ValidationException
 *
 * Dilempar saat validasi input gagal. Membawa daftar error per field,
 * supaya Controller bisa tangkap dan tampilkan ke user.
 */
class ValidationException extends RuntimeException
{
    /** @var array<string, string> field => pesan error */
    private array $errors;

    public function __construct(array $errors, string $message = 'Validasi gagal.')
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}