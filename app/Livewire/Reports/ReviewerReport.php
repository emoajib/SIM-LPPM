<?php

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

namespace App\Livewire\Reports;

use App\Enums\ProposalStatus;
use App\Livewire\Concerns\HasToast;
use App\Livewire\Traits\WithInstitutionalApproval;
use App\Models\CommunityService;
use App\Models\InstitutionalReport;
use App\Models\Proposal;
use App\Models\ProposalReviewer;
use App\Models\Research;
use App\Models\ReviewLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Laporan Reviewer', 'pageTitle' => 'Laporan Penugasan & Reviewer'])]
class ReviewerReport extends Component
{
    use HasToast, WithInstitutionalApproval, WithPagination;

    #[Url(history: true)]
    public string $activeTab = 'assignment'; // assignment, workload, scoring

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $typeFilter = 'all'; // all, research, community_service

    #[Url(history: true)]
    public string $yearFilter = '';

    public ?string $selectedProposalId = null;

    public ?Proposal $selectedProposal = null;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->hasAnyRole(['admin lppm', 'superadmin', 'rektor', 'kepala lppm'])) {
            abort(403, 'Anda tidak memiliki hak akses untuk halaman ini.');
        }

        $this->yearFilter = (string) date('Y');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedYearFilter(): void
    {
        $this->resetPage();
    }

    public function updatedActiveTab(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->typeFilter = 'all';
        $this->yearFilter = (string) date('Y');
        $this->resetPage();
    }

    public function selectProposal(?string $proposalId): void
    {
        $this->selectedProposalId = $proposalId;
        if ($proposalId) {
            $this->selectedProposal = Proposal::query()
                ->with([
                    'submitter.identity.faculty',
                    'reviewers.user',
                    'reviewers.scores.criteria',
                ])
                ->find($proposalId);
        } else {
            $this->selectedProposal = null;
        }
    }

    #[Computed]
    public function proposals()
    {
        $query = Proposal::query()
            ->whereIn('status', [
                ProposalStatus::WAITING_REVIEWER,
                ProposalStatus::UNDER_REVIEW,
                ProposalStatus::REVIEWED,
                ProposalStatus::APPROVED,
                ProposalStatus::COMPLETED,
            ]);

        return $query
            ->with([
                'submitter.identity.faculty',
                'submitter.identity.studyProgram',
                'detailable',
                'researchScheme',
                'communityServiceScheme',
                'reviewers.user',
            ])
            ->when($this->search, function ($q) {
                $search = (string) $this->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhereHas('submitter', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($this->typeFilter !== 'all', function ($q) {
                $type = (string) $this->typeFilter;
                $detailableType = $type === 'research'
                    ? Research::class
                    : CommunityService::class;
                $q->where('detailable_type', $detailableType);
            })
            ->when($this->yearFilter, function ($q) {
                $year = (int) $this->yearFilter;
                $q->whereYear('created_at', $year);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    #[Computed]
    public function reviewersWorkload()
    {
        return User::role('reviewer')
            ->withCount([
                'reviews as total_assigned' => function ($query) {
                    if ($this->yearFilter) {
                        $query->whereHas('proposal', function ($pq) {
                            $pq->whereYear('created_at', (int) $this->yearFilter);
                        });
                    }
                },
                'reviews as pending_count' => function ($query) {
                    $query->where('status', 'pending');
                    if ($this->yearFilter) {
                        $query->whereHas('proposal', function ($pq) {
                            $pq->whereYear('created_at', (int) $this->yearFilter);
                        });
                    }
                },
                'reviews as completed_count' => function ($query) {
                    $query->where('status', 'completed');
                    if ($this->yearFilter) {
                        $query->whereHas('proposal', function ($pq) {
                            $pq->whereYear('created_at', (int) $this->yearFilter);
                        });
                    }
                },
            ])
            ->with(['identity.faculty'])
            ->get();
    }

    #[Computed]
    public function summaryStats(): array
    {
        $year = (int) $this->yearFilter;

        $totalProposals = Proposal::query()
            ->whereIn('status', [
                ProposalStatus::WAITING_REVIEWER,
                ProposalStatus::UNDER_REVIEW,
                ProposalStatus::REVIEWED,
                ProposalStatus::APPROVED,
                ProposalStatus::COMPLETED,
            ])
            ->when($year, fn ($q) => $q->whereYear('created_at', $year))
            ->count();

        $assigned = Proposal::query()
            ->whereIn('status', [
                ProposalStatus::WAITING_REVIEWER,
                ProposalStatus::UNDER_REVIEW,
                ProposalStatus::REVIEWED,
                ProposalStatus::APPROVED,
                ProposalStatus::COMPLETED,
            ])
            ->when($year, fn ($q) => $q->whereYear('created_at', $year))
            ->has('reviewers')
            ->count();

        $unassigned = $totalProposals - $assigned;

        // Calculate review progress
        $totalReviews = ProposalReviewer::query()
            ->whereHas('proposal', function ($q) use ($year) {
                $q->when($year, fn ($sub) => $sub->whereYear('created_at', $year));
            })
            ->count();

        $completedReviews = ProposalReviewer::query()
            ->whereHas('proposal', function ($q) use ($year) {
                $q->when($year, fn ($sub) => $sub->whereYear('created_at', $year));
            })
            ->where('status', 'completed')
            ->count();

        $progressPercent = $totalReviews > 0 ? round(($completedReviews / $totalReviews) * 100) : 0;

        // Calculate average score of completed reviews
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        $avgScore = round(ReviewLog::query()
            ->whereHas('proposal', function ($q) use ($year) {
                $q->whereIn('status', [
                    ProposalStatus::REVIEWED,
                    ProposalStatus::APPROVED,
                    ProposalStatus::COMPLETED,
                ])->when($year, fn ($sub) => $sub->whereYear('created_at', $year));
            })
            ->whereNotNull('completed_at')
            ->avg('total_score') ?? 0, 1);

        return [
            'total_proposals' => $totalProposals,
            'assigned' => $assigned,
            'unassigned' => $unassigned,
            'progress_percent' => $progressPercent,
            'avg_score' => $avgScore,
        ];
    }

    #[Computed]
    public function availableYears(): array
    {
        $years = Proposal::query()
            ->selectRaw(sql_year().' as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->map(fn ($y) => (string) $y)
            ->toArray();

        return array_filter($years) ?: [(string) date('Y')];
    }

    #[Computed]
    public function institutionalReport(): ?InstitutionalReport
    {
        return $this->getInstitutionalReport('reviewer', (int) $this->yearFilter);
    }

    public function submitToRektor(): void
    {
        $this->submitInstitutionalReport('reviewer', (int) $this->yearFilter, [
            'search' => $this->search,
            'type' => $this->typeFilter,
            'period' => $this->yearFilter,
        ]);
    }

    public function approveReport(): void
    {
        $this->approveInstitutionalReport('reviewer', (int) $this->yearFilter);
    }

    public function rejectReport(): void
    {
        $this->rejectInstitutionalReport('reviewer', (int) $this->yearFilter);
    }

    public function resetReport(): void
    {
        $report = $this->institutionalReport();
        if ($report) {
            $report->delete();
            $this->toastSuccess('Status laporan telah di-reset.');
        }
    }

    public function render(): View
    {
        return view('livewire.reports.reviewer-report');
    }
}
