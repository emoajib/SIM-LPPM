<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;

class DatabaseRestoreService
{
    protected array $allowedPrefixes = [
        'INSERT INTO',
        'INSERT IGNORE INTO',
        'REPLACE INTO',
        'SET',
    ];

    protected array $blockedPatterns = [
        '/^\s*DROP\s+(TABLE|DATABASE|SCHEMA|PROCEDURE|FUNCTION|TRIGGER|VIEW|INDEX)\s/i',
        '/^\s*ALTER\s+(TABLE|DATABASE|SCHEMA|PROCEDURE|FUNCTION|TRIGGER|VIEW)\s/i',
        '/^\s*TRUNCATE\s+(TABLE)\s/i',
        '/^\s*DELETE\s+(FROM\s+)?/i',
        '/^\s*UPDATE\s+.+\s+SET\s/i',
        '/^\s*RENAME\s+TABLE\s/i',
        '/^\s*CREATE\s+(PROCEDURE|FUNCTION|TRIGGER|EVENT|USER|DATABASE|SCHEMA)\s/i',
        '/^\s*GRANT\s/i',
        '/^\s*REVOKE\s/i',
        '/^\s*FLUSH\s/i',
        '/^\s*KILL\s/i',
        '/^\s*SHUTDOWN\s/i',
    ];

    protected int $batchSize = 500;

    protected array $preservedTables = [
        'migrations',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'personal_access_tokens',
        'telescope_entries',
        'telescope_entries_tags',
        'telescope_monitoring',
    ];

    public function preview(string $sqlPath): array
    {
        if (! file_exists($sqlPath)) {
            throw new \RuntimeException('File SQL tidak ditemukan.');
        }

        $statements = $this->parseStatements($sqlPath);
        $filtered = [];
        $blocked = [];
        $tables = [];

        foreach ($statements as $i => $stmt) {
            $trimmed = trim($stmt);
            if (empty($trimmed)) {
                continue;
            }

            if ($this->isAllowed($trimmed)) {
                $filtered[] = $trimmed;

                if (preg_match('/INSERT\s+(IGNORE\s+)?INTO\s+[`"\']?(\w+)[`"\']?\s/i', $trimmed, $m)) {
                    $table = $m[2];
                    $tables[$table] = ($tables[$table] ?? 0) + 1;
                }
            } else {
                $type = $this->classifyStatement($trimmed);
                if ($type !== 'comment' && $type !== 'empty') {
                    $blocked[] = [
                        'line' => $i + 1,
                        'type' => $type,
                        'preview' => mb_substr($trimmed, 0, 80),
                    ];
                }
            }
        }

        return [
            'total_statements' => count($statements),
            'allowed' => count($filtered),
            'blocked' => $blocked,
            'blocked_count' => count($blocked),
            'tables' => $tables,
            'statements' => $filtered,
        ];
    }

    protected function fixIdentityStatement(string $statement): string
    {
        if (! preg_match('/INSERT\s+(IGNORE\s+)?INTO\s+[`"\']?identities[`"\']?\s/i', $statement)) {
            return $statement;
        }

        $columns = [
            'id', 'identity_id', 'user_id', 'sinta_id', 'scopus_id',
            'google_scholar_id', 'wos_id', 'type', 'gender',
            'is_external', 'address', 'birthdate', 'birthplace',
            'institution_id', 'institution_name', 'study_program_id',
            'science_cluster_id', 'profile_picture', 'faculty_id',
            'created_at', 'updated_at', 'last_education', 'functional_position',
            'title_prefix', 'title_suffix',
            'sinta_score_v2_overall', 'sinta_score_v2_3yr',
            'sinta_score_v3_overall', 'sinta_score_v3_3yr',
            'affil_score_v3_overall', 'affil_score_v3_3yr',
            'scopus_documents', 'scopus_citations', 'scopus_cited_documents',
            'scopus_h_index', 'scopus_g_index', 'scopus_i10_index',
            'gs_documents', 'gs_citations', 'gs_cited_documents',
            'gs_h_index', 'gs_g_index', 'gs_i10_index',
            'wos_documents', 'wos_citations', 'wos_cited_documents',
            'wos_h_index', 'wos_g_index', 'wos_i10_index',
            'garuda_documents', 'garuda_citations', 'garuda_cited_documents',
            'is_active',
        ];

        $quote = DB::getDriverName() === 'mysql' ? '`' : '"';
        $quoted = array_map(fn ($c) => $quote.$c.$quote, $columns);
        $colList = implode(',', $quoted);

        return preg_replace(
            '/(INSERT\s+(IGNORE\s+)?INTO\s+[`"\']?identities[`"\']?\s*)\s*VALUES\s/i',
            '$1('.$colList.') VALUES ',
            $statement
        );
    }

