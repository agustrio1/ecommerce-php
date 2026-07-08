<?php

declare(strict_types=1);

namespace App\Modules\Category\Domain\Entities;

class Category
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?int $parentId,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly ?string $image,
        public readonly bool $isActive,
        public readonly int $sortOrder,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
    ) {
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            parentId: $row['parent_id'] !== null ? (int) $row['parent_id'] : null,
            name: $row['name'],
            slug: $row['slug'],
            description: $row['description'] ?? null,
            image: $row['image'] ?? null,
            isActive: (bool) $row['is_active'],
            sortOrder: (int) $row['sort_order'],
            createdAt: $row['created_at'] ?? null,
            updatedAt: $row['updated_at'] ?? null,
        );
    }

    public function isRoot(): bool
    {
        return $this->parentId === null;
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'parent_id'   => $this->parentId,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'image'       => $this->image,
            'is_active'   => $this->isActive,
            'sort_order'  => $this->sortOrder,
        ];
    }
}