<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

// create_flash_sale_products_table
return new class extends Migration {
    public function up(): void {
        Schema::create('flash_sale_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_sale_id');
            $table->foreignId('product_id');
            $table->decimal('sale_price', 15, 2);
            $table->integer('stock_limit')->nullable();
            $table->integer('sold_count')->default(0);
            $table->timestamps();

            $table->foreign('flash_sale_id')->references('id')->on('flash_sales')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->uniqueCombo(['flash_sale_id', 'product_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('flash_sale_products'); }
};
