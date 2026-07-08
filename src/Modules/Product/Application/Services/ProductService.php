<?php

declare(strict_types=1);

namespace App\Modules\Product\Application\Services;

use App\Core\Exceptions\ValidationException;
use App\Modules\Product\Domain\Entities\Product;
use App\Modules\Product\Domain\Repositories\AttributeRepositoryInterface;
use App\Modules\Product\Domain\Repositories\ProductRepositoryInterface;
use App\Modules\Product\Infrastructure\Persistence\MysqlAttributeRepository;
use App\Modules\Product\Infrastructure\Persistence\MysqlProductRepository;
use PDO;
use RuntimeException;

class ProductService
{
    private ProductRepositoryInterface $products;
    private AttributeRepositoryInterface $attributes;
    private PDO $pdo;

    public function __construct()
    {
        $this->products   = new MysqlProductRepository();
        $this->attributes = new MysqlAttributeRepository();
        $this->pdo        = db();
    }

    public function paginate(int $page, int $perPage, array $filters = []): array
    {
        $where  = ['p.deleted_at IS NULL'];
        $params = [];

        if (! empty($filters['status'])) {
            $where[]           = 'p.status = :status';
            $params['status']  = $filters['status'];
        }

        if (! empty($filters['search'])) {
            // PENTING: PDO native prepare tidak mendukung named placeholder
            // yang sama dipakai lebih dari sekali dalam satu query.
            $where[] = '(p.name LIKE :search1 OR p.sku LIKE :search2 OR p.short_description LIKE :search3)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params['search1'] = $searchTerm;
            $params['search2'] = $searchTerm;
            $params['search3'] = $searchTerm;
        }

        if (! empty($filters['category_id'])) {
            $where[] = 'EXISTS (SELECT 1 FROM product_categories pc WHERE pc.product_id = p.id AND pc.category_id = :category_id)';
            $params['category_id'] = $filters['category_id'];
        }

        // CATATAN: klausa untuk category_ids (multi-kategori) SENGAJA TIDAK
        // ditambahkan di sini. Ia dibangun terpisah di bawah (blok positional
        // params) supaya tidak duplikat dengan klausa yang di-generate ulang
        // di situ. Sebelumnya klausa ini ditambahkan di sini JUGA lalu
        // ditambahkan lagi di blok bawah, menyebabkan jumlah placeholder '?'
        // dobel sementara $catParams cuma disuplai sekali -> PDOException
        // "Invalid parameter number".

        if (! empty($filters['min_price'])) {
            $where[]             = 'p.price >= :min_price';
            $params['min_price'] = $filters['min_price'];
        }

        if (! empty($filters['max_price'])) {
            $where[]             = 'p.price <= :max_price';
            $params['max_price'] = $filters['max_price'];
        }

        if (! empty($filters['min_rating'])) {
            $where[] = '(SELECT COALESCE(AVG(r.rating), 0) FROM reviews r WHERE r.product_id = p.id) >= :min_rating';
            $params['min_rating'] = $filters['min_rating'];
        }

        $whereSql = implode(' AND ', $where);

        $orderBy = match ($filters['sort'] ?? 'terbaru') {
            'termurah'  => 'p.price ASC',
            'termahal'  => 'p.price DESC',
            'terlaris'  => '(SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi WHERE oi.product_id = p.id) DESC',
            'rating'    => '(SELECT COALESCE(AVG(r.rating), 0) FROM reviews r WHERE r.product_id = p.id) DESC',
            default     => 'p.created_at DESC',
        };

        $offset = ($page - 1) * $perPage;

        // Untuk category_ids, kita pakai positional params (?) yang digabung
        // dengan named params (dikonversi ke '?' juga) karena PDO tidak bisa
        // campur named + positional dalam satu execute().
        if (! empty($filters['category_ids']) && is_array($filters['category_ids'])) {
            $phs      = implode(',', array_fill(0, count($filters['category_ids']), '?'));
            $catWhere = "EXISTS (SELECT 1 FROM product_categories pc WHERE pc.product_id = p.id AND pc.category_id IN ({$phs}))";

            $finalWhere   = $where; // $where di sini TIDAK mengandung klausa category_ids (lihat catatan di atas)
            $finalWhere[] = $catWhere;

            // Konversi semua named params jadi positional supaya bisa dicampur
            $positionalParams = [];
            $positionalWhere  = [];

            foreach ($finalWhere as $w) {
                while (preg_match('/:([a-z0-9_]+)/i', $w, $m)) {
                    $name = $m[1];
                    $positionalParams[] = $params[$name];
                    $w = preg_replace('/:' . preg_quote($name, '/') . '\b/', '?', $w, 1);
                }
                $positionalWhere[] = $w;
            }

            $positionalSql = implode(' AND ', $positionalWhere);

            // Tambahkan category_ids values (posisinya di akhir, sesuai urutan '?' pada $catWhere)
            $catParams = array_values($filters['category_ids']);

            // Count
            $countSql  = "SELECT COUNT(*) FROM products p WHERE {$positionalSql}";
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute(array_merge($positionalParams, $catParams));
            $total = (int) $countStmt->fetchColumn();

            // Data
            $dataSql  = "SELECT p.* FROM products p WHERE {$positionalSql} ORDER BY {$orderBy} LIMIT ? OFFSET ?";
            $dataStmt = $this->pdo->prepare($dataSql);
            $dataStmt->execute(array_merge($positionalParams, $catParams, [$perPage, $offset]));
        } else {
            // Semua named params, lebih simpel
            $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM products p WHERE {$whereSql}");
            $countStmt->execute($params);
            $total = (int) $countStmt->fetchColumn();

            $dataStmt = $this->pdo->prepare(
                "SELECT p.* FROM products p WHERE {$whereSql} ORDER BY {$orderBy} LIMIT :limit OFFSET :offset"
            );
            foreach ($params as $key => $value) {
                $dataStmt->bindValue(':' . $key, $value);
            }
            $dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $dataStmt->execute();
        }

        $products = array_map(fn ($row) => Product::fromArray($row), $dataStmt->fetchAll());

        return ['data' => $products, 'total' => $total];
    }

