<?php

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

namespace App\Exports;

use App\Enums\ProposalStatus;
use App\Models\CommunityService;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReviewerReportExport implements FromView, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected string $period,
        protected ?string $type = null,
        protected ?string $search = null
    ) {}

    public function view(): View
    {
        $year = (int) $this->period;

        // 1. Fetch Proposals (for assignment and scoring rekap)
        $proposalsQuery = Proposal::query()
            ->whereIn('status', [
                ProposalStatus::WAITING_REVIEWER,
                ProposalStatus::UNDER_REVIEW,
                ProposalStatus::REVIEWED,
                ProposalStatus::APPROVED,
                ProposalStatus::COMPLETED,
            ])
            ->when($year, fn ($q) => $q->whereYear('created_at', $year))
            ->when($this->search, function ($q) {
                $search = (string) $this->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhereHas('submitter', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($this->type && $this->type !== 'all', function ($q) {
                $detailableType = $this->type === 'research'
                    ? Research::class
                    : CommunityService::class;
                $q->where('detailable_type', $detailableType);
            });

        $proposals = $proposalsQuery
            ->with([
                'submitter.identity.faculty',
                'submitter.identity.studyProgram',
                'detailable',
                'researchScheme',
                'communityServiceScheme',
                'reviewers.user',
                'reviewers.scores.criteria',
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        // 2. Fetch Reviewers (for workload rekap)
        $reviewers = User::role('reviewer')
            ->withCount([
                'reviews as total_assigned' => function ($query) use ($year) {
                    if ($year) {
                        $query->whereHas('proposal', function ($pq) use ($year) {
                            $pq->whereYear('created_at', $year);
                        });
                    }
                },
                'reviews as pending_count' => function ($query) use ($year) {
                    $query->where('status', 'pending');
                    if ($year) {
                        $query->whereHas('proposal', function ($pq) use ($year) {
                            $pq->whereYear('created_at', $year);
                        });
                    }
                },
                'reviews as completed_count' => function ($query) use ($year) {
                    $query->where('status', 'completed');
                    if ($year) {
                        $query->whereHas('proposal', function ($pq) use ($year) {
                            $pq->whereYear('created_at', $year);
                        });
                    }
                },
            ])
            ->with(['identity.faculty'])
            ->get();

        return view('exports.reviewer-report', [
            'proposals' => $proposals,
            'reviewers' => $reviewers,
            'period' => $this->period,
            'type' => $this->type,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
        ];
    }
}
