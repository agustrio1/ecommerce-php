<?php

declare(strict_types=1);

namespace App\Core\Database;

use PDO;

/**
 * Seeder
 *
 * Base class abstract untuk semua seeder.
 * Tiap seeder wajib implement run() untuk insert data awal.
 */
abstract class Seeder
{
    protected PDO $pdo;

    public function __construct()
    {
        $this->pdo = ConnectionManager::getInstance()->connection();
    }

    abstract public function run(): void;

    /**
     * Helper insert cepat dengan return last insert id.
     */
    protected function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_map(fn ($c) => "`{$c}`", array_keys($data)));
        $placeholders = implode(', ', array_map(fn ($c) => ":{$c}", array_keys($data)));

        $stmt = $this->pdo->prepare("INSERT INTO `{$table}` ({$columns}) VALUES ({$placeholders})");
        $stmt->execute($data);

        return (int) $this->pdo->lastInsertId();
    }
}