    public function find(int $id): Product
    {
        $product = $this->products->findById($id);

        if ($product === null) {
            throw new RuntimeException('Produk tidak ditemukan.');
        }

        return $product;
    }

    public function findBySlug(string $slug): Product
    {
        $product = $this->products->findBySlug($slug);

        if ($product === null) {
            throw new RuntimeException('Produk tidak ditemukan.');
        }

        return $product;
    }

    public function getVariants(int $productId): array
    {
        return $this->products->getVariantsRaw($productId);
    }

    public function getImages(int $productId): array
    {
        return $this->products->getImagesRaw($productId);
    }

    /**
     * Ambil primary image untuk array of Product objects.
     * Return: array dengan key = product_id, value = URL siap pakai
     *
     * @param Product[] $products
     * @return array<int, string>
     */
    public function getPrimaryImagesByProducts(array $products): array
    {
        $ids = array_filter(array_map(fn ($p) => $p->id, $products));

        if (empty($ids)) {
            return [];
        }

        $paths = $this->products->getPrimaryImages(array_values($ids));

        $urls = [];
        foreach ($paths as $productId => $path) {
            $urls[$productId] = '/storage/' . ltrim($path, '/');
        }

        return $urls;
    }

    public function getCategoryIds(int $productId): array
    {
        return $this->products->getCategoryIds($productId);
    }

    public function getAllAttributesWithValues(): array
    {
        return $this->attributes->allWithValues();
    }

