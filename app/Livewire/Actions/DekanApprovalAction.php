<?php

namespace App\Livewire\Actions;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use App\Models\Setting;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DekanApprovalAction
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Execute the Dekan approval action
     *
     * @param  string  $decision  'approved' or 'need_assignment'
     * @return array{success: bool, message: string}
     */
    public function execute(Proposal $proposal, string $decision, ?string $notes = null, ?User $dekan = null): array
    {
        // Validate proposal status
        if ($proposal->status !== ProposalStatus::SUBMITTED) {
            return [
                'success' => false,
                'message' => 'Proposal tidak dalam status yang dapat diproses oleh Dekan.',
            ];
        }

        // Validate decision
        if (! in_array($decision, ['approved', 'need_assignment', 'rejected'])) {
            return [
                'success' => false,
                'message' => 'Keputusan tidak valid.',
            ];
        }

        // Validate faculty matching: Dekan can only approve proposals from their own faculty
        $dekan = $dekan ?? Auth::user();
        $dekanFacultyId = $dekan->identity?->faculty_id;
        $submitterFacultyId = $proposal->submitter->identity?->faculty_id;

        if ($dekanFacultyId && $submitterFacultyId && $dekanFacultyId !== $submitterFacultyId) {
            return [
                'success' => false,
                'message' => 'Dekan hanya dapat menyetujui proposal dari fakultas yang sama.',
            ];
        }

        // Validate roadmap validation by Kaprodi if feature is active
        $isRoadmapFeatureActive = Setting::get('feature_roadmap_active', false);
        $isKaprodiValidationActive = Setting::get('feature_kaprodi_validation', false);
        if ($isRoadmapFeatureActive && $isKaprodiValidationActive && ! $proposal->is_roadmap_validated_by_kaprodi) {
            return [
                'success' => false,
                'message' => 'Proposal belum divalidasi kesesuaian peta jalannya oleh Kaprodi.',
            ];
        }

        try {
            $newStatus = match ($decision) {
                'approved' => ProposalStatus::APPROVED,
                'rejected' => ProposalStatus::REJECTED,
                default => ProposalStatus::NEED_ASSIGNMENT,
            };

            // Validate transition
            if (! $proposal->status->canTransitionTo($newStatus)) {
                return [
                    'success' => false,
                    'message' => 'Transisi status tidak diizinkan.',
                ];
            }

            DB::transaction(function () use ($proposal, $newStatus, $decision, $notes, $dekan): void {
                // Update proposal status
                $proposal->update([
                    'status' => $newStatus,
                ]);

                // Log the activity
                Log::info('Dekan approval action', [
                    'proposal_id' => $proposal->id,
                    'decision' => $decision,
                    'new_status' => $newStatus->value,
                    'notes' => $notes,
                ]);

                // Send notifications based on decision
                $this->sendNotifications($proposal, $decision, $dekan ?? Auth::user());
            });

            $message = match ($decision) {
                'approved' => 'Proposal berhasil disetujui dan diteruskan ke Kepala LPPM.',
                'rejected' => 'Proposal ditolak oleh Dekan.',
                default => 'Proposal dikembalikan ke pengusul untuk memperbaiki persetujuan anggota.',
            };

            return [
                'success' => true,
                'message' => $message,
            ];
        } catch (\Exception $e) {
            Log::error('Dekan approval action failed', [
                'proposal_id' => $proposal->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses persetujuan: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Send notifications based on decision
     */
    protected function sendNotifications(Proposal $proposal, string $decision, User $dekan): void
    {
        // Eager load team members to prevent N+1 lazy loading
        $proposal->load('teamMembers');

        $recipients = collect();

        if ($decision === 'approved') {
            // Notify: Submitter, Kepala LPPM, Team Members
            $recipients->push($proposal->submitter);
            $recipients->push(User::role('kepala lppm')->first());
            $recipients = $recipients->merge($proposal->teamMembers);
        } elseif ($decision === 'rejected') {
            // Notify: Submitter, Team Members
            $recipients->push($proposal->submitter);
            $recipients = $recipients->merge($proposal->teamMembers);
        } else {
            // Notify: Submitter, Team Members (for approval)
            $recipients->push($proposal->submitter);
            $recipients = $recipients->merge($proposal->teamMembers()->where('user_id', '!=', $proposal->submitter_id)->get());
        }

        $this->notificationService->notifyDekanApprovalDecision(
            $proposal,
            $decision,
            $dekan,
            $recipients->filter()->unique('id')->values()
        );
    }
}
