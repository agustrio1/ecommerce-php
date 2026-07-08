<?php

declare(strict_types=1);

namespace App\Core\Database;

use PDO;
use PDOException;
use RuntimeException;

final class ConnectionManager
{
    private static ?ConnectionManager $instance = null;

    /** @var array<string, PDO> */
    private array $connections = [];

    /** @var array<string, array> */
    private array $config;

    private string $defaultConnection;

    private function __construct()
    {
        $this->config            = config('database.connections', []);
        $this->defaultConnection = config('database.default', 'mysql');
    }

    private function __clone(): void
    {
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function connection(?string $name = null): PDO
    {
        $name = $name ?? $this->defaultConnection;

        if (! isset($this->connections[$name])) {
            $this->connections[$name] = $this->createConnection($name);
        }

        return $this->connections[$name];
    }

    private function createConnection(string $name): PDO
    {
        if (! isset($this->config[$name])) {
            throw new RuntimeException("Konfigurasi koneksi database [{$name}] tidak ditemukan di config/database.php");
        }

        $cfg = $this->config[$name];

        try {
            return $this->connectWithDatabase($cfg);
        } catch (PDOException $e) {
            if ($this->isUnknownDatabaseError($e)) {
                $this->createDatabaseIfMissing($cfg);

                try {
                    return $this->connectWithDatabase($cfg);
                } catch (PDOException $retryException) {
                    throw new RuntimeException(
                        "Database [{$cfg['database']}] berhasil dibuat tapi gagal connect ulang: " . $retryException->getMessage(),
                        (int) $retryException->getCode(),
                        $retryException
                    );
                }
            }

            throw new RuntimeException(
                "Gagal konek ke database [{$name}]: " . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }

    private function connectWithDatabase(array $cfg): PDO
    {
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $cfg['driver'] ?? 'mysql',
            $cfg['host'] ?? '127.0.0.1',
            $cfg['port'] ?? '3306',
            $cfg['database'] ?? '',
            $cfg['charset'] ?? 'utf8mb4'
        );

        return new PDO(
            $dsn,
            $cfg['username'] ?? 'root',
            $cfg['password'] ?? '',
            $cfg['options'] ?? []
        );
    }

    private function isUnknownDatabaseError(PDOException $e): bool
    {
        return str_contains($e->getMessage(), '1049')
            || str_contains(strtolower($e->getMessage()), 'unknown database');
    }

    private function createDatabaseIfMissing(array $cfg): void
    {
        $dsnWithoutDb = sprintf(
            '%s:host=%s;port=%s;charset=%s',
            $cfg['driver'] ?? 'mysql',
            $cfg['host'] ?? '127.0.0.1',
            $cfg['port'] ?? '3306',
            $cfg['charset'] ?? 'utf8mb4'
        );

        $pdo = new PDO(
            $dsnWithoutDb,
            $cfg['username'] ?? 'root',
            $cfg['password'] ?? '',
            $cfg['options'] ?? []
        );

        $database  = $cfg['database'] ?? '';
        $charset   = $cfg['charset'] ?? 'utf8mb4';
        $collation = $cfg['collation'] ?? 'utf8mb4_unicode_ci';

        if ($database === '') {
            throw new RuntimeException('Nama database kosong, tidak bisa auto-create. Cek DB_DATABASE di .env');
        }

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET {$charset} COLLATE {$collation}");

        echo "Database [{$database}] belum ada, berhasil dibuat otomatis.\n";
    }

    public function disconnect(?string $name = null): void
    {
        $name = $name ?? $this->defaultConnection;
        unset($this->connections[$name]);
    }

    public function disconnectAll(): void
    {
        $this->connections = [];
    }

    public function isConnected(?string $name = null): bool
    {
        $name = $name ?? $this->defaultConnection;

        return isset($this->connections[$name]);
    }
}