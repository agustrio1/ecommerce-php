<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $pdo = \App\Core\Database\ConnectionManager::getInstance()->connection();

        $indexes = [
            'ALTER TABLE orders ADD INDEX IF NOT EXISTS idx_orders_status (status)',
            'ALTER TABLE orders ADD INDEX IF NOT EXISTS idx_orders_user_id (user_id)',
            'ALTER TABLE orders ADD INDEX IF NOT EXISTS idx_orders_created_at (created_at)',
            'ALTER TABLE order_items ADD INDEX IF NOT EXISTS idx_order_items_product_id (product_id)',
            'ALTER TABLE payments ADD INDEX IF NOT EXISTS idx_payments_order_id (order_id)',
            'ALTER TABLE payments ADD INDEX IF NOT EXISTS idx_payments_payment_no (payment_no)',
            'ALTER TABLE payments ADD INDEX IF NOT EXISTS idx_payments_status (status)',
            'ALTER TABLE shipments ADD INDEX IF NOT EXISTS idx_shipments_order_id (order_id)',
            'ALTER TABLE shipments ADD INDEX IF NOT EXISTS idx_shipments_biteship_order_id (biteship_order_id)',
            'ALTER TABLE cart_items ADD INDEX IF NOT EXISTS idx_cart_items_cart_id (cart_id)',
            'ALTER TABLE product_variants ADD INDEX IF NOT EXISTS idx_variants_product_id (product_id)',
            'ALTER TABLE product_variants ADD INDEX IF NOT EXISTS idx_variants_stock (stock)',
            'ALTER TABLE reviews ADD INDEX IF NOT EXISTS idx_reviews_product_id (product_id)',
            'ALTER TABLE stock_movements ADD INDEX IF NOT EXISTS idx_sm_variant_id (variant_id)',
            'ALTER TABLE stock_movements ADD INDEX IF NOT EXISTS idx_sm_created_at (created_at)',
            'ALTER TABLE webhook_logs ADD INDEX IF NOT EXISTS idx_wl_source_reference (source, reference)',
            'ALTER TABLE webhook_logs ADD INDEX IF NOT EXISTS idx_wl_status (status)',
        ];

        foreach ($indexes as $sql) {
            try {
                $pdo->exec($sql);
            } catch (\Throwable $e) {
                // Skip kalau index sudah ada
            }
        }
    }

    public function down(): void
    {
        // Skip drop indexes — tidak kritis
    }
};