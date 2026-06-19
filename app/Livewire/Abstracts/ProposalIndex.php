<?php

namespace App\Livewire\Abstracts;

use App\Enums\ProposalUserStatus;
use App\Livewire\Concerns\HasToast;
use App\Livewire\Traits\WithFilters;
use App\Models\CommunityService;
use App\Models\Proposal;
use App\Models\Research;
use App\Services\EligibilityService;
use App\Services\ProposalService;
use App\Services\QuotaMessageService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

abstract class ProposalIndex extends Component
{
    use HasToast;
    use WithFilters;
    use WithPagination;

    private ?ProposalService $proposalService = null;

    public function mount(): void
    {
        // If user is a regular 'dosen' (not an admin/leader role), default to 'ketua' view
        if (! Auth::user()->activeHasAnyRole(['admin lppm', 'kepala lppm', 'rektor', 'dekan'])) {
            $this->roleFilter = 'ketua';
        }
    }

    private function proposalService(): ProposalService
    {
        return $this->proposalService ??= app(ProposalService::class);
    }

    abstract protected function getProposalType(): string;

    abstract protected function getViewName(): string;

    abstract protected function getIndexRoute(): string;

    abstract protected function getShowRoute(string $proposalId): string;

    /**
     * @return array{can_create: bool, reason: ?string, quota_info: array{head_limit: int, head_current: int, member_limit: int, member_current: int}}
     */
    #[Computed]
    public function canCreateProposal(): array
    {
        $eligibilityService = app(EligibilityService::class);
        $result = $eligibilityService->canCreateProposal(Auth::user(), $this->getProposalType());

        return $result;
    }

    #[Computed]
    public function quotaTooltip(): string
    {
        $quotaInfo = $this->canCreateProposal()['quota_info'];
        $messageService = app(QuotaMessageService::class);

        return $messageService->getMessage('button_tooltip', [
            'limit' => $quotaInfo['head_limit'],
        ]);
    }

    #[Computed]
    public function proposals()
    {
        return $this->proposalService()->getProposalsWithFilters([
            'search' => $this->search,
            'status' => $this->statusFilter,
            'year' => $this->yearFilter,
            'role' => $this->roleFilter,
            'type' => $this->getProposalType(),
        ]);
    }

    #[Computed]
    public function statusStats()
    {
        return $this->proposalService()->getProposalStatistics([
            'type' => $this->getProposalType(),
        ]);
    }

    #[Computed]
    public function typeStats()
    {
        return [];
    }

    #[Computed]
    public function availableYears()
    {
        return $this->proposalService()->getAvailableYears(
            $this->getProposalType()
        );
    }

    #[Computed]
    public function pendingInvitationsCount()
    {
        return Proposal::whereHas('teamMembers', function ($q) {
            $q->where('user_id', Auth::id())->where('status', ProposalUserStatus::PENDING->value);
        })->whereHas('detailable', function ($q) {
            $q->where('detailable_type', $this->getProposalType() === 'research' ? Research::class : CommunityService::class);
        })->count();
    }

    public function render()
    {
        return view($this->getViewName());
    }
}
