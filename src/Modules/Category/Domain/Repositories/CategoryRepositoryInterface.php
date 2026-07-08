<?php

declare(strict_types=1);

namespace App\Modules\Category\Domain\Repositories;

use App\Modules\Category\Domain\Entities\Category;

interface CategoryRepositoryInterface
{
    public function findById(int $id): ?Category;

    public function findBySlug(string $slug): ?Category;

    public function slugExists(string $slug, ?int $exceptId = null): bool;

    /** @return Category[] */
    public function all(): array;

    /** @return Category[] */
    public function findChildren(int $parentId): array;

    /** @return Category[] */
    public function findRootCategories(): array;

    public function create(array $data): Category;

    public function update(int $id, array $data): void;

    public function delete(int $id): void;

    public function hasChildren(int $id): bool;

    public function hasProducts(int $id): bool;
}