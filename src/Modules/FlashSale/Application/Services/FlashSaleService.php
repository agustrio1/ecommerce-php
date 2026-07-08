<?php

declare(strict_types=1);

namespace App\Modules\FlashSale\Application\Services;

use PDO;

class FlashSaleService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    public function getActive(): ?array
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM flash_sales
             WHERE is_active = 1 AND starts_at <= NOW() AND ends_at >= NOW()
             ORDER BY id DESC LIMIT 1'
        );
        $flashSale = $stmt->fetch();

        if (!$flashSale) return null;

        $prodStmt = $this->pdo->prepare(
            'SELECT fsp.*, p.name AS product_name, p.slug, p.price AS original_price,
                (SELECT pi.path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) AS product_image
             FROM flash_sale_products fsp
             JOIN products p ON p.id = fsp.product_id
             WHERE fsp.flash_sale_id = :id AND p.deleted_at IS NULL
             ORDER BY fsp.id ASC'
        );
        $prodStmt->execute(['id' => $flashSale['id']]);

        $flashSale['products'] = $prodStmt->fetchAll();

        return $flashSale;
    }

    public function all(): array
    {
        return $this->pdo->query(
            'SELECT fs.*,
                (SELECT COUNT(*) FROM flash_sale_products fsp WHERE fsp.flash_sale_id = fs.id) AS product_count
             FROM flash_sales fs ORDER BY fs.created_at DESC'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM flash_sales WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $fs = $stmt->fetch();
        if (!$fs) return null;

        $prodStmt = $this->pdo->prepare(
            'SELECT fsp.*, p.name AS product_name, p.price AS original_price
             FROM flash_sale_products fsp
             JOIN products p ON p.id = fsp.product_id
             WHERE fsp.flash_sale_id = :id'
        );
        $prodStmt->execute(['id' => $id]);
        $fs['products'] = $prodStmt->fetchAll();

        return $fs;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO flash_sales (name, starts_at, ends_at, is_active, created_at, updated_at)
             VALUES (:name, :starts_at, :ends_at, :is_active, NOW(), NOW())'
        );
        $stmt->execute([
            'name'      => $data['name'],
            'starts_at' => $data['starts_at'],
            'ends_at'   => $data['ends_at'],
            'is_active' => isset($data['is_active']) ? 1 : 0,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function addProduct(int $flashSaleId, int $productId, float $salePrice, ?int $stockLimit = null): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO flash_sale_products (flash_sale_id, product_id, sale_price, stock_limit, sold_count, created_at, updated_at)
             VALUES (:flash_sale_id, :product_id, :sale_price, :stock_limit, 0, NOW(), NOW())
             ON DUPLICATE KEY UPDATE sale_price = :sale_price2, stock_limit = :stock_limit2'
        );
        $stmt->execute([
            'flash_sale_id'  => $flashSaleId,
            'product_id'     => $productId,
            'sale_price'     => $salePrice,
            'stock_limit'    => $stockLimit,
            'sale_price2'    => $salePrice,
            'stock_limit2'   => $stockLimit,
        ]);
    }

    public function removeProduct(int $flashSaleId, int $productId): void
    {
        $this->pdo->prepare(
            'DELETE FROM flash_sale_products WHERE flash_sale_id = :fid AND product_id = :pid'
        )->execute(['fid' => $flashSaleId, 'pid' => $productId]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM flash_sales WHERE id = :id')->execute(['id' => $id]);
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE flash_sales SET name = :name, starts_at = :starts_at, ends_at = :ends_at,
             is_active = :is_active, updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute([
            'id'        => $id,
            'name'      => $data['name'],
            'starts_at' => $data['starts_at'],
            'ends_at'   => $data['ends_at'],
            'is_active' => isset($data['is_active']) ? 1 : 0,
        ]);
    }

    /**
     * Ambil harga flash sale aktif untuk banyak produk sekaligus (anti N+1).
     *
     * @param int[] $productIds
     */
    public function getActivePricesForProducts(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $phs = implode(',', array_fill(0, count($productIds), '?'));

        $stmt = $this->pdo->prepare(
            "SELECT fsp.product_id, fsp.sale_price, fsp.stock_limit, fsp.sold_count
             FROM flash_sale_products fsp
             JOIN flash_sales fs ON fs.id = fsp.flash_sale_id
             WHERE fs.is_active = 1
             AND fs.starts_at <= NOW()
             AND fs.ends_at >= NOW()
             AND fsp.product_id IN ({$phs})
             ORDER BY fsp.sale_price ASC"
        );
        $stmt->execute(array_values($productIds));

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $pid = (int) $row['product_id'];
            if (! isset($result[$pid])) {
                $result[$pid] = [
                    'sale_price'   => (float) $row['sale_price'],
                    'stock_limit'  => $row['stock_limit'] ? (int) $row['stock_limit'] : null,
                    'sold_count'   => (int) $row['sold_count'],
                    'is_exhausted' => $row['stock_limit'] !== null && $row['sold_count'] >= (int) $row['stock_limit'],
                ];
            }
        }

        return $result;
    }

    /**
     * Cek harga flash sale untuk satu produk.
     * Return null kalau tidak ada flash sale aktif untuk produk ini.
     */
    public function getActivePriceForProduct(int $productId): ?array
    {
        $result = $this->getActivePricesForProducts([$productId]);
        return $result[$productId] ?? null;
    }

    /**
     * Tambah sold_count untuk produk flash sale yang aktif, SECARA ATOMIC,
     * sebanyak $quantity sekaligus (bukan +1 per panggilan berulang).
     *
     * SEBELUMNYA: tidak ada method ini sama sekali di codebase — sold_count
     * tidak pernah bertambah walau ada transaksi terjadi, sehingga fitur
     * "stok terbatas flash sale" (is_exhausted, progress "Terjual X/Y")
     * secara fungsional tidak pernah bekerja.
     *
     * RACE CONDITION FIX: kondisi "belum melebihi stock_limit" dicek DI
     * DALAM WHERE clause UPDATE yang sama (bukan SELECT dulu baru UPDATE
     * terpisah). Ini membuat "cek + tambah" jadi satu operasi atomic di
     * level database. Kalau 2 checkout untuk produk flash sale yang sama
     * terjadi bersamaan saat sisa kuota tinggal sedikit, MySQL akan
     * menjalankan UPDATE-UPDATE itu secara berurutan (row-level locking
     * bawaan UPDATE), sehingga tidak mungkin dua-duanya lolos menembus
     * stock_limit.
     *
     * @return bool true kalau berhasil (masih dalam kuota, atau memang
     *              tidak ada stock_limit / bukan flash sale aktif untuk
     *              produk ini — dianggap "tidak perlu dibatasi"), false
     *              kalau flash sale ada tapi kuota sudah/akan terlampaui.
     */
    public function incrementSoldCount(int $productId, int $quantity): bool
    {
        if ($quantity < 1) {
            return true;
        }

        // UPDATE ... JOIN memastikan kondisi "flash sale sedang aktif"
        // (fs.is_active, starts_at, ends_at) ikut dicek dalam operasi
        // atomic yang sama, bukan dipisah jadi query SELECT sebelumnya.
        $stmt = $this->pdo->prepare(
            'UPDATE flash_sale_products fsp
             INNER JOIN flash_sales fs ON fs.id = fsp.flash_sale_id
             SET fsp.sold_count = fsp.sold_count + :quantity,
                 fsp.updated_at = NOW()
             WHERE fsp.product_id = :product_id
               AND fs.is_active = 1
               AND fs.starts_at <= NOW()
               AND fs.ends_at >= NOW()
               AND (fsp.stock_limit IS NULL OR fsp.sold_count + :quantity2 <= fsp.stock_limit)'
        );

        $stmt->execute([
            'quantity'    => $quantity,
            'quantity2'   => $quantity,
            'product_id'  => $productId,
        ]);

        if ($stmt->rowCount() > 0) {
            return true;
        }

        // rowCount() == 0 bisa berarti dua hal: (a) memang tidak ada flash
        // sale aktif untuk produk ini sama sekali (bukan flash sale item),
        // atau (b) flash sale ada tapi kuota akan terlampaui. Kita perlu
        // bedakan supaya item non-flash-sale tidak salah dianggap gagal.
        $checkStmt = $this->pdo->prepare(
            'SELECT fsp.id
             FROM flash_sale_products fsp
             INNER JOIN flash_sales fs ON fs.id = fsp.flash_sale_id
             WHERE fsp.product_id = :product_id
               AND fs.is_active = 1
               AND fs.starts_at <= NOW()
               AND fs.ends_at >= NOW()
             LIMIT 1'
        );
        $checkStmt->execute(['product_id' => $productId]);

        // Kalau memang tidak ada flash sale aktif untuk produk ini,
        // rowCount() 0 di atas itu wajar (bukan gagal) — return true.
        // Kalau ADA flash sale aktif tapi UPDATE tetap gagal, berarti
        // benar-benar kehabisan kuota — return false.
        return $checkStmt->fetch() === false;
    }
    
    /**
     * Kebalikan dari incrementSoldCount() — dipakai saat order dibatalkan.
     * Cocokkan produk ke flash sale aktif via product_id, TANPA syarat harga
     * harus sama persis dengan sale_price SAAT INI (soalnya flash sale bisa
     * saja sudah tidak aktif lagi ketika pembatalan terjadi belakangan) —
     * cukup cocokkan ke flash_sale_products yang product_id-nya sama dan
     * $price yang diberikan sama dengan sale_price yang tersimpan di baris
     * itu (harga historis, bukan status aktif sekarang).
     */
    public function decrementSoldCount(int $productId, int $quantity, float $price): void
    {
        if ($quantity < 1) {
            return;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE flash_sale_products
             SET sold_count = GREATEST(0, sold_count - :quantity),
                 updated_at = NOW()
             WHERE product_id = :product_id
               AND sale_price = :price'
        );
        $stmt->execute([
            'quantity'   => $quantity,
            'product_id' => $productId,
            'price'      => $price,
        ]);
    }
}