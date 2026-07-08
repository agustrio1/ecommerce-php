<?php

declare(strict_types=1);

namespace App\Modules\Category\Infrastructure\Persistence;

use App\Modules\Category\Domain\Entities\Category;
use App\Modules\Category\Domain\Repositories\CategoryRepositoryInterface;
use PDO;

class MysqlCategoryRepository implements CategoryRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    public function findById(int $id): ?Category
    {
        $stmt = $this->pdo->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        return $row ? Category::fromArray($row) : null;
    }

    public function findBySlug(string $slug): ?Category
    {
        $stmt = $this->pdo->prepare('SELECT * FROM categories WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);

        $row = $stmt->fetch();

        return $row ? Category::fromArray($row) : null;
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM categories WHERE slug = :slug';
        $params = ['slug' => $slug];

        if ($exceptId !== null) {
            $sql .= ' AND id != :except_id';
            $params['except_id'] = $exceptId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM categories ORDER BY sort_order ASC, name ASC');

        return array_map(fn ($row) => Category::fromArray($row), $stmt->fetchAll());
    }

    public function findChildren(int $parentId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM categories WHERE parent_id = :parent_id ORDER BY sort_order ASC, name ASC');
        $stmt->execute(['parent_id' => $parentId]);

        return array_map(fn ($row) => Category::fromArray($row), $stmt->fetchAll());
    }

    public function findRootCategories(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM categories WHERE parent_id IS NULL ORDER BY sort_order ASC, name ASC');

        return array_map(fn ($row) => Category::fromArray($row), $stmt->fetchAll());
    }

    public function create(array $data): Category
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO categories (parent_id, name, slug, description, image, is_active, sort_order, created_at, updated_at)
             VALUES (:parent_id, :name, :slug, :description, :image, :is_active, :sort_order, NOW(), NOW())'
        );

        $stmt->execute([
            'parent_id'   => $data['parent_id'] ?? null,
            'name'        => $data['name'],
            'slug'        => $data['slug'],
            'description' => $data['description'] ?? null,
            'image'       => $data['image'] ?? null,
            'is_active'   => $data['is_active'] ?? 1,
            'sort_order'  => $data['sort_order'] ?? 0,
        ]);

        return $this->findById((int) $this->pdo->lastInsertId());
    }

    public function update(int $id, array $data): void
    {
        $fields = [];
        $params = ['id' => $id];

        foreach (['parent_id', 'name', 'slug', 'description', 'image', 'is_active', 'sort_order'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`{$field}` = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if (empty($fields)) {
            return;
        }

        $fields[] = 'updated_at = NOW()';
        $sql = 'UPDATE categories SET ' . implode(', ', $fields) . ' WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM categories WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function hasChildren(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM categories WHERE parent_id = :id');
        $stmt->execute(['id' => $id]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function hasProducts(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM product_categories WHERE category_id = :id');
        $stmt->execute(['id' => $id]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function findByParentId(int $parentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM categories WHERE parent_id = :parent_id ORDER BY name ASC'
        );
        $stmt->execute(['parent_id' => $parentId]);

        return array_map(fn ($row) => Category::fromArray($row), $stmt->fetchAll());
    }
}