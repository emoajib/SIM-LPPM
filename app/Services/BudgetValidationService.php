<?php

namespace App\Services;

use App\Models\BudgetCap;
use App\Models\BudgetGroup;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class BudgetValidationService
{
    public function validateBudgetGroupPercentages(
        array $budgetItems,
        string $proposalType,
        ?int $currentYear = null,
        ?string $semester = null,
        ?int $schemeId = null
    ): void {
        $proposalType = str_replace('-', '_', $proposalType);

        if (empty($budgetItems)) {
            return;
        }

        $currentYear ??= (int) date('Y');
        $semester ??= 'ganjil';

        $budgetCapModel = BudgetCap::where('year', $currentYear)
            ->when($semester && Schema::hasColumn('budget_caps', 'semester'), fn ($q) => $q->where('semester', $semester))
            ->first();

        if ($budgetCapModel && ! $budgetCapModel->enforce_percentage) {
            return;
        }

        $budgetCap = BudgetCap::getCapForPeriod($currentYear, $semester, $proposalType, $schemeId);

        if ($budgetCap === null || $budgetCap <= 0) {
            throw ValidationException::withMessages([
                'budget_items' => [
                    sprintf(
                        'Batas anggaran untuk %s tahun %s (%s) belum diatur. Silakan hubungi Admin LPPM.',
                        $proposalType === 'research' ? 'Penelitian' : 'Pengabdian Masyarakat',
                        $currentYear,
                        ucfirst($semester)
                    ),
                ],
            ]);
        }

        $budgetGroups = BudgetGroup::forProposalType($proposalType)
            ->whereNotNull('percentage')
            ->where('is_active', true)
            ->get();
        $errors = [];

        foreach ($budgetGroups as $group) {
            $groupTotal = collect($budgetItems)
                ->where('budget_group_id', $group->id)
                ->sum(fn ($item) => (float) ($item['total'] ?? 0));

            $percentageUsed = ($groupTotal / $budgetCap) * 100;
            $allowedPercentage = (float) $group->percentage;

            // Treat null percentage_type as 'max' for backward compatibility
            $percentageType = $group->percentage_type ?? 'max';

            if ($percentageType === 'max' && $percentageUsed > $allowedPercentage) {
                $errors[] = sprintf(
                    'Kelompok anggaran "%s" melebihi batas MAKSIMAL %s%%. Saat ini: %s%% (Rp %s dari batas anggaran Rp %s)',
                    $group->name,
                    number_format($allowedPercentage, 2),
                    number_format($percentageUsed, 2),
                    number_format($groupTotal, 0, ',', '.'),
                    number_format($budgetCap, 0, ',', '.')
                );
            } elseif ($percentageType === 'min' && $percentageUsed < $allowedPercentage) {
                $errors[] = sprintf(
                    'Kelompok anggaran "%s" belum mencapai batas MINIMAL %s%%. Saat ini: %s%%',
                    $group->name,
                    number_format($allowedPercentage, 2),
                    number_format($percentageUsed, 2)
                );
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages([
                'budget_items' => $errors,
            ]);
        }
    }

    public function validateBudgetCap(
        array $budgetItems,
        string $proposalType,
        ?int $currentYear = null,
        ?string $semester = null,
        ?int $schemeId = null
    ): void {
        $proposalType = str_replace('-', '_', $proposalType);

        if (empty($budgetItems)) {
            return;
        }

        $totalBudget = $this->calculateTotalBudget($budgetItems);

        if ($totalBudget <= 0) {
            return;
        }

        $currentYear ??= (int) date('Y');
        $semester ??= 'ganjil';
        $budgetCap = BudgetCap::getCapForPeriod($currentYear, $semester, $proposalType, $schemeId);

        if ($budgetCap === null) {
            return;
        }

        if ($totalBudget > $budgetCap) {
            $typeLabel = $proposalType === 'research' ? 'Penelitian' : 'Pengabdian Masyarakat';
            throw ValidationException::withMessages([
                'budget_items' => [
                    sprintf(
                        'Total anggaran melebihi batas maksimal untuk %s tahun %s (%s). Batas: Rp %s, Total saat ini: Rp %s',
                        $typeLabel,
                        $currentYear,
                        ucfirst($semester),
                        number_format($budgetCap, 0, ',', '.'),
                        number_format($totalBudget, 0, ',', '.')
                    ),
                ],
            ]);
        }
    }

    public function calculateTotalBudget(array $budgetItems): float
    {
        return collect($budgetItems)->sum(fn ($item) => (float) ($item['total'] ?? 0));
    }

    /**
     * Validasi delta amandemen RAB terhadap versi aktif.
     * Lebih longgar dari aturan pengajuan awal: total boleh lewat pagu
     * sejauh dalam toleransi, dan tiap kelompok-max boleh tumbuh sejauh
     * dalam toleransi di atas nilai terbesar (versi lama vs pagu).
     * Tanpa pagu terkonfigurasi → fallback ke aturan ketat lama.
     *
     * @param  array<int, array{budget_group_id: mixed, total: mixed}>  $oldItems
     * @param  array<int, array{budget_group_id: mixed, total: mixed}>  $newItems
     */
    public function validateAmendmentDelta(
        array $oldItems,
        array $newItems,
        string $proposalType,
        ?int $currentYear = null,
        ?string $semester = null,
        ?int $schemeId = null
    ): void {
        $proposalType = str_replace('-', '_', $proposalType);
        $currentYear ??= (int) date('Y');
        $semester ??= 'ganjil';

        $budgetCapModel = BudgetCap::where('year', $currentYear)
            ->when($semester && Schema::hasColumn('budget_caps', 'semester'), fn ($q) => $q->where('semester', $semester))
            ->first();

        $budgetCap = BudgetCap::getCapForPeriod($currentYear, $semester, $proposalType, $schemeId);

        if ($budgetCap === null || $budgetCap <= 0) {
            $this->validateBudgetCap($newItems, $proposalType, $currentYear, $semester, $schemeId);
            $this->validateBudgetGroupPercentages($newItems, $proposalType, $currentYear, $semester, $schemeId);

            return;
        }

        $tolerance = max(0, (float) ($budgetCapModel ? $budgetCapModel->amendment_tolerance_percent ?? 10 : 10)) / 100;
        $typeLabel = $proposalType === 'research' ? 'Penelitian' : 'Pengabdian Masyarakat';

        $newTotal = $this->calculateTotalBudget($newItems);
        if ($newTotal > $budgetCap * (1 + $tolerance)) {
            throw ValidationException::withMessages([
                'budget_items' => [
                    sprintf(
                        'Total amandemen melebihi pagu + toleransi %s%% untuk %s tahun %s (%s). Batas: Rp %s, total usulan: Rp %s',
                        number_format($tolerance * 100, 2),
                        $typeLabel,
                        $currentYear,
                        ucfirst($semester),
                        number_format($budgetCap * (1 + $tolerance), 0, ',', '.'),
                        number_format($newTotal, 0, ',', '.')
                    ),
                ],
            ]);
        }

        if ($budgetCapModel && ! $budgetCapModel->enforce_percentage) {
            return;
        }

        $budgetGroups = BudgetGroup::forProposalType($proposalType)
            ->whereNotNull('percentage')
            ->where('is_active', true)
            ->get();

        $errors = [];
        foreach ($budgetGroups as $group) {
            $oldGroupTotal = collect($oldItems)->where('budget_group_id', $group->id)->sum(fn ($item) => (float) ($item['total'] ?? 0));
            $newGroupTotal = collect($newItems)->where('budget_group_id', $group->id)->sum(fn ($item) => (float) ($item['total'] ?? 0));
            $allowedPercentage = (float) $group->percentage;
            $percentageType = $group->percentage_type ?? 'max';

            if ($percentageType === 'max') {
                $ceiling = max($oldGroupTotal, $allowedPercentage / 100 * $budgetCap) * (1 + $tolerance);
                if ($newGroupTotal > $ceiling) {
                    $errors[] = sprintf(
                        'Kelompok "%s" melebihi pagu + toleransi %s%% (maks Rp %s; usulan Rp %s).',
                        $group->name,
                        number_format($tolerance * 100, 2),
                        number_format($ceiling, 0, ',', '.'),
                        number_format($newGroupTotal, 0, ',', '.')
                    );
                }
            } elseif ($percentageType === 'min' && $newGroupTotal < ($allowedPercentage / 100 * $budgetCap) * (1 - $tolerance)) {
                $errors[] = sprintf(
                    'Kelompok "%s" di bawah batas minimal %s%% − toleransi (usulan Rp %s).',
                    $group->name,
                    number_format($allowedPercentage, 2),
                    number_format($newGroupTotal, 0, ',', '.')
                );
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['budget_items' => $errors]);
        }
    }

    public function getBudgetSummary(array $budgetItems, string $proposalType, ?int $currentYear = null, ?string $semester = null, ?int $schemeId = null): array
    {
        $proposalType = str_replace('-', '_', $proposalType);

        $currentYear ??= (int) date('Y');
        $semester ??= 'ganjil';
        $budgetCap = BudgetCap::getCapForPeriod($currentYear, $semester, $proposalType, $schemeId) ?? 0;
        $totalBudget = $this->calculateTotalBudget($budgetItems);
        $remainingBudget = max(0, $budgetCap - $totalBudget);

        return [
            'total' => $totalBudget,
            'cap' => $budgetCap,
            'remaining' => $remainingBudget,
            'percentage_used' => $budgetCap > 0 ? ($totalBudget / $budgetCap) * 100 : 0,
        ];
    }
}
