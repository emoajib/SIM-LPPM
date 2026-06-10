<?php

namespace App\Actions\Reports;

use App\Enums\ProposalStatus;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Builder;

class GetPartnerReportQuery
{
    /**
     * Execute the action to get a standardized partner collaboration query.
     *
     * @return Builder<Partner>
     */
    public function handle(string $search = '', string $type = '', string $period = '', ?string $facultyId = null): Builder
    {
        return Partner::query()
            ->when($facultyId, fn ($q) => $q->whereHas('proposals.submitter.identity', fn ($i) => $i->where('faculty_id', $facultyId)))
            ->when($search, fn ($q) => $q->where(fn ($inner) => $inner->where('name', 'like', "%{$search}%")
                ->orWhere('institution', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
            ))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->with(['media'])
            ->withCount([
                'proposals' => fn ($q) => $q->when($period, fn ($q2) => $q2->where('start_year', $period)),
                'proposals as approved_count' => fn ($q) => $q->whereIn('status', [ProposalStatus::APPROVED->value, ProposalStatus::COMPLETED->value])
                    ->when($period, fn ($q2) => $q2->where('start_year', $period)),
            ])
            ->withSum(
                ['proposals as total_budget' => fn ($q) => $q->whereIn('status', [ProposalStatus::APPROVED->value, ProposalStatus::COMPLETED->value])
                    ->when($period, fn ($q2) => $q2->where('start_year', $period)),
                ],
                'sbk_value'
            )
            ->orderByDesc('proposals_count');
    }
}
