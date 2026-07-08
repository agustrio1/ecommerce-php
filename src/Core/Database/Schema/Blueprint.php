<?php

declare(strict_types=1);

namespace App\Core\Database\Schema;

class Blueprint
{
    public string $table;
    public array $columns = [];
    public array $indexes = [];
    public array $commands = [];

    public string $engine = 'InnoDB';
    public string $charset = 'utf8mb4';
    public string $collation = 'utf8mb4_unicode_ci';

    private ?string $lastColumnName = null;
    private ?string $pendingForeignColumn = null;

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function id(string $name = 'id'): static
    {
        $this->columns[] = "`{$name}` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY";
        $this->lastColumnName = $name;
        return $this;
    }

    public function uuid(string $name = 'uuid'): static
    {
        return $this->addColumn($name, "CHAR(36)");
    }

    public function string(string $name, int $length = 255): static
    {
        return $this->addColumn($name, "VARCHAR({$length})");
    }

    public function text(string $name): static
    {
        return $this->addColumn($name, "TEXT");
    }

    public function longText(string $name): static
    {
        return $this->addColumn($name, "LONGTEXT");
    }

    public function char(string $name, int $length = 1): static
    {
        return $this->addColumn($name, "CHAR({$length})");
    }

    public function integer(string $name): static
    {
        return $this->addColumn($name, "INT");
    }

    public function bigInteger(string $name): static
    {
        return $this->addColumn($name, "BIGINT");
    }

    public function unsignedBigInteger(string $name): static
    {
        return $this->addColumn($name, "BIGINT UNSIGNED");
    }

    public function unsignedInteger(string $name): static
    {
        return $this->addColumn($name, "INT UNSIGNED");
    }

    public function tinyInteger(string $name): static
    {
        return $this->addColumn($name, "TINYINT");
    }

    public function smallInteger(string $name): static
    {
        return $this->addColumn($name, "SMALLINT");
    }

    public function decimal(string $name, int $precision = 10, int $scale = 2): static
    {
        return $this->addColumn($name, "DECIMAL({$precision},{$scale})");
    }

    public function float(string $name, int $precision = 8, int $scale = 2): static
    {
        return $this->addColumn($name, "FLOAT({$precision},{$scale})");
    }

    public function boolean(string $name): static
    {
        return $this->addColumn($name, "TINYINT(1)");
    }

    public function date(string $name): static
    {
        return $this->addColumn($name, "DATE");
    }

    public function dateTime(string $name): static
    {
        return $this->addColumn($name, "DATETIME");
    }

    public function timestamp(string $name): static
    {
        return $this->addColumn($name, "TIMESTAMP NULL");
    }

    public function time(string $name): static
    {
        return $this->addColumn($name, "TIME");
    }

    public function timestamps(): static
    {
        $this->columns[] = "`created_at` TIMESTAMP NULL DEFAULT NULL";
        $this->columns[] = "`updated_at` TIMESTAMP NULL DEFAULT NULL";
        return $this;
    }

    public function softDeletes(string $name = 'deleted_at'): static
    {
        $this->columns[] = "`{$name}` TIMESTAMP NULL DEFAULT NULL";
        return $this;
    }

    public function json(string $name): static
    {
        return $this->addColumn($name, "JSON");
    }

    public function enum(string $name, array $values): static
    {
        $escaped = implode(',', array_map(fn ($v) => "'" . addslashes($v) . "'", $values));
        return $this->addColumn($name, "ENUM({$escaped})");
    }

    public function foreignId(string $name): static
    {
        return $this->addColumn($name, "BIGINT UNSIGNED");
    }

    public function constrained(?string $table = null, string $column = 'id'): static
    {
        $columnName = $this->lastColumnName;

        if ($table === null && $columnName !== null) {
            $table = $this->guessTableName($columnName);
        }

        $this->indexes[] = "FOREIGN KEY (`{$columnName}`) REFERENCES `{$table}`(`{$column}`)";
        return $this;
    }

    public function references(string $column): static
    {
        $this->pendingForeignColumn = $column;
        return $this;
    }

    public function on(string $table): static
    {
        $columnName = $this->lastColumnName;
        $refColumn  = $this->pendingForeignColumn ?? 'id';

        $this->indexes[] = "FOREIGN KEY (`{$columnName}`) REFERENCES `{$table}`(`{$refColumn}`)";
        return $this;
    }

    public function cascadeOnDelete(): static
    {
        $this->appendToLastIndex('ON DELETE CASCADE');
        return $this;
    }

    public function nullOnDelete(): static
    {
        $this->appendToLastIndex('ON DELETE SET NULL');
        return $this;
    }

    public function restrictOnDelete(): static
    {
        $this->appendToLastIndex('ON DELETE RESTRICT');
        return $this;
    }

