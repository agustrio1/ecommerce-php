<?php

declare(strict_types=1);

namespace App\Modules\Product\Domain\Entities;

class AttributeValue
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $attributeId,
        public readonly string $value,
        public readonly string $slug,
    ) {
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            attributeId: (int) $row['attribute_id'],
            value: $row['value'],
            slug: $row['slug'],
        );
    }
}