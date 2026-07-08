<?php

declare(strict_types=1);

namespace App\Modules\Cart\Infrastructure\Persistence;

use App\Modules\Cart\Domain\Entities\Address;
use PDO;

class MysqlAddressRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    /** @return Address[] */
    public function findByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM addresses WHERE user_id = :user_id ORDER BY is_primary DESC, created_at DESC'
        );
        $stmt->execute(['user_id' => $userId]);

        return array_map(fn ($row) => Address::fromArray($row), $stmt->fetchAll());
    }

    public function findById(int $id, int $userId): ?Address
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM addresses WHERE id = :id AND user_id = :user_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $row = $stmt->fetch();

        return $row ? Address::fromArray($row) : null;
    }

    public function findPrimary(int $userId): ?Address
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM addresses WHERE user_id = :user_id AND is_primary = 1 LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();

        if (! $row) {
            // Kalau tidak ada primary, ambil yang pertama
            $stmt2 = $this->pdo->prepare('SELECT * FROM addresses WHERE user_id = :user_id LIMIT 1');
            $stmt2->execute(['user_id' => $userId]);
            $row = $stmt2->fetch();
        }

        return $row ? Address::fromArray($row) : null;
    }

    public function create(array $data): Address
    {
        if (! empty($data['is_primary'])) {
            $this->unsetPrimary($data['user_id']);
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO addresses (user_id, label, recipient_name, phone, address, province, city, district, postal_code, area_id, latitude, longitude, is_primary, created_at, updated_at)
             VALUES (:user_id, :label, :recipient_name, :phone, :address, :province, :city, :district, :postal_code, :area_id, :latitude, :longitude, :is_primary, NOW(), NOW())'
        );

        $stmt->execute([
            'user_id'        => $data['user_id'],
            'label'          => $data['label'] ?? 'Rumah',
            'recipient_name' => $data['recipient_name'],
            'phone'          => $data['phone'],
            'address'        => $data['address'],
            'province'       => $data['province'] ?? null,
            'city'           => $data['city'] ?? null,
            'district'       => $data['district'] ?? null,
            'postal_code'    => $data['postal_code'],
            'area_id'        => $data['area_id'] ?? null,
            'latitude'       => $data['latitude'] ?? null,
            'longitude'      => $data['longitude'] ?? null,
            'is_primary'     => $data['is_primary'] ?? 0,
        ]);

        return $this->findById((int) $this->pdo->lastInsertId(), $data['user_id']);
    }

    public function update(int $id, int $userId, array $data): void
    {
        if (! empty($data['is_primary'])) {
            $this->unsetPrimary($userId);
        }

        $stmt = $this->pdo->prepare(
            'UPDATE addresses SET label = :label, recipient_name = :recipient_name, phone = :phone,
             address = :address, province = :province, city = :city, district = :district,
             postal_code = :postal_code, area_id = :area_id, latitude = :latitude,
             longitude = :longitude, is_primary = :is_primary, updated_at = NOW()
             WHERE id = :id AND user_id = :user_id'
        );

        $stmt->execute([
            'id'             => $id,
            'user_id'        => $userId,
            'label'          => $data['label'] ?? 'Rumah',
            'recipient_name' => $data['recipient_name'],
            'phone'          => $data['phone'],
            'address'        => $data['address'],
            'province'       => $data['province'] ?? null,
            'city'           => $data['city'] ?? null,
            'district'       => $data['district'] ?? null,
            'postal_code'    => $data['postal_code'],
            'area_id'        => $data['area_id'] ?? null,
            'latitude'       => $data['latitude'] ?? null,
            'longitude'      => $data['longitude'] ?? null,
            'is_primary'     => $data['is_primary'] ?? 0,
        ]);
    }

    public function delete(int $id, int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM addresses WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
    }

    public function setPrimary(int $id, int $userId): void
    {
        $this->unsetPrimary($userId);

        $stmt = $this->pdo->prepare('UPDATE addresses SET is_primary = 1 WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
    }

    private function unsetPrimary(int $userId): void
    {
        $stmt = $this->pdo->prepare('UPDATE addresses SET is_primary = 0 WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
    }
}