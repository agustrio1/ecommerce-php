<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use PDO;

/**
 * Hitung ulang sold_count di flash_sale_products berdasarkan order_items
 * historis, untuk transaksi yang terjadi SEBELUM fix incrementSoldCount()
 * ditambahkan (saat itu sold_count tidak pernah bertambah sama sekali).
 *
 * Cara mencocokkan sebuah order_item sebagai "bagian dari flash sale X":
 *   1. order_items.product_id  = flash_sale_products.product_id
 *   2. order_items.price       = flash_sale_products.sale_price
 *      (harga item harus persis sama dengan harga flash sale saat itu —
 *      ini best-effort; kalau ada produk yang kebetulan pernah dijual
 *      dengan harga sama tapi BUKAN lewat flash sale, ini bisa salah
 *      hitung. Tidak ada cara sempurna tanpa kolom penanda eksplisit di
 *      order_items yang menyatakan "ini dibeli via flash sale mana").
 *   3. orders.created_at berada di antara flash_sales.starts_at dan
 *      flash_sales.ends_at (transaksi terjadi selagi flash sale aktif)
 *   4. orders.status != 'cancelled' (order yang dibatalkan tidak dihitung
 *      sebagai terjual)
 *
 * PENTING: jalankan ini HANYA SEKALI setelah deploy fix incrementSoldCount().
 * Menjalankannya berkali-kali aman (idempotent) karena command ini SET
 * ulang sold_count ke hasil hitung fresh, bukan menambah-nambahkan.
 *
 * Usage: php cli flashsale:recalculate-sold
 *        php cli flashsale:recalculate-sold --dry-run   (lihat hasil tanpa menyimpan)
 */
class RecalculateFlashSaleSoldCommand
{
    public function handle(array $args): int
    {
        $dryRun = in_array('--dry-run', $args, true);
        $pdo    = db();

        echo $dryRun
            ? "=== DRY RUN — tidak ada perubahan disimpan ===\n\n"
            : "=== Menghitung ulang sold_count flash sale dari data historis ===\n\n";

        $products = $pdo->query(
            'SELECT fsp.id, fsp.flash_sale_id, fsp.product_id, fsp.sale_price, fsp.stock_limit, fsp.sold_count,
                    fs.starts_at, fs.ends_at, fs.name AS flash_sale_name, p.name AS product_name
             FROM flash_sale_products fsp
             JOIN flash_sales fs ON fs.id = fsp.flash_sale_id
             JOIN products p ON p.id = fsp.product_id
             ORDER BY fs.id ASC, fsp.id ASC'
        )->fetchAll();

        if (empty($products)) {
            echo "Tidak ada data flash_sale_products sama sekali. Tidak ada yang dihitung.\n";
            return 0;
        }

        $updated = 0;

        foreach ($products as $row) {
            $stmt = $pdo->prepare(
                'SELECT COALESCE(SUM(oi.quantity), 0) AS total_sold
                 FROM order_items oi
                 JOIN orders o ON o.id = oi.order_id
                 WHERE oi.product_id = :product_id
                   AND oi.price = :sale_price
                   AND o.status != "cancelled"
                   AND o.created_at >= :starts_at
                   AND o.created_at <= :ends_at'
            );
            $stmt->execute([
                'product_id' => $row['product_id'],
                'sale_price' => $row['sale_price'],
                'starts_at'  => $row['starts_at'],
                'ends_at'    => $row['ends_at'],
            ]);
            $totalSold = (int) $stmt->fetchColumn();

            $status = $totalSold === (int) $row['sold_count'] ? 'SAMA' : 'BERUBAH';

            printf(
                "[%s] Flash Sale #%d \"%s\" — Produk: %s (harga: %s)\n    sold_count lama: %d -> baru: %d\n",
                $status,
                $row['flash_sale_id'],
                $row['flash_sale_name'],
                $row['product_name'],
                number_format((float) $row['sale_price'], 0, ',', '.'),
                $row['sold_count'],
                $totalSold
            );

            if ($row['stock_limit'] !== null && $totalSold > (int) $row['stock_limit']) {
                printf(
                    "    PERINGATAN: total terjual (%d) melebihi stock_limit (%d) — kemungkinan ada oversell historis sebelum fix race condition diterapkan.\n",
                    $totalSold,
                    $row['stock_limit']
                );
            }

            if (! $dryRun && $totalSold !== (int) $row['sold_count']) {
                $update = $pdo->prepare(
                    'UPDATE flash_sale_products SET sold_count = :sold_count, updated_at = NOW() WHERE id = :id'
                );
                $update->execute(['sold_count' => $totalSold, 'id' => $row['id']]);
                $updated++;
            }
        }

        echo "\n=== Selesai ===\n";
        echo $dryRun
            ? "Dry run selesai. Jalankan tanpa --dry-run untuk benar-benar menyimpan perubahan.\n"
            : "Total {$updated} baris flash_sale_products di-update.\n";

        return 0;
    }
}