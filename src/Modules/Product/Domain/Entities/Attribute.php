<?php

declare(strict_types=1);

namespace App\Modules\Product\Domain\Entities;

class Attribute
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly string $slug,
    ) {
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            name: $row['name'],
            slug: $row['slug'],
        );
    }
}