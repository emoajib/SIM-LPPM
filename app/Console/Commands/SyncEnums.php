<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use ReflectionClass;

class SyncEnums extends Command
{
    protected $signature = 'enum:sync {--check : Only check for drift, do not fix} {--database= : Database connection to use}';
    protected $description = 'Sync database CHECK constraints with PHP Enums';

    public function handle(): int
    {
        $connection = $this->option('database') ?: config('database.default');
        $isCheckOnly = $this->option('check');

        $this->info("Checking enum synchronization on connection: {$connection}");
        $this->info('Mode: ' . ($isCheckOnly ? 'CHECK ONLY (no fixes)' : 'FIX MODE'));

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

            if (!$constraint) {
                $this->error("❌ Missing CHECK constraint: {$constraintName} on {$table}.{$col}");
                $hasErrors = true;

                if (!$isCheckOnly) {
                    $this->createCheckConstraint($connection, $table, $col, $constraintName, $enumClass);
                }
                continue;
            }

            $expectedValues = $this->getEnumValues($enumClass);
            $expectedPattern = "/{$col} IN \('?" . implode("', '?", $expectedValues) . "'?\)/";

            if (!preg_match($expectedPattern, $constraint->def)) {
                $this->error("❌ CHECK constraint mismatch on {$table}.{$col}");
                $this->line("  Expected pattern: " . $expectedPattern);
                $this->line("  Found: " . $constraint->def);
                $hasErrors = true;

                if (!$isCheckOnly) {
                    $this->recreateCheckConstraint($connection, $table, $col, $constraintName, $enumClass);
                }
            } else {
                $this->line("✅ {$table}.{$col} - CHECK constraint matches Enum");
            }
        } catch (\Exception $e) {
            $this->error("❌ Error checking {$table}.{$col}: " . $e->getMessage());
            $hasErrors = true;
        }
        }

        if ($hasErrors && $isCheckOnly) {
            $this->error('Enum drift detected! Run without --check to fix.');
            return 1;
        }

        if (!$hasErrors) {
            $this->info('✅ All enum constraints synchronized');
        }

        return $hasErrors ? 1 : 0;
    }

    private function getEnumMap(): array
    {
        return [
            // Existing enums
            'proposals.status' => \App\Enums\ProposalStatus::class,
            'proposal_reviewer.status' => \App\Enums\ReviewStatus::class,
            'proposal_reviewer.recommendation' => \App\Enums\ReviewRecommendation::class,
            'progress_reports.status' => \App\Enums\ReportStatus::class,
            'progress_reports.reporting_period' => \App\Enums\ReportingPeriod::class,
            'identities.type' => \App\Enums\IdentityType::class,
            'mandatory_outputs.status_type' => \App\Enums\OutputStatusType::class,
            'mandatory_outputs.author_status' => \App\Enums\AuthorStatus::class,
            'additional_outputs.status' => \App\Enums\AdditionalOutputStatus::class,
            'institutional_reports.status' => \App\Enums\InstitutionalReportStatus::class,
            'kaprodi_approvals.status' => \App\Enums\KaprodiStatus::class,
            'letters.team_source' => \App\Enums\TeamSource::class,
            'document_signatures.mode' => \App\Enums\SignatureMode::class,
            
            // New enums
            'review_logs.recommendation' => \App\Enums\ReviewRecommendation::class,
            'proposal_user.role' => \App\Enums\ProposalUserRole::class,
            'proposal_user.status' => \App\Enums\ProposalUserStatus::class,
            'monev_reviews.status' => \App\Enums\MonevReviewStatus::class,
            'monev_reviews.semester' => \App\Enums\MonevReviewSemester::class,
            'research_schemes.strata' => \App\Enums\ResearchSchemeStrata::class,
            'budget_groups.proposal_type' => \App\Enums\BudgetGroupProposalType::class,
            'budget_groups.percentage_type' => \App\Enums\BudgetGroupPercentageType::class,
            'strata.category' => \App\Enums\StrataCategory::class,
            'additional_outputs.status' => \App\Enums\AdditionalOutputStatusType::class,
            'progress_reports.status' => \App\Enums\ProgressReportStatus::class,
            'proposal_monevs.semester' => \App\Enums\ProposalMonevSemester::class,
            'manual_books.status' => \App\Enums\ManualBookStatus::class,
            'iku_output_types.group' => \App\Enums\IkuOutputTypeGroup::class,
            'policy_involvements.level' => \App\Enums\PolicyInvolvementLevel::class,
            'policy_involvements.status' => \App\Enums\PolicyInvolvementStatus::class,
        ];
    }

    private function getEnumValues(string $enumClass): array
    {
        if (!class_exists($enumClass)) {
            $this->warn("Enum class not found: {$enumClass}");
            return [];
        }

        $reflection = new ReflectionClass($enumClass);
        if (!$reflection->isEnum()) {
            $this->warn("Not an enum class: {$enumClass}");
            return [];
        }

        return array_map(fn($case) => $case->getValue(), $reflection->getCases());
    }

    private function createCheckConstraint(string $connection, string $table, string $column, string $constraintName, string $enumClass): void
    {
        $values = $this->getEnumValues($enumClass);
        if (empty($values)) {
            return;
        }

        $valuesList = implode(', ', array_map(fn($v) => "'{$v}'", $values));

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

        $valuesList = implode(', ', array_map(fn($v) => "'{$v}'", $values));

        DB::connection($connection)->statement("
            ALTER TABLE {$table}
            ADD CONSTRAINT {$constraintName}
            CHECK ({$column} IN ({$valuesList}))
        ");

        $this->info("✅ Recreated CHECK constraint: {$constraintName}");
    }
}