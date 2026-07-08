<?php

declare(strict_types=1);

namespace App\Modules\Product\Domain\Entities;

class ProductVariant
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $productId,
        public readonly string $sku,
        public readonly ?float $price,
        public readonly ?float $comparePrice,
        public readonly int $stock,
        public readonly ?float $weight,
        public readonly bool $isActive,
        /** @var array<int, array{attribute: string, value: string}> */
        public readonly array $attributeValues = [],
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
    ) {
    }

    public static function fromArray(array $row, array $attributeValues = []): self
    {
        return new self(
            id: (int) $row['id'],
            productId: (int) $row['product_id'],
            sku: $row['sku'],
            price: $row['price'] !== null ? (float) $row['price'] : null,
            comparePrice: $row['compare_price'] !== null ? (float) $row['compare_price'] : null,
            stock: (int) $row['stock'],
            weight: $row['weight'] !== null ? (float) $row['weight'] : null,
            isActive: (bool) $row['is_active'],
            attributeValues: $attributeValues,
            createdAt: $row['created_at'] ?? null,
            updatedAt: $row['updated_at'] ?? null,
        );
    }

    /**
     * Label deskriptif kombinasi varian, mis. "Merah / L"
     */
    public function label(): string
    {
        if (empty($this->attributeValues)) {
            return 'Default';
        }

        return implode(' / ', array_column($this->attributeValues, 'value'));
    }

    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    /**
     * Resolve harga efektif: pakai harga variant jika diset, fallback ke harga produk induk.
     */
    public function effectivePrice(Product $product): float
    {
        return $this->price ?? $product->price;
    }

    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'product_id'     => $this->productId,
            'sku'            => $this->sku,
            'price'          => $this->price,
            'compare_price'  => $this->comparePrice,
            'stock'          => $this->stock,
            'weight'         => $this->weight,
            'is_active'      => $this->isActive,
            'label'          => $this->label(),
            'attribute_values' => $this->attributeValues,
        ];
    }
}