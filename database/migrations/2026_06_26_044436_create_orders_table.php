<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Identifikasi
            $table->string('order_number', 50)->unique();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('address_id')->nullable();

            // Status order
            $table->enum('status', [
                'pending',           // baru dibuat, belum bayar
                'waiting_payment',   // menunggu pembayaran
                'paid',              // sudah dibayar
                'processing',        // sedang diproses toko
                'shipped',           // sudah dikirim
                'delivered',         // sudah diterima
                'completed',         // selesai (buyer konfirmasi)
                'cancelled',         // dibatalkan
                'refunded',          // sudah direfund
            ])->default('pending');

            // Harga
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            // Kurir (dari Biteship Rates API response)
            $table->string('courier_company', 50)->nullable();
            $table->string('courier_type', 50)->nullable();
            $table->string('courier_service_name', 100)->nullable();

            // Snapshot alamat tujuan (disimpan saat checkout agar tidak berubah jika user edit alamat)
            $table->string('recipient_name', 150)->nullable();
            $table->string('recipient_phone', 20)->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('shipping_district', 100)->nullable();
            $table->string('shipping_city', 100)->nullable();
            $table->string('shipping_province', 100)->nullable();
            $table->string('shipping_postal_code', 10)->nullable();
            $table->string('shipping_area_id', 100)->nullable();
            $table->decimal('shipping_latitude', 10, 7)->nullable();
            $table->decimal('shipping_longitude', 10, 7)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('address_id')->references('id')->on('addresses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};