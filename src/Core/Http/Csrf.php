<?php

declare(strict_types=1);

namespace App\Core\Http;

/**
 * Csrf
 *
 * Generate dan verifikasi CSRF token untuk proteksi form dari Cross-Site Request Forgery.
 * Token disimpan di session, di-generate sekali per session (bukan per request,
 * supaya tidak invalid kalau user buka banyak tab).
 */
class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        if (! Session::has(self::SESSION_KEY)) {
            Session::put(self::SESSION_KEY, bin2hex(random_bytes(32)));
        }

        return Session::get(self::SESSION_KEY);
    }

    public static function verify(?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        $sessionToken = Session::get(self::SESSION_KEY);

        if ($sessionToken === null) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * Helper untuk dipakai langsung di view: <?= Csrf::field() ?>
     * Render hidden input siap pakai di dalam <form>.
     */
    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');

        return "<input type=\"hidden\" name=\"_csrf_token\" value=\"{$token}\">";
    }

    /**
     * Regenerate token baru (panggil setelah login/logout untuk keamanan ekstra).
     */
    public static function regenerate(): void
    {
        Session::put(self::SESSION_KEY, bin2hex(random_bytes(32)));
    }
}