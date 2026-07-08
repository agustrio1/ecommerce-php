<?php

declare(strict_types=1);

namespace App\Core\Support;

/**
 * Hash
 *
 * Wrapper password_hash/password_verify bawaan PHP.
 * Pakai algoritma bcrypt/argon2 (PASSWORD_DEFAULT) yang aman untuk production.
 */
class Hash
{
    public static function make(string $value): string
    {
        return password_hash($value, PASSWORD_DEFAULT);
    }

    public static function check(string $value, string $hashedValue): bool
    {
        if ($hashedValue === '') {
            return false;
        }

        return password_verify($value, $hashedValue);
    }

    /**
     * Cek apakah hash perlu di-rehash (mis. cost factor bcrypt berubah).
     * Berguna dipanggil setelah login sukses untuk upgrade hash lama secara transparan.
     */
    public static function needsRehash(string $hashedValue): bool
    {
        return password_needs_rehash($hashedValue, PASSWORD_DEFAULT);
    }
}