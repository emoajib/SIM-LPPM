<?php

namespace App\Console\Commands;

use App\Models\BudgetCap;
use App\Models\CommunityServiceScheme;
use App\Models\ResearchScheme;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditBudgetCaps extends Command
{
    protected $signature = 'budget-caps:audit {--dry-run : Hanya tampilkan, tidak mengubah data}';

    protected $description = 'Audit tabel budget_caps: duplikat, semester null, index hilang, skema tanpa pagu.';

    public function handle(): int
    {
        $this->info('=== Audit Budget Caps ===');

        // 1. Unique index check
        $indexes = collect(DB::select('SHOW INDEX FROM budget_caps'))->pluck('Key_name')->unique()->values();
        if (! $indexes->contains('budget_caps_year_semester_unique')) {
            $this->warn('Index unique (year, semester) TIDAK ditemukan. Duplikat bisa terjadi.');
        } else {
            $this->info('Index unique (year, semester): OK');
        }

        // 2. Duplicate rows
        $dupes = DB::table('budget_caps')
            ->select('year', 'semester', DB::raw('COUNT(*) as c'))
            ->groupBy('year', 'semester')
            ->having('c', '>', 1)
            ->get();
        if ($dupes->isEmpty()) {
            $this->info('Duplikat (year, semester): tidak ada.');
        } else {
            $this->warn('Duplikat ditemukan:');
            foreach ($dupes as $d) {
                $this->line("  - {$d->year} / {$d->semester}: {$d->c} baris");
            }
        }

        // 3. Null semester
        $nullSem = BudgetCap::whereNull('semester')->count();
        if ($nullSem > 0) {
            $this->warn("Baris dengan semester NULL: {$nullSem}");
        } else {
            $this->info('Baris semester NULL: tidak ada.');
        }

        // 4. Schemes without any cap (global or per-scheme) across all periods
        $researchSchemes = ResearchScheme::pluck('id');
        $pkmSchemes = CommunityServiceScheme::pluck('id');
        $caps = BudgetCap::all();
        $missingResearch = [];
        $missingPkm = [];
        foreach ($caps as $cap) {
            $sc = is_array($cap->scheme_caps) ? $cap->scheme_caps : [];
            foreach ($researchSchemes as $sid) {
                $has = ! empty($cap->research_budget_cap) || ! empty($sc['research'][$sid] ?? null);
                if (! $has) {
                    $missingResearch[] = "{$cap->year}/{$cap->semester}: skema #{$sid}";
                }
            }
            foreach ($pkmSchemes as $sid) {
                $has = ! empty($cap->community_service_budget_cap) || ! empty($sc['community_service'][$sid] ?? null);
                if (! $has) {
                    $missingPkm[] = "{$cap->year}/{$cap->semester}: skema #{$sid}";
                }
            }
        }
        if (empty($missingResearch) && empty($missingPkm)) {
            $this->info('Semua skema memiliki pagu (default atau per-skema) di setiap periode.');
        } else {
            if ($missingResearch) {
                $this->warn('Penelitian - skema tanpa pagu:');
                foreach ($missingResearch as $m) {
                    $this->line("  - {$m}");
                }
            }
            if ($missingPkm) {
                $this->warn('Pengabdian - skema tanpa pagu:');
                foreach ($missingPkm as $m) {
                    $this->line("  - {$m}");
                }
            }
        }

        $this->info('Total baris budget_caps: '.BudgetCap::count());
        $this->info('Audit selesai (read-only).');

        return self::SUCCESS;
    }
}
