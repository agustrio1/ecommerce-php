<?php

declare(strict_types=1);

namespace App\Modules\Cart\Domain\Entities;

class Address
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $userId,
        public readonly string $label,
        public readonly string $recipientName,
        public readonly string $phone,
        public readonly string $address,
        public readonly ?string $province,
        public readonly ?string $city,
        public readonly ?string $district,
        public readonly string $postalCode,
        public readonly ?string $areaId,
        public readonly ?float $latitude,
        public readonly ?float $longitude,
        public readonly bool $isPrimary,
        public readonly ?string $createdAt = null,
    ) {
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            label: $row['label'],
            recipientName: $row['recipient_name'],
            phone: $row['phone'],
            address: $row['address'],
            province: $row['province'] ?? null,
            city: $row['city'] ?? null,
            district: $row['district'] ?? null,
            postalCode: $row['postal_code'],
            areaId: $row['area_id'] ?? null,
            latitude: $row['latitude'] !== null ? (float) $row['latitude'] : null,
            longitude: $row['longitude'] !== null ? (float) $row['longitude'] : null,
            isPrimary: (bool) $row['is_primary'],
            createdAt: $row['created_at'] ?? null,
        );
    }

    public function fullAddress(): string
    {
        $parts = array_filter([
            $this->address,
            $this->district,
            $this->city,
            $this->province,
            $this->postalCode,
        ]);

        return implode(', ', $parts);
    }

    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'user_id'        => $this->userId,
            'label'          => $this->label,
            'recipient_name' => $this->recipientName,
            'phone'          => $this->phone,
            'address'        => $this->address,
            'province'       => $this->province,
            'city'           => $this->city,
            'district'       => $this->district,
            'postal_code'    => $this->postalCode,
            'area_id'        => $this->areaId,
            'latitude'       => $this->latitude,
            'longitude'      => $this->longitude,
            'is_primary'     => $this->isPrimary,
            'full_address'   => $this->fullAddress(),
        ];
    }
}