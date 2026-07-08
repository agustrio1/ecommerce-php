<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\Database\Migrator;

final class FreshCommand
{
    public function handle(array $args = []): int
    {
        if (! in_array('--force', $args, true)) {
            echo "Perintah ini akan MENGHAPUS SEMUA TABEL di database.\n";
            echo "Ketik 'yes' untuk melanjutkan (atau jalankan dengan --force untuk skip konfirmasi): ";

            $handle = fopen('php://stdin', 'r');
            $confirmation = trim(fgets($handle));
            fclose($handle);

            if (strtolower($confirmation) !== 'yes') {
                echo "Dibatalkan.\n";
                return 1;
            }
        }

        $migrator = new Migrator();

        echo "Menghapus semua tabel dan menjalankan ulang migration...\n";

        $executed = $migrator->fresh();

        foreach ($executed as $name) {
            echo "  Migrated: {$name}\n";
        }

        echo "\nDatabase fresh, " . count($executed) . " migration berhasil dijalankan.\n";

        return 0;
    }
}