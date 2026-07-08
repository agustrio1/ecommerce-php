<?php

declare(strict_types=1);

use App\Core\Database\Migration;

return new class extends Migration {
    public function up(): void
    {
        $pdo = db();

        $indexes = [
            ['products', 'slug', 'idx_products_slug'],
            ['products', 'status', 'idx_products_status'],
            ['products', 'sku', 'idx_products_sku'],
            ['cart_items', 'cart_id', 'idx_cart_items_cart_id'],
            ['cart_items', 'variant_id', 'idx_cart_items_variant_id'],
            ['carts', 'session_id', 'idx_carts_session_id'],
            ['carts', 'user_id', 'idx_carts_user_id'],
            ['orders', 'order_number', 'idx_orders_order_number'],
            ['orders', 'user_id', 'idx_orders_user_id'],
            ['orders', 'status', 'idx_orders_status'],
            ['order_items', 'order_id', 'idx_order_items_order_id'],
            ['order_items', 'product_id', 'idx_order_items_product_id'],
            ['product_categories', 'product_id', 'idx_product_categories_product_id'],
            ['product_categories', 'category_id', 'idx_product_categories_category_id'],
            ['flash_sale_products', 'product_id', 'idx_fsp_product_id'],
            ['payments', 'order_id', 'idx_payments_order_id'],
            ['payments', 'payment_no', 'idx_payments_payment_no'],
            ['payments', 'ipaymu_trx_id', 'idx_payments_ipaymu_trx_id'],
        ];

        foreach ($indexes as [$table, $column, $indexName]) {
            $exists = $pdo->query(
                "SHOW INDEX FROM `{$table}` WHERE Key_name = '{$indexName}'"
            )->fetch();

            if (! $exists) {
                $pdo->exec("ALTER TABLE `{$table}` ADD INDEX `{$indexName}` (`{$column}`)");
                echo "  Index {$indexName} ditambahkan ke {$table}.{$column}\n";
            } else {
                echo "  Index {$indexName} sudah ada, dilewati.\n";
            }
        }
    }

    public function down(): void
    {
        $pdo = db();

        $indexes = [
            ['products', 'idx_products_slug'],
            ['products', 'idx_products_status'],
            ['products', 'idx_products_sku'],
            ['cart_items', 'idx_cart_items_cart_id'],
            ['cart_items', 'idx_cart_items_variant_id'],
            ['carts', 'idx_carts_session_id'],
            ['carts', 'idx_carts_user_id'],
            ['orders', 'idx_orders_order_number'],
            ['orders', 'idx_orders_user_id'],
            ['orders', 'idx_orders_status'],
            ['order_items', 'idx_order_items_order_id'],
            ['order_items', 'idx_order_items_product_id'],
            ['product_categories', 'idx_product_categories_product_id'],
            ['product_categories', 'idx_product_categories_category_id'],
            ['flash_sale_products', 'idx_fsp_product_id'],
            ['payments', 'idx_payments_order_id'],
            ['payments', 'idx_payments_payment_no'],
            ['payments', 'idx_payments_ipaymu_trx_id'],
        ];

        foreach ($indexes as [$table, $indexName]) {
            $pdo->exec("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
        }
    }
};