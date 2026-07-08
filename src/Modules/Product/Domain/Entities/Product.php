<?php

declare(strict_types=1);

namespace App\Modules\Product\Domain\Entities;

class Product
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly ?string $shortDescription,
        public readonly string $sku,
        public readonly float $price,
        public readonly ?float $comparePrice,
        public readonly ?float $costPrice,
        public readonly ?float $weight,
        public readonly int $length,
        public readonly int $width,
        public readonly int $height,
        public readonly bool $hasVariants,
        public readonly string $status,
        public readonly ?string $publishedAt,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?string $metaTitle = null,
        public readonly ?string $metaDescription = null,
        public readonly ?string $metaKeywords = null,
    ) {
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            name: $row['name'],
            slug: $row['slug'],
            description: $row['description'] ?? null,
            shortDescription: $row['short_description'] ?? null,
            sku: $row['sku'],
            price: (float) $row['price'],
            comparePrice: isset($row['compare_price']) && $row['compare_price'] !== null
                ? (float) $row['compare_price']
                : null,
            costPrice: isset($row['cost_price']) && $row['cost_price'] !== null
                ? (float) $row['cost_price']
                : null,
            weight: isset($row['weight']) && $row['weight'] !== null
                ? (float) $row['weight']
                : null,
            length: (int) ($row['length'] ?? 0),
            width: (int) ($row['width'] ?? 0),
            height: (int) ($row['height'] ?? 0),
            hasVariants: (bool) ($row['has_variants'] ?? false),
            status: $row['status'],
            publishedAt: $row['published_at'] ?? null,
            createdAt: $row['created_at'] ?? null,
            updatedAt: $row['updated_at'] ?? null,
            metaTitle: $row['meta_title'] ?? null,
            metaDescription: $row['meta_description'] ?? null,
            metaKeywords: $row['meta_keywords'] ?? null,
        );
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isOnSale(): bool
    {
        return $this->comparePrice !== null
            && $this->comparePrice > $this->price;
    }

    public function discountPercentage(): int
    {
        if (! $this->isOnSale()) {
            return 0;
        }

        return (int) round(
            (($this->comparePrice - $this->price) / $this->comparePrice) * 100
        );
    }

    public function toArray(): array
    {
        return [
            'id'                    => $this->id,
            'name'                  => $this->name,
            'slug'                  => $this->slug,
            'description'           => $this->description,
            'short_description'     => $this->shortDescription,
            'sku'                   => $this->sku,
            'price'                 => $this->price,
            'compare_price'         => $this->comparePrice,
            'cost_price'            => $this->costPrice,
            'weight'                => $this->weight,
            'length'                => $this->length,
            'width'                 => $this->width,
            'height'                => $this->height,
            'has_variants'          => $this->hasVariants,
            'status'                => $this->status,
            'published_at'          => $this->publishedAt,
            'created_at'            => $this->createdAt,
            'updated_at'            => $this->updatedAt,
            'meta_title'            => $this->metaTitle,
            'meta_description'      => $this->metaDescription,
            'meta_keywords'         => $this->metaKeywords,
            'is_on_sale'            => $this->isOnSale(),
            'discount_percentage'   => $this->discountPercentage(),
        ];
    }
}