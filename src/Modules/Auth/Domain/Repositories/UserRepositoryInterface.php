<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Repositories;

use App\Modules\Auth\Domain\Entities\User;

/**
 * UserRepositoryInterface
 *
 * Kontrak akses data User. Implementasi konkret ada di Infrastructure/Persistence
 * (pola Repository, supaya Service tidak bergantung langsung ke PDO/SQL).
 */
interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function emailExists(string $email): bool;

    public function countAll(): int;

    public function create(array $data): User;

    public function updatePassword(int $userId, string $hashedPassword): void;

    public function markEmailAsVerified(int $userId): void;

    public function updateProfile(int $userId, array $data): void;
}