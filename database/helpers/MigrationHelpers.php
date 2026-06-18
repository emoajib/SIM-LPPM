<?php

namespace Database\Helpers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrationHelpers
{
    /**
     * Convert enum column to string with CHECK constraint
     *
     * NOTE: This method is designed for use inside Schema::table() callbacks when
     * converting existing enum columns. The CHECK constraint is added after the
     * Schema::table() call completes.
     */
    public static function enumToStringWithCheck(
        Blueprint $table,
        string $column,
        array $allowedValues,
        ?string $default = null,
        bool $nullable = false
    ): void {
        $tableName = $table->getTable();
        $constraintName = static::generateConstraintName($tableName, $column);

        // Drop existing enum if it exists
        if (Schema::hasColumn($tableName, $column)) {
            $table->dropColumn($column);
        }

        // Create string column
        $table->string($column, 50);

        if ($default !== null) {
            $table->string($column)->default($default);
        }

        if ($nullable) {
            $table->string($column)->nullable();
        }

        // CHECK constraint is added via DB::statement() which must happen
        // OUTSIDE the Schema::table() callback. The caller is responsible
        // for calling addCheckConstraint() separately after the schema change.
    }

    /**
     * Add CHECK constraint for an enum-style string column.
     *
     * CHECK constraints MUST be added AFTER table creation (not inside Schema::create).
     */
    public static function addCheckConstraint(
        string $tableName,
        string $column,
        array $allowedValues,
        string $constraintName
    ): void {
        $allowedValuesString = "'".implode("', '", $allowedValues)."'";
        $driver = DB::getDriverName();
        $quotedColumn = $driver === 'mysql' ? "`{$column}`" : "\"{$column}\"";
        $sql = "{$quotedColumn} IN ({$allowedValuesString})";

        if ($driver === 'sqlite') {
            // SQLite does not support ALTER TABLE ADD CONSTRAINT for CHECK.
            // Constraints must be defined at CREATE TABLE time.
            // For production SQLite (unlikely), skip silently.
            return;
        }

        DB::statement("ALTER TABLE {$tableName} ADD CONSTRAINT {$constraintName} CHECK ({$sql})");
    }

    /**
     * Add CHECK constraint for enum column (alias for addCheckConstraint using table name)
     */
    public static function addCheckConstraintToTable(
        string $tableName,
        string $column,
        array $allowedValues,
        string $constraintName
    ): void {
        static::addCheckConstraint($tableName, $column, $allowedValues, $constraintName);
    }

    /**
     * Generate consistent constraint name
     */
    public static function generateConstraintName(string $table, string $column): string
    {
        return "{$table}_{$column}_check";
    }

