<?php

declare(strict_types=1);

namespace App\Modules\Product\Infrastructure\Persistence;

use PDO;

class MysqlProductVariantRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    /**
     * Cari variant produk berdasarkan kombinasi attribute_value_id yang dipilih user.
     * $attributeValueIds harus berisi SEMUA attribute value yang dipilih (mis. [warna_id, ukuran_id]).
     *
     * FIX: sebelumnya query ini membaca dari tabel `product_variant_attribute_values`
     * dengan kolom `product_variant_id` — tabel/kolom itu TIDAK PERNAH diisi
     * data oleh kode manapun di project ini. Semua variant yang dibuat lewat
     * admin panel (ProductService::generateVariantCombinations() ->
     * MysqlProductRepository::attachVariantAttributeValue()) disimpan ke
     * tabel `variant_attribute_values` dengan kolom `variant_id` — itu yang
     * juga dipakai konsisten di getVariantsRaw(). Query di sini disamakan
     * supaya mencari di tabel yang BENAR-BENAR berisi data.
     */
    public function findVariantByAttributeValues(int $productId, array $attributeValueIds): ?array
    {
        if (empty($attributeValueIds)) {
            return null;
        }

        sort($attributeValueIds); // urutan harus konsisten
        $count = count($attributeValueIds);

        $placeholders = implode(',', array_fill(0, $count, '?'));

        $sql = "
            SELECT pv.*
            FROM product_variants pv
            JOIN variant_attribute_values vav ON vav.variant_id = pv.id
            WHERE pv.product_id = ?
              AND vav.attribute_value_id IN ({$placeholders})
            GROUP BY pv.id
            HAVING COUNT(DISTINCT vav.attribute_value_id) = ?
               AND (SELECT COUNT(*) FROM variant_attribute_values WHERE variant_id = pv.id) = ?
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $productId,
            ...$attributeValueIds,
            $count,
            $count,
        ]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * FIX: sama seperti di atas, disamakan ke tabel/kolom yang benar-benar
     * dipakai di seluruh project (variant_attribute_values / variant_id),
     * konsisten dengan MysqlProductRepository::attachVariantAttributeValue().
     */
    public function attachAttributeValues(int $variantId, array $attributeValueIds): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO variant_attribute_values (variant_id, attribute_value_id, created_at, updated_at) VALUES (:variant_id, :value_id, NOW(), NOW())'
        );

        foreach ($attributeValueIds as $valueId) {
            $stmt->execute(['variant_id' => $variantId, 'value_id' => $valueId]);
        }
    }
}