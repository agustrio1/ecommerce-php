<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Entities;

/**
 * User Entity
 *
 * Representasi domain object User, lepas dari detail database.
 * Tidak boleh ada query SQL di sini — itu tugas Repository.
 */
class User
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $roleId,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone,
        public readonly string $password,
        public readonly ?string $avatar,
        public readonly ?string $emailVerifiedAt,
        public readonly bool $isActive,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
    ) {
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerifiedAt !== null;
    }

    /**
     * Buat instance User dari baris hasil query database (array asosiatif).
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            roleId: (int) $row['role_id'],
            name: $row['name'],
            email: $row['email'],
            phone: $row['phone'] ?? null,
            password: $row['password'],
            avatar: $row['avatar'] ?? null,
            emailVerifiedAt: $row['email_verified_at'] ?? null,
            isActive: (bool) $row['is_active'],
            createdAt: $row['created_at'] ?? null,
            updatedAt: $row['updated_at'] ?? null,
        );
    }

    /**
     * Convert ke array yang aman ditampilkan ke client (tanpa password).
     */
    public function toPublicArray(): array
    {
        return [
            'id'                => $this->id,
            'role_id'           => $this->roleId,
            'name'              => $this->name,
            'email'             => $this->email,
            'phone'             => $this->phone,
            'avatar'            => $this->avatar,
            'is_email_verified' => $this->isEmailVerified(),
            'is_active'         => $this->isActive,
        ];
    }
}