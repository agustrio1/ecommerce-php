<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void {
        $pdo = \App\Core\Database\ConnectionManager::getInstance()->connection();
        try { $pdo->exec('ALTER TABLE products ADD COLUMN meta_title VARCHAR(100) NULL AFTER description'); } catch(\Throwable $e) {}
        try { $pdo->exec('ALTER TABLE products ADD COLUMN meta_description VARCHAR(200) NULL AFTER meta_title'); } catch(\Throwable $e) {}
        try { $pdo->exec('ALTER TABLE products ADD COLUMN meta_keywords VARCHAR(200) NULL AFTER meta_description'); } catch(\Throwable $e) {}
    }
    public function down(): void {}
};
