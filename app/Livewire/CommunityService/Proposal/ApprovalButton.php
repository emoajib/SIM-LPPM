<?php

namespace App\Livewire\CommunityService\Proposal;

use App\Enums\ProposalStatus;
use App\Livewire\Actions\ApproveProposalAction;
use App\Livewire\Concerns\HasToast;
use App\Models\Proposal;
use App\Models\ProposalReviewer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read Proposal|null $proposal
 * @property-read bool $canApprove
 * @property-read Collection|ProposalReviewer[] $pendingReviewers
 * @property-read Collection $reviewSummary
 */
class ApprovalButton extends Component
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
    public function canApprove(): bool
    {
        $user = Auth::user();
        $isAdmin = $user->activeHasAnyRole(['admin lppm', 'kepala lppm', 'rektor']);
        $proposal = $this->proposal;

        return $isAdmin
            && $proposal->status === ProposalStatus::REVISION_SUBMITTED;
    }

    #[Computed]
    public function pendingReviewers()
    {
        return $this->proposal->pendingReviewers()->get();
    }

    #[Computed]
    public function reviewSummary()
    {
        return $this->proposal->reviewers()
            ->select('recommendation')
            ->get()
            ->groupBy('recommendation')
            ->map->count();
    }

    public function approve(ApproveProposalAction $action): void
    {
        $user = Auth::user();
        $isAdmin = $user->activeHasAnyRole(['admin lppm', 'kepala lppm', 'rektor']);

        if (! $isAdmin) {
            $message = 'Anda tidak memiliki akses untuk approve proposal';
            $this->dispatch('error', message: $message);
            $this->toastError($message);

            return;
        }

        $proposal = $this->proposal;
        $result = $action->execute($proposal, 'completed');

        if ($result['success']) {
            session()->flash('success', $result['message']);
            $this->toastSuccess($result['message']);
            $this->dispatch('success', message: $result['message']);
            $this->dispatch('proposal-approved', proposalId: $proposal->id);
        } else {
            session()->flash('error', $result['message']);
            $this->toastError($result['message']);
            $this->dispatch('error', message: $result['message']);
        }
    }

    public function reject(ApproveProposalAction $action): void
    {
        $user = Auth::user();
        $isAdmin = $user->activeHasAnyRole(['admin lppm', 'kepala lppm', 'rektor']);

        if (! $isAdmin) {
            $message = 'Anda tidak memiliki akses untuk reject proposal';
            $this->dispatch('error', message: $message);
            $this->toastError($message);

            return;
        }

        $proposal = $this->proposal;
        $result = $action->execute($proposal, 'rejected');

        if ($result['success']) {
            session()->flash('warning', $result['message']);
            $this->toastWarning($result['message']);
            $this->dispatch('warning', message: $result['message']);
            $this->dispatch('proposal-rejected', proposalId: $proposal->id);
        } else {
            session()->flash('error', $result['message']);
            $this->toastError($result['message']);
            $this->dispatch('error', message: $result['message']);
        }
    }

    public function render(): View
    {
        return view('livewire.community-service.proposal.approval-button');
    }
}
