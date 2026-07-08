<?php

declare(strict_types=1);

namespace App\Core\Database;

use PDO;

final class Migrator
{
    private PDO $pdo;
    private string $migrationsPath;
    private string $migrationsTable = 'migrations';

    public function __construct(?string $migrationsPath = null)
    {
        $this->pdo = ConnectionManager::getInstance()->connection();
        $this->migrationsPath = $migrationsPath ?? base_path('database/migrations');

        $this->ensureMigrationsTableExists();
    }

    private function ensureMigrationsTableExists(): void
    {
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS `{$this->migrationsTable}` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `migration` VARCHAR(255) NOT NULL,
                `batch` INT NOT NULL,
                `executed_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
        );
    }

    private function getRanMigrations(): array
    {
        $stmt = $this->pdo->query("SELECT migration FROM `{$this->migrationsTable}` ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getAllMigrationFiles(): array
    {
        if (! is_dir($this->migrationsPath)) {
            return [];
        }

        $files = glob($this->migrationsPath . '/*.php') ?: [];
        sort($files);

        return array_map(fn ($file) => basename($file, '.php'), $files);
    }

    private function getNextBatchNumber(): int
    {
        $stmt = $this->pdo->query("SELECT MAX(batch) FROM `{$this->migrationsTable}`");
        $max = $stmt->fetchColumn();

        return $max ? ((int) $max + 1) : 1;
    }

    private function resolve(string $migrationName): Migration
    {
        $path = $this->migrationsPath . '/' . $migrationName . '.php';

        if (! file_exists($path)) {
            throw new \RuntimeException("File migration tidak ditemukan: {$path}");
        }

        if (! class_exists(Migration::class)) {
            require_once __DIR__ . '/Migration.php';
        }

        $instance = require $path;

        if (! $instance instanceof Migration) {
            throw new \RuntimeException("File migration [{$migrationName}] harus me-return instance Migration (anonymous class).");
        }

        return $instance;
    }

    public function migrate(): array
    {
        $ran     = $this->getRanMigrations();
        $all     = $this->getAllMigrationFiles();
        $pending = array_values(array_diff($all, $ran));

        if (empty($pending)) {
            return [];
        }

        $batch = $this->getNextBatchNumber();
        $executed = [];

        foreach ($pending as $migrationName) {
            $migration = $this->resolve($migrationName);
            $migration->up();

            $stmt = $this->pdo->prepare(
                "INSERT INTO `{$this->migrationsTable}` (migration, batch) VALUES (:migration, :batch)"
            );
            $stmt->execute(['migration' => $migrationName, 'batch' => $batch]);

            $executed[] = $migrationName;
        }

        return $executed;
    }

    public function rollback(): array
    {
        $stmt = $this->pdo->query("SELECT MAX(batch) FROM `{$this->migrationsTable}`");
        $lastBatch = (int) $stmt->fetchColumn();

        if ($lastBatch === 0) {
            return [];
        }

        $stmt = $this->pdo->prepare(
            "SELECT migration FROM `{$this->migrationsTable}` WHERE batch = :batch ORDER BY id DESC"
        );
        $stmt->execute(['batch' => $lastBatch]);
        $migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($migrations as $migrationName) {
            $migration = $this->resolve($migrationName);
            $migration->down();

            $del = $this->pdo->prepare("DELETE FROM `{$this->migrationsTable}` WHERE migration = :migration");
            $del->execute(['migration' => $migrationName]);
        }

        return $migrations;
    }

    public function rollbackAll(): array
    {
        $ran = array_reverse($this->getRanMigrations());

        foreach ($ran as $migrationName) {
            $migration = $this->resolve($migrationName);
            $migration->down();

            $del = $this->pdo->prepare("DELETE FROM `{$this->migrationsTable}` WHERE migration = :migration");
            $del->execute(['migration' => $migrationName]);
        }

        return $ran;
    }

    public function fresh(): array
    {
        $this->dropAllTables();
        $this->ensureMigrationsTableExists();

        return $this->migrate();
    }

    private function dropAllTables(): void
    {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0;');

        $stmt = $this->pdo->query('SHOW TABLES');
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $this->pdo->exec("DROP TABLE IF EXISTS `{$table}`;");
        }

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function status(): array
    {
        $ranStmt = $this->pdo->query("SELECT migration, batch FROM `{$this->migrationsTable}` ORDER BY id ASC");
        $ranRows = $ranStmt->fetchAll(PDO::FETCH_ASSOC);

        $ranMap = [];
        foreach ($ranRows as $row) {
            $ranMap[$row['migration']] = (int) $row['batch'];
        }

        $all = $this->getAllMigrationFiles();
        $result = [];

        foreach ($all as $migrationName) {
            $result[] = [
                'migration' => $migrationName,
                'status'    => isset($ranMap[$migrationName]) ? 'Ran' : 'Pending',
                'batch'     => $ranMap[$migrationName] ?? null,
            ];
        }

        return $result;
    }
}