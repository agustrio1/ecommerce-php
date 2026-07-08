<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\Services;

use App\Core\Http\Session;
use App\Modules\Auth\Domain\Entities\User;
use App\Modules\Auth\Infrastructure\Persistence\MysqlUserRepository;
use App\Modules\Auth\Infrastructure\Persistence\MysqlRoleRepository;
use PDO;

/**
 * CurrentUserService
 *
 * Helper untuk ambil data user yang sedang login (dari session),
 * termasuk role slug dan cek permission. Di-cache per-request (static)
 * supaya tidak query berulang kali dalam satu request yang sama.
 */
class CurrentUserService
{
    private static ?User $cachedUser = null;
    private static ?string $cachedRoleSlug = null;

    public static function user(): ?User
    {
        $userId = Session::userId();

        if ($userId === null) {
            return null;
        }

        if (self::$cachedUser !== null && self::$cachedUser->id === $userId) {
            return self::$cachedUser;
        }

        $repository = new MysqlUserRepository();
        self::$cachedUser = $repository->findById($userId);

        return self::$cachedUser;
    }

    public static function roleSlug(): ?string
    {
        if (self::$cachedRoleSlug !== null) {
            return self::$cachedRoleSlug;
        }

        $user = self::user();

        if ($user === null) {
            return null;
        }

        $stmt = db()->prepare('SELECT slug FROM roles WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $user->roleId]);
        $slug = $stmt->fetchColumn();

        self::$cachedRoleSlug = $slug !== false ? $slug : null;

        return self::$cachedRoleSlug;
    }

    public static function hasRole(string|array $roles): bool
    {
        $currentRole = self::roleSlug();

        if ($currentRole === null) {
            return false;
        }

        return in_array($currentRole, (array) $roles, true);
    }

    public static function hasPermission(string $permissionSlug): bool
    {
        $user = self::user();

        if ($user === null) {
            return false;
        }

        $repository = new MysqlRoleRepository();

        return $repository->hasPermission($user->roleId, $permissionSlug);
    }

    /**
     * Reset cache (panggil setelah login/logout supaya tidak ada data basi).
     */
    public static function clearCache(): void
    {
        self::$cachedUser = null;
        self::$cachedRoleSlug = null;
    }
}