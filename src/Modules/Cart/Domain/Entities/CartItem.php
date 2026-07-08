<?php

declare(strict_types=1);

namespace App\Modules\Cart\Domain\Entities;

class CartItem
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $cartId,
        public readonly int $productId,
        public readonly int $variantId,
        public readonly string $productName,
        public readonly string $variantLabel,
        public readonly string $productSku,
        public readonly ?string $productImage,
        public readonly float $price,
        public readonly int $quantity,
        public readonly ?float $weight,
        public readonly int $length,
        public readonly int $width,
        public readonly int $height,
    ) {
    }

    public function subtotal(): float
    {
        return $this->price * $this->quantity;
    }

    public function totalWeight(): float
    {
        return ($this->weight ?? 0) * $this->quantity;
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            cartId: (int) $row['cart_id'],
            productId: (int) $row['product_id'],
            variantId: (int) $row['variant_id'],
            productName: $row['product_name'] ?? '',
            variantLabel: $row['variant_label'] ?? '',
            productSku: $row['product_sku'] ?? '',
            productImage: $row['product_image'] ?? null,
            price: (float) $row['price'],
            quantity: (int) $row['quantity'],
            weight: $row['weight'] !== null ? (float) $row['weight'] : null,
            length: (int) ($row['length'] ?? 0),
            width: (int) ($row['width'] ?? 0),
            height: (int) ($row['height'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'cart_id'       => $this->cartId,
            'product_id'    => $this->productId,
            'variant_id'    => $this->variantId,
            'product_name'  => $this->productName,
            'variant_label' => $this->variantLabel,
            'product_sku'   => $this->productSku,
            'product_image' => $this->productImage,
            'price'         => $this->price,
            'quantity'      => $this->quantity,
            'subtotal'      => $this->subtotal(),
            'weight'        => $this->weight,
            'length'        => $this->length,
            'width'         => $this->width,
            'height'        => $this->height,
        ];
    }
}