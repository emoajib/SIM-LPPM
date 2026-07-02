<?php

namespace App\Livewire\Actions;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;

class ApproveProposalAction
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Approve or reject a proposal.
     * Only possible if all reviewers have completed their reviews.
     */
    public function execute(Proposal $proposal, string $decision): array
    {
        // Authorization check - only kepala lppm can approve/reject
        $user = Auth::user();
        if (! $user || ! $user->activeHasRole('kepala lppm')) {
            return [
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk melakukan keputusan ini.',
            ];
        }

        // Normalize decision to lowercase to match enum values
        $decision = strtolower($decision);

        $validDecisions = [
            ProposalStatus::COMPLETED->value,
            ProposalStatus::REJECTED->value,
            ProposalStatus::REVISION_NEEDED->value,
        ];

        if (! in_array($decision, $validDecisions)) {
            return [
                'success' => false,
                'message' => 'Keputusan harus "'.ProposalStatus::COMPLETED->value.'", "'.ProposalStatus::REJECTED->value.'", atau "'.ProposalStatus::REVISION_NEEDED->value.'".',
            ];
        }

        // Check if all reviewers completed (skip for REVISION_SUBMITTED or revision_needed decision)
        if ($proposal->status !== ProposalStatus::REVISION_SUBMITTED
            && $decision !== ProposalStatus::REVISION_NEEDED->value
            && ! $proposal->allReviewsCompleted()) {
            $pendingReviewers = $proposal->getPendingReviewers();

            return [
                'success' => false,
                'message' => 'Belum semua reviewer menyelesaikan review.',
            ];
        }

        // Update proposal status
        $proposal->update(['status' => $decision]);

        // Send Final Notification to Submitter and All Stakeholders
        $this->notificationService->notifyFinalDecision(
            $proposal,
            $decision,
            Auth::user(),
            [$proposal->submitter]
        );

        $message = $decision === 'completed'
            ? 'Proposal berhasil disetujui dan selesai.'
            : 'Proposal ditolak.';

        return [
            'success' => true,
            'message' => $message,
        ];
    }
}
