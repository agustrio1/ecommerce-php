<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variant_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id');
            $table->foreignId('attribute_value_id');
            $table->timestamps();

            $table->foreign('product_variant_id')->references('id')->on('product_variants')->cascadeOnDelete();
            $table->foreign('attribute_value_id')->references('id')->on('attribute_values')->cascadeOnDelete();
            $table->unique(['product_variant_id', 'attribute_value_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_attribute_values');
    }
};