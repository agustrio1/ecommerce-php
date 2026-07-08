<?php

declare(strict_types=1);

namespace App\Modules\Setting\Infrastructure\Persistence;

use App\Modules\Setting\Domain\Repositories\SettingRepositoryInterface;
use PDO;

class MysqlSettingRepository implements SettingRepositoryInterface
{
    private PDO $pdo;
    private array $cache = [];

    public function __construct()
    {
        $this->pdo = db();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $stmt = $this->pdo->prepare('SELECT value FROM settings WHERE `key` = :key LIMIT 1');
        $stmt->execute(['key' => $key]);
        $row = $stmt->fetch();

        if ($row === false) {
            return $default;
        }

        $value = $this->decode($row['value']);
        $this->cache[$key] = $value;

        return $value;
    }

    public function set(string $key, mixed $value, string $group = 'general'): void
    {
        $encoded = $this->encode($value);

        $stmt = $this->pdo->prepare(
            'INSERT INTO settings (`key`, `value`, `group`, created_at, updated_at)
             VALUES (:key, :value, :group, NOW(), NOW())
             ON DUPLICATE KEY UPDATE `value` = :value2, `group` = :group2, updated_at = NOW()'
        );
        $stmt->execute([
            'key'    => $key,
            'value'  => $encoded,
            'group'  => $group,
            'value2' => $encoded,
            'group2' => $group,
        ]);

        $this->cache[$key] = $value;
    }

    public function setMany(array $data, string $group = 'general'): void
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value, $group);
        }
    }

    public function getByGroup(string $group): array
    {
        $stmt = $this->pdo->prepare('SELECT `key`, `value` FROM settings WHERE `group` = :group');
        $stmt->execute(['group' => $group]);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $value = $this->decode($row['value']);
            $result[$row['key']] = $value;
            $this->cache[$row['key']] = $value;
        }

        return $result;
    }

    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT `key`, `value`, `group` FROM settings ORDER BY `group`, `key`');

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $value = $this->decode($row['value']);
            $result[$row['group']][$row['key']] = $value;
            $this->cache[$row['key']] = $value;
        }

        return $result;
    }

    private function encode(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function decode(string $value): mixed
    {
        $decoded = json_decode($value, true);
        return $decoded ?? $value;
    }
}