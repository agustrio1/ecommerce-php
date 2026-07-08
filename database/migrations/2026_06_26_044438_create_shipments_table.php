<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id');

            // Kurir & layanan
            $table->string('courier_company', 50);
            $table->string('courier_type', 50);
            $table->string('courier_service_name', 100)->nullable();

            // Biteship order response fields
            $table->string('biteship_order_id', 100)->nullable();
            $table->string('biteship_tracking_id', 100)->nullable();

            // Waybill — bisa berubah (webhook order.waybill_id)
            $table->string('waybill_id', 100)->nullable();
            $table->string('courier_tracking_link', 500)->nullable();

            // Origin (snapshot dari setting toko)
            $table->string('origin_contact_name', 150)->nullable();
            $table->string('origin_contact_phone', 20)->nullable();
            $table->text('origin_address')->nullable();
            $table->string('origin_postal_code', 10)->nullable();
            $table->string('origin_area_id', 100)->nullable();

            // Destination (snapshot dari alamat order)
            $table->string('destination_contact_name', 150)->nullable();
            $table->string('destination_contact_phone', 20)->nullable();
            $table->text('destination_address')->nullable();
            $table->string('destination_postal_code', 10)->nullable();
            $table->string('destination_area_id', 100)->nullable();

            // Biaya — bisa berubah (webhook order.price)
            $table->decimal('cost', 15, 2)->default(0);
            $table->decimal('actual_cost', 15, 2)->nullable();
            $table->decimal('weight', 10, 2)->nullable();

            // Status (sesuai Biteship tracking status)
            $table->enum('status', [
                'pending',
                'confirmed',
                'allocated',
                'picking_up',
                'picked',
                'dropping_off',
                'delivered',
                'rejected',
                'cancelled',
                'returned',
            ])->default('pending');

            $table->json('raw_response')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};