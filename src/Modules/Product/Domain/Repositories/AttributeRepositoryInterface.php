<?php

declare(strict_types=1);

namespace App\Modules\Product\Domain\Repositories;

interface AttributeRepositoryInterface
{
    /** @return array<int, array> */
    public function allWithValues(): array;

    public function create(string $name, string $slug): int;

    public function createValue(int $attributeId, string $value, string $slug): int;

    public function findValueById(int $id): ?array;

    /**
     * @param int[] $ids
     * @return array<int, array>
     */
    public function findValuesByIds(array $ids): array;
}