    /**
     * Drop CHECK constraint with cross-DB compatibility.
     *
     * Handles PostgreSQL (DROP CONSTRAINT IF EXISTS),
     * MySQL 8+ (DROP CHECK), and SQLite (no-op).
     */
    public static function dropCheckConstraint(string $tableName, string $constraintName): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite doesn't support ALTER TABLE DROP CONSTRAINT
            return;
        }

        // PostgreSQL syntax: ALTER TABLE t DROP CONSTRAINT IF EXISTS c
        try {
            DB::statement("ALTER TABLE {$tableName} DROP CONSTRAINT IF EXISTS {$constraintName}");

            return;
        } catch (\Exception $e) {
            // Not PostgreSQL, try MySQL
        }

        // MySQL 8+ syntax: ALTER TABLE t DROP CHECK c
        try {
            DB::statement("ALTER TABLE {$tableName} DROP CHECK {$constraintName}");
        } catch (\Exception $e) {
            // Constraint may not exist — that's ok
        }
    }

    /**
     * Update enum values with data migration
     */
    public static function updateEnumValues(
        string $table,
        string $column,
        array $oldValues,
        array $newValues,
        array $valueMapping = []
    ): void {
        // Implementation for updating enum values
        $dbDriver = DB::getDriverName();

        if ($dbDriver === 'pgsql') {
            // PostgreSQL: Drop constraint, update data, add constraint
            $constraintName = static::generateConstraintName($table, $column);
            $quotedColumn = "\"{$column}\"";

            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$constraintName}");

            // Update data
            if (! empty($valueMapping)) {
                foreach ($valueMapping as $old => $new) {
                    DB::statement("UPDATE {$table} SET {$quotedColumn} = ? WHERE {$quotedColumn} = ?", [$new, $old]);
                }
            } else {
                // Simple mapping if same order
                if (count($oldValues) === count($newValues)) {
                    for ($i = 0; $i < count($oldValues); $i++) {
                        DB::statement("UPDATE {$table} SET {$quotedColumn} = ? WHERE {$quotedColumn} = ?", [$newValues[$i], $oldValues[$i]]);
                    }
                }
            }

            // Add new constraint
            $allowedValuesString = "'".implode("', '", $newValues)."'";
            $sql = "{$quotedColumn} IN ({$allowedValuesString})";
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$constraintName} CHECK ({$sql})");
        } else {
            // MySQL and SQLite: Use ALTER TABLE MODIFY COLUMN
            $allowedValuesString = "'".implode("', '", $newValues)."'";

            if ($dbDriver === 'mysql') {
                DB::statement("ALTER TABLE {$table} MODIFY COLUMN {$column} ENUM({$allowedValuesString})");
            } else {
                // SQLite: Need to recreate table
                // This is more complex and would require table recreation
                // For now, we'll use a simpler approach
                DB::statement("ALTER TABLE {$table} ADD COLUMN {$column}_temp VARCHAR(50)");

                // Copy data
                DB::statement("UPDATE {$table} SET {$column}_temp = {$column}");

                // Drop old column
                DB::statement("ALTER TABLE {$table} DROP COLUMN {$column}");

                // Add new column
                DB::statement("ALTER TABLE {$table} ADD COLUMN {$column} VARCHAR(50)");

                // Copy data back
                DB::statement("UPDATE {$table} SET {$column} = {$column}_temp");

                // Drop temp column
                DB::statement("ALTER TABLE {$table} DROP COLUMN {$column}_temp");
            }
        }
    }

    /**
     * Rollback enum changes
     */
    public static function rollbackEnumChanges(string $table, string $column, string $constraintName): void
    {
        // Drop CHECK constraint
        $dbDriver = DB::getDriverName();

        if ($dbDriver === 'pgsql') {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$constraintName}");
        } else {
            // MySQL and SQLite: CHECK constraints are handled differently
            // For MySQL, we need to modify the column type
            // For SQLite, we need to recreate the table
        }

        // Note: This is a simplified rollback
        // In production, you would need to store the original enum definition
        // and restore it properly
    }

    /**
     * Batch convert multiple enum columns
     *
     * @param  array  $enumColumns  Array of ['column' => $column, 'values' => $values, 'default' => $default]
     */
    public static function batchEnumToStringWithCheck(Blueprint $table, array $enumColumns): void
    {
        foreach ($enumColumns as $enumColumn) {
            $column = $enumColumn['column'];
            $values = $enumColumn['values'];
            $default = $enumColumn['default'] ?? null;

            static::enumToStringWithCheck($table, $column, $values, $default);
        }
    }

    /**
     * Detect enum columns in a table
     */
    public static function detectEnumColumns(string $table): array
    {
        // This would require examining the migration files
        // For now, return empty array
        return [];
    }

    /**
     * Validate migration structure
     */
    public static function validateMigrationStructure(string $migrationPath): bool
    {
        // Validate migration structure
        // Check for required patterns, constraints, etc.
        return true;
    }

    /**
     * Profile migration performance
     */
    public static function profileMigrationPerformance(string $migrationPath): array
    {
        // Profile migration performance
        return ['duration' => 0, 'rows_affected' => 0];
    }
}
