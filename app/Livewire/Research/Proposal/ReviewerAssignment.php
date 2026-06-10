<?php

namespace App\Livewire\Research\Proposal;

use App\Livewire\Actions\AssignReviewersAction;
use App\Livewire\Concerns\HasToast;
use App\Models\Proposal;
use App\Models\ProposalReviewer;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * @property-read Proposal|null $proposal
 * @property-read Collection|User[] $availableReviewers
 * @property-read Collection|ProposalReviewer[] $currentReviewers
 */
class ReviewerAssignment extends Component
{
    use HasToast;

    public string $proposalId = '';

    public string $confirmingRemoveReviewerId = '';

    #[Validate('required')]
    public string $selectedReviewer = '';

    public function mount(string $proposalId): void
    {
        $this->proposalId = $proposalId;
    }

    #[Computed]
    public function proposal(): ?Proposal
    {
        return Proposal::with(['reviewers.user', 'teamMembers'])->find($this->proposalId);
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function availableReviewers(): Collection
    {
        return User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['reviewer']);
        })
            ->with('identity:id,user_id,identity_id')
            ->get();
    }

    /**
     * @return Collection<int, ProposalReviewer>
     */
    #[Computed]
    public function currentReviewers(): Collection
    {
        return $this->proposal->reviewers()
            ->with('user')
            ->get();
    }

    // Vetted by AI - Manual Review Required by Senior Engineer/Manager
    #[Computed]
    public function requiredReviewerCount(): int
    {
        return (int) Setting::get('reviewer_count_required', 2);
    }

    public function assignReviewers(): void
    {
        $this->validate();

        $proposal = $this->proposal;
        $action = app(AssignReviewersAction::class);
        $result = $action->execute($proposal, $this->selectedReviewer);

        if ($result['success']) {
            session()->flash('success', $result['message']);
            $this->toastSuccess($result['message']);
            $this->dispatch('reviewers-assigned', proposalId: $proposal->id);
            $this->selectedReviewer = '';
        } else {
            session()->flash('error', $result['message']);
            $this->toastError($result['message']);
        }
    }

    public function removeReviewer(string $reviewerId): void
    {
        $reviewer = $this->proposal->reviewers()
            ->where('user_id', $reviewerId)
            ->first();

        if ($reviewer instanceof ProposalReviewer) {
            $reviewer->delete();
            session()->flash('success', 'Reviewer berhasil dihapus');
            $this->toastSuccess('Reviewer berhasil dihapus');
        }
    }

    public function confirmRemoveReviewer(string $reviewerId): void
    {
        $this->confirmingRemoveReviewerId = $reviewerId;
    }

    public function cancelRemoveReviewer(): void
    {
        $this->confirmingRemoveReviewerId = '';
    }

    public function resetReviewerForm(): void
    {
        $this->selectedReviewer = '';
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.research.proposal.reviewer-assignment');
    }
}