    /**
     * Adapt MySQL-dialect SQL to be compatible with the current database driver.
     *
     * Handles:
     * - Backtick identifiers (`table`) → double-quote identifiers ("table") for pgsql/sqlite
     * - INSERT IGNORE INTO → INSERT INTO ... ON CONFLICT DO NOTHING for pgsql
     * - REPLACE INTO → INSERT INTO ... ON CONFLICT DO NOTHING for pgsql
     * - SET statements are no-ops on pgsql (discarded)
     *
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    protected function adaptSqlForCurrentDriver(string $statement): string
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // MySQL is the native format — no transformation needed
            return $statement;
        }

        // 1. Convert MySQL backtick-quoted identifiers → standard double-quoted identifiers
        //    Matches `identifier` that contains only word chars (safe for table/column names)
        $statement = preg_replace_callback(
            '/`([^`]+)`/',
            fn ($matches) => '"'.$matches[1].'"',
            $statement
        );

        if ($driver === 'pgsql') {
            // Convert MySQL escaped single quotes (\') to SQL standard ('')
            $statement = str_replace("\\'", "''", $statement);

            // 2. INSERT IGNORE INTO → INSERT INTO ... ON CONFLICT DO NOTHING
            if (preg_match('/^\s*INSERT\s+IGNORE\s+INTO\s/i', $statement)) {
                $statement = preg_replace('/^\s*INSERT\s+IGNORE\s+INTO\s/i', 'INSERT INTO ', $statement);
                // Append ON CONFLICT clause (strip trailing semicolon first, then re-add)
                $statement = rtrim($statement, '; ');
                $statement .= ' ON CONFLICT DO NOTHING';
            }

            // 3. REPLACE INTO → INSERT INTO ... ON CONFLICT DO NOTHING
            //    (REPLACE = DELETE + INSERT, nearest pgsql equivalent is upsert or ignore)
            if (preg_match('/^\s*REPLACE\s+INTO\s/i', $statement)) {
                $statement = preg_replace('/^\s*REPLACE\s+INTO\s/i', 'INSERT INTO ', $statement);
                $statement = rtrim($statement, '; ');
                $statement .= ' ON CONFLICT DO NOTHING';
            }

            // 4. MySQL SET statements (e.g. SET NAMES utf8, SET FOREIGN_KEY_CHECKS) are no-ops
            if (preg_match('/^\s*SET\s+(?!SEARCH_PATH|ROLE|SESSION|LOCAL)/i', $statement)) {
                return '';
            }
        }

        return $statement;
    }

    public function restore(string $sqlPath, bool $backupFirst = true): array
    {
        if (! file_exists($sqlPath)) {
            throw new \RuntimeException('File SQL tidak ditemukan.');
        }

        $preview = $this->preview($sqlPath);
        $statements = $preview['statements'];

        if (empty($statements)) {
            return [
                'success' => false,
                'message' => 'Tidak ada statement INSERT yang ditemukan dalam file.',
                'inserted' => 0,
            ];
        }

        if ($backupFirst) {
            $backupPath = $this->autoBackup();
        }

        $inserted = 0;
        $errors = [];

        // Jalankan penonaktifan foreign key sebelum transaksi dimulai
        Schema::disableForeignKeyConstraints();

        DB::beginTransaction();

        try {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('SET UNIQUE_CHECKS = 0');
                DB::statement('SET SQL_MODE = ""');
            }
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('SET session_replication_role = replica;');
            }

            foreach ($statements as $stmt) {
                try {
                    $adapted = $this->adaptSqlForCurrentDriver($this->fixIdentityStatement($stmt));
                    if (empty(trim($adapted))) {
                        continue; // Skip statements that became empty after adaptation (e.g. MySQL SET)
                    }

                    if (DB::getDriverName() === 'pgsql') {
                        DB::unprepared('SAVEPOINT pg_restore_sp;');
                    }

                    DB::unprepared($adapted);
                    $inserted++;

                    if (DB::getDriverName() === 'pgsql') {
                        DB::unprepared('RELEASE SAVEPOINT pg_restore_sp;');
                    }
                } catch (\Throwable $e) {
                    if (DB::getDriverName() === 'pgsql') {
                        DB::unprepared('ROLLBACK TO SAVEPOINT pg_restore_sp;');
                    }

                    $errors[] = [
                        'statement' => mb_substr($stmt, 0, 100),
                        'error' => $e->getMessage(),
                    ];

                    Log::error('SQL Error during statement restore', [
                        'stmt' => mb_substr($stmt, 0, 250),
                        'msg' => $e->getMessage(),
                    ]);

                    if (count($errors) > 500) {
                        throw new \RuntimeException('Terlalu banyak error. Rollback.');
                    }
                }
            }

            if (DB::getDriverName() === 'mysql') {
                DB::statement('SET UNIQUE_CHECKS = 1');
            }
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('SET session_replication_role = DEFAULT;');
            }

            DB::commit();
            Schema::enableForeignKeyConstraints();

            $message = count($errors) === 0
                ? "✅ Restore berhasil! {$inserted} baris data dipulihkan."
                : "✅ Restore selesai dengan {$inserted} baris dan ".count($errors).' peringatan.';

            Log::info('Database restore completed', [
                'file' => basename($sqlPath),
                'inserted' => $inserted,
                'errors' => count($errors),
            ]);

            return [
                'success' => true,
                'message' => $message,
                'inserted' => $inserted,
                'errors' => $errors,
                'backup_path' => $backupPath ?? null,
                'tables' => $preview['tables'],
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Schema::enableForeignKeyConstraints();

            if (DB::getDriverName() === 'mysql') {
                DB::statement('SET UNIQUE_CHECKS = 1');
            }
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('SET session_replication_role = DEFAULT;');
            }

            Log::error('Database restore failed', [
                'file' => basename($sqlPath),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => '❌ Restore gagal: '.$e->getMessage().' Semua perubahan di-rollback.',
                'inserted' => 0,
                'errors' => [],
                'backup_path' => $backupPath ?? null,
            ];
        }
    }

    public function restoreWithReplace(string $sqlPath, bool $backupFirst = true): array
    {
        if (! file_exists($sqlPath)) {
            throw new \RuntimeException('File SQL tidak ditemukan.');
        }

        $preview = $this->preview($sqlPath);
        $statements = $preview['statements'];

        if (empty($statements)) {
            return [
                'success' => false,
                'message' => 'Tidak ada statement INSERT yang ditemukan dalam file.',
                'inserted' => 0,
            ];
        }

        if ($backupFirst) {
            $backupPath = $this->autoBackup();
        }

        $inserted = 0;
        $deleted = 0;
        $errors = [];

        $tablesToDelete = [];
        foreach ($preview['tables'] as $table => $count) {
            if (! in_array($table, $this->preservedTables)) {
                $tablesToDelete[] = $table;
            }
        }

        // Jalankan penonaktifan foreign key sebelum transaksi dimulai
        Schema::disableForeignKeyConstraints();

        DB::beginTransaction();

        try {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('SET UNIQUE_CHECKS = 0');
                DB::statement('SET SQL_MODE = ""');
            }
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('SET session_replication_role = replica;');
            }

            if (DB::getDriverName() === 'pgsql') {
                // Untuk PostgreSQL, kumpulkan semua tabel yang akan dihapus dan gunakan TRUNCATE ... CASCADE dalam satu perintah tunggal
                if (! empty($tablesToDelete)) {
                    $quotedTables = array_map(fn ($t) => '"'.$t.'"', $tablesToDelete);
                    $tableList = implode(', ', $quotedTables);

                    // Hitung estimasi baris sebelum dikosongkan untuk log statistik pencatatan data terhapus
                    foreach ($tablesToDelete as $table) {
                        $deleted += DB::table($table)->count();
                    }

                    DB::statement("TRUNCATE TABLE {$tableList} CASCADE");
                }
            } else {
                // Driver non-Postgres (MySQL/SQLite) tetap menggunakan loop delete standar
                foreach ($tablesToDelete as $table) {
                    $count = DB::table($table)->count();
                    DB::table($table)->delete();
                    $deleted += $count;
                }
            }

            foreach ($statements as $stmt) {
                if ($this->isPreservedTableStatement($stmt)) {
                    continue;
                }

                try {
                    $adapted = $this->adaptSqlForCurrentDriver($this->fixIdentityStatement($stmt));
                    if (empty(trim($adapted))) {
                        continue; // Skip statements that became empty after adaptation (e.g. MySQL SET)
                    }

                    if (DB::getDriverName() === 'pgsql') {
                        DB::unprepared('SAVEPOINT pg_restore_sp;');
                    }

                    DB::unprepared($adapted);
                    $inserted++;

                    if (DB::getDriverName() === 'pgsql') {
                        DB::unprepared('RELEASE SAVEPOINT pg_restore_sp;');
                    }
                } catch (\Throwable $e) {
                    if (DB::getDriverName() === 'pgsql') {
                        DB::unprepared('ROLLBACK TO SAVEPOINT pg_restore_sp;');
                    }

                    $errors[] = [
                        'statement' => mb_substr($stmt, 0, 100),
                        'error' => $e->getMessage(),
                    ];

                    Log::error('SQL Error during statement restore', [
                        'stmt' => mb_substr($stmt, 0, 250),
                        'msg' => $e->getMessage(),
                    ]);

                    if (count($errors) > 500) {
                        throw new \RuntimeException('Terlalu banyak error. Rollback.');
                    }
                }
            }

            if (DB::getDriverName() === 'mysql') {
                DB::statement('SET UNIQUE_CHECKS = 1');
            }
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('SET session_replication_role = DEFAULT;');
            }

            DB::commit();
            Schema::enableForeignKeyConstraints();

            $skippedPreserved = 0;
            foreach ($preview['tables'] as $table => $count) {
                if (in_array($table, $this->preservedTables)) {
                    $skippedPreserved += $count;
                }
            }

            $message = count($errors) === 0
                ? "✅ Sinkron berhasil! {$deleted} baris dihapus, {$inserted} baris dipulihkan."
                : "✅ Sinkron selesai dengan {$inserted} baris dan ".count($errors).' peringatan.';

            if ($skippedPreserved > 0) {
                $message .= " {$skippedPreserved} baris dari tabel sistem dilewati.";
            }

            Log::info('Database restore-with-replace completed', [
                'file' => basename($sqlPath),
                'deleted' => $deleted,
                'inserted' => $inserted,
                'skipped_preserved' => $skippedPreserved,
                'errors' => count($errors),
            ]);

            return [
                'success' => true,
                'message' => $message,
                'deleted' => $deleted,
                'inserted' => $inserted,
                'errors' => $errors,
                'backup_path' => $backupPath ?? null,
                'tables' => $preview['tables'],
                'preserved_tables' => $this->getPreservedTableInfo($preview['tables']),
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Schema::enableForeignKeyConstraints();

            if (DB::getDriverName() === 'mysql') {
                DB::statement('SET UNIQUE_CHECKS = 1');
            }
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('SET session_replication_role = DEFAULT;');
            }

            Log::error('Database restore-with-replace failed', [
                'file' => basename($sqlPath),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => '❌ Sinkron gagal: '.$e->getMessage().' Semua perubahan di-rollback.',
                'deleted' => 0,
                'inserted' => 0,
                'errors' => [],
                'backup_path' => $backupPath ?? null,
            ];
        }
    }

    public function autoBackup(): string
    {
        $backupDir = storage_path('app/backup');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $timestamp = now()->format('Ymd_His');
        $filename = "pre_restore_backup_{$timestamp}.sql";
        $path = "{$backupDir}/{$filename}";

        $connection = config('database.default');
        $driver = DB::getDriverName();
        $dbName = config("database.connections.{$connection}.database");
        $dbUser = config("database.connections.{$connection}.username");
        $dbPass = config("database.connections.{$connection}.password");
        $dbHost = config("database.connections.{$connection}.host");
        $dbPort = config("database.connections.{$connection}.port");

        if ($driver === 'mysql') {
            $cmd = ['mysqldump', '--complete-insert', '-u', $dbUser];
            if ($dbPass) {
                $cmd[] = "-p{$dbPass}";
            }
            $cmd[] = $dbName;
        } elseif ($driver === 'pgsql') {
            $cmd = ['pg_dump', '-U', $dbUser, '--column-inserts', '--no-owner', '--no-acl'];
            if ($dbPass) {
                putenv("PGPASSWORD={$dbPass}");
            }
            $cmd[] = '-h';
            $cmd[] = $dbHost;
            $cmd[] = '-p';
            $cmd[] = $dbPort;
            $cmd[] = $dbName;
        } elseif ($driver === 'sqlite') {
            $cmd = ['sqlite3', $dbName, '.dump'];
        } else {
            throw new \RuntimeException('Unsupported database driver for backup: '.$driver);
        }

        $result = Process::run($cmd, function () {});

        if ($result->successful()) {
            file_put_contents($path, $result->output());
        }

        return $path;
    }

    protected function parseStatements(string $sqlPath): array
    {
        $content = file_get_contents($sqlPath);
        if ($content === false) {
            throw new \RuntimeException('Gagal membaca file SQL.');
        }

        $lines = explode("\n", $content);
        $statements = [];
        $current = '';

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (empty($trimmed) || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
                continue;
            }

            if (str_starts_with($trimmed, '/*!')) {
                $end = strpos($trimmed, '*/');
                if ($end !== false) {
                    $content = substr($trimmed, 3, $end - 3);
                    $rest = substr($trimmed, $end + 2);
                    $current .= $content;
                    if (str_contains($rest, ';')) {
                        $parts = explode(';', $rest);
                        $current .= array_shift($parts);
                        $statements[] = trim($current);
                        $current = '';
                        foreach ($parts as $part) {
                            if (trim($part)) {
                                $statements[] = trim($part);
                            }
                        }
                    } else {
                        $current .= $rest;
                    }
                }

                continue;
            }

            $current .= ($current ? "\n" : '').$line;

            if (str_ends_with(trim($line), ';')) {
                $statements[] = trim($current);
                $current = '';
            }
        }

        if (trim($current)) {
            $statements[] = trim($current);
        }

        return $statements;
    }

    protected function isAllowed(string $statement): bool
    {
        foreach ($this->blockedPatterns as $pattern) {
            if (preg_match($pattern, $statement)) {
                return false;
            }
        }

        foreach ($this->allowedPrefixes as $prefix) {
            if (str_starts_with(strtoupper($statement), strtoupper($prefix))) {
                return true;
            }
        }

        if (preg_match('/^\s*\/\*!\d+\s+SET\s/i', $statement)) {
            return true;
        }

        return false;
    }

    protected function classifyStatement(string $statement): string
    {
        if (preg_match('/^\s*\/\*!/', $statement)) {
            return 'comment';
        }
        if (preg_match('/^\s*--/', $statement)) {
            return 'comment';
        }
        if (preg_match('/^\s*CREATE\s+TABLE\s/i', $statement)) {
            return 'create_table';
        }
        if (preg_match('/^\s*DROP\s/i', $statement)) {
            return 'drop';
        }
        if (preg_match('/^\s*ALTER\s/i', $statement)) {
            return 'alter';
        }
        if (preg_match('/^\s*TRUNCATE\s/i', $statement)) {
            return 'truncate';
        }
        if (preg_match('/^\s*DELETE\s/i', $statement)) {
            return 'delete';
        }
        if (preg_match('/^\s*UPDATE\s/i', $statement)) {
            return 'update';
        }

        return 'other';
    }

    protected function isPreservedTableStatement(string $statement): bool
    {
        if (preg_match('/INSERT\s+(IGNORE\s+)?INTO\s+[`"\']?(\w+)[`"\']?\s/i', $statement, $m)) {
            return in_array($m[2], $this->preservedTables);
        }

        return false;
    }

    public function getPreservedTableInfo(array $tables): array
    {
        $preserved = [];
        foreach ($tables as $table => $count) {
            if (in_array($table, $this->preservedTables)) {
                $preserved[$table] = $count;
            }
        }

        return $preserved;
    }
}
