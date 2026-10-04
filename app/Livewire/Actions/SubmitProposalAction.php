<?php

namespace App\Livewire\Actions;

use App\Actions\Kaprodi\KaprodiApprovalAction;
use App\Enums\ProposalStatus;
use App\Models\Proposal;
use App\Models\Setting;
use App\Models\User;
use App\Services\LecturerEligibilityService;
use App\Services\NotificationService;
use App\Traits\HandlesProposalStateTransitions;
use Illuminate\Support\Facades\Auth;

class SubmitProposalAction
{
    use HandlesProposalStateTransitions;

    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Submit a proposal.
     * For new submissions (DRAFT/NEED_ASSIGNMENT): full validation, status → SUBMITTED.
     * For revision resubmit (REVISION_NEEDED): lightweight validation, status → REVISION_SUBMITTED.
     */
    public function execute(Proposal $proposal): array
    {
        $user = Auth::user();
        if (! $user || ($proposal->submitter_id !== $user->getAuthIdentifier())) {
            return [
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk mengajukan proposal ini.',
            ];
        }

        $allowedStatuses = [
            ProposalStatus::DRAFT,
            ProposalStatus::NEED_ASSIGNMENT,
            ProposalStatus::REVISION_NEEDED,
        ];

        if (! in_array($proposal->status, $allowedStatuses)) {
            return [
                'success' => false,
                'message' => 'Proposal tidak dapat diajukan dari status saat ini.',
            ];
        }

        $isRevision = $proposal->status === ProposalStatus::REVISION_NEEDED;

        // Full validation for new submissions only
        if (! $isRevision) {
            if (! $proposal->allTeamMembersAccepted()) {
                $pendingMembers = $proposal->getPendingTeamMembers();

                return [
                    'success' => false,
                    'message' => sprintf(
                        'Tidak dapat mengirim proposal. %d anggota masih belum menerima undangan.',
                        $pendingMembers->count()
                    ),
                ];
            }

            if (Setting::get('feature_kaprodi_validation', false)) {
                $kaprodiAction = app(KaprodiApprovalAction::class);
                $kaprodiCheck = $kaprodiAction->canSubmit($proposal);

                if (! $kaprodiCheck['can_submit']) {
                    return [
                        'success' => false,
                        'message' => $kaprodiCheck['reason'],
                    ];
                }
            }

            if ($proposal->submitter->activeHasRole('dosen')) {
                $eligibilityService = app(LecturerEligibilityService::class);
                $eligibility = $eligibilityService->checkEligibility($proposal->submitter);

                if (! $eligibility['eligible']) {
                    return [
                        'success' => false,
                        'message' => 'Anda tidak memenuhi syarat untuk mengajukan proposal baru. '.implode(', ', $eligibility['reasons']),
                    ];
                }
            }

            if (Setting::get('feature_community_partner_required', true)
                && $proposal->detailable_type === 'App\Models\CommunityService'
                && $proposal->partners()->count() === 0) {
                return [
                    'success' => false,
                    'message' => 'Proposal Pengabdian Masyarakat wajib memiliki minimal 1 mitra.',
                ];
            }

            if ($proposal->detailable_type === 'App\Models\Research' && ! $proposal->research_scheme_id) {
                return [
                    'success' => false,
                    'message' => 'Skema Penelitian wajib dipilih sebelum mengajukan proposal.',
                ];
            }

            if ($proposal->detailable_type === 'App\Models\CommunityService' && ! $proposal->community_service_scheme_id) {
                return [
                    'success' => false,
                    'message' => 'Skema Pengabdian Masyarakat wajib dipilih sebelum mengajukan proposal.',
                ];
            }
        }

        if ($proposal->budgetItems()->count() === 0) {
            return [
                'success' => false,
                'message' => 'RAB (Rencana Anggaran Biaya) wajib diisi sebelum mengajukan proposal.',
            ];
        }

        // Validate substance file exists (proposal document) on detailable
        $detailable = $proposal->detailable;
        if (! $detailable || ! method_exists($detailable, 'hasMedia') || ! $detailable->hasMedia('substance_file')) {
            return [
                'success' => false,
                'message' => 'File substansi proposal wajib diunggah sebelum mengajukan.',
            ];
        }

        try {
            $newStatus = $isRevision ? ProposalStatus::REVISION_SUBMITTED : ProposalStatus::SUBMITTED;
            $expectedStatus = $proposal->status;

            $result = $this->transitionProposal(
                $proposal,
                $expectedStatus,
                $newStatus,
                function ($updatedProposal) use ($isRevision) {
                    $snapshot = $isRevision ? $updatedProposal->qualification_snapshot
                        : app(LecturerEligibilityService::class)->generateSnapshot($updatedProposal->submitter, $updatedProposal);

                    $updatedProposal->update([
                        'qualification_snapshot' => $snapshot,
                    ]);
                }
            );

            if (! $result['success']) {
                return $result;
            }

            $this->sendNotifications($proposal, $isRevision);

            return [
                'success' => true,
                'message' => $isRevision ? 'Revisi proposal berhasil diajukan.' : 'Proposal berhasil diajukan.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Gagal mengajukan proposal: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Send notifications to relevant stakeholders
     */
    protected function sendNotifications(Proposal $proposal, bool $isRevision = false): void
    {
        $submitter = $proposal->submitter;

        if ($isRevision) {
            // Revision resubmit: notify Kepala LPPM + Admin LPPM only
            $recipients = collect()
                ->push(User::role('kepala lppm')->first())
                ->push(User::role('admin lppm')->first())
                ->filter()
                ->unique('id')
                ->values();

            $this->notificationService->notifyProposalSubmitted(
                $proposal,
                $submitter,
                $recipients
            );

            return;
        }

        // New submission: notify Dean + Team Members
        $faculty = $submitter->identity?->faculty;

        $dean = $faculty
            ? ($faculty->deanUser()->first()
                ?? User::role('dekan')->whereHas('identity', fn ($q) => $q->where('faculty_id', $faculty->id))->first())
            : User::role('dekan')->first();

        $teamMembers = $proposal->teamMembers()->where('user_id', '!=', $proposal->submitter_id)->get();

        $recipients = collect()
            ->push($dean)
            ->merge($teamMembers)
            ->filter()
            ->unique('id')
            ->values();

        $this->notificationService->notifyProposalSubmitted(
            $proposal,
            $submitter,
            $recipients
        );
    }
}
