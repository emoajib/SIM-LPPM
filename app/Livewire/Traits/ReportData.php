<?php

declare(strict_types=1);

namespace App\Livewire\Traits;

use App\Enums\ProposalUserStatus;
use App\Enums\ReportStatus;
use App\Models\Proposal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager
trait ReportData
{
    protected function buildProposalQuery(string $detailableType, array $statuses): Builder
    {
        $query = Proposal::where('detailable_type', $detailableType)
            ->whereIn('status', $statuses);

        $query = $this->filterByUserAccess($query);

        $user = Auth::user();
        /** @phpstan-ignore-next-line */
        $roleFilter = property_exists($this, 'roleFilter') ? $this->roleFilter : '';

        if ($roleFilter && ! $user->activeHasAnyRole(['admin lppm', 'kepala lppm', 'rektor', 'dekan', 'kaprodi'])) {
            $query = $this->applyRoleFilter($query, $user, $roleFilter);
        }

        return $query;
    }

    protected function applySearchFilter(Builder $query, string $searchTerm): Builder
    {
        if (empty($searchTerm)) {
            return $query;
        }

        $searchPattern = '%'.$searchTerm.'%';

        return $query->where(function ($q) use ($searchPattern) {
            $q->where('title', 'LIKE', $searchPattern)
                ->orWhereHas('submitter', function ($sq) use ($searchPattern) {
                    $sq->where('name', 'LIKE', $searchPattern);
                });
        });
    }

    protected function applyRoleFilter(Builder $query, $user, string $role): Builder
    {
        if ($role === 'ketua') {
            return $query->where('submitter_id', $user->id);
        } elseif ($role === 'anggota') {
            return $query->whereHas('teamMembers', function ($teamQuery) use ($user) {
                $teamQuery->where('user_id', $user->id)
                    ->where('role', 'anggota')
                    ->where('status', ProposalUserStatus::ACCEPTED->value);
            });
        }

        return $query;
    }

    protected function applyYearFilter(Builder $query, string $year): Builder
    {
        if (empty($year)) {
            return $query;
        }

        return $query->whereYear('created_at', $year);
    }

    protected function applySchemeFilter(Builder $query, string $detailableType, string $schemeId): Builder
    {
        if (empty($schemeId) || $schemeId === 'all') {
            return $query;
        }

        $schemeColumn = str_contains($detailableType, 'CommunityService')
            ? 'community_service_scheme_id'
            : 'research_scheme_id';

        return $query->where($schemeColumn, $schemeId);
    }

    protected function applyStatusFilter(Builder $query, string $status): Builder
    {
        if (empty($status) || $status === 'all') {
            return $query;
        }

        if ($status === 'belum_laporan') {
            return $query->whereDoesntHave('progressReports', function ($rq) {
                $rq->where('reporting_period', 'final');
            });
        }

        return $query->whereHas('progressReports', function ($rq) use ($status) {
            $rq->where('reporting_period', 'final')
                ->where('status', $status);
        });
    }

    /**
     * Hitung ringkasan statistik pelaporan informatif.
     *
     * @return array<string, int>
     */
    protected function getReportStatistics(string $detailableType, array $statuses): array
    {
        $baseQuery = $this->buildProposalQuery($detailableType, $statuses);

        /** @phpstan-ignore-next-line */
        if (property_exists($this, 'selectedYear') && ! empty($this->selectedYear)) {
            $baseQuery = $this->applyYearFilter($baseQuery, (string) $this->selectedYear);
        }

        return [
            'total' => (clone $baseQuery)->count(),
            'approved' => (clone $baseQuery)->whereHas('progressReports', fn ($rq) => $rq->where('reporting_period', 'final')->where('status', ReportStatus::APPROVED->value))->count(),
            'in_review' => (clone $baseQuery)->whereHas('progressReports', fn ($rq) => $rq->where('reporting_period', 'final')->whereIn('status', [
                ReportStatus::SUBMITTED->value,
                ReportStatus::APPROVED_BY_DEKAN->value,
            ]))->count(),
            'belum_laporan' => (clone $baseQuery)->whereDoesntHave('progressReports', fn ($rq) => $rq->where('reporting_period', 'final'))->count(),
            'draft' => (clone $baseQuery)->whereHas('progressReports', fn ($rq) => $rq->where('reporting_period', 'final')->where('status', ReportStatus::DRAFT->value))->count(),
            'rejected' => (clone $baseQuery)->whereHas('progressReports', fn ($rq) => $rq->where('reporting_period', 'final')->where('status', ReportStatus::REJECTED->value))->count(),
        ];
    }

    protected function eagerLoadRelations(Builder $query, array $relations = []): Builder
    {
        if (empty($relations)) {
            $relations = [
                'submitter.identity',
                'focusArea',
                'latestFinalReport',
                'progressReports' => fn ($q) => $q->latest(),
            ];
        }

        return $query->with($relations);
    }

    protected function getAvailableYears(string $detailableType, array $statuses): Collection
    {
        $query = $this->buildProposalQuery($detailableType, $statuses);

        return $query->selectRaw('DISTINCT '.sql_year().' as year')
            ->orderByDesc('year')
            ->pluck('year');
    }
}
