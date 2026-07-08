<?php

declare(strict_types=1);

namespace App\Modules\Product\Domain\Entities;

class ProductImage
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $productId,
        public readonly ?int $variantId,
        public readonly string $path,
        public readonly ?string $altText,
        public readonly bool $isPrimary,
        public readonly int $sortOrder,
    ) {
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            productId: (int) $row['product_id'],
            variantId: $row['variant_id'] !== null ? (int) $row['variant_id'] : null,
            path: $row['path'],
            altText: $row['alt_text'] ?? null,
            isPrimary: (bool) $row['is_primary'],
            sortOrder: (int) $row['sort_order'],
        );
    }

    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'product_id' => $this->productId,
            'variant_id' => $this->variantId,
            'path'       => $this->path,
            'url'        => '/storage/' . ltrim($this->path, '/'),
            'alt_text'   => $this->altText,
            'is_primary' => $this->isPrimary,
        ];
    }
}