<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Presentation\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use PDO;

class ExportController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    /**
     * Export order ke CSV.
     */
    public function orders(Request $request): Response
    {
        $status    = $request->query('status', '');
        $dateFrom  = $request->query('date_from', date('Y-m-01'));
        $dateTo    = $request->query('date_to', date('Y-m-d'));

        $where  = ['o.deleted_at IS NULL'];
        $params = [];

        if ($status !== '') {
            $where[] = 'o.status = :status';
            $params['status'] = $status;
        }

        if ($dateFrom) {
            $where[] = 'DATE(o.created_at) >= :date_from';
            $params['date_from'] = $dateFrom;
        }

        if ($dateTo) {
            $where[] = 'DATE(o.created_at) <= :date_to';
            $params['date_to'] = $dateTo;
        }

        $whereSql = implode(' AND ', $where);

        $stmt = $this->pdo->prepare(
            "SELECT
                o.order_number, o.status, o.recipient_name, o.recipient_phone,
                o.shipping_city, o.shipping_province, o.shipping_postal_code,
                o.courier_company, o.courier_type,
                o.subtotal, o.shipping_cost, o.discount, o.total,
                p.payment_method, p.payment_channel, p.status AS payment_status,
                s.waybill_id,
                o.notes, o.created_at
             FROM orders o
             LEFT JOIN payments p ON p.order_id = o.id
             LEFT JOIN shipments s ON s.order_id = o.id
             WHERE {$whereSql}
             ORDER BY o.created_at DESC"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $filename = 'orders_' . date('Ymd_His') . '.csv';

        $output = fopen('php://temp', 'r+');

        // Header CSV
        fputcsv($output, [
            'No. Order', 'Status Order', 'Nama Penerima', 'Telepon',
            'Kota', 'Provinsi', 'Kode Pos',
            'Kurir', 'Layanan', 'Subtotal', 'Ongkir', 'Diskon', 'Total',
            'Metode Bayar', 'Channel Bayar', 'Status Bayar',
            'Waybill/Resi', 'Catatan', 'Tanggal Order',
        ]);

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['order_number'],
                $row['status'],
                $row['recipient_name'],
                $row['recipient_phone'],
                $row['shipping_city'],
                $row['shipping_province'],
                $row['shipping_postal_code'],
                strtoupper($row['courier_company'] ?? ''),
                $row['courier_type'] ?? '',
                $row['subtotal'],
                $row['shipping_cost'],
                $row['discount'],
                $row['total'],
                strtoupper($row['payment_method'] ?? ''),
                strtoupper($row['payment_channel'] ?? ''),
                $row['payment_status'] ?? '',
                $row['waybill_id'] ?? '',
                $row['notes'] ?? '',
                $row['created_at'],
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return Response::make($csv, 200)
            ->withHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->withHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->withHeader('Content-Length', (string) strlen($csv));
    }

    /**
     * Export produk ke CSV.
     */
    public function products(Request $request): Response
    {
        $stmt = $this->pdo->query(
            'SELECT
                p.sku, p.name, p.status, p.price, p.compare_price,
                p.weight, p.length, p.width, p.height,
                pv.sku AS variant_sku, pv.stock,
                COALESCE(
                    (SELECT GROUP_CONCAT(av.value SEPARATOR " / ")
                     FROM variant_attribute_values vav
                     JOIN attribute_values av ON av.id = vav.attribute_value_id
                     WHERE vav.variant_id = pv.id),
                    "Default"
                ) AS variant_label,
                p.created_at
             FROM products p
             JOIN product_variants pv ON pv.product_id = p.id
             WHERE p.deleted_at IS NULL
             ORDER BY p.name, pv.id'
        );
        $rows = $stmt->fetchAll();

        $filename = 'products_' . date('Ymd_His') . '.csv';

        $output = fopen('php://temp', 'r+');

        fputcsv($output, [
            'SKU Produk', 'Nama Produk', 'Status',
            'Harga Jual', 'Harga Coret', 'Berat (g)',
            'Panjang (cm)', 'Lebar (cm)', 'Tinggi (cm)',
            'SKU Varian', 'Varian', 'Stok', 'Tanggal Dibuat',
        ]);

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['sku'],
                $row['name'],
                $row['status'],
                $row['price'],
                $row['compare_price'] ?? '',
                $row['weight'] ?? '',
                $row['length'] ?? '',
                $row['width'] ?? '',
                $row['height'] ?? '',
                $row['variant_sku'],
                $row['variant_label'],
                $row['stock'],
                $row['created_at'],
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return Response::make($csv, 200)
            ->withHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->withHeader('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Export stock movement ke CSV.
     */
    public function inventory(Request $request): Response
    {
        $dateFrom = $request->query('date_from', date('Y-m-01'));
        $dateTo   = $request->query('date_to', date('Y-m-d'));

        $stmt = $this->pdo->prepare(
            'SELECT
                sm.created_at, p.name AS product_name, pv.sku, sm.type,
                sm.quantity, sm.stock_before, sm.stock_after,
                sm.reason, o.order_number, u.name AS created_by, sm.note
             FROM stock_movements sm
             JOIN product_variants pv ON pv.id = sm.variant_id
             JOIN products p ON p.id = sm.product_id
             LEFT JOIN orders o ON o.id = sm.order_id
             LEFT JOIN users u ON u.id = sm.created_by
             WHERE DATE(sm.created_at) BETWEEN :date_from AND :date_to
             ORDER BY sm.created_at DESC'
        );
        $stmt->execute(['date_from' => $dateFrom, 'date_to' => $dateTo]);
        $rows = $stmt->fetchAll();

        $filename = 'inventory_' . date('Ymd_His') . '.csv';

        $output = fopen('php://temp', 'r+');

        fputcsv($output, [
            'Tanggal', 'Produk', 'SKU', 'Tipe', 'Jumlah',
            'Stok Sebelum', 'Stok Sesudah', 'Alasan', 'No. Order', 'Oleh', 'Catatan',
        ]);

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['created_at'],
                $row['product_name'],
                $row['sku'],
                $row['type'],
                $row['quantity'],
                $row['stock_before'],
                $row['stock_after'],
                $row['reason'],
                $row['order_number'] ?? '',
                $row['created_by'] ?? 'Sistem',
                $row['note'] ?? '',
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return Response::make($csv, 200)
            ->withHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->withHeader('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}