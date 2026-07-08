<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use RuntimeException;

/**
 * NotFoundException
 *
 * Dilempar saat resource (mis. produk, order) yang dicari tidak ditemukan di database.
 */
class NotFoundException extends RuntimeException
{
    public function __construct(string $message = 'Data tidak ditemukan.')
    {
        parent::__construct($message, 404);
    }
}