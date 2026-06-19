<?php

declare(strict_types=1);

namespace App\Livewire\Traits;

use App\Enums\ProposalUserStatus;
use App\Models\Proposal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

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

        if ($roleFilter && ! $user->activeHasAnyRole(['admin lppm', 'kepala lppm', 'rektor', 'dekan'])) {
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

    protected function eagerLoadRelations(Builder $query, array $relations = []): Builder
    {
        if (empty($relations)) {
            $relations = [
                'submitter.identity',
                'focusArea',
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
