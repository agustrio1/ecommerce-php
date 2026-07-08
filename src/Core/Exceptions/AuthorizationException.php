<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use RuntimeException;

/**
 * AuthorizationException
 *
 * Dilempar saat user tidak punya permission/role yang dibutuhkan
 * untuk mengakses suatu resource/aksi.
 */
class AuthorizationException extends RuntimeException
{
    public function __construct(string $message = 'Anda tidak memiliki akses untuk melakukan aksi ini.')
    {
        parent::__construct($message, 403);
    }
}