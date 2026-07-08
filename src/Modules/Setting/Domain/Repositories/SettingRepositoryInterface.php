<?php

declare(strict_types=1);

namespace App\Modules\Setting\Domain\Repositories;

interface SettingRepositoryInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value, string $group = 'general'): void;

    public function setMany(array $data, string $group = 'general'): void;

    /** @return array<string, mixed> */
    public function getByGroup(string $group): array;

    public function all(): array;
}