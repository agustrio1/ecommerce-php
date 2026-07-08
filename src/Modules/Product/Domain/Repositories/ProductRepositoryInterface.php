<?php

declare(strict_types=1);

namespace App\Modules\Product\Domain\Repositories;

use App\Modules\Product\Domain\Entities\Product;

interface ProductRepositoryInterface
{
    public function findById(int $id): ?Product;

    public function findBySlug(string $slug): ?Product;

    public function slugExists(string $slug, ?int $exceptId = null): bool;

    public function skuExists(string $sku, ?int $exceptId = null): bool;
    
    /**
     * @param int[] $productIds
     * @return array<int, string> product_id => image path
     */
    public function getPrimaryImages(array $productIds): array;

    /**
     * @return array{data: Product[], total: int}
     */
    public function paginate(int $page, int $perPage, array $filters = []): array;

    public function create(array $data): Product;

    public function update(int $id, array $data): void;

    public function delete(int $id): void;

    // ===== Categories relation =====

    public function syncCategories(int $productId, array $categoryIds): void;

    /** @return array<int, array{id: int, name: string}> */
    public function getCategoryIds(int $productId): array;

    // ===== Variants relation =====

    public function deleteVariants(int $productId): void;

    public function createVariant(array $data): int;

    public function attachVariantAttributeValue(int $variantId, int $attributeValueId): void;

    /** @return array<int, array> raw rows */
    public function getVariantsRaw(int $productId): array;

    public function updateVariantStock(int $variantId, int $stock): void;

    public function findVariantById(int $variantId): ?array;

    // ===== Images relation =====

    public function addImage(array $data): int;

    public function getImagesRaw(int $productId): array;

    public function deleteImage(int $imageId): void;

    public function unsetPrimaryImages(int $productId): void;
}