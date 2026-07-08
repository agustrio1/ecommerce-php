<?php

declare(strict_types=1);

namespace App\Modules\Product\Infrastructure\Persistence;

use App\Modules\Product\Domain\Repositories\AttributeRepositoryInterface;
use PDO;

class MysqlAttributeRepository implements AttributeRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    public function allWithValues(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM attributes ORDER BY name ASC');
        $attributes = $stmt->fetchAll();

        foreach ($attributes as &$attribute) {
            $valueStmt = $this->pdo->prepare('SELECT * FROM attribute_values WHERE attribute_id = :attribute_id ORDER BY value ASC');
            $valueStmt->execute(['attribute_id' => $attribute['id']]);
            $attribute['values'] = $valueStmt->fetchAll();
        }

        return $attributes;
    }

    public function create(string $name, string $slug): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO attributes (name, slug, created_at, updated_at) VALUES (:name, :slug, NOW(), NOW())');
        $stmt->execute(['name' => $name, 'slug' => $slug]);

        return (int) $this->pdo->lastInsertId();
    }

    public function createValue(int $attributeId, string $value, string $slug): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO attribute_values (attribute_id, value, slug, created_at, updated_at) VALUES (:attribute_id, :value, :slug, NOW(), NOW())'
        );
        $stmt->execute(['attribute_id' => $attributeId, 'value' => $value, 'slug' => $slug]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findValueById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM attribute_values WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findValuesByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM attribute_values WHERE id IN ({$placeholders})");
        $stmt->execute(array_values($ids));

        return $stmt->fetchAll();
    }
}