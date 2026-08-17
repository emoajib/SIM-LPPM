<?php

namespace App\Livewire\Abstracts;

use App\Enums\ProposalStatus;
use App\Enums\ReportStatus;
use App\Livewire\Forms\ProposalForm;
use App\Livewire\Traits\WithApproval;
use App\Livewire\Traits\WithTeamManagement;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\Setting;
use App\Models\User;
use App\Services\LecturerEligibilityService;
use App\Services\ProposalService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read bool $canEdit
 * @property-read bool $canDelete
 * @property-read string $statusLabel
 * @property-read string $statusColor
 *
 * "Efficiency is the goal, but Integrity is the foundation."
 */
abstract class ProposalShow extends Component
{
    use WithApproval, WithTeamManagement {
        WithApproval::toast insteadof WithTeamManagement;
        WithApproval::toastSuccess insteadof WithTeamManagement;
        WithApproval::toastError insteadof WithTeamManagement;
        WithApproval::toastWarning insteadof WithTeamManagement;
        WithApproval::toastInfo insteadof WithTeamManagement;
        WithApproval::getDefaultToastTitle insteadof WithTeamManagement;
    }

    public ProposalForm $form;

    public Proposal $proposal;

    // Contract info for Admin LPPM
    public string $contractNumber = '';

    public ?string $contractDate = null;

    protected ProposalService $proposalService;

    public function boot(): void
    {
        $this->proposalService = app(ProposalService::class);
    }

    public function mount(Proposal $proposal): void
    {
        $this->authorize('view', $proposal);

        try {
            $this->form->setProposal($proposal);
            // CRITICAL: Use the form's proposal which has detailable & relationships loaded
            $this->proposal = $this->form->proposal;
            $this->contractNumber = $this->proposal->contract_number ?? '';
            $this->contractDate = $this->proposal->contract_date ? Carbon::parse($this->proposal->contract_date)->format('Y-m-d') : null;
        } catch (\Throwable $e) {
            \Log::error('Error in ProposalShow mount', [
                'proposal_id' => $proposal->id,
                'user_id' => \Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Update contract number by Admin LPPM
     */
    public function saveContract(): void
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->activeHasAnyRole(['admin lppm', 'admin lppm saintek', 'admin lppm dekabita', 'kepala lppm', 'superadmin'])) {
            abort(403, 'Hanya Admin LPPM yang berwenang mengubah nomor kontrak.');
        }

        $this->validate([
            'contractNumber' => 'nullable|string|max:100',
            'contractDate' => 'nullable|date',
        ]);

        $this->proposal->update([
            'contract_number' => $this->contractNumber ?: null,
            'contract_date' => $this->contractDate ?: null,
        ]);

        $this->toastSuccess('Nomor kontrak berhasil disimpan.');
    }

    abstract protected function getProposalType(): string;

    abstract protected function getIndexRoute(): string;

    abstract protected function getEditRoute(string $proposalId): string;

    abstract protected function getReviewRoute(string $proposalId): string;

    protected function getProposal(): Proposal
    {
        return $this->proposal;
    }

    public function delete(): void
    {
        if (! $this->canDelete) {
            abort(403, 'Hanya pengusul proposal yang dapat menghapus proposal draft.');
        }

        $this->proposalService->deleteProposal($this->proposal);

        $this->redirectRoute($this->getIndexRoute());
    }

    public function edit(): void
    {
        if (! $this->canEdit) {
            abort(403, 'Hanya pengusul proposal yang dapat mengedit proposal draft.');
        }

        $this->redirectRoute($this->getEditRoute($this->proposal->id));
    }

    public function review(): void
    {
        $this->redirectRoute($this->getReviewRoute($this->proposal->id));
    }

    #[Computed]
    public function isLetteringModuleActive(): bool
    {
        return (bool) Setting::get('module_persuratan_active', false);
    }

    #[Computed]
    public function statusLabel(): string
    {
        return $this->proposal->status->label();
    }

    #[Computed]
    public function statusColor(): string
    {
        return $this->proposal->status->color();
    }

    #[Computed]
    public function canEdit(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        // Allow editing for draft, revision_needed, need_assignment, or completed (if final report not yet approved)
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        $isEditableStatus = in_array($this->proposal->status, [ProposalStatus::DRAFT, ProposalStatus::REVISION_NEEDED, ProposalStatus::NEED_ASSIGNMENT]);

        if (! $isEditableStatus && $this->proposal->status === ProposalStatus::COMPLETED) {
            /** @var ProgressReport|null $finalReport */
            $finalReport = $this->proposal->progressReports()->where('reporting_period', 'final')->latest()->first();
            if (! $finalReport || $finalReport->status !== ReportStatus::APPROVED) {
                $isEditableStatus = true;
            }
        }

        if (! $isEditableStatus || $this->proposal->submitter_id !== $user->id) {
            return false;
        }

        // Admin LPPM is always allowed to assist editing
        if ($user->activeHasAnyRole(['admin lppm', 'admin lppm saintek', 'admin lppm dekabita', 'superadmin'])) {
            return true;
        }

        // Dosen: enforce submission schedule window
        return $this->isScheduleOpen();
    }

    /**
     * Check whether the submission schedule is currently open for this proposal type.
     * Returns true if no schedule is configured (defaults open).
     */
    #[Computed]
    public function isScheduleOpen(): bool
    {
        $schedule = app(LecturerEligibilityService::class)->getScheduleStatus();
        $type = $this->getProposalType(); // 'research' or 'community-service'

        return $type === 'research'
            ? $schedule['research_open']
            : $schedule['pkm_open'];
    }

    #[Computed]
    public function canDelete(): bool
    {
        $user = Auth::user();

        // Submitter of a draft proposal can always delete (schedule does not restrict deletion)
        return $this->proposal->status === ProposalStatus::DRAFT
            && $this->proposal->submitter_id === $user->id;
    }

    public function render()
    {
        return view($this->getViewName());
    }

    abstract protected function getViewName(): string;
}
