<?php

declare(strict_types=1);

namespace App\Core\Database\Schema;

use App\Core\Database\ConnectionManager;
use PDO;

class Schema
{
    private static function pdo(): PDO
    {
        return ConnectionManager::getInstance()->connection();
    }

    public static function create(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        self::pdo()->exec($blueprint->toCreateSql());
    }

    public static function table(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        self::pdo()->exec($blueprint->toAlterSql());
    }

    public static function dropIfExists(string $table): void
    {
        self::pdo()->exec("DROP TABLE IF EXISTS `{$table}`;");
    }

    public static function drop(string $table): void
    {
        self::pdo()->exec("DROP TABLE `{$table}`;");
    }

    public static function hasTable(string $table): bool
    {
        $stmt = self::pdo()->prepare(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table"
        );
        $stmt->execute(['table' => $table]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public static function hasColumn(string $table, string $column): bool
    {
        $stmt = self::pdo()->prepare(
            "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table AND column_name = :column"
        );
        $stmt->execute(['table' => $table, 'column' => $column]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public static function disableForeignKeyChecks(): void
    {
        self::pdo()->exec('SET FOREIGN_KEY_CHECKS=0;');
    }

    public static function enableForeignKeyChecks(): void
    {
        self::pdo()->exec('SET FOREIGN_KEY_CHECKS=1;');
    }
}