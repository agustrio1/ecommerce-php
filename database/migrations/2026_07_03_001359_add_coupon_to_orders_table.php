<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void {
        $pdo = \App\Core\Database\ConnectionManager::getInstance()->connection();
        try { $pdo->exec('ALTER TABLE orders ADD COLUMN coupon_code VARCHAR(50) NULL AFTER notes'); } catch(\Throwable $e) {}
        try { $pdo->exec('ALTER TABLE orders ADD COLUMN coupon_discount DECIMAL(15,2) DEFAULT 0 AFTER coupon_code'); } catch(\Throwable $e) {}
    }
    public function down(): void {}
};
