<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Presentation\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use PDO;

class NotificationController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    /**
     * Endpoint polling — dipanggil setiap 30 detik dari admin panel.
     * Return JSON dengan badge counts untuk navbar admin.
     */
    public function poll(Request $request): Response
    {
        $toProcess = (int) $this->pdo->query(
            'SELECT COUNT(*) FROM orders WHERE status IN ("paid", "processing") AND deleted_at IS NULL'
        )->fetchColumn();

        $pendingPayment = (int) $this->pdo->query(
            'SELECT COUNT(*) FROM orders WHERE status = "waiting_payment" AND deleted_at IS NULL'
        )->fetchColumn();

        $lowStock = (int) $this->pdo->query(
            'SELECT COUNT(*) FROM product_variants pv
             JOIN products p ON p.id = pv.product_id
             WHERE pv.stock <= 5 AND pv.is_active = 1 AND p.deleted_at IS NULL'
        )->fetchColumn();

        $newOrders = (int) $this->pdo->query(
            'SELECT COUNT(*) FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) AND deleted_at IS NULL'
        )->fetchColumn();

        return Response::json([
            'to_process'      => $toProcess,
            'pending_payment' => $pendingPayment,
            'low_stock'       => $lowStock,
            'new_orders'      => $newOrders,
            'total_alerts'    => $toProcess + $pendingPayment + $lowStock,
        ]);
    }
}