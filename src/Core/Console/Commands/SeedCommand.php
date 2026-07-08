<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

/**
 * Command: db:seed [SeederClassName]
 * Jika tidak ada argumen, jalankan Database\Seeders\DatabaseSeeder (master seeder).
 */
final class SeedCommand
{
    public function handle(array $args = []): int
    {
        $seederClass = $args[0] ?? 'Database\\Seeders\\DatabaseSeeder';

        if (! str_contains($seederClass, '\\')) {
            $seederClass = 'Database\\Seeders\\' . $seederClass;
        }

        if (! class_exists($seederClass)) {
            echo "Seeder [{$seederClass}] tidak ditemukan.\n";
            return 1;
        }

        echo "Menjalankan seeder: {$seederClass}\n";

        $seeder = new $seederClass();
        $seeder->run();

        echo "Seeder selesai dijalankan.\n";

        return 0;
    }
}