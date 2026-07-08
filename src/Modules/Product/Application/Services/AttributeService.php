<?php

declare(strict_types=1);

namespace App\Modules\Product\Application\Services;

use App\Core\Exceptions\ValidationException;
use App\Modules\Product\Domain\Repositories\AttributeRepositoryInterface;
use App\Modules\Product\Infrastructure\Persistence\MysqlAttributeRepository;

class AttributeService
{
    private AttributeRepositoryInterface $attributes;

    public function __construct()
    {
        $this->attributes = new MysqlAttributeRepository();
    }

    public function getAllWithValues(): array
    {
        return $this->attributes->allWithValues();
    }

    public function createAttribute(string $name): int
    {
        $name = trim($name);

        if ($name === '') {
            throw new ValidationException(['name' => 'Nama atribut wajib diisi.']);
        }

        $slug = $this->slugify($name);

        return $this->attributes->create($name, $slug);
    }

    public function createValue(int $attributeId, string $value): int
    {
        $value = trim($value);

        if ($value === '') {
            throw new ValidationException(['value' => 'Value atribut wajib diisi.']);
        }

        $slug = $this->slugify($value);

        return $this->attributes->createValue($attributeId, $value, $slug);
    }

    private function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);

        return trim($text, '-');
    }
}