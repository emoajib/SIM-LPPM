<?php

namespace App\Console\Commands;

use App\Enums\AdditionalOutputStatusType;
use App\Enums\AuthorStatus;
use App\Enums\BudgetGroupPercentageType;
use App\Enums\BudgetGroupProposalType;
use App\Enums\IdentityType;
use App\Enums\IkuOutputTypeGroup;
use App\Enums\InstitutionalReportStatus;
use App\Enums\KaprodiStatus;
use App\Enums\ManualBookStatus;
use App\Enums\MonevReviewSemester;
use App\Enums\MonevReviewStatus;
use App\Enums\OutputStatusType;
use App\Enums\PolicyInvolvementLevel;
use App\Enums\PolicyInvolvementStatus;
use App\Enums\ProposalMonevSemester;
use App\Enums\ProposalStatus;
use App\Enums\ProposalUserRole;
use App\Enums\ProposalUserStatus;
use App\Enums\ReportingPeriod;
use App\Enums\ReportStatus;
use App\Enums\ResearchSchemeStrata;
use App\Enums\ReviewRecommendation;
use App\Enums\ReviewStatus;
use App\Enums\SignatureMode;
use App\Enums\StrataCategory;
use App\Enums\TeamSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncEnums extends Command
{
    protected $signature = 'enum:sync {--check : Only check for drift, do not fix} {--database= : Database connection to use}';

    protected $description = 'Sync database CHECK constraints with PHP Enums';

    public function handle(): int
    {
        $connection = $this->option('database') ?: config('database.default');
        $isCheckOnly = $this->option('check');

        $this->info("Checking enum synchronization on connection: {$connection}");
        $this->info('Mode: '.($isCheckOnly ? 'CHECK ONLY (no fixes)' : 'FIX MODE'));

        $enumMap = $this->getEnumMap();
        $hasErrors = false;

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
                    $this->error("❌ Missing CHECK constraint: {$constraintName} on {$table}.{$col}");
                    $hasErrors = true;

                    if (! $isCheckOnly) {
                        $this->createCheckConstraint($connection, $table, $col, $constraintName, $enumClass);
                    }

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
                    $this->error("❌ CHECK constraint mismatch on {$table}.{$col}");
                    $this->line('  Expected pattern: '.$expectedPattern);
                    $this->line('  Found: '.$constraint->def);
                    $hasErrors = true;

                    if (! $isCheckOnly) {
                        $this->recreateCheckConstraint($connection, $table, $col, $constraintName, $enumClass);
                    }
                } else {
                    $this->line("✅ {$table}.{$col} - CHECK constraint matches Enum");
                }
            } catch (\Exception $e) {
                $this->error("❌ Error checking {$table}.{$col}: ".$e->getMessage());
                $hasErrors = true;
            }
        }

        if ($hasErrors && $isCheckOnly) {
            $this->error('Enum drift detected! Run without --check to fix.');

            return 1;
        }

        if (! $hasErrors) {
            $this->info('✅ All enum constraints synchronized');
        }

        return $hasErrors ? 1 : 0;
    }

    private function getEnumMap(): array
    {
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        // PENTING: Jangan tambahkan duplicate key — PHP array akan silently override entry sebelumnya.
        // Setiap 'table.column' harus muncul tepat SATU kali dengan enum class yang tepat.
        return [
            // Core proposal flow
            'proposals.status' => ProposalStatus::class,
            'proposal_reviewer.status' => ReviewStatus::class,
            'proposal_reviewer.recommendation' => ReviewRecommendation::class,

            // Progress reports — menggunakan ReportStatus (5 values: draft, submitted,
            // approved_by_dekan, approved, rejected). BUKAN ProgressReportStatus (3 values).
            'progress_reports.status' => ReportStatus::class,
            'progress_reports.reporting_period' => ReportingPeriod::class,

            // Identity & user
            'identities.type' => IdentityType::class,
            'proposal_user.role' => ProposalUserRole::class,
            'proposal_user.status' => ProposalUserStatus::class,

            // Outputs — additional_outputs.status menggunakan AdditionalOutputStatusType
            // (hasil refactor Batch 2/3). AdditionalOutputStatus (lama) tidak lagi dipakai
            // sebagai constraint di DB setelah refactor.
            'mandatory_outputs.status_type' => OutputStatusType::class,
            'mandatory_outputs.author_status' => AuthorStatus::class,
            'additional_outputs.status' => AdditionalOutputStatusType::class,

            // Reports & reviews
            'institutional_reports.status' => InstitutionalReportStatus::class,
            'review_logs.recommendation' => ReviewRecommendation::class,
            'monev_reviews.status' => MonevReviewStatus::class,
            'monev_reviews.semester' => MonevReviewSemester::class,

            // Schemes & budgets
            'research_schemes.strata' => ResearchSchemeStrata::class,
            'budget_groups.proposal_type' => BudgetGroupProposalType::class,
            'budget_groups.percentage_type' => BudgetGroupPercentageType::class,
            'strata.category' => StrataCategory::class,

            // Approvals & other
            'proposal_kaprodi_approvals.status' => KaprodiStatus::class,
            'letters.team_source' => TeamSource::class,
            'document_signatures.mode' => SignatureMode::class,
            'proposal_monevs.semester' => ProposalMonevSemester::class,
            'manual_books.status' => ManualBookStatus::class,
            'iku_output_types.group' => IkuOutputTypeGroup::class,
            'policy_involvements.level' => PolicyInvolvementLevel::class,
            'policy_involvements.status' => PolicyInvolvementStatus::class,
        ];
    }

    private function getEnumValues(string $enumClass): array
    {
        if (! enum_exists($enumClass)) {
            $this->warn("Enum class not found: {$enumClass}");

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

    private function createCheckConstraint(string $connection, string $table, string $column, string $constraintName, string $enumClass): void
    {
        $values = $this->getEnumValues($enumClass);
        if (empty($values)) {
            return;
        }

        $valuesList = implode(', ', array_map(fn ($v) => "'{$v}'", $values));

        DB::connection($connection)->statement("
            ALTER TABLE {$table}
            ADD CONSTRAINT {$constraintName}
            CHECK ({$column} IN ({$valuesList}))
        ");

        $this->info("✅ Created CHECK constraint: {$constraintName}");
    }

    private function recreateCheckConstraint(string $connection, string $table, string $column, string $constraintName, string $enumClass): void
    {
        $values = $this->getEnumValues($enumClass);
        if (empty($values)) {
            return;
        }

        DB::connection($connection)->statement("
            ALTER TABLE {$table}
            DROP CONSTRAINT IF EXISTS {$constraintName}
        ");

        $valuesList = implode(', ', array_map(fn ($v) => "'{$v}'", $values));

        DB::connection($connection)->statement("
            ALTER TABLE {$table}
            ADD CONSTRAINT {$constraintName}
            CHECK ({$column} IN ({$valuesList}))
        ");

        $this->info("✅ Recreated CHECK constraint: {$constraintName}");
    }
}
