<?php

namespace App\Livewire\Actions;

use App\Enums\ProposalStatus;
use App\Enums\ReviewStatus;
use App\Models\Proposal;
use App\Models\ProposalReviewer;
use App\Models\Setting;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AssignReviewersAction
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Assign a reviewer to a proposal.
     */
    public function execute(Proposal $proposal, int|string $reviewerId, int $daysToReview = 14): array
    {
        // Allow assignment for both WAITING_REVIEWER and UNDER_REVIEW statuses
        $allowedStatuses = [
            ProposalStatus::WAITING_REVIEWER,
            ProposalStatus::UNDER_REVIEW,
        ];

        if (! in_array($proposal->status, $allowedStatuses)) {
            return [
                'success' => false,
                'message' => 'Proposal harus dalam status menunggu penugasan reviewer atau sedang direview.',
            ];
        }

        // Validate already exists
        $existingReviewer = $proposal->reviewers()->where('user_id', $reviewerId)->first();
        if ($existingReviewer) {
            return [
                'success' => false,
                'message' => 'Reviewer sudah ditugaskan untuk proposal ini.',
            ];
        }

        // Validate max limit
        $requiredCount = (int) Setting::get('reviewer_count_required', 1);
        if ($proposal->reviewers()->count() >= $requiredCount) {
            return [
                'success' => false,
                'message' => "Jumlah reviewer sudah mencapai batas maksimal ({$requiredCount} reviewer).",
            ];
        }

        // Validate reviewer exists
        $reviewer = User::find($reviewerId);
        if (! $reviewer) {
            return [
                'success' => false,
                'message' => 'Reviewer tidak ditemukan.',
            ];
        }

        // 1. CONFLICT OF INTEREST CHECK: Submitter
        if ($proposal->submitter_id === $reviewerId) {
            return [
                'success' => false,
                'message' => 'Pelanggaran CoI: Pengusul tidak boleh menjadi reviewer bagi proposalnya sendiri.',
            ];
        }

        // 2. CONFLICT OF INTEREST CHECK: Team Members
        $isTeamMember = $proposal->teamMembers()->where('users.id', $reviewerId)->exists();
        if ($isTeamMember) {
            return [
                'success' => false,
                'message' => 'Pelanggaran CoI: Anggota tim proposal tidak boleh menjadi reviewer.',
            ];
        }

        try {
            $deadline = Carbon::now()->addDays($daysToReview);

            DB::transaction(function () use ($proposal, $reviewerId, $reviewer, $daysToReview, $deadline): void {
                // Get current round (for new assignments, start at round 1)
                $currentRound = $proposal->reviewers()->max('round') ?? 1;

                // Assign reviewer with new timestamp fields
                ProposalReviewer::create([
                    'proposal_id' => $proposal->id,
                    'user_id' => $reviewerId,
                    'status' => ReviewStatus::PENDING,
                    'round' => $currentRound,
                    'assigned_at' => now(),
                    'deadline_at' => $deadline,
                ]);

                // Log activity
                $proposal->activities()->create([
                    'user_id' => auth()->id(),
                    'activity_type' => 'updated',
                    'description' => 'Penugasan reviewer baru: '.$reviewer->name,
                    'changes' => [
                        'reviewer' => [
                            'old' => '-',
                            'new' => $reviewer->name,
                        ],
                    ],
                ]);

                // Send notifications
                $this->sendNotifications($proposal, $reviewer, $daysToReview);

                // Update proposal status to UNDER_REVIEW if first reviewer assigned
                // (transition from WAITING_REVIEWER to UNDER_REVIEW)
                if ($proposal->status === ProposalStatus::WAITING_REVIEWER) {
                    $proposal->update(['status' => ProposalStatus::UNDER_REVIEW]);
                }
            });

            return [
                'success' => true,
                'message' => 'Berhasil menugaskan reviewer untuk proposal ini.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Gagal menugaskan reviewer: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Send notifications to reviewer and stakeholders
     */
    protected function sendNotifications(Proposal $proposal, User $reviewer, int $daysToReview): void
    {
        $deadline = Carbon::now()->addDays($daysToReview)->format('Y-m-d');

        // Get recipients
        $recipients = collect()
            ->push($reviewer) // The reviewer
            ->push($proposal->submitter) // Submitter
            ->push(User::role('kepala lppm')->first()) // Kepala LPPM
            ->filter()
            ->unique('id')
            ->values();

        $this->notificationService->notifyReviewerAssigned(
            $proposal,
            $reviewer,
            $deadline,
            $recipients
        );
    }
}
