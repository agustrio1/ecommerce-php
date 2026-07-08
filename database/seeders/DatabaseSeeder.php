<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        (new RolePermissionSeeder())->run();
        echo "  - Roles & Permissions ter-seed.\n";

        (new SettingSeeder())->run();
    }
}