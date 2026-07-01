<?php

namespace App\Livewire\Actions;

use App\Enums\ProposalStatus;
use App\Models\ProposalReviewer;
use App\Models\ReviewLog;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompleteReviewAction
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Complete a review submission.
     */
    public function execute(ProposalReviewer $review, string $comments, string $recommendation): array
    {
        // 0. SELF-REVIEW CHECK: Prevent reviewer from reviewing their own proposal
        if ($review->proposal->submitter_id === Auth::id()) {
            return [
                'success' => false,
                'message' => 'Reviewer tidak dapat mereview proposal sendiri.',
            ];
        }

        // 1. SECURITY: Ownership check
        if ($review->user_id !== Auth::id()) {
            return [
                'success' => false,
                'message' => 'Anda bukan reviewer yang ditugaskan untuk proposal ini.',
            ];
        }

        // 2. ACCOUNTABILITY: Validate scores existence for current round
        if ($review->currentScores()->count() === 0) {
            return [
                'success' => false,
                'message' => 'Anda wajib mengisi skor penilaian sebelum menyerahkan review.',
            ];
        }

        $validRecommendations = [
            ProposalStatus::APPROVED->value,
            ProposalStatus::REJECTED->value,
            ProposalStatus::REVISION_NEEDED->value,
        ];
        if (! in_array($recommendation, $validRecommendations)) {
            return [
                'success' => false,
                'message' => 'Rekomendasi harus "'.ProposalStatus::APPROVED->value.'", "'.ProposalStatus::REJECTED->value.'", atau "'.ProposalStatus::REVISION_NEEDED->value.'".',
            ];
        }

        if ($review->isCompleted()) {
            return [
                'success' => false,
                'message' => 'Review sudah selesai dan tidak dapat diubah.',
            ];
        }

        return DB::transaction(function () use ($review, $comments, $recommendation) {
            // Complete the review
            $review->complete($comments, $recommendation);

            // Create review log for history tracking
            $this->createReviewLog($review, $comments, $recommendation);

            $proposal = $review->proposal;

            // Send notifications
            $this->sendNotifications($proposal, $review->user, $review);

            if ($proposal->allReviewsCompleted()) {
                $proposal->update(['status' => ProposalStatus::REVISION_NEEDED]);

                // Send notification that revision is needed
                $this->sendAllReviewsCompletedNotification($proposal);
            }

            return [
                'success' => true,
                'message' => 'Review berhasil diserahkan.',
            ];
        });
    }

    /**
     * Create a review log entry for history tracking.
     */
    protected function createReviewLog(ProposalReviewer $review, string $comments, string $recommendation): ReviewLog
    {
        // Calculate total score from current round scores
        $totalScore = $review->currentScores()->sum('value');

        return ReviewLog::create([
            'proposal_reviewer_id' => $review->id,
            'proposal_id' => $review->proposal_id,
            'user_id' => $review->user_id,
            'round' => $review->round ?? 1,
            'review_notes' => $comments,
            'recommendation' => $recommendation,
            'total_score' => $totalScore,
            'started_at' => $review->started_at,
            'completed_at' => $review->completed_at ?? now(),
        ]);
    }

    /**
     * Send notifications when a review is completed
     */
    protected function sendNotifications($proposal, User $reviewer, ProposalReviewer $review): void
    {
        // Eager load team members to prevent N+1 lazy loading
        $proposal->load('teamMembers');

        $recipients = collect()
            ->push($proposal->submitter) // Submitter
            ->merge($proposal->teamMembers) // Team Members (now eager loaded)
            ->filter(fn ($user) => $user && $user->id !== $reviewer->id) // Exclude reviewer
            ->unique('id')
            ->values();

        $this->notificationService->notifyReviewCompleted(
            $proposal,
            $reviewer,
            false, // Not all reviews complete yet
            $recipients
        );
    }

    /**
     * Send special notification when all reviews are completed
     */
    protected function sendAllReviewsCompletedNotification($proposal): void
    {
        // Eager load team members to prevent N+1 lazy loading
        $proposal->load('teamMembers');

        $recipients = collect()
            ->push($proposal->submitter) // Submitter
            ->merge(User::role('kepala lppm')->get()) // All Kepala LPPM
            ->merge(User::role('admin lppm')->get()) // All Admin LPPM
            ->push($proposal->submitter?->identity?->faculty?->deanUser) // Specific Dekan from Faculty relation
            ->merge(User::role('dekan')->whereHas('identity', function ($query) use ($proposal) {
                $query->where('faculty_id', $proposal->submitter?->identity?->faculty_id);
            })->get()) // Relevant Dekan(s) by identity (backup)
            ->merge($proposal->teamMembers) // Team Members (now eager loaded)
            ->filter()
            ->unique('id')
            ->values();

        $this->notificationService->notifyReviewCompleted(
            $proposal,
            Auth::user(),
            true, // All reviews complete
            $recipients
        );
    }
}
