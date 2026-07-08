<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id');

            // Referensi produk (nullable — produk boleh dihapus, tapi history tetap ada)
            $table->foreignId('product_id')->nullable();
            $table->foreignId('variant_id')->nullable();

            // Snapshot data produk saat checkout
            $table->string('product_name', 200);
            $table->string('variant_label', 200)->nullable();
            $table->string('product_sku', 100);
            $table->decimal('price', 15, 2);
            $table->integer('quantity');
            $table->decimal('subtotal', 15, 2);

            // Snapshot dimensi (dipakai Biteship untuk kalkulasi ongkir)
            $table->decimal('weight', 10, 2)->nullable();
            $table->integer('length')->default(0);
            $table->integer('width')->default(0);
            $table->integer('height')->default(0);

            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};