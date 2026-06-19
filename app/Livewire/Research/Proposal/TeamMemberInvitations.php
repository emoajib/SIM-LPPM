<?php

namespace App\Livewire\Research\Proposal;

use App\Enums\ProposalUserStatus;
use App\Livewire\Concerns\HasToast;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read Proposal|null $proposal
 * @property-read Collection|User[] $teamMembers
 * @property-read Collection|User[] $pendingInvitations
 * @property-read Collection|User[] $acceptedMembers
 * @property-read Collection|User[] $rejectedMembers
 * @property-read bool $allAccepted
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
    public function proposal(): ?Proposal
    {
        return Proposal::with(['teamMembers'])->find($this->proposalId);
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function teamMembers(): Collection
    {
        return $this->proposal->teamMembers()
            ->orderByPivot('created_at', 'desc')
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function pendingInvitations(): Collection
    {
        return $this->teamMembers->filter(fn ($member) => $member->pivot->status === 'pending');
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function acceptedMembers(): Collection
    {
        return $this->teamMembers->filter(fn ($member) => $member->pivot->status === ProposalUserStatus::ACCEPTED->value);
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function rejectedMembers(): Collection
    {
        return $this->teamMembers->filter(fn ($member) => $member->pivot->status === 'rejected');
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
        return view('livewire.research.proposal.team-member-invitations');
    }
}