    public function cascadeOnUpdate(): static
    {
        $this->appendToLastIndex('ON UPDATE CASCADE');
        return $this;
    }

    private function appendToLastIndex(string $clause): void
    {
        $lastKey = array_key_last($this->indexes);
        if ($lastKey !== null) {
            $this->indexes[$lastKey] .= ' ' . $clause;
        }
    }

    private function guessTableName(string $columnName): string
    {
        $base = preg_replace('/_id$/', '', $columnName);

        if (str_ends_with($base, 'y')) {
            return substr($base, 0, -1) . 'ies';
        }

        return $base . 's';
    }

    public function nullable(): static
    {
        $this->modifyLastColumn(fn ($def) => preg_replace('/ NOT NULL/', '', $def) . ' NULL');
        return $this;
    }

    public function default(mixed $value): static
    {
        $formatted = match (true) {
            is_string($value) => "'" . addslashes($value) . "'",
            is_bool($value)   => $value ? '1' : '0',
            is_null($value)   => 'NULL',
            default            => (string) $value,
        };

        $this->modifyLastColumn(fn ($def) => $def . " DEFAULT {$formatted}");
        return $this;
    }

    public function unique(): static
    {
        if ($this->lastColumnName !== null) {
            $this->indexes[] = "UNIQUE KEY `{$this->table}_{$this->lastColumnName}_unique` (`{$this->lastColumnName}`)";
        }
        return $this;
    }

    public function index(): static
    {
        if ($this->lastColumnName !== null) {
            $this->indexes[] = "KEY `{$this->table}_{$this->lastColumnName}_index` (`{$this->lastColumnName}`)";
        }
        return $this;
    }

    public function unsigned(): static
    {
        $this->modifyLastColumn(fn ($def) => str_contains($def, 'UNSIGNED') ? $def : $def . ' UNSIGNED');
        return $this;
    }

    public function comment(string $text): static
    {
        $this->modifyLastColumn(fn ($def) => $def . " COMMENT '" . addslashes($text) . "'");
        return $this;
    }

    public function after(string $column): static
    {
        $this->modifyLastColumn(fn ($def) => $def . " AFTER `{$column}`");
        return $this;
    }

    public function dropColumn(string|array $columns): static
    {
        foreach ((array) $columns as $col) {
            $this->commands[] = "DROP COLUMN `{$col}`";
        }
        return $this;
    }

    public function renameColumn(string $from, string $to): static
    {
        $this->commands[] = "RENAME COLUMN `{$from}` TO `{$to}`";
        return $this;
    }

    private function addColumn(string $name, string $type): static
    {
        $this->columns[] = "`{$name}` {$type} NOT NULL";
        $this->lastColumnName = $name;
        return $this;
    }

    private function modifyLastColumn(callable $callback): void
    {
        $lastKey = array_key_last($this->columns);
        if ($lastKey !== null) {
            $this->columns[$lastKey] = $callback($this->columns[$lastKey]);
        }
    }

    public function toCreateSql(): string
    {
        $definitions = array_merge($this->columns, $this->indexes);
        $body = implode(",\n  ", $definitions);

        return "CREATE TABLE `{$this->table}` (\n  {$body}\n) ENGINE={$this->engine} DEFAULT CHARSET={$this->charset} COLLATE={$this->collation};";
    }

    public function toAlterSql(): string
    {
        $alters = [];

        foreach ($this->columns as $col) {
            $alters[] = "ADD COLUMN {$col}";
        }

        foreach ($this->indexes as $idx) {
            $alters[] = "ADD {$idx}";
        }

        foreach ($this->commands as $cmd) {
            $alters[] = $cmd;
        }

        $body = implode(",\n  ", $alters);

        return "ALTER TABLE `{$this->table}`\n  {$body};";
    }
    
    /**
     * Definisikan foreign key untuk kolom tertentu secara eksplisit
     * (tidak harus kolom yang baru saja ditambahkan via addColumn()).
     * Berguna saat semua kolom sudah didefinisikan duluan, baru constraint-nya menyusul.
     *
     * Contoh:
     *   $table->foreignId('role_id');
     *   $table->foreignId('permission_id');
     *   $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
     *   $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
     */
    public function foreign(string $column): static
    {
        $this->lastColumnName = $column;
        $this->pendingForeignColumn = null;

        return $this;
    }

    /**
     * Tambahkan UNIQUE constraint gabungan beberapa kolom sekaligus.
     * Contoh: $table->uniqueCombo(['role_id', 'permission_id']);
     */
    public function uniqueCombo(array $columns): static
    {
        $columnList = implode('`, `', $columns);
        $indexName  = $this->table . '_' . implode('_', $columns) . '_unique';

        $this->indexes[] = "UNIQUE KEY `{$indexName}` (`{$columnList}`)";

        return $this;
    }
}