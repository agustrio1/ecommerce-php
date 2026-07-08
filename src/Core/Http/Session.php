<?php

declare(strict_types=1);

namespace App\Core\Http;

/**
 * Session
 *
 * Wrapper $_SESSION dengan helper aman: regenerate ID (cegah session fixation),
 * flash data (pesan sekali tampil, contoh: notifikasi sukses/error),
 * dan helper auth (login/logout user).
 */
class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'use_strict_mode' => true,
            ]);
        }
    }

    public static function regenerate(bool $deleteOldSession = true): void
    {
        session_regenerate_id($deleteOldSession);
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Ambil flash data sekali pakai, otomatis terhapus setelah diambil.
     */
    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);

        return $value;
    }

    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie('PHPSESSID', '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
    }

    // ===================== AUTH HELPERS =====================

    public static function loginUserId(int $userId): void
    {
        self::regenerate();
        self::put('user_id', $userId);
    }

    public static function userId(): ?int
    {
        $id = self::get('user_id');
        return $id !== null ? (int) $id : null;
    }

    public static function isLoggedIn(): bool
    {
        return self::has('user_id');
    }

    public static function logout(): void
    {
        self::forget('user_id');
        self::regenerate();
    }
}