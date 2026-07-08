<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\Database\Migrator;

final class StatusCommand
{
    public function handle(): int
    {
        $migrator = new Migrator();
        $rows = $migrator->status();

        if (empty($rows)) {
            echo "Belum ada file migration di database/migrations.\n";
            return 0;
        }

        printf("%-50s %-10s %s\n", 'Migration', 'Status', 'Batch');
        echo str_repeat('-', 70) . "\n";

        foreach ($rows as $row) {
            $status = $row['status'] === 'Ran' ? 'Ran' : 'Pending';
            $batch  = $row['batch'] ?? '-';

            printf("%-50s %-10s %s\n", $row['migration'], $status, $batch);
        }

        return 0;
    }
}