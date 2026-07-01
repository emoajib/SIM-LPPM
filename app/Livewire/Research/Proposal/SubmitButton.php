<?php

namespace App\Livewire\Research\Proposal;

use App\Enums\ProposalStatus;
use App\Livewire\Actions\SubmitProposalAction;
use App\Livewire\Concerns\HasToast;
use App\Models\Proposal;
use App\Models\User;
use App\Services\LecturerEligibilityService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read Proposal|null $proposal
 * @property-read bool $canSubmit
 * @property-read Collection|User[] $pendingMembers
 * @property-read Collection|User[] $rejectedMembers
 */
class SubmitButton extends Component
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
    public function canSubmit(): bool
    {
        $proposal = $this->proposal;

        $statusValue = $proposal->status instanceof \BackedEnum ? $proposal->status->value : $proposal->status;

        $allowedStatuses = [
            ProposalStatus::DRAFT->value,
            ProposalStatus::NEED_ASSIGNMENT->value,
            ProposalStatus::REVISION_NEEDED->value,
        ];

        return in_array($statusValue, $allowedStatuses)
            && $proposal->allTeamMembersAccepted()
            && Auth::id() === $proposal->submitter_id
            && $this->eligibility()['eligible']
            && $proposal->budgetItems()->count() > 0;
    }

    #[Computed]
    public function pendingMembers()
    {
        return $this->proposal->pendingTeamMembers()->get();
    }

    #[Computed]
    public function rejectedMembers()
    {
        return $this->proposal->teamMembers()
            ->wherePivot('status', 'rejected')
            ->get();
    }

    #[Computed]
    public function eligibility()
    {
        $eligibilityService = app(LecturerEligibilityService::class);
        $statusValue = $this->proposal->status instanceof \BackedEnum ? $this->proposal->status->value : $this->proposal->status;

        if ($statusValue === ProposalStatus::REVISION_NEEDED->value) {
            if (! $eligibilityService->isRevisionOpen('research')) {
                return ['eligible' => false, 'reasons' => ['Masa perbaikan usulan telah ditutup.']];
            }

            return ['eligible' => true, 'reasons' => []];
        }

        $user = Auth::user();
        if ($user && $user->activeHasRole('dosen')) {
            return $eligibilityService->checkEligibility($user);
        }

        return ['eligible' => true, 'reasons' => []];
    }

    public function confirmSubmit(): void
    {
        $this->dispatch('open-modal', modalId: 'confirmSubmitModal');
    }

    public function submit(): void
    {
        $proposal = $this->proposal;
        $action = app(SubmitProposalAction::class);
        $result = $action->execute($proposal);

        if ($result['success']) {
            $message = 'Proposal penelitian berhasil diajukan';
            session()->flash('success', $message);
            $this->toastSuccess($message);
            $this->dispatch('proposal-submitted', proposalId: $proposal->id);
            $this->redirect(route('research.proposal.show', $proposal->id));
        } else {
            $message = 'Gagal mengajukan proposal: '.$result['message'];
            session()->flash('error', $message);
            $this->toastError($message);
        }
    }

    public function render(): View
    {
        return view('livewire.research.proposal.submit-button');
    }
}
