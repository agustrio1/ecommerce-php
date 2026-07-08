<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\DTOs;

/**
 * AuthResult
 *
 * DTO hasil operasi auth (register/login), supaya Controller tidak
 * perlu tau detail internal Service, cukup baca object ini.
 */
class AuthResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly ?array $user = null,
    ) {
    }

    public static function ok(string $message, ?array $user = null): self
    {
        return new self(true, $message, $user);
    }

    public static function fail(string $message): self
    {
        return new self(false, $message);
    }
}