<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

class DatabaseRestoreService
{
    /**
     * Source (MySQL) column orders for tables where `after()` was used in migrations.
     * PostgreSQL ignores `after()`, causing column order mismatch with MySQL dumps.
     */
    protected array $mysqlColumnOrders = [
        'community_service_schemes' => [
            'id', 'name', 'strata', 'eligibility_rules', 'created_at', 'updated_at',
        ],
        'community_services' => [
            'id', 'macro_research_group_id', 'partner_id', 'partner_issue_summary',
            'solution_offered', 'created_at', 'updated_at', 'deleted_at',
        ],
        'faculties' => [
            'id', 'institution_id', 'name', 'dean_name', 'dean_id', 'dean_user_id',
            'research_roadmap', 'code', 'created_at', 'updated_at',
        ],
        'institutions' => [
            'id', 'name', 'code', 'type', 'is_verified', 'lppm_head_name',
            'lppm_head_id', 'lppm_head_user_id', 'short_name', 'address',
            'phone', 'email', 'website', 'created_at', 'updated_at',
        ],
        'letter_types' => [
            'id', 'code', 'name', 'description', 'category', 'numbering_format',
            'template_view', 'template_file_path', 'template_file_original_name',
            'template_file_size', 'template_uploaded_at', 'template_uploaded_by',
            'is_uploadable', 'is_active', 'created_at', 'updated_at', 'deleted_at',
        ],
        'national_priorities' => [
            'id', 'name', 'prn_code', 'valid_from', 'valid_until', 'description', 'created_at', 'updated_at',
        ],
        'research_schemes' => [
            'id', 'name', 'strata', 'eligibility_rules', 'description', 'created_at', 'updated_at',
        ],
        'proposal_outputs' => [
            'id', 'proposal_id', 'output_year', 'category', 'group', 'type', 'target_status', 'description', 'created_at', 'updated_at',
        ],
        'proposals' => [
            'id', 'title', 'submitter_id', 'detailable_id', 'detailable_type',
            'research_scheme_id', 'focus_area_id', 'theme_id', 'topic_id', 'national_priority_id',
            'cluster_level1_id', 'cluster_level2_id', 'cluster_level3_id', 'sbk_value',
            'duration_in_years', 'start_year', 'semester', 'summary', 'asta_cita', 'status',
            'logbook_signed_at', 'student_members', 'created_at', 'updated_at', 'deleted_at',
            'community_service_scheme_id', 'qualification_snapshot', 'logbook_approved_at',
            'study_program_roadmap_id', 'bima_proposal_id', 'is_roadmap_validated_by_kaprodi',
            'kaprodi_validation_notes', 'kaprodi_validated_at', 'kaprodi_id',
        ],
        'review_criterias' => [
            'id', 'type', 'criteria', 'description', 'weight', 'order', 'is_active', 'created_at', 'updated_at',
        ],
        'study_programs' => [
            'id', 'institution_id', 'faculty_id', 'kaprodi_user_id', 'research_roadmap',
            'roadmap_status', 'name', 'code', 'created_at', 'updated_at',
        ],
        'budget_caps' => [
            'id', 'year', 'semester', 'research_budget_cap', 'community_service_budget_cap',
            'scheme_caps', 'enforce_percentage', 'created_at', 'updated_at',
        ],
        'budget_groups' => [
            'id', 'code', 'name', 'description', 'percentage', 'proposal_type',
            'percentage_type', 'is_active', 'created_at', 'updated_at',
        ],
        'iku_output_types' => [
            'id', 'name', 'group', 'is_active', 'created_at', 'updated_at',
        ],
        'master_ikus' => [
            'id', 'code', 'name', 'description', 'target_percentage', 'internal_weight',
            'is_active', 'created_at', 'updated_at',
        ],
    ];

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
        'budget_caps',
        'budget_groups',
        'budget_components',
    ];

    private function resyncPostgresSequences(): void
    {
        try {
            $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'");
            foreach ($tables as $tableInfo) {
                $tableName = $tableInfo->table_name;
                try {
                    DB::statement("SELECT setval(pg_get_serial_sequence('\"{$tableName}\"', 'id'), COALESCE((SELECT MAX(id)+1 FROM \"{$tableName}\"), 1), false)");
                } catch (\Throwable $e) {
                    // Abaikan tabel yang tidak memiliki auto-increment 'id' (misalnya tabel pivot tanpa ID primary key)
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal melakukan sinkronisasi sequence PostgreSQL', ['error' => $e->getMessage()]);
        }
    }

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

    protected function fixUsersStatement(string $statement): string
    {
        if (! preg_match('/INSERT\s+(IGNORE\s+)?INTO\s+[`"\']?users[`"\']?\s/i', $statement)) {
            return $statement;
        }

        // MySQL dump columns order
        $columns = [
            'id', 'name', 'username', 'email', 'email_verified_at', 'password',
            'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
            'remember_token', 'created_at', 'updated_at', 'last_active_at',
        ];

        $quote = DB::getDriverName() === 'mysql' ? '`' : '"';
        $quoted = array_map(fn ($c) => $quote.$c.$quote, $columns);
        $colList = implode(',', $quoted);

        return preg_replace(
            '/(INSERT\s+(IGNORE\s+)?INTO\s+[`"\']?users[`"\']?\s*)\s*VALUES\s/i',
            '$1('.$colList.') VALUES ',
            $statement
        );
    }

    /**
     * Inject explicit column list (in source/MySQL order) for tables where `after()` was used.
     * This prevents PostgreSQL from misinterpreting VALUES due to column order mismatch.
     */
    protected function injectColumnList(string $statement): string
    {
        if (DB::getDriverName() !== 'pgsql') {
            return $statement;
        }

        if (! preg_match('/INSERT\s+(IGNORE\s+)?INTO\s+[`"\']?(\w+)[`"\']?\s/i', $statement, $m)) {
            return $statement;
        }

        $table = $m[2];

        if (! isset($this->mysqlColumnOrders[$table])) {
            return $statement;
        }

        if (preg_match('/INSERT\s+(IGNORE\s+)?INTO\s+[`"\']?\w+[`"\']?\s*\(/', $statement)) {
            return $statement;
        }

        $cols = array_map(fn ($c) => '"'.$c.'"', $this->mysqlColumnOrders[$table]);
        $colList = implode(',', $cols);

        return preg_replace(
            '/(INSERT\s+(IGNORE\s+)?INTO\s+[`"\']?\w+[`"\']?\s*)\s*VALUES\s/i',
            '$1('.$colList.') VALUES ',
            $statement
        );
    }

    /**
     * Fix boolean values in INSERT statements for PostgreSQL compatibility.
     * Converts MySQL-style integer booleans (0/1) and empty string to proper boolean literals.
     */
    protected function fixBooleanValues(string $statement): string
    {
        if (DB::getDriverName() !== 'pgsql') {
            return $statement;
        }

        if (! preg_match('/INSERT\s+(IGNORE\s+)?INTO\s+[`"\']?(\w+)[`"\']?\s*/i', $statement, $m)) {
            return $statement;
        }

        $table = $m[2];

        try {
            $boolCols = DB::select(
                "SELECT column_name, ordinal_position FROM information_schema.columns
                 WHERE table_name = ? AND table_schema = 'public' AND data_type IN ('boolean')
                 ORDER BY ordinal_position",
                [$table]
            );
        } catch (\Throwable $e) {
            return $statement;
        }

        if (empty($boolCols)) {
            return $statement;
        }

        $boolColumnNames = array_map(fn ($c) => $c->column_name, $boolCols);
        $colNames = $this->extractColumnNames($statement, $table);

        if (empty($colNames)) {
            return $statement;
        }

        $boolPositions = [];
        foreach ($colNames as $pos => $name) {
            if (in_array($name, $boolColumnNames, true)) {
                $boolPositions[] = $pos;
            }
        }

        if (empty($boolPositions)) {
            return $statement;
        }

        return $this->convertBooleanValuesInSql($statement, $boolPositions);
    }

    /**
     * Extract column names from an INSERT statement.
     * Returns ordered array of column names, or empty array if cannot determine.
     */
    protected function extractColumnNames(string $statement, string $table): array
    {
        if (preg_match('/INSERT\s+(?:IGNORE\s+)?INTO\s+[`"\']?\w+[`"\']?\s*\(([^)]+)\)\s*VALUES\s/i', $statement, $m)) {
            return array_map(fn ($c) => trim($c, ' `"'), explode(',', $m[1]));
        }

        if (isset($this->mysqlColumnOrders[$table])) {
            return $this->mysqlColumnOrders[$table];
        }

        try {
            $cols = DB::select(
                "SELECT column_name FROM information_schema.columns
                 WHERE table_name = ? AND table_schema = 'public'
                 ORDER BY ordinal_position",
                [$table]
            );

            return array_map(fn ($c) => $c->column_name, $cols);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Convert 0/1/'' at the given positions to false/true in all VALUES tuples.
     * Properly handles parentheses inside string literals.
     */
    protected function convertBooleanValuesInSql(string $statement, array $boolPositions): string
    {
        $sortPos = array_fill_keys($boolPositions, true);

        // Find the VALUES clause and extract each tuple properly,
        // respecting string literals that may contain parentheses
        return preg_replace_callback(
            '/VALUES\s+(.*?)(?:\s+ON\s+CONFLICT\s+DO\s+NOTHING\s*)?$/si',
            function ($matches) use ($sortPos) {
                $clause = $matches[1];
                $tuples = $this->parseTuples($clause);
                $converted = [];

                foreach ($tuples as $tuple) {
                    $parts = $this->splitTupleValues($tuple);
                    if (count($parts) > max(array_keys($sortPos))) {
                        foreach ($sortPos as $pos => $_) {
                            $val = trim($parts[$pos]);
                            if ($val === '0') {
                                $parts[$pos] = 'false';
                            } elseif ($val === '1') {
                                $parts[$pos] = 'true';
                            } elseif ($val === "''" || $val === '') {
                                $parts[$pos] = 'false';
                            }
                        }
                    }
                    $converted[] = '('.implode(',', $parts).')';
                }

                $suffix = str_contains($matches[0], 'ON CONFLICT') ? ' ON CONFLICT DO NOTHING' : '';

                return 'VALUES '.implode(',', $converted).$suffix;
            },
            $statement
        );
    }

    /**
     * Parse individual tuples from VALUES clause, handling parentheses inside strings.
     */
    protected function parseTuples(string $clause): array
    {
        $tuples = [];
        $depth = 0;
        $inString = false;
        $current = '';
        $len = strlen($clause);

        for ($i = 0; $i < $len; $i++) {
            $ch = $clause[$i];

            if ($ch === "'" && ! $inString) {
                $inString = true;
                $current .= $ch;
            } elseif ($ch === "'" && $inString) {
                if ($i + 1 < $len && $clause[$i + 1] === "'") {
                    $current .= $ch;
                    $i++;
                }
                $inString = false;
                $current .= $ch;
            } elseif ($ch === '(' && ! $inString) {
                if ($depth === 0) {
                    $current = '';
                } else {
                    $current .= $ch;
                }
                $depth++;
            } elseif ($ch === ')' && ! $inString) {
                $depth--;
                if ($depth === 0) {
                    $tuples[] = $current;
                    $current = '';
                } else {
                    $current .= $ch;
                }
            } else {
                $current .= $ch;
            }
        }

        return $tuples;
    }

    /**
     * Split a VALUES tuple (content between outer parentheses) into individual values,
     * respecting single-quoted strings.
     */
    protected function splitTupleValues(string $tuple): array
    {
        $values = [];
        $current = '';
        $inString = false;
        $len = strlen($tuple);

        for ($i = 0; $i < $len; $i++) {
            $ch = $tuple[$i];

            if ($ch === "'" && ! $inString) {
                $inString = true;
                $current .= $ch;
            } elseif ($ch === "'" && $inString) {
                if ($i + 1 < $len && $tuple[$i + 1] === "'") {
                    $current .= $ch;
                    $i++;
                }
                $inString = false;
                $current .= $ch;
            } elseif ($ch === ',' && ! $inString) {
                $values[] = $current;
                $current = '';
            } else {
                $current .= $ch;
            }
        }

        if ($current !== '') {
            $values[] = $current;
        }

        return $values;
    }

    /**
     * Fix MySQL-style escaped quotes within JSON string values for PostgreSQL compatibility.
     * MySQL mysqldump escapes double quotes inside JSON as \", but PostgreSQL needs unescaped.
     */
    protected function fixJsonEscaping(string $statement): string
    {
        if (DB::getDriverName() !== 'pgsql') {
            return $statement;
        }

        return str_replace('\\"', '"', $statement);
    }

    /**
     * Apply all statement fixes in the correct order.
     */
    protected function processStatement(string $statement): string
    {
        $statement = $this->fixUsersStatement($statement);
        $statement = $this->fixIdentityStatement($statement);
        $statement = $this->injectColumnList($statement);
        $statement = $this->fixJsonEscaping($statement);
        $statement = $this->adaptSqlForCurrentDriver($statement);
        $statement = $this->fixBooleanValues($statement);

        return $statement;
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
        // --- LEGACY DATA FIX ---
        // Fix legacy budget_items statements (13 columns -> 12 columns by dropping the obsolete 5th column)
        if (preg_match('/INSERT\s+(IGNORE\s+)?INTO\s+[`"\']?budget_items[`"\']?\s/i', $statement)) {
            $statement = preg_replace(
                '/\(\s*(\d+)\s*,\s*(\'[0-9a-fA-F-]+\')\s*,\s*(\d+|NULL)\s*,\s*(\d+|NULL)\s*,\s*(?:\d+|NULL)\s*,/',
                '($1,$2,$3,$4,',
                $statement
            );
        }

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

            // Convert MySQL escaped backslashes (\\) to standard SQL backslashes (\)
            // e.g. 'App\\Models\\User' -> 'App\Models\User'
            $statement = str_replace('\\\\', '\\', $statement);

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
                    $adapted = $this->processStatement($stmt);
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

            if (DB::getDriverName() === 'pgsql') {
                $this->resyncPostgresSequences();
            }

            $message = count($errors) === 0
                ? "✅ Restore berhasil! {$inserted} baris data dipulihkan."
                : "✅ Restore selesai dengan {$inserted} baris dan ".count($errors).' peringatan.';

            Log::info('Database restore completed', [
                'file' => basename($sqlPath),
                'inserted' => $inserted,
                'errors' => count($errors),
            ]);

            try {
                app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
            } catch (\Throwable $e) {
                // Ignore if Spatie isn't set up yet
            }

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
                    $adapted = $this->processStatement($stmt);
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

            if (DB::getDriverName() === 'pgsql') {
                $this->resyncPostgresSequences();
            }

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

            try {
                app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
            } catch (\Throwable $e) {
                // Ignore if Spatie isn't set up yet
            }

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
