<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Persistence;

use App\Modules\Auth\Domain\Repositories\RoleRepositoryInterface;
use PDO;

class MysqlRoleRepository implements RoleRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    public function findIdBySlug(string $slug): ?int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM roles WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);

        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    public function hasPermission(int $roleId, string $permissionSlug): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM role_permissions rp
             JOIN permissions p ON p.id = rp.permission_id
             WHERE rp.role_id = :role_id AND p.slug = :slug'
        );
        $stmt->execute(['role_id' => $roleId, 'slug' => $permissionSlug]);

        return (int) $stmt->fetchColumn() > 0;
    }
}