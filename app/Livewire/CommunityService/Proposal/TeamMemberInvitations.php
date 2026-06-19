<?php

namespace App\Livewire\CommunityService\Proposal;

use App\Enums\ProposalUserStatus;
use App\Livewire\Concerns\HasToast;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read Proposal|null $proposal
 * @property-read Collection<int, User> $teamMembers
 * @property-read Collection<int, User> $pendingInvitations
 * @property-read Collection<int, User> $acceptedMembers
 * @property-read Collection<int, User> $rejectedMembers
 */
class TeamMemberInvitations extends Component
{
    use HasToast;

    public string $proposalId = '';

    public function mount(string $proposalId): void
    {
        $this->proposalId = $proposalId;
    }

    #[Computed]
    public function proposal()
    {
        return Proposal::find($this->proposalId);
    }

    #[Computed]
    public function teamMembers()
    {
        return $this->proposal->teamMembers()
            ->orderByPivot('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function pendingInvitations()
    {
        return $this->teamMembers->filter(fn ($member) => $member->pivot->getAttribute('status') === 'pending');
    }

    #[Computed]
    public function acceptedMembers()
    {
        return $this->teamMembers->filter(fn ($member) => $member->pivot->getAttribute('status') === ProposalUserStatus::ACCEPTED->value);
    }

    #[Computed]
    public function rejectedMembers()
    {
        return $this->teamMembers->filter(fn ($member) => $member->pivot->getAttribute('status') === 'rejected');
    }

    #[Computed]
    public function allAccepted(): bool
    {
        $total = $this->teamMembers->count();
        $accepted = $this->acceptedMembers->count();

        return $total > 0 && $total === $accepted;
    }

    public function acceptInvitation(): void
    {
        $user = Auth::user();
        $proposal = $this->proposal;

        $member = $proposal->teamMembers()
            ->where('user_id', $user->id)
            ->first();

        if (! $member) {
            $this->toastError('Anda bukan anggota proposal ini');

            return;
        }

        if ($member->pivot->getAttribute('status') === ProposalUserStatus::ACCEPTED->value) {
            $this->toastInfo('Anda sudah menerima undangan');

            return;
        }

        $proposal->teamMembers()
            ->updateExistingPivot($user->id, ['status' => ProposalUserStatus::ACCEPTED->value]);

        session()->flash('success', 'Undangan diterima');
        $this->toastSuccess('Undangan diterima');
        $this->dispatch('team-member-action');
    }

    public function rejectInvitation(): void
    {
        $user = Auth::user();
        $proposal = $this->proposal;

        $member = $proposal->teamMembers()
            ->where('user_id', $user->id)
            ->first();

        if (! $member) {
            session()->flash('error', 'Anda bukan anggota proposal ini');
            $this->toastError('Anda bukan anggota proposal ini');

            return;
        }

        $proposal->teamMembers()
            ->updateExistingPivot($user->id, ['status' => 'rejected']);

        session()->flash('success', 'Undangan ditolak');
        $this->toastSuccess('Undangan ditolak');
        $this->dispatch('team-member-action');
    }

    public function render(): View
    {
        return view('livewire.community-service.proposal.team-member-invitations');
    }
}
