<?php

namespace App\Console\Commands;

use App\Console\Commands\Traits\ParsesMigrationColumns;
use Illuminate\Console\Command;

class GenerateMySqlColumnOrders extends Command
{
    use ParsesMigrationColumns;

    protected $signature = 'mysql-column-orders:generate
        {--table= : Generate for a specific table only}';

    protected $description = 'Generate $mysqlColumnOrders array from migration files (offline, no DB needed)';

    public function handle(): int
    {
        $migrations = $this->getSortedMigrationFiles();
        $columnOrders = $this->buildColumnOrders($migrations);

        $tableFilter = $this->option('table');
        if ($tableFilter) {
            if (! isset($columnOrders[$tableFilter])) {
                $this->error("Table '{$tableFilter}' not found in migrations.");
                $this->line('Available tables: '.implode(', ', array_keys($columnOrders)));

                return 1;
            }
            $columnOrders = [$tableFilter => $columnOrders[$tableFilter]];
        }

        $this->line($this->formatAsPhpArray($columnOrders));

        return 0;
    }

    private function formatAsPhpArray(array $columnOrders): string
    {
        if (empty($columnOrders)) {
            return '[]';
        }

        $output = "[\n";

        foreach ($columnOrders as $table => $columns) {
            $output .= "        '{$table}' => [\n";
            $colLines = [];
            $currentLine = '            ';
            $lineLen = strlen($currentLine);

            foreach ($columns as $i => $col) {
                $item = "'{$col}'";
                if ($i < count($columns) - 1) {
                    $item .= ', ';
                } else {
                    $item .= ',';
                }

                if ($lineLen + strlen($item) > 100) {
                    $colLines[] = $currentLine;
                    $currentLine = '            '.$item;
                    $lineLen = strlen($currentLine);
                } else {
                    $currentLine .= $item;
                    $lineLen += strlen($item);
                }
            }

            if ($currentLine !== '            ') {
                $colLines[] = $currentLine;
            }

            foreach ($colLines as $line) {
                $output .= "{$line}\n";
            }

            $output .= "        ],\n";
        }

        $output .= '    ]';

        return $output;
    }
}
