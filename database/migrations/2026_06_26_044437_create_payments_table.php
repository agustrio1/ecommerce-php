<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id');

            // Nomor referensi internal (dikirim ke iPaymu sebagai referenceId)
            $table->string('payment_no', 100)->unique();

            // iPaymu response fields
            $table->string('ipaymu_trx_id', 100)->nullable();
            $table->string('ipaymu_session_id', 100)->nullable();
            $table->string('ipaymu_reference_id', 100)->nullable();
            $table->string('ipaymu_payment_url', 500)->nullable();

            // Direct payment fields
            $table->string('ipaymu_via', 50)->nullable();
            $table->string('ipaymu_channel', 50)->nullable();
            $table->string('ipaymu_pay_code', 100)->nullable();
            $table->string('ipaymu_pay_code_url', 500)->nullable();

            // Payment detail
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_channel', 50)->nullable();
            $table->decimal('amount', 15, 2);
            $table->decimal('fee', 10, 2)->default(0);

            // Status
            $table->enum('status', [
                'pending',
                'paid',
                'failed',
                'expired',
                'cancelled',
                'refunded',
            ])->default('pending');

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();

            // Raw response iPaymu callback (untuk audit/debugging)
            $table->json('raw_callback')->nullable();

            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};