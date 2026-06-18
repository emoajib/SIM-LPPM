<?php

namespace App\Console\Commands;

use App\Enums\AdditionalOutputStatusType;
use App\Enums\AuthorStatus;
use App\Enums\IdentityType;
use App\Enums\InstitutionalReportStatus;
use App\Enums\KaprodiStatus;
use App\Enums\OutputStatusType;
use App\Enums\ProposalStatus;
use App\Enums\ReportingPeriod;
use App\Enums\ReportStatus;
use App\Enums\ReviewRecommendation;
use App\Enums\ReviewStatus;
use App\Enums\SignatureMode;
use App\Enums\TeamSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckSchemaDrift extends Command
{
    protected $signature = 'schema:drift {--database= : Database connection to use}';

    protected $description = 'Check for schema drift between migrations and database';

    public function handle(): int
    {
        $connection = $this->option('database') ?: config('database.default');
        $this->info("Checking schema drift on connection: {$connection}");

        $hasDrift = false;

        $hasDrift |= $this->checkMissingTables($connection);
        $hasDrift |= $this->checkExtraTables($connection);
        $hasDrift |= $this->checkMissingColumns($connection);
        $hasDrift |= $this->checkExtraColumns($connection);
        $hasDrift |= $this->checkMissingConstraints($connection);
        $hasDrift |= $this->checkExtraConstraints($connection);
        $hasDrift |= $this->checkEnumConstraintDrift($connection);

        if ($hasDrift) {
            $this->error('Schema drift detected!');

            return 1;
        }

        $this->info('✅ No schema drift detected');

        return 0;
    }

    private function checkMissingTables(string $connection): bool
    {
        $migrations = $this->getMigrationFiles();
        $tablesInMigrations = $this->extractTablesFromMigrations($migrations);
        $tablesInDatabase = $this->getTablesInDatabase($connection);

        $missingTables = array_diff($tablesInMigrations, $tablesInDatabase);

        if (! empty($missingTables)) {
            $this->error('Missing tables in database:');
            foreach ($missingTables as $table) {
                $this->line("  - {$table}");
            }

            return true;
        }

        return false;
    }

    private function checkExtraTables(string $connection): bool
    {
        $tablesInMigrations = $this->getTablesInMigrations();
        $tablesInDatabase = $this->getTablesInDatabase($connection);

        $extraTables = array_diff($tablesInDatabase, $tablesInMigrations);

        if (! empty($extraTables)) {
            $this->error('Extra tables in database (not in migrations):');
            foreach ($extraTables as $table) {
                $this->line("  - {$table}");
            }

            return true;
        }

        return false;
    }

    private function checkMissingColumns(string $connection): bool
    {
        $tablesInDatabase = $this->getTablesInDatabase($connection);
        $hasDrift = false;

        foreach ($tablesInDatabase as $table) {
            $columnsInMigrations = $this->getColumnsInMigrationsForTable($table);
            $columnsInDatabase = $this->getColumnsInDatabaseForTable($connection, $table);

            $missingColumns = array_diff($columnsInMigrations, $columnsInDatabase);

            if (! empty($missingColumns)) {
                $this->error("Missing columns in table '{$table}':");
                foreach ($missingColumns as $column) {
                    $this->line("  - {$column}");
                }
                $hasDrift = true;
            }
        }

        return $hasDrift;
    }

    private function checkExtraColumns(string $connection): bool
    {
        $tablesInDatabase = $this->getTablesInDatabase($connection);
        $hasDrift = false;

        foreach ($tablesInDatabase as $table) {
            $columnsInMigrations = $this->getColumnsInMigrationsForTable($table);
            $columnsInDatabase = $this->getColumnsInDatabaseForTable($connection, $table);

            $extraColumns = array_diff($columnsInDatabase, $columnsInMigrations);

            if (! empty($extraColumns)) {
                $this->error("Extra columns in table '{$table}':");
                foreach ($extraColumns as $column) {
                    $this->line("  - {$column}");
                }
                $hasDrift = true;
            }
        }

        return $hasDrift;
    }

    private function checkMissingConstraints(string $connection): bool
    {
        $tablesInDatabase = $this->getTablesInDatabase($connection);
        $hasDrift = false;

        foreach ($tablesInDatabase as $table) {
            $constraintsInMigrations = $this->getConstraintsInMigrationsForTable($table);
            $constraintsInDatabase = $this->getConstraintsInDatabaseForTable($connection, $table);

            $missingConstraints = array_diff($constraintsInMigrations, $constraintsInDatabase);

            if (! empty($missingConstraints)) {
                $this->error("Missing constraints in table '{$table}':");
                foreach ($missingConstraints as $constraint) {
                    $this->line("  - {$constraint}");
                }
                $hasDrift = true;
            }
        }

        return $hasDrift;
    }

    private function checkExtraConstraints(string $connection): bool
    {
        $tablesInDatabase = $this->getTablesInDatabase($connection);
        $hasDrift = false;

        foreach ($tablesInDatabase as $table) {
            $constraintsInMigrations = $this->getConstraintsInMigrationsForTable($table);
            $constraintsInDatabase = $this->getConstraintsInDatabaseForTable($connection, $table);

            $extraConstraints = array_diff($constraintsInDatabase, $constraintsInMigrations);

            if (! empty($extraConstraints)) {
                $this->error("Extra constraints in table '{$table}':");
                foreach ($extraConstraints as $constraint) {
                    $this->line("  - {$constraint}");
                }
                $hasDrift = true;
            }
        }

        return $hasDrift;
    }

    private function checkEnumConstraintDrift(string $connection): bool
    {
        $enumMap = $this->getEnumMap();
        $hasDrift = false;

        foreach ($enumMap as $column => $enumClass) {
            [$table, $col] = explode('.', $column);
            $constraintName = "{$table}_{$col}_check";

            try {
                $dbDriver = DB::connection($connection)->getDriverName();
                $constraint = null;

                if ($dbDriver === 'pgsql') {
                    $constraint = DB::connection($connection)->selectOne("
                        SELECT pg_get_constraintdef(oid) as def
                        FROM pg_constraint
                        WHERE conname = ? AND contype = 'c'
                    ", [$constraintName]);
                } elseif ($dbDriver === 'sqlite') {
                    $constraints = DB::connection($connection)->select("
                        SELECT sql FROM sqlite_master
                        WHERE type = 'table' AND tbl_name = ? AND sql LIKE '%CHECK%'
                    ", [$table]);

                    foreach ($constraints as $c) {
                        if (strpos($c->sql, "{$constraintName}") !== false) {
                            $constraint = (object) ['def' => $c->sql];
                            break;
                        }
                    }
                }

                if (! $constraint) {
                    $this->error("Missing CHECK constraint: {$constraintName} on {$table}.{$col}");
                    $hasDrift = true;

                    continue;
                }

                $expectedValues = $this->getEnumValues($enumClass);
                $quotedValues = array_map(fn ($v) => preg_quote($v, '/'), $expectedValues);

                if ($dbDriver === 'pgsql') {
                    $pattern = $col.".*= ANY.*ARRAY\\[.*'".implode("'.*'", $quotedValues)."'";
                } else {
                    $pattern = $col." IN \\('?".implode("', '?", $quotedValues)."'?\\)";
                }

                $expectedPattern = "/{$pattern}/";

                if (! preg_match($expectedPattern, $constraint->def)) {
                    $this->error("CHECK constraint mismatch on {$table}.{$col}");
                    $this->line('  Expected pattern: '.$expectedPattern);
                    $this->line('  Found: '.$constraint->def);
                    $hasDrift = true;
                }
            } catch (\Exception $e) {
                $this->error("Error checking enum constraint for {$table}.{$col}: ".$e->getMessage());
                $hasDrift = true;
            }
        }

        return $hasDrift;
    }

    private function getMigrationFiles(): array
    {
        $path = database_path('migrations');
        $files = glob("$path/*.php");
        $files = array_filter($files, function ($file) {
            return preg_match('/\\d{4}_\\d{2}_\\d{2}_\\d{6}_.*\\.php$/', basename($file));
        });
        sort($files);

        return $files;
    }

    private function extractTablesFromMigrations(array $migrationFiles): array
    {
        $tables = [];

        foreach ($migrationFiles as $file) {
            $content = file_get_contents($file);

            if (preg_match_all('/Schema::table\\(\\s*[\'"]([^\'"\\s]+)[\'"]/i', $content, $matches)) {
                $tables = array_merge($tables, $matches[1]);
            }
        }

        return array_unique($tables);
    }

    private function getTablesInMigrations(): array
    {
        $path = database_path('migrations');
        $files = glob("$path/*.php");
        $tables = [];

        foreach ($files as $file) {
            $content = file_get_contents($file);

            if (preg_match_all('/Schema::table\\(\\s*[\'"]([^\'"\\s]+)[\'"]/i', $content, $matches)) {
                $tables = array_merge($tables, $matches[1]);
            }
        }

        return array_unique($tables);
    }

    private function getTablesInDatabase(string $connection): array
    {
        $dbDriver = DB::connection($connection)->getDriverName();

        if ($dbDriver === 'pgsql') {
            $tables = DB::connection($connection)->select(
                "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'"
            );
        } elseif ($dbDriver === 'sqlite') {
            $tables = collect(DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"))
                ->filter(function ($table) {
                    return ! in_array($table->name, ['schema_migrations', 'migrations']);
                })
                ->values()
                ->toArray();
        } else {
            $tables = [];
        }

        return array_column($tables, 'table_name');
    }

    private function getColumnsInMigrationsForTable(string $table): array
    {
        $path = database_path('migrations');
        $files = glob("$path/*.php");
        $columns = [];

        foreach ($files as $file) {
            $content = file_get_contents($file);

            if (preg_match_all("/Schema::table\\(\\s*[\'\"](?:[^\'\"\\s]+)[\'\"],\\s*function\\(.*?\\$table\\s*=>.*?\\)\)/s", $content, $matches)) {
                $tableContent = $matches[0][0];

                if (preg_match_all('/\$table->(string|integer|bigInteger|decimal|boolean|text|json|jsonb|uuid|date|datetime|timestamp|time|float|double|char|varchar|mediumText|longText|mediumInteger|unsignedInteger|unsignedBigInteger|unsignedDecimal|unsignedFloat|unsignedDouble|unsignedMediumInteger|unsignedMediumDecimal|unsignedBigInteger|rememberToken|foreignId|morphs|belongsToMany|hasMany|hasManyThrough|hasOneThrough|hasOne|hasMany|hasManyThrough|hasOneThrough|morphTo|morphMany|morphToMany|morphOne|belongsTo|hasOneThrough|hasMany|hasManyThrough|hasOneThrough|morphTo|morphMany|morphToMany|morphOne|belongsTo|hasOneThrough|hasMany|hasManyThrough|hasOneThrough|morphTo|morphMany|morphToMany|morphOne|belongsTo)/', $tableContent, $columnMatches)) {
                    $columns = array_merge($columns, $columnMatches[1]);
                }
            }
        }

        return array_unique($columns);
    }

    private function getColumnsInDatabaseForTable(string $connection, string $table): array
    {
        $dbDriver = DB::connection($connection)->getDriverName();

        if ($dbDriver === 'pgsql') {
            $columns = DB::connection($connection)->select(
                "SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = ?",
                [$table]
            );
        } elseif ($dbDriver === 'sqlite') {
            $columns = collect(DB::connection($connection)->select("PRAGMA table_info({$table})"))
                ->map(fn ($column) => $column->name)
                ->values()
                ->toArray();
        } else {
            $columns = [];
        }

        return array_column($columns, 'column_name');
    }

    private function getConstraintsInMigrationsForTable(string $table): array
    {
        $path = database_path('migrations');
        $files = glob("$path/*.php");
        $constraints = [];

        foreach ($files as $file) {
            $content = file_get_contents($file);

            if (preg_match_all("/Schema::table\\(\\s*[\'\"](?:[^\'\"\\s]+)[\'\"],\\s*function\\(.*?\\$table\\s*=>.*?\\)\)/s", $content, $matches)) {
                $tableContent = $matches[0][0];

                if (preg_match_all('/\$table->check\\(\\s*[\'"]([^\'"\\s]+)[\'"]\\s*,\\s*[\'"]([^\'"\\s]+)[\'"]\\s*\\)/', $tableContent, $constraintMatches)) {
                    $constraints = array_merge($constraints, [
                        "{$constraintMatches[2][0]} on {$table}",
                    ]);
                }
            }
        }

        return array_unique($constraints);
    }

    private function getConstraintsInDatabaseForTable(string $connection, string $table): array
    {
        $dbDriver = DB::connection($connection)->getDriverName();

        if ($dbDriver === 'pgsql') {
            $constraints = DB::connection($connection)->select(
                "SELECT constraint_name FROM information_schema.table_constraints WHERE table_schema = 'public' AND table_name = ? AND constraint_type = 'CHECK'",
                [$table]
            );
        } elseif ($dbDriver === 'sqlite') {
            $constraints = collect(DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type = 'table' AND tbl_name = ? AND sql LIKE '%CHECK%'", [$table]))
                ->map(fn ($constraint) => $constraint->name)
                ->values()
                ->toArray();
        } else {
            $constraints = [];
        }

        return array_column($constraints, 'constraint_name');
    }

    private function getEnumMap(): array
    {
        return [
            'proposals.status' => ProposalStatus::class,
            'proposal_reviewer.status' => ReviewStatus::class,
            'proposal_reviewer.recommendation' => ReviewRecommendation::class,
            'progress_reports.status' => ReportStatus::class,
            'progress_reports.reporting_period' => ReportingPeriod::class,
            'identities.type' => IdentityType::class,
            'mandatory_outputs.status_type' => OutputStatusType::class,
            'mandatory_outputs.author_status' => AuthorStatus::class,
            'additional_outputs.status' => AdditionalOutputStatusType::class,
            'institutional_reports.status' => InstitutionalReportStatus::class,
            'kaprodi_approvals.status' => KaprodiStatus::class,
            'letters.team_source' => TeamSource::class,
            'document_signatures.mode' => SignatureMode::class,
        ];
    }

    private function getEnumValues(string $enumClass): array
    {
        if (! enum_exists($enumClass)) {
            return [];
        }

        $values = [];
        foreach ((new \ReflectionEnum($enumClass))->getCases() as $case) {
            if ($case instanceof \ReflectionEnumBackedCase) {
                $values[] = (string) $case->getBackingValue();
            }
        }

        return $values;
    }
}
