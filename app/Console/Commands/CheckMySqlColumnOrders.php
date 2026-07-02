<?php

namespace App\Console\Commands;

use App\Console\Commands\Traits\ParsesMigrationColumns;
use App\Services\DatabaseRestoreService;
use Illuminate\Console\Command;
use ReflectionClass;

class CheckMySqlColumnOrders extends Command
{
    use ParsesMigrationColumns;

    protected $signature = 'mysql-column-orders:check
        {--table= : Check a specific table only}
        {--strict : Also fail on untracked tables}';

    protected $description = 'Validate $mysqlColumnOrders sync with migration files (offline, no DB needed)';

    public function handle(): int
    {
        $currentOrders = $this->getCurrentColumnOrders();
        $migrationFiles = $this->getSortedMigrationFiles();
        $generatedOrders = $this->buildColumnOrders($migrationFiles);

        $tableFilter = $this->option('table');
        if ($tableFilter) {
            $currentOrders = isset($currentOrders[$tableFilter])
                ? [$tableFilter => $currentOrders[$tableFilter]]
                : [];
            $generatedOrders = isset($generatedOrders[$tableFilter])
                ? [$tableFilter => $generatedOrders[$tableFilter]]
                : [];

            if (empty($generatedOrders)) {
                $this->error("Table '{$tableFilter}' not found in migrations.");

                return 1;
            }
        }

        $hasMismatch = false;

        foreach ($currentOrders as $table => $current) {
            $generated = $generatedOrders[$table] ?? [];

            if ($current === $generated) {
                continue;
            }

            $hasMismatch = true;
            $this->warn("Mismatch: {$table}");

            $missingFromCurrent = array_diff($generated, $current);
            if (! empty($missingFromCurrent)) {
                $this->line('  + Missing (add): '.implode(', ', $missingFromCurrent));
            }

            $extraInCurrent = array_diff($current, $generated);
            if (! empty($extraInCurrent)) {
                $this->line('  - Extra (remove): '.implode(', ', $extraInCurrent));
            }

            $orderedCurrent = array_values(array_intersect($current, $generated));
            $orderedGenerated = array_values(array_intersect($generated, $current));
            if ($orderedCurrent !== $orderedGenerated) {
                $this->line('  ~ Expected order: '.implode(', ', $orderedGenerated));
                $this->line('  ~ Current order:  '.implode(', ', $orderedCurrent));
            }

            $this->line('');
        }

        $untrackedTables = array_diff(array_keys($generatedOrders), array_keys($currentOrders));
        if (! empty($untrackedTables)) {
            $this->warn(count($untrackedTables).' untracked tables (not in $mysqlColumnOrders):');
            foreach ($untrackedTables as $t) {
                $this->line('  - '.$t);
            }
            $this->line('(These are informational only — not all tables need column order tracking.)');
            $this->line('');
        }

        $obsoleteTables = array_diff(array_keys($currentOrders), array_keys($generatedOrders));
        if (! empty($obsoleteTables)) {
            $this->warn('Tables in $mysqlColumnOrders but not in migrations: '.implode(', ', $obsoleteTables));
            $hasMismatch = true;
        }

        if (! $hasMismatch) {
            $trackedCount = count($currentOrders);
            $this->info("OK — {$trackedCount} tracked tables in sync.");

            return 0;
        }

        $this->warn('Fix tracked table mismatches before deploying.');

        return 1;
    }

    private function getCurrentColumnOrders(): array
    {
        $reflection = new ReflectionClass(DatabaseRestoreService::class);
        $defaults = $reflection->getDefaultProperties();

        return $defaults['mysqlColumnOrders'] ?? [];
    }
}
