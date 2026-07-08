<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

// create_flash_sales_table
return new class extends Migration {
    public function up(): void {
        Schema::create('flash_sales', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('flash_sales'); }
};
