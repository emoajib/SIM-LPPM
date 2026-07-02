<?php

namespace App\Console\Commands;

use App\Services\DatabaseRestoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidateMySqlColumnOrders extends Command
{
    protected $signature = 'mysql-column-orders:validate
        {--connection=mysql : Database connection for information_schema queries}';

    protected $description = 'Validate config/restore-column-orders.php matches MySQL information_schema.COLUMNS';

    public function handle(DatabaseRestoreService $restoreService): int
    {
        $connection = $this->option('connection');
        $expectedOrders = $restoreService->getMysqlColumnOrders();

        if (empty($expectedOrders)) {
            $this->warn('No column orders found.');

            return 1;
        }

        $failures = 0;

        foreach ($expectedOrders as $table => $expectedColumns) {
            try {
                $actualColumns = DB::connection($connection)
                    ->table('information_schema.COLUMNS')
                    ->where('TABLE_SCHEMA', DB::connection($connection)->getDatabaseName())
                    ->where('TABLE_NAME', $table)
                    ->orderBy('ORDINAL_POSITION')
                    ->pluck('COLUMN_NAME')
                    ->toArray();
            } catch (\Throwable $e) {
                $this->error("[{$table}] Query failed: ".$e->getMessage());
                $failures++;

                continue;
            }

            if (empty($actualColumns)) {
                $this->warn("[{$table}] Table not found, skipping.");

                continue;
            }

            if ($expectedColumns === $actualColumns) {
                $this->info("  [{$table}] OK");
            } else {
                $failures++;
                $this->error("[{$table}] MISMATCH");

                $onlyInExpected = array_diff($expectedColumns, $actualColumns);
                $onlyInActual = array_diff($actualColumns, $expectedColumns);

                if ($onlyInExpected) {
                    $this->line('    Expected only: '.implode(', ', $onlyInExpected));
                }
                if ($onlyInActual) {
                    $this->line('    MySQL only: '.implode(', ', $onlyInActual));
                }
                if (empty($onlyInExpected) && empty($onlyInActual)) {
                    $this->line('    Expected: '.implode(', ', $expectedColumns));
                    $this->line('    MySQL:    '.implode(', ', $actualColumns));
                }
            }
        }

        if ($failures === 0) {
            $this->info('All tables match MySQL column order.');

            return 0;
        }

        $this->error("{$failures} table(s) mismatch.");

        return 1;
    }
}
