<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\Database\Migrator;

final class MigrateCommand
{
    public function handle(): int
    {
        $migrator = new Migrator();

        echo "Menjalankan migration...\n";

        $executed = $migrator->migrate();

        if (empty($executed)) {
            echo "Tidak ada migration baru. Semua sudah up to date.\n";
            return 0;
        }

        foreach ($executed as $name) {
            echo "  Migrated: {$name}\n";
        }

        echo "\n" . count($executed) . " migration berhasil dijalankan.\n";

        return 0;
    }
}