<?php

declare(strict_types=1);

namespace App\Modules\Product\Infrastructure\Persistence;

use App\Modules\Product\Domain\Entities\Product;
use App\Modules\Product\Domain\Repositories\ProductRepositoryInterface;
use PDO;

class MysqlProductRepository implements ProductRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    public function findById(int $id): ?Product
    {
        $stmt = $this->pdo->prepare('SELECT * FROM products WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        return $row ? Product::fromArray($row) : null;
    }

    public function findBySlug(string $slug): ?Product
    {
        $stmt = $this->pdo->prepare('SELECT * FROM products WHERE slug = :slug AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['slug' => $slug]);

        $row = $stmt->fetch();

        return $row ? Product::fromArray($row) : null;
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM products WHERE slug = :slug AND deleted_at IS NULL';
        $params = ['slug' => $slug];

        if ($exceptId !== null) {
            $sql .= ' AND id != :except_id';
            $params['except_id'] = $exceptId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function skuExists(string $sku, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM products WHERE sku = :sku AND deleted_at IS NULL';
        $params = ['sku' => $sku];

        if ($exceptId !== null) {
            $sql .= ' AND id != :except_id';
            $params['except_id'] = $exceptId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function paginate(int $page, int $perPage, array $filters = []): array
    {
        $where = ['deleted_at IS NULL'];
        $params = [];

        if (! empty($filters['search'])) {
            $where[] = '(name LIKE :search OR sku LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (! empty($filters['status'])) {
            $where[] = 'status = :status';
            $params['status'] = $filters['status'];
        }

        if (! empty($filters['category_id'])) {
            $where[] = 'id IN (SELECT product_id FROM product_categories WHERE category_id = :category_id)';
            $params['category_id'] = $filters['category_id'];
        }

        if (! empty($filters['category_ids']) && is_array($filters['category_ids'])) {
            $placeholders = implode(',', array_fill(0, count($filters['category_ids']), '?'));
            $where[] = "EXISTS (SELECT 1 FROM product_categories pc WHERE pc.product_id = p.id AND pc.category_id IN ({$placeholders}))";
            foreach ($filters['category_ids'] as $cid) {
                $params[] = $cid;
            }
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM products WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare(
            "SELECT * FROM products WHERE {$whereSql} ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $data = array_map(fn ($row) => Product::fromArray($row), $stmt->fetchAll());

        return ['data' => $data, 'total' => $total];
    }

    public function create(array $data): Product
    {
        // FIX: kolom meta_title/meta_description/meta_keywords sebelumnya
        // tidak ada di query INSERT sama sekali, jadi walau service sudah
        // mengirim nilainya, tetap tidak akan pernah tersimpan.
        $stmt = $this->pdo->prepare(
            'INSERT INTO products (
                name, slug, description, short_description, sku,
                price, compare_price, cost_price, weight,
                length, width, height,
                meta_title, meta_description, meta_keywords,
                has_variants, status, published_at, created_at, updated_at
            ) VALUES (
                :name, :slug, :description, :short_description, :sku,
                :price, :compare_price, :cost_price, :weight,
                :length, :width, :height,
                :meta_title, :meta_description, :meta_keywords,
                :has_variants, :status, :published_at, NOW(), NOW()
            )'
        );

        $stmt->execute([
            'name'              => $data['name'],
            'slug'              => $data['slug'],
            'description'       => $data['description'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'sku'               => $data['sku'],
            'price'             => $data['price'],
            'compare_price'     => $data['compare_price'] ?? null,
            'cost_price'        => $data['cost_price'] ?? null,
            'weight'            => $data['weight'] ?? null,
            'length'            => (int) ($data['length'] ?? 0),
            'width'             => (int) ($data['width'] ?? 0),
            'height'            => (int) ($data['height'] ?? 0),
            'meta_title'        => $data['meta_title'] ?? null,
            'meta_description'  => $data['meta_description'] ?? null,
            'meta_keywords'     => $data['meta_keywords'] ?? null,
            'has_variants'      => $data['has_variants'] ?? 0,
            'status'            => $data['status'] ?? 'draft',
            'published_at'      => ($data['status'] ?? '') === 'published' ? date('Y-m-d H:i:s') : null,
        ]);

        return $this->findById((int) $this->pdo->lastInsertId());
    }

    public function update(int $id, array $data): void
    {
        $fields = [];
        $params = ['id' => $id];

        // FIX: length, width, height sudah ada sebelumnya di whitelist ini
        // (jadi sebenarnya SUDAH didukung repository), tapi meta_title/
        // meta_description/meta_keywords belum pernah ada — ditambahkan
        // di sini.
        $allowedFields = [
            'name', 'slug', 'description', 'short_description',
            'sku', 'price', 'compare_price', 'cost_price',
            'weight', 'length', 'width', 'height',
            'meta_title', 'meta_description', 'meta_keywords',
            'has_variants', 'status'
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`{$field}` = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if (isset($data['status']) && $data['status'] === 'published') {
            $fields[] = 'published_at = COALESCE(published_at, NOW())';
        }

        if (empty($fields)) {
            return;
        }

        $fields[] = 'updated_at = NOW()';
        $sql = 'UPDATE products SET ' . implode(', ', $fields) . ' WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE products SET deleted_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    // ===== Categories =====

    public function syncCategories(int $productId, array $categoryIds): void
    {
        $del = $this->pdo->prepare('DELETE FROM product_categories WHERE product_id = :product_id');
        $del->execute(['product_id' => $productId]);

        $stmt = $this->pdo->prepare(
            'INSERT INTO product_categories (product_id, category_id, created_at, updated_at) VALUES (:product_id, :category_id, NOW(), NOW())'
        );

        foreach ($categoryIds as $categoryId) {
            $stmt->execute(['product_id' => $productId, 'category_id' => $categoryId]);
        }
    }

    public function getCategoryIds(int $productId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.id, c.name FROM categories c
             JOIN product_categories pc ON pc.category_id = c.id
             WHERE pc.product_id = :product_id'
        );
        $stmt->execute(['product_id' => $productId]);

        return $stmt->fetchAll();
    }

    // ===== Variants =====

    public function deleteVariants(int $productId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM product_variants WHERE product_id = :product_id');
        $stmt->execute(['product_id' => $productId]);
    }

    public function createVariant(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO product_variants (product_id, sku, price, compare_price, stock, weight, is_active, created_at, updated_at)
             VALUES (:product_id, :sku, :price, :compare_price, :stock, :weight, :is_active, NOW(), NOW())'
        );

        $stmt->execute([
            'product_id'     => $data['product_id'],
            'sku'            => $data['sku'],
            'price'          => $data['price'] ?? null,
            'compare_price'  => $data['compare_price'] ?? null,
            'stock'          => $data['stock'] ?? 0,
            'weight'         => $data['weight'] ?? null,
            'is_active'      => $data['is_active'] ?? 1,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function attachVariantAttributeValue(int $variantId, int $attributeValueId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO variant_attribute_values (variant_id, attribute_value_id, created_at, updated_at)
             VALUES (:variant_id, :attribute_value_id, NOW(), NOW())'
        );
        $stmt->execute(['variant_id' => $variantId, 'attribute_value_id' => $attributeValueId]);
    }

    public function getVariantsRaw(int $productId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM product_variants WHERE product_id = :product_id ORDER BY id ASC');
        $stmt->execute(['product_id' => $productId]);

        $variants = $stmt->fetchAll();

        foreach ($variants as &$variant) {
            $attrStmt = $this->pdo->prepare(
                'SELECT a.name AS attribute, av.value AS value
                 FROM variant_attribute_values vav
                 JOIN attribute_values av ON av.id = vav.attribute_value_id
                 JOIN attributes a ON a.id = av.attribute_id
                 WHERE vav.variant_id = :variant_id'
            );
            $attrStmt->execute(['variant_id' => $variant['id']]);
            $variant['attribute_values'] = $attrStmt->fetchAll();
        }

        return $variants;
    }

    public function updateVariantStock(int $variantId, int $stock): void
    {
        $stmt = $this->pdo->prepare('UPDATE product_variants SET stock = :stock, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['stock' => $stock, 'id' => $variantId]);
    }

    public function findVariantById(int $variantId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM product_variants WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $variantId]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    // ===== Images =====

    public function addImage(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO product_images (product_id, variant_id, path, alt_text, is_primary, sort_order, created_at, updated_at)
             VALUES (:product_id, :variant_id, :path, :alt_text, :is_primary, :sort_order, NOW(), NOW())'
        );

        $stmt->execute([
            'product_id' => $data['product_id'],
            'variant_id' => $data['variant_id'] ?? null,
            'path'       => $data['path'],
            'alt_text'   => $data['alt_text'] ?? null,
            'is_primary' => $data['is_primary'] ?? 0,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function getImagesRaw(int $productId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM product_images WHERE product_id = :product_id ORDER BY sort_order ASC, id ASC');
        $stmt->execute(['product_id' => $productId]);

        return $stmt->fetchAll();
    }

    public function deleteImage(int $imageId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM product_images WHERE id = :id');
        $stmt->execute(['id' => $imageId]);
    }

    public function unsetPrimaryImages(int $productId): void
    {
        $stmt = $this->pdo->prepare('UPDATE product_images SET is_primary = 0 WHERE product_id = :product_id');
        $stmt->execute(['product_id' => $productId]);
    }

    /**
     * @param int[] $productIds
     * @return array<int, string>
     */
    public function getPrimaryImages(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));

        $stmt = $this->pdo->prepare(
            "SELECT product_id, path FROM product_images
             WHERE product_id IN ({$placeholders})
             AND is_primary = 1
             ORDER BY sort_order ASC"
        );
        $stmt->execute(array_values($productIds));

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            if (! isset($result[(int) $row['product_id']])) {
                $result[(int) $row['product_id']] = $row['path'];
            }
        }

        return $result;
    }
}