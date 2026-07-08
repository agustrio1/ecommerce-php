<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Services;

use App\Core\Exceptions\ValidationException;
use PDO;

class InventoryService
{
    private PDO $pdo;

    private const LOW_STOCK_THRESHOLD = 5;

    public function __construct()
    {
        $this->pdo = db();
    }

    /**
     * Catat pergerakan stok dan update stock di product_variants.
     * Ini SATU-SATUNYA tempat yang boleh mengubah product_variants.stock,
     * supaya history selalu konsisten.
     *
     * PENTING (race condition fix):
     * - SELECT baris variant sekarang pakai "FOR UPDATE", yang mengunci
     *   baris itu sampai transaksi selesai (commit/rollback). Kalau ada
     *   request lain yang juga mencoba recordMovement() untuk variant yang
     *   SAMA di waktu bersamaan, request kedua akan MENUNGGU sampai
     *   request pertama selesai — bukan baca stok basi lalu dua-duanya
     *   lolos mengurangi stok yang sama. Ini mencegah oversell saat banyak
     *   orang checkout produk yang sama secara bersamaan.
     * - FOR UPDATE hanya efektif mengunci kalau dijalankan DI DALAM sebuah
     *   transaksi (bukan autocommit). Makanya sekarang transaksi dibuka
     *   SEBELUM SELECT (dulu dibuka SESUDAH SELECT, yang membuat lock
     *   tidak berguna).
     * - Untuk tipe 'out', kalau stok hasil akhir akan negatif, method ini
     *   SEKARANG MELEMPAR ValidationException alih-alih diam-diam meng-
     *   clamp ke 0. Sebelumnya, order tetap bisa terbentuk walau stok
     *   sebenarnya tidak cukup (silent oversell) karena stockAfter di-
     *   paksa jadi 0 tanpa pernah gagal.
     *
     * Aman dipanggil baik standalone maupun dari dalam transaksi yang sudah
     * berjalan (mis. dari OrderService::createFromCart). Kalau PDO sudah
     * dalam transaksi, method ini TIDAK akan beginTransaction/commit lagi —
     * cukup numpang di transaksi yang sudah ada, dan row lock FOR UPDATE
     * akan tetap berlaku sampai transaksi terluar itu commit/rollback.
     */
    public function recordMovement(
        int $variantId,
        string $type,
        int $quantity,
        string $reason,
        ?int $orderId = null,
        ?int $userId = null,
        ?string $note = null
    ): void {
        $ownsTransaction = ! $this->pdo->inTransaction();

        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            // FOR UPDATE: kunci baris ini sampai transaksi selesai, supaya
            // request lain yang juga mau ubah stok variant yang sama harus
            // antre, tidak baca nilai stok yang sama-sama basi.
            $variant = $this->pdo->prepare(
                'SELECT * FROM product_variants WHERE id = :id LIMIT 1 FOR UPDATE'
            );
            $variant->execute(['id' => $variantId]);
            $variantRow = $variant->fetch();

            if (! $variantRow) {
                throw new ValidationException(['variant' => 'Varian tidak ditemukan.']);
            }

            $stockBefore = (int) $variantRow['stock'];

            $stockAfter = match ($type) {
                'in'         => $stockBefore + $quantity,
                'out'        => $stockBefore - $quantity,
                'adjustment' => $quantity, // quantity di sini = nilai stok baru langsung
                default      => $stockBefore,
            };

            // Untuk 'out' (mis. order baru), stok gak boleh sampai minus —
            // dan ini WAJIB gagal (bukan di-clamp diam-diam), supaya order
            // yang stoknya gak cukup benar-benar gagal dan transaksi
            // pemanggil (mis. OrderService::createFromCart) ikut rollback.
            if ($type === 'out' && $stockAfter < 0) {
                throw new ValidationException([
                    'stock' => "Stok tidak cukup untuk SKU {$variantRow['sku']}. Tersedia: {$stockBefore}, diminta: {$quantity}.",
                ]);
            }

            $stockAfter = max(0, $stockAfter);

            // Update stock di variant
            $update = $this->pdo->prepare(
                'UPDATE product_variants SET stock = :stock, updated_at = NOW() WHERE id = :id'
            );
            $update->execute(['stock' => $stockAfter, 'id' => $variantId]);

            // Insert movement log
            $insert = $this->pdo->prepare(
                'INSERT INTO stock_movements (
                    variant_id, product_id, type, quantity,
                    stock_before, stock_after, reason, order_id, created_by, note,
                    created_at, updated_at
                ) VALUES (
                    :variant_id, :product_id, :type, :quantity,
                    :stock_before, :stock_after, :reason, :order_id, :created_by, :note,
                    NOW(), NOW()
                )'
            );
            $insert->execute([
                'variant_id'   => $variantId,
                'product_id'   => $variantRow['product_id'],
                'type'         => $type,
                'quantity'     => $type === 'adjustment' ? ($stockAfter - $stockBefore) : $quantity,
                'stock_before' => $stockBefore,
                'stock_after'  => $stockAfter,
                'reason'       => $reason,
                'order_id'     => $orderId,
                'created_by'   => $userId,
                'note'         => $note,
            ]);

            if ($ownsTransaction) {
                $this->pdo->commit();
            }
        } catch (\Throwable $e) {
            if ($ownsTransaction) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Manual restock dari admin.
     */
    public function restock(int $variantId, int $quantity, ?int $userId = null, ?string $note = null): void
    {
        if ($quantity <= 0) {
            throw new ValidationException(['quantity' => 'Jumlah restock harus lebih dari 0.']);
        }

        $this->recordMovement($variantId, 'in', $quantity, 'restock', null, $userId, $note);
    }

    /**
     * Manual adjustment — set stok langsung ke nilai tertentu (mis. setelah stock opname).
     */
    public function adjustStock(int $variantId, int $newStock, ?int $userId = null, ?string $note = null): void
    {
        if ($newStock < 0) {
            throw new ValidationException(['stock' => 'Stok tidak boleh negatif.']);
        }

        $this->recordMovement($variantId, 'adjustment', $newStock, 'manual_adjustment', null, $userId, $note);
    }

    /**
     * Ambil history movement untuk satu variant.
     */
    public function getMovementHistory(int $variantId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare(
            'SELECT sm.*, u.name AS created_by_name, o.order_number
             FROM stock_movements sm
             LEFT JOIN users u ON u.id = sm.created_by
             LEFT JOIN orders o ON o.id = sm.order_id
             WHERE sm.variant_id = :variant_id
             ORDER BY sm.created_at DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':variant_id', $variantId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Ambil semua produk dengan stok rendah (untuk alert/dashboard).
     */
    public function getLowStockVariants(int $threshold = self::LOW_STOCK_THRESHOLD): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT pv.id AS variant_id, pv.sku, pv.stock, p.id AS product_id, p.name AS product_name,
                    COALESCE(
                        (SELECT GROUP_CONCAT(av.value SEPARATOR " / ")
                         FROM variant_attribute_values vav
                         JOIN attribute_values av ON av.id = vav.attribute_value_id
                         WHERE vav.variant_id = pv.id),
                        "Default"
                    ) AS variant_label
             FROM product_variants pv
             JOIN products p ON p.id = pv.product_id
             WHERE pv.stock <= :threshold AND pv.is_active = 1 AND p.deleted_at IS NULL
             ORDER BY pv.stock ASC'
        );
        $stmt->execute(['threshold' => $threshold]);

        return $stmt->fetchAll();
    }

    public function countLowStock(int $threshold = self::LOW_STOCK_THRESHOLD): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM product_variants pv
             JOIN products p ON p.id = pv.product_id
             WHERE pv.stock <= :threshold AND pv.is_active = 1 AND p.deleted_at IS NULL'
        );
        $stmt->execute(['threshold' => $threshold]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Ambil semua variant dengan info produk (untuk halaman inventory utama).
     */
    public function getAllVariants(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $where  = ['p.deleted_at IS NULL'];
        $params = [];

        if (! empty($filters['search'])) {
            $where[] = '(p.name LIKE :search OR pv.sku LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (! empty($filters['low_stock'])) {
            $where[] = 'pv.stock <= :threshold';
            $params['threshold'] = self::LOW_STOCK_THRESHOLD;
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM product_variants pv JOIN products p ON p.id = pv.product_id WHERE {$whereSql}"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            "SELECT pv.id AS variant_id, pv.sku, pv.stock, pv.is_active,
                    p.id AS product_id, p.name AS product_name,
                    COALESCE(
                        (SELECT GROUP_CONCAT(av.value SEPARATOR ' / ')
                         FROM variant_attribute_values vav
                         JOIN attribute_values av ON av.id = vav.attribute_value_id
                         WHERE vav.variant_id = pv.id),
                        'Default'
                    ) AS variant_label
             FROM product_variants pv
             JOIN products p ON p.id = pv.product_id
             WHERE {$whereSql}
             ORDER BY pv.stock ASC, p.name ASC
             LIMIT :limit OFFSET :offset"
        );

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['data' => $stmt->fetchAll(), 'total' => $total];
    }
}