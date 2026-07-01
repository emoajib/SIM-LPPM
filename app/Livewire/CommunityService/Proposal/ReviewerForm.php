<?php

namespace App\Livewire\CommunityService\Proposal;

use App\Enums\ProposalStatus;
use App\Livewire\Actions\CompleteReviewAction;
use App\Livewire\Concerns\HasToast;
use App\Models\Proposal;
use App\Models\ProposalReviewer;
use App\Models\ReviewCriteria;
use App\Models\ReviewLog;
use App\Models\ReviewScore;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read Collection|ReviewCriteria[] $activeCriterias
 * @property-read float $totalScore
 * @property-read Proposal|null $proposal
 * @property-read ProposalReviewer|null $myReview
 * @property-read Collection|ProposalReviewer[] $allReviews
 * @property-read bool $canReview
 * @property-read bool $needsAction
 * @property-read bool $hasReviewed
 * @property-read bool $needsReReview
 * @property-read bool $canEditReview
 * @property-read int $reviewRound
 * @property-read mixed $deadline
 * @property-read bool $isOverdue
 * @property-read int|null $daysRemaining
 * @property-read Collection|ReviewLog[] $previousRoundLogs
 * @property-read Collection<int, \Illuminate\Database\Eloquent\Collection<int, ReviewLog>> $allReviewLogs
 */
class ReviewerForm extends Component
{
    use HasToast;

    public string $proposalId = '';

    public bool $showForm = false;

    public string $reviewNotes = '';

    public string $recommendation = '';

    public array $scores = []; // [criteria_id => ['score' => 1-5, 'acuan' => 'text']]

    public function mount(string $proposalId): void
    {
        $this->proposalId = $proposalId;

        // Load existing review data if available
        $myReview = $this->myReview;
        if ($myReview && $myReview->isCompleted()) {
            $this->reviewNotes = $myReview->review_notes ?? '';
            $this->recommendation = $myReview->recommendation ?? '';

            // Load existing scores
            $existingScores = $myReview->scores()->where('round', $myReview->round)->get();
            /** @var ReviewScore $score */
            foreach ($existingScores as $score) {
                $this->scores[$score->review_criteria_id] = [
                    'score' => $score->score,
                    'acuan' => $score->acuan,
                ];
            }
        }

        // Initialize empty scores for active criteria if not exists
        foreach ($this->activeCriterias as $criteria) {
            if (! isset($this->scores[$criteria->id])) {
                $this->scores[$criteria->id] = [
                    'score' => '',
                    'acuan' => '',
                ];
            }
        }

        // Mark as started when form is mounted (if reviewer is viewing)
        $this->markReviewAsStarted();

        // If review is in progress, show the form by default
        if ($this->myReview && $this->myReview->isInProgress()) {
            $this->showForm = true;
        }
    }

    protected function rules(): array
    {
        $rules = [
            'reviewNotes' => 'required|min:10',
            'recommendation' => 'required|in:'.implode(',', [
                ProposalStatus::APPROVED->value,
                ProposalStatus::REJECTED->value,
                ProposalStatus::REVISION_NEEDED->value,
            ]),
        ];

        foreach ($this->activeCriterias as $criteria) {
            $rules["scores.{$criteria->id}.score"] = 'required|integer|min:1|max:5';
            $rules["scores.{$criteria->id}.acuan"] = 'required|string|min:3';
        }

        return $rules;
    }

    protected function validationAttributes(): array
    {
        $attributes = [
            'reviewNotes' => 'Catatan Review',
            'recommendation' => 'Rekomendasi',
        ];

        foreach ($this->activeCriterias as $criteria) {
            $attributes["scores.{$criteria->id}.score"] = "Skor {$criteria->criteria}";
            $attributes["scores.{$criteria->id}.acuan"] = "Acuan {$criteria->criteria}";
        }

        return $attributes;
    }

    /**
     * Mark the review as started when reviewer first opens the form
     */
    protected function markReviewAsStarted(): void
    {
        $review = $this->myReview;
        if ($review && $review->isPending()) {
            $review->markAsStarted();
        }
    }

    #[Computed]
    public function activeCriterias()
    {
        return ReviewCriteria::where('type', 'community_service')
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
    }

    #[Computed]
    public function totalScore(): float
    {
        $total = 0;
        foreach ($this->activeCriterias as $criteria) {
            $score = $this->scores[$criteria->id]['score'] ?? 0;
            if (is_numeric($score)) {
                $total += ($score * $criteria->weight);
            }
        }

        return $total;
    }

    #[Computed]
    public function proposal()
    {
        return Proposal::with([
            'reviewers.user.identity',
            'reviewers.scores.criteria',
        ])->find($this->proposalId);
    }

    #[Computed]
    public function myReview()
    {
        return $this->proposal->reviewers
            ->where('user_id', Auth::id())
            ->first();
    }

    #[Computed]
    public function allReviews()
    {
        return $this->proposal->reviewers;
    }

    #[Computed]
    public function canReview(): bool
    {
        return $this->myReview !== null;
    }

    #[Computed]
    public function needsAction(): bool
    {
        return $this->myReview !== null && (
            $this->myReview->requiresAction() || $this->myReview->isInProgress()
        );
    }

    #[Computed]
    public function hasReviewed(): bool
    {
        $review = $this->myReview;

        return $review && $review->isCompleted();
    }

    #[Computed]
    public function needsReReview(): bool
    {
        $review = $this->myReview;

        return $review && $review->isReReviewRequested();
    }

    #[Computed]
    public function canEditReview(): bool
    {
        $review = $this->myReview;
        if (! $review) {
            return false;
        }

        // Jika rekomendasi sudah approved, tidak bisa edit
        if ($review->recommendation === 'approved') {
            return false;
        }

        // Jika proposal sudah final, tidak bisa edit
        if ($this->proposal->status->isFinal()) {
            return false;
        }

        return $review->isCompleted();
    }

    #[Computed]
    public function reviewRound(): int
    {
        $currentRound = $this->myReview->round ?? 1;

        return $currentRound;
    }

    #[Computed]
    public function deadline()
    {
        return $this->myReview?->deadline_at;
    }

    #[Computed]
    public function isOverdue(): bool
    {
        return $this->myReview?->isOverdue() ?? false;
    }

    #[Computed]
    public function daysRemaining(): ?int
    {
        return $this->myReview?->days_remaining;
    }

    /**
     * Get previous round logs for the current reviewer (for showing history during re-review).
     */
    #[Computed]
    public function previousRoundLogs()
    {
        $review = $this->myReview;
        if (! $review) {
            return collect();
        }

        return ReviewLog::where('proposal_reviewer_id', $review->id)
            ->orderBy('round', 'desc')
            ->get();
    }

    /**
     * Get scores for history (by round)
     */
    public function getScoresForRound(int $round)
    {
        $review = $this->myReview;
        if (! $review) {
            return collect();
        }

        return ReviewScore::where('proposal_reviewer_id', $review->id)
            ->where('round', $round)
            ->with('criteria')
            ->get();
    }

    /**
     * Get all review logs for this proposal (for showing complete history).
     *
     * @return Collection<int, \Illuminate\Database\Eloquent\Collection<int, ReviewLog>>
     */
    #[Computed]
    public function allReviewLogs(): Collection
    {
        return ReviewLog::forProposal($this->proposalId)
            ->with('user')
            ->orderBy('round', 'desc')
            ->orderBy('completed_at', 'desc')
            ->get()
            ->groupBy('round');
    }

    public function toggleForm(): void
    {
        $this->showForm = ! $this->showForm;

        // Mark as started when form is opened
        if ($this->showForm) {
            $this->markReviewAsStarted();
        }
    }

    public function submitReview(CompleteReviewAction $completeReviewAction): void
    {
        $this->validate();

        $review = $this->myReview;

        if (! $review) {
            $message = 'Anda bukan reviewer untuk proposal ini';
            $this->toastError($message);
            $this->dispatch('error', message: $message);

            return;
        }

        try {
            DB::transaction(function () use ($review, $completeReviewAction): void {
                // 1. Save individual scores first
                foreach ($this->activeCriterias as $criteria) {
                    $scoreData = $this->scores[$criteria->id];
                    ReviewScore::updateOrCreate(
                        [
                            'proposal_reviewer_id' => $review->id,
                            'review_criteria_id' => $criteria->id,
                            'round' => $review->round,
                        ],
                        [
                            'acuan' => $scoreData['acuan'],
                            'score' => $scoreData['score'],
                            'weight_snapshot' => $criteria->weight,
                            'value' => $scoreData['score'] * $criteria->weight,
                        ]
                    );
                }

                // 2. Execute CompleteReviewAction
                // This handles: status update, logging, notifications, and final decision check
                $result = $completeReviewAction->execute($review, $this->reviewNotes, $this->recommendation);

                if (! $result['success']) {
                    throw new \Exception($result['message']);
                }
            });

            $message = $this->needsReReview ? 'Review ulang berhasil disubmit' : 'Review berhasil disubmit';

            // Close the form after successful submission
            $this->showForm = false;

            // Clear computed property cache to refresh data immediately
            unset($this->proposal);
            unset($this->myReview);
            unset($this->allReviews);
            unset($this->allReviewLogs);

            // Flash message and dispatch event
            session()->flash('success', $message);
            $this->toastSuccess($message);
            $this->dispatch('review-submitted', proposalId: $this->proposalId);
        } catch (\Exception $e) {
            $message = 'Gagal menyimpan review: '.$e->getMessage();
            $this->addError('error', $message);
            $this->toastError($message);
        }
    }

    public function render(): View
    {
        return view('livewire.community-service.proposal.reviewer-form');
    }
}
