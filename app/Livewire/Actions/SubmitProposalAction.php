<?php

namespace App\Livewire\Actions;

use App\Actions\Kaprodi\KaprodiApprovalAction;
use App\Enums\ProposalStatus;
use App\Models\Proposal;
use App\Models\Setting;
use App\Models\User;
use App\Services\LecturerEligibilityService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubmitProposalAction
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Submit a proposal (change status to SUBMITTED).
     */
    public function execute(Proposal $proposal): array
    {
        // Authorization check - only submitter can submit
        $user = Auth::user();
        if (! $user || ($proposal->submitter_id !== $user->getAuthIdentifier())) {
            return [
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk mengajukan proposal ini.',
            ];
        }

        // Check if proposal can be submitted from current status
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

        // Check if all team members accepted
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

        // Check kaprodi approval (pre-gate before submission)
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

        // Check lecturer eligibility
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

        // Check partner requirement for community service proposals
        if (Setting::get('feature_community_partner_required', true)
            && $proposal->detailable_type === 'App\Models\CommunityService'
            && $proposal->partners()->count() === 0) {
            return [
                'success' => false,
                'message' => 'Proposal Pengabdian Masyarakat wajib memiliki minimal 1 mitra.',
            ];
        }

        // Validate scheme selection before submission
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

        try {
            DB::transaction(function () use ($proposal) {
                $snapshot = app(LecturerEligibilityService::class)->generateSnapshot($proposal->submitter, $proposal);
                $proposal->update([
                    'status' => ProposalStatus::SUBMITTED->value,
                    'qualification_snapshot' => $snapshot,
                ]);
            });

            // Send notifications
            $this->sendNotifications($proposal);

            return [
                'success' => true,
                'message' => 'Proposal berhasil diajukan.',
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
    protected function sendNotifications(Proposal $proposal): void
    {
        // Get recipients: Dean, Team Members
        $submitter = $proposal->submitter;
        $faculty = null;
        $dean = null;

        if ($submitter && $submitter->identity) {
            $faculty = $submitter->identity->faculty;
        }

        if ($faculty) {
            $dean = $faculty->deanUser()->first() ?? User::role('dekan')->whereHas('identity', function ($query) use ($faculty) {
                $query->where('faculty_id', $faculty->id);
            })->first();
        }

        if (! $dean) {
            $dean = User::role('dekan')->first();
        }

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