    public function create(array $data): Product
    {
        $errors = $this->validate($data);

        if (! empty($errors)) {
            throw new ValidationException($errors);
        }

        $slug = $this->generateUniqueSlug($data['name']);
        $hasVariants = ($data['variant_mode'] ?? 'single') === 'combination';

        $product = $this->products->create([
            'name'              => $data['name'],
            'slug'              => $slug,
            'description'       => $data['description'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'sku'               => $data['sku'],
            'price'             => $data['price'],
            'compare_price'     => $data['compare_price'] ?? null,
            'cost_price'        => $data['cost_price'] ?? null,
            'weight'            => $data['weight'] ?? null,
            'has_variants'      => $hasVariants ? 1 : 0,
            'status'            => $data['status'] ?? 'draft',
        ]);

        if (! empty($data['category_ids'])) {
            $this->products->syncCategories($product->id, $data['category_ids']);
        }

        if ($hasVariants) {
            $this->generateVariantCombinations($product->id, $data['sku'], $data['selected_attribute_values'] ?? []);
        } else {
            $this->createDefaultVariant($product->id, $data);
        }

        return $product;
    }

    public function update(int $id, array $data): void
    {
        $existing = $this->find($id);

        $errors = $this->validate($data, $id);

        if (! empty($errors)) {
            throw new ValidationException($errors);
        }

        $updateData = [
            'description'       => $data['description'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'price'             => $data['price'],
            'compare_price'     => $data['compare_price'] ?? null,
            'cost_price'        => $data['cost_price'] ?? null,
            'weight'            => $data['weight'] ?? null,
            'status'            => $data['status'] ?? $existing->status,
        ];

        if ($data['name'] !== $existing->name) {
            $updateData['name'] = $data['name'];
            $updateData['slug'] = $this->generateUniqueSlug($data['name'], $id);
        }

        if ($data['sku'] !== $existing->sku) {
            $updateData['sku'] = $data['sku'];
        }

        $this->products->update($id, $updateData);

        if (isset($data['category_ids'])) {
            $this->products->syncCategories($id, $data['category_ids']);
        }
    }

    public function delete(int $id): void
    {
        $this->find($id);
        $this->products->delete($id);
    }

    public function regenerateVariants(int $productId, array $selectedAttributeValues): void
    {
        $product = $this->find($productId);

        $this->products->deleteVariants($productId);
        $this->generateVariantCombinations($productId, $product->sku, $selectedAttributeValues);
        $this->products->update($productId, ['has_variants' => 1]);
    }

    public function updateVariantStock(int $variantId, int $stock): void
    {
        if ($stock < 0) {
            throw new ValidationException(['stock' => 'Stok tidak boleh negatif.']);
        }

        $this->products->updateVariantStock($variantId, $stock);
    }
    
    /**
     * Inject flash sale prices ke array of products.
     * Dipakai di home, listing, category page supaya harga konsisten.
     *
     * @param Product[] $products
     * @return array<int, array> key = product_id, value = flash sale info
     */
    public function getFlashSalePrices(array $products): array
    {
        if (empty($products)) {
            return [];
        }

        $flashSaleService = new \App\Modules\FlashSale\Application\Services\FlashSaleService();
        $ids = array_map(fn ($p) => $p->id, $products);

        return $flashSaleService->getActivePricesForProducts($ids);
    }

    // ===================== VARIANT GENERATION (CARTESIAN PRODUCT) =====================

    private function createDefaultVariant(int $productId, array $data): void
    {
        $this->products->createVariant([
            'product_id' => $productId,
            'sku'        => $data['sku'],
            'price'      => null,
            'stock'      => $data['stock'] ?? 0,
            'is_active'  => 1,
        ]);
    }

    private function generateVariantCombinations(int $productId, string $baseSku, array $selectedAttributeValues): void
    {
        if (empty($selectedAttributeValues)) {
            throw new ValidationException(['attributes' => 'Pilih minimal 1 atribut dengan value untuk membuat kombinasi varian.']);
        }

        $groupedValues = [];

        foreach ($selectedAttributeValues as $group) {
            $valueIds = $group['value_ids'] ?? [];

            if (empty($valueIds)) {
                continue;
            }

            $values = $this->attributes->findValuesByIds($valueIds);

            if (empty($values)) {
                continue;
            }

            $groupedValues[] = $values;
        }

        if (empty($groupedValues)) {
            throw new ValidationException(['attributes' => 'Tidak ada value atribut yang valid dipilih.']);
        }

        $combinations = $this->cartesianProduct($groupedValues);

        $index = 1;

        foreach ($combinations as $combination) {
            $variantSku = $this->buildVariantSku($baseSku, $combination, $index);

            $variantId = $this->products->createVariant([
                'product_id' => $productId,
                'sku'        => $variantSku,
                'price'      => null,
                'stock'      => 0,
                'is_active'  => 1,
            ]);

            foreach ($combination as $valueRow) {
                $this->products->attachVariantAttributeValue($variantId, (int) $valueRow['id']);
            }

            $index++;
        }
    }

    private function cartesianProduct(array $arrays): array
    {
        $result = [[]];

        foreach ($arrays as $propertyValues) {
            $tmp = [];

            foreach ($result as $resultItem) {
                foreach ($propertyValues as $propertyValue) {
                    $tmp[] = array_merge($resultItem, [$propertyValue]);
                }
            }

            $result = $tmp;
        }

        return $result;
    }

    private function buildVariantSku(string $baseSku, array $combination, int $index): string
    {
        $suffix = implode('-', array_map(
            fn ($value) => strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $value['slug'] ?? $value['value']), 0, 4)),
            $combination
        ));

        return $baseSku . '-' . $suffix;
    }

    // ===================== VALIDATION =====================

    private function validate(array $data, ?int $exceptId = null): array
    {
        $errors = [];

        if (empty(trim($data['name'] ?? ''))) {
            $errors['name'] = 'Nama produk wajib diisi.';
        }

        if (empty(trim($data['sku'] ?? ''))) {
            $errors['sku'] = 'SKU wajib diisi.';
        } elseif ($this->products->skuExists($data['sku'], $exceptId)) {
            $errors['sku'] = 'SKU sudah digunakan produk lain.';
        }

        if (! isset($data['price']) || ! is_numeric($data['price']) || (float) $data['price'] < 0) {
            $errors['price'] = 'Harga wajib diisi dan tidak boleh negatif.';
        }

        if (isset($data['compare_price']) && $data['compare_price'] !== null && $data['compare_price'] !== '') {
            if ((float) $data['compare_price'] <= (float) ($data['price'] ?? 0)) {
                $errors['compare_price'] = 'Harga coret harus lebih besar dari harga jual.';
            }
        }

        foreach (['length', 'width', 'height'] as $dim) {
            if (isset($data[$dim]) && (int) $data[$dim] < 0) {
                $label = ['length' => 'Panjang', 'width' => 'Lebar', 'height' => 'Tinggi'][$dim];
                $errors[$dim] = "{$label} tidak boleh negatif.";
            }
        }

        return $errors;
    }

    private function generateUniqueSlug(string $name, ?int $exceptId = null): string
    {
        $baseSlug = $this->slugify($name);
        $slug = $baseSlug;
        $counter = 1;

        while ($this->products->slugExists($slug, $exceptId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);

        return trim($text, '-');
    }
}