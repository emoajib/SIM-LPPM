<?php

namespace App\Livewire\Research\Proposal;

use App\Enums\ProposalUserStatus;
use App\Livewire\Concerns\HasToast;
use App\Models\Proposal;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TeamMemberInvitation extends Component
{
    use HasToast;

    public Proposal $proposal;

    #[Computed]
    public function pendingMembers()
    {
        return $this->proposal->teamMembers()
            ->wherePivot('status', 'pending')
            ->get();
    }

    #[Computed]
    public function acceptedMembers()
    {
        return $this->proposal->teamMembers()
            ->wherePivot('status', ProposalUserStatus::ACCEPTED->value)
            ->get();
    }

    #[Computed]
    public function rejectedMembers()
    {
        return $this->proposal->teamMembers()
            ->wherePivot('status', 'rejected')
            ->get();
    }

    #[Computed]
    public function allAccepted()
    {
        return $this->proposal->allTeamMembersAccepted();
    }

    public function acceptInvitation(): void
    {
        $user = Auth::user();

        // Check if user is actually a member
        $isMember = $this->proposal->teamMembers()
            ->where('user_id', $user->id)
            ->exists();

        if ($isMember) {
            DB::transaction(function () use ($user): void {
                $this->proposal->teamMembers()->updateExistingPivot($user->id, ['status' => ProposalUserStatus::ACCEPTED->value]);

                // Send notification
                $notificationService = app(NotificationService::class);
                $notificationService->notifyTeamInvitationAccepted($this->proposal, $user);
            });

            $this->dispatch('team-member-accepted', proposalId: $this->proposal->id);
            $message = 'Anda telah menerima undangan menjadi anggota proposal ini.';
            session()->flash('success', $message);
            $this->toastSuccess($message);
        }
    }

    public function rejectInvitation(): void
    {
        $user = Auth::user();

        // Check if user is actually a member
        $isMember = $this->proposal->teamMembers()
            ->where('user_id', $user->id)
            ->exists();

        if ($isMember) {
            DB::transaction(function () use ($user): void {
                $this->proposal->teamMembers()->updateExistingPivot($user->id, ['status' => 'rejected']);

                // Send notification
                $notificationService = app(NotificationService::class);
                $notificationService->notifyTeamInvitationRejected($this->proposal, $user);
            });

            $this->dispatch('team-member-rejected', proposalId: $this->proposal->id);
            $message = 'Anda telah menolak undangan untuk proposal ini.';
            session()->flash('warning', $message);
            $this->toastWarning($message);
        }
    }

    public function render(): View
    {
        return view('livewire.research.proposal.team-member-invitations');
    }
}
