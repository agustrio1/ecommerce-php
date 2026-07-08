<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Repositories;

interface RoleRepositoryInterface
{
    public function findIdBySlug(string $slug): ?int;

    public function hasPermission(int $roleId, string $permissionSlug): bool;
}