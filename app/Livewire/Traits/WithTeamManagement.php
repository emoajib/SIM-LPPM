<?php

namespace App\Livewire\Traits;

use App\Enums\ProposalUserStatus;
use App\Livewire\Concerns\HasToast;
use App\Models\Proposal;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait WithTeamManagement
{
    use HasToast;

    protected function teamNotificationService(): NotificationService
    {
        return app(NotificationService::class);
    }

    public function acceptMember(?string $userId = null): void
    {
        $userId = $userId ?? (string) Auth::id();

        if (! $userId) {
            abort(403, 'Anda tidak memiliki akses.');
        }

        $proposal = $this->getProposal();

        DB::transaction(function () use ($proposal, $userId) {
            $proposal->teamMembers()
                ->updateExistingPivot($userId, ['status' => ProposalUserStatus::ACCEPTED->value]);

            $member = $proposal->teamMembers()->find($userId);
            if ($member) {
                $this->teamNotificationService()->notifyTeamInvitationAccepted(
                    $proposal,
                    $member
                );
            }
        });

        session()->flash('success', 'Undangan tim berhasil diterima.');
        $this->toastSuccess('Undangan tim berhasil diterima.');
    }

    public function rejectMember(?string $userId = null): void
    {
        $userId = $userId ?? (string) Auth::id();

        if (! $userId) {
            abort(403, 'Anda tidak memiliki akses.');
        }

        $proposal = $this->getProposal();

        DB::transaction(function () use ($proposal, $userId) {
            $proposal->teamMembers()
                ->updateExistingPivot($userId, ['status' => 'rejected']);

            $member = $proposal->teamMembers()->find($userId);
            if ($member) {
                $this->teamNotificationService()->notifyTeamInvitationRejected(
                    $proposal,
                    $member
                );
            }
        });

        session()->flash('warning', 'Undangan tim telah ditolak.');
        $this->toastSuccess('Undangan tim telah ditolak.');
    }

    abstract protected function getProposal(): Proposal;
}
