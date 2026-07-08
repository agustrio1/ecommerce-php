<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Application\Services;

use PDO;

class DashboardService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    /**
     * Statistik ringkasan utama — ditampilkan di card atas dashboard.
     */
    public function getSummaryStats(): array
    {
        // Total order bulan ini
        $orderStmt = $this->pdo->query(
            'SELECT
                COUNT(*) AS total_orders,
                COUNT(CASE WHEN status NOT IN ("cancelled", "refunded") THEN 1 END) AS active_orders,
                COUNT(CASE WHEN status = "waiting_payment" THEN 1 END) AS pending_payment,
                COUNT(CASE WHEN status = "paid" OR status = "processing" THEN 1 END) AS to_process,
                COUNT(CASE WHEN MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW()) THEN 1 END) AS orders_this_month
             FROM orders WHERE deleted_at IS NULL'
        );
        $orderStats = $orderStmt->fetch();

        // Revenue
        $revenueStmt = $this->pdo->query(
            'SELECT
                COALESCE(SUM(CASE WHEN status NOT IN ("cancelled", "refunded") THEN total ELSE 0 END), 0) AS total_revenue,
                COALESCE(SUM(CASE WHEN status NOT IN ("cancelled", "refunded")
                    AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())
                    THEN total ELSE 0 END), 0) AS revenue_this_month,
                COALESCE(SUM(CASE WHEN status NOT IN ("cancelled", "refunded")
                    AND MONTH(created_at) = MONTH(NOW()) - 1 AND YEAR(created_at) = YEAR(NOW())
                    THEN total ELSE 0 END), 0) AS revenue_last_month
             FROM orders WHERE deleted_at IS NULL'
        );
        $revenueStats = $revenueStmt->fetch();

        // Total produk & customer
        $prodStmt  = $this->pdo->query('SELECT COUNT(*) FROM products WHERE deleted_at IS NULL AND status = "published"');
        $custStmt  = $this->pdo->query('SELECT COUNT(*) FROM users WHERE role_id = (SELECT id FROM roles WHERE slug = "customer" LIMIT 1)');
        $totalProd = (int) $prodStmt->fetchColumn();
        $totalCust = (int) $custStmt->fetchColumn();

        // Low stock
        $lowStmt  = $this->pdo->query('SELECT COUNT(*) FROM product_variants WHERE stock <= 5 AND is_active = 1');
        $lowStock = (int) $lowStmt->fetchColumn();

        // Revenue growth %
        $lastMonth  = (float) $revenueStats['revenue_last_month'];
        $thisMonth  = (float) $revenueStats['revenue_this_month'];
        $revenueGrowth = $lastMonth > 0
            ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1)
            : ($thisMonth > 0 ? 100 : 0);

        return [
            'total_orders'       => (int) $orderStats['total_orders'],
            'orders_this_month'  => (int) $orderStats['orders_this_month'],
            'pending_payment'    => (int) $orderStats['pending_payment'],
            'to_process'         => (int) $orderStats['to_process'],
            'total_revenue'      => (float) $revenueStats['total_revenue'],
            'revenue_this_month' => (float) $revenueStats['revenue_this_month'],
            'revenue_last_month' => (float) $revenueStats['revenue_last_month'],
            'revenue_growth'     => $revenueGrowth,
            'total_products'     => $totalProd,
            'total_customers'    => $totalCust,
            'low_stock_count'    => $lowStock,
        ];
    }

    /**
     * Data revenue per hari untuk 30 hari terakhir (untuk grafik line).
     */
    public function getRevenueChart(int $days = 30): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                DATE(created_at) AS date,
                COUNT(*) AS order_count,
                COALESCE(SUM(CASE WHEN status NOT IN ("cancelled","refunded") THEN total ELSE 0 END), 0) AS revenue
             FROM orders
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
             AND deleted_at IS NULL
             GROUP BY DATE(created_at)
             ORDER BY date ASC'
        );
        $stmt->execute(['days' => $days]);
        $rows = $stmt->fetchAll();

        // Fill missing dates supaya grafik tidak ada gap
        $result = [];
        $start  = new \DateTime("-{$days} days");
        $end    = new \DateTime('today');
        $interval = new \DateInterval('P1D');
        $period   = new \DatePeriod($start, $interval, $end->modify('+1 day'));

        $byDate = [];
        foreach ($rows as $row) {
            $byDate[$row['date']] = $row;
        }

        foreach ($period as $dt) {
            $dateStr = $dt->format('Y-m-d');
            $result[] = [
                'date'        => $dateStr,
                'date_label'  => $dt->format('d M'),
                'order_count' => (int) ($byDate[$dateStr]['order_count'] ?? 0),
                'revenue'     => (float) ($byDate[$dateStr]['revenue'] ?? 0),
            ];
        }

        return $result;
    }

    /**
     * Produk terlaris berdasarkan total qty terjual dari order items.
     */
    public function getTopProducts(int $limit = 5): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                oi.product_id,
                oi.product_name,
                SUM(oi.quantity) AS total_qty,
                SUM(oi.subtotal) AS total_revenue,
                COUNT(DISTINCT oi.order_id) AS total_orders,
                (SELECT pi.path FROM product_images pi WHERE pi.product_id = oi.product_id AND pi.is_primary = 1 LIMIT 1) AS product_image
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             WHERE o.status NOT IN ("cancelled", "refunded") AND o.deleted_at IS NULL
             GROUP BY oi.product_id, oi.product_name
             ORDER BY total_qty DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Order terbaru (untuk tabel di dashboard).
     */
    public function getRecentOrders(int $limit = 8): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT o.*,
                (SELECT p.status FROM payments p WHERE p.order_id = o.id ORDER BY p.id DESC LIMIT 1) AS payment_status
             FROM orders o
             WHERE o.deleted_at IS NULL
             ORDER BY o.created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Distribusi status order (untuk donut chart).
     */
    public function getOrderStatusDistribution(): array
    {
        $stmt = $this->pdo->query(
            'SELECT status, COUNT(*) AS count FROM orders WHERE deleted_at IS NULL GROUP BY status ORDER BY count DESC'
        );

        return $stmt->fetchAll();
    }

    /**
     * Customer baru bulan ini vs bulan lalu.
     */
    public function getCustomerStats(): array
    {
        $stmt = $this->pdo->query(
            'SELECT
                COUNT(CASE WHEN MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW()) THEN 1 END) AS new_this_month,
                COUNT(CASE WHEN MONTH(created_at) = MONTH(NOW()) - 1 AND YEAR(created_at) = YEAR(NOW()) THEN 1 END) AS new_last_month
             FROM users
             WHERE role_id = (SELECT id FROM roles WHERE slug = "customer" LIMIT 1)'
        );

        return $stmt->fetch();
    }
    
    /**
     * Laporan penjualan per periode.
     */
    public function getSalesReport(string $from, string $to, string $groupBy = 'day'): array
    {
        $groupFormat = match ($groupBy) {
            'month' => '%Y-%m',
            'week'  => '%Y-%u',
            default => '%Y-%m-%d',
        };

        $stmt = $this->pdo->prepare(
            "SELECT
                DATE_FORMAT(created_at, :format) AS period,
                COUNT(*) AS total_orders,
                COUNT(CASE WHEN status NOT IN ('cancelled','refunded') THEN 1 END) AS valid_orders,
                COALESCE(SUM(CASE WHEN status NOT IN ('cancelled','refunded') THEN total ELSE 0 END), 0) AS revenue,
                COALESCE(SUM(CASE WHEN status NOT IN ('cancelled','refunded') THEN subtotal ELSE 0 END), 0) AS subtotal,
                COALESCE(SUM(CASE WHEN status NOT IN ('cancelled','refunded') THEN shipping_cost ELSE 0 END), 0) AS shipping,
                COALESCE(SUM(CASE WHEN status NOT IN ('cancelled','refunded') THEN discount ELSE 0 END), 0) AS discount
             FROM orders
             WHERE DATE(created_at) BETWEEN :from AND :to
             AND deleted_at IS NULL
             GROUP BY DATE_FORMAT(created_at, :format2)
             ORDER BY period ASC"
        );
        $stmt->execute(['format' => $groupFormat, 'from' => $from, 'to' => $to, 'format2' => $groupFormat]);
        return $stmt->fetchAll();
    }

    /**
     * Laporan per produk.
     */
    public function getProductReport(string $from, string $to, int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                oi.product_id,
                oi.product_name,
                SUM(oi.quantity) AS total_qty,
                SUM(oi.subtotal) AS total_revenue,
                COUNT(DISTINCT oi.order_id) AS total_orders,
                AVG(oi.price) AS avg_price,
                (SELECT pi.path FROM product_images pi WHERE pi.product_id = oi.product_id AND pi.is_primary = 1 LIMIT 1) AS product_image
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             WHERE DATE(o.created_at) BETWEEN :from AND :to
             AND o.status NOT IN ('cancelled','refunded')
             AND o.deleted_at IS NULL
             GROUP BY oi.product_id, oi.product_name
             ORDER BY total_qty DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':from', $from);
        $stmt->bindValue(':to', $to);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Laporan per kategori.
     */
    public function getCategoryReport(string $from, string $to): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                c.name AS category_name,
                SUM(oi.quantity) AS total_qty,
                SUM(oi.subtotal) AS total_revenue,
                COUNT(DISTINCT oi.order_id) AS total_orders
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             JOIN product_categories pc ON pc.product_id = oi.product_id
             JOIN categories c ON c.id = pc.category_id AND c.parent_id IS NULL
             WHERE DATE(o.created_at) BETWEEN :from AND :to
             AND o.status NOT IN ('cancelled','refunded')
             AND o.deleted_at IS NULL
             GROUP BY c.id, c.name
             ORDER BY total_revenue DESC"
        );
        $stmt->execute(['from' => $from, 'to' => $to]);
        return $stmt->fetchAll();
    }

    /**
     * Summary totals untuk periode tertentu.
     */
    public function getPeriodSummary(string $from, string $to): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                COUNT(*) AS total_orders,
                COUNT(CASE WHEN status NOT IN ('cancelled','refunded') THEN 1 END) AS valid_orders,
                COUNT(CASE WHEN status = 'cancelled' OR status = 'refunded' THEN 1 END) AS cancelled_orders,
                COALESCE(SUM(CASE WHEN status NOT IN ('cancelled','refunded') THEN total ELSE 0 END), 0) AS total_revenue,
                COALESCE(SUM(CASE WHEN status NOT IN ('cancelled','refunded') THEN shipping_cost ELSE 0 END), 0) AS total_shipping,
                COALESCE(SUM(CASE WHEN status NOT IN ('cancelled','refunded') THEN discount ELSE 0 END), 0) AS total_discount,
                COALESCE(AVG(CASE WHEN status NOT IN ('cancelled','refunded') THEN total ELSE NULL END), 0) AS avg_order_value
             FROM orders
             WHERE DATE(created_at) BETWEEN :from AND :to
             AND deleted_at IS NULL"
        );
        $stmt->execute(['from' => $from, 'to' => $to]);
        return $stmt->fetch();
    }
}