<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

final class MakeMigrationCommand
{
    public function handle(array $args = []): int
    {
        $name = $args[0] ?? null;

        if (! $name) {
            echo "Nama migration wajib diisi. Contoh:\n";
            echo "  php cli make:migration create_products_table\n";
            return 1;
        }

        $name = strtolower(trim($name));
        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_{$name}.php";
        $path = base_path('database/migrations/' . $fileName);

        $className = $this->toStudlyCase($name);

        if (preg_match('/^create_(.+)_table$/', $name, $m)) {
            $table = $m[1];
            $stub = $this->createTableStub($className, $table);
        } elseif (preg_match('/^(?:add|drop)_.+_(?:to|from)_(.+)_table$/', $name, $m)) {
            $table = $m[1];
            $stub = $this->alterTableStub($className, $table);
        } else {
            $stub = $this->blankStub($className);
        }

        file_put_contents($path, $stub);

        echo "Migration berhasil dibuat: database/migrations/{$fileName}\n";

        return 0;
    }

    private function toStudlyCase(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $name)));
    }

    private function createTableStub(string $className, string $table): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$table}', function (Blueprint \$table) {
            \$table->id();
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$table}');
    }
};

PHP;
    }

    private function alterTableStub(string $className, string $table): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('{$table}', function (Blueprint \$table) {
            //
        });
    }

    public function down(): void
    {
        Schema::table('{$table}', function (Blueprint \$table) {
            //
        });
    }
};

PHP;
    }

    private function blankStub(string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Schema;

return new class extends Migration
{
    public function up(): void
    {
        //
    }

    public function down(): void
    {
        //
    }
};

PHP;
    }
}