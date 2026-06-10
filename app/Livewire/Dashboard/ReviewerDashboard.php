<?php

namespace App\Livewire\Dashboard;

use App\Enums\ReviewStatus;
use App\Models\Proposal;
use App\Models\ProposalReviewer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Dashboard Reviewer', 'pageTitle' => 'Ruang Penilaian', 'pageSubtitle' => 'Kelola tugas review, deadline usulan, dan standarisasi kualitas riset'])]
class ReviewerDashboard extends Component
{
    public $user;

    public $roleName;

    public $stats = [];

    public $recentResearch = [];

    public $recentCommunityService = [];

    public $researchReviewerStats = [];

    public $communityServiceReviewerStats = [];

    public $overdueReviews = [];

    public $dueSoonReviews = [];

    public $reReviewNeeded = [];

    public $selectedYear;

    public $availableYears = [];

    public function mount(): void
    {
        $this->user = Auth::user();
        $this->roleName = active_role();
        $this->selectedYear = date('Y');
        $this->availableYears = $this->getAvailableYears();

        $this->loadAnalytics();
    }

    public function updatedSelectedYear(): void
    {
        $this->loadAnalytics();
    }

    private function getAvailableYears(): array
    {
        $years = Proposal::select(DB::raw(sql_year().' as year'))
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        if (empty($years)) {
            $years = [date('Y')];
        }

        return $years;
    }

    public function loadAnalytics(): void
    {
        $yearFilter = $this->selectedYear;

        // OPTIMIZED: Single aggregated query for all stats (replaces 8 separate count queries)
        $this->loadStats($yearFilter);

        // OPTIMIZED: Single query for reviewer stats by type
        $this->loadReviewerStatsByType($yearFilter);

        // OPTIMIZED: Single query for all urgent reviews (overdue + due soon + re-review)
        $this->loadUrgentReviews($yearFilter);

        // Load recent proposals needing review
        $this->loadRecentProposals($yearFilter);
    }

    /**
     * Load all stats in a single aggregated query.
     * Replaces 8 separate count queries with 1 grouped query.
     */
    private function loadStats(string $yearFilter): void
    {
        $statsRaw = ProposalReviewer::query()
            ->where('user_id', $this->user->id)
            ->whereHas('proposal', fn ($q) => $q->whereYear('created_at', $yearFilter))
            ->join('proposals', 'proposal_reviewer.proposal_id', '=', 'proposals.id')
            ->select([
                'proposal_reviewer.status',
                DB::raw("CASE 
                    WHEN proposals.detailable_type LIKE '%Research' THEN 'research' 
                    ELSE 'community_service' 
                END as type"),
                DB::raw('COUNT(*) as count'),
            ])
            ->groupBy('proposal_reviewer.status', 'type')
            ->get();

        $this->stats = $this->transformStats($statsRaw);
    }

    /**
     * Transform raw stats query result into stats array.
     */
    private function transformStats(Collection $raw): array
    {
        $research = $raw->where('type', 'research');
        $communityService = $raw->where('type', 'community_service');

        return [
            'research_assigned' => $research->sum('count'),
            'research_completed' => $research->where('status', ReviewStatus::COMPLETED)->sum('count'),
            'research_pending' => $research->where('status', ReviewStatus::PENDING)->sum('count'),
            'research_re_review' => $research->where('status', ReviewStatus::RE_REVIEW_REQUESTED)->sum('count'),
            'community_service_assigned' => $communityService->sum('count'),
            'community_service_completed' => $communityService->where('status', ReviewStatus::COMPLETED)->sum('count'),
            'community_service_pending' => $communityService->where('status', ReviewStatus::PENDING)->sum('count'),
            'community_service_re_review' => $communityService->where('status', ReviewStatus::RE_REVIEW_REQUESTED)->sum('count'),
        ];
    }

    /**
     * Load reviewer stats grouped by type.
     */
    private function loadReviewerStatsByType(string $yearFilter): void
    {
        $allReviewerStats = ProposalReviewer::with('proposal')
            ->where('user_id', $this->user->id)
            ->whereHas('proposal', fn ($query) => $query->whereYear('created_at', $yearFilter))
            ->get();

        $this->researchReviewerStats = $allReviewerStats->filter(
            fn ($r) => str_contains($r->proposal->detailable_type, 'Research')
        );

        $this->communityServiceReviewerStats = $allReviewerStats->filter(
            fn ($r) => str_contains($r->proposal->detailable_type, 'CommunityService')
        );
    }

    /**
     * Load all urgent reviews in a single query.
     * Replaces 3 separate queries for overdue, due soon, and re-review.
     */
    private function loadUrgentReviews(string $yearFilter): void
    {
        $urgentReviews = ProposalReviewer::with(['proposal.submitter', 'proposal.detailable', 'proposal.researchScheme', 'proposal.communityServiceScheme'])
            ->where('user_id', $this->user->id)
            ->whereHas('proposal', fn ($q) => $q->whereYear('created_at', $yearFilter))
            ->where(function ($query) {
                $query->where(function ($q) {
                    // Overdue: past deadline, not completed
                    $q->where('deadline_at', '<', now())
                        ->whereNotIn('status', [ReviewStatus::COMPLETED]);
                })
                    ->orWhere(function ($q) {
                        // Due soon: within 3 days
                        $q->whereBetween('deadline_at', [now(), now()->addDays(3)])
                            ->whereNotIn('status', [ReviewStatus::COMPLETED]);
                    })
                    ->orWhere('status', ReviewStatus::RE_REVIEW_REQUESTED);
            })
            ->orderBy('deadline_at', 'asc')
            ->limit(30)
            ->get();

        // Group in PHP (much faster than 3 separate DB queries)
        $this->overdueReviews = $urgentReviews
            ->filter(fn ($r) => $r->deadline_at && $r->deadline_at->isPast() && $r->status !== ReviewStatus::COMPLETED)
            ->take(10)
            ->values();

        $this->dueSoonReviews = $urgentReviews
            ->filter(fn ($r) => $r->deadline_at
                && $r->deadline_at->isFuture()
                && $r->deadline_at->diffInDays(now()) <= 3
                && $r->status !== ReviewStatus::COMPLETED)
            ->take(10)
            ->values();

        $this->reReviewNeeded = $urgentReviews
            ->where('status', ReviewStatus::RE_REVIEW_REQUESTED)
            ->take(10)
            ->values();
    }

    /**
     * Load recent proposals needing review.
     */
    private function loadRecentProposals(string $yearFilter): void
    {
        $pendingStatuses = [ReviewStatus::PENDING, ReviewStatus::IN_PROGRESS, ReviewStatus::RE_REVIEW_REQUESTED];

        $recentProposals = Proposal::with(['submitter', 'researchScheme', 'communityServiceScheme'])
            ->whereHas('reviewers', function ($query) use ($pendingStatuses) {
                $query->where('user_id', $this->user->id)
                    ->whereIn('status', $pendingStatuses);
            })
            ->whereYear('created_at', $yearFilter)
            ->latest()
            ->limit(20)
            ->get();

        $this->recentResearch = $recentProposals
            ->filter(fn ($p) => str_contains($p->detailable_type, 'Research'))
            ->take(10)
            ->values();

        $this->recentCommunityService = $recentProposals
            ->filter(fn ($p) => str_contains($p->detailable_type, 'CommunityService'))
            ->take(10)
            ->values();
    }

    public function render()
    {
        return view('livewire.dashboard.reviewer-dashboard');
    }
}
