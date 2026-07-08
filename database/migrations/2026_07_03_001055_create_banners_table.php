<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

// create_banners_table
return new class extends Migration {
    public function up(): void {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('subtitle', 200)->nullable();
            $table->string('image_path', 255)->nullable();
            $table->string('button_text', 50)->nullable();
            $table->string('button_url', 255)->nullable();
            $table->string('bg_color', 20)->default('#f97316');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('banners'); }
};