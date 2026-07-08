<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\Database\Migrator;

final class RollbackCommand
{
    public function handle(array $args = []): int
    {
        $migrator = new Migrator();

        if (in_array('--all', $args, true)) {
            echo "Rollback SEMUA migration...\n";
            $rolledBack = $migrator->rollbackAll();
        } else {
            echo "Rollback batch terakhir...\n";
            $rolledBack = $migrator->rollback();
        }

        if (empty($rolledBack)) {
            echo "Tidak ada migration untuk di-rollback.\n";
            return 0;
        }

        foreach ($rolledBack as $name) {
            echo "  Rolled back: {$name}\n";
        }

        echo "\n" . count($rolledBack) . " migration berhasil di-rollback.\n";

        return 0;
    }
}