<?php

declare(strict_types=1);

namespace App\Livewire\Research\ProposalRevision;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

use App\Livewire\Concerns\HasToast;
use App\Livewire\Forms\ProposalForm;
use App\Models\MacroResearchGroup;
use App\Models\Proposal;
use App\Models\Research;
use App\Services\LecturerEligibilityService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Detail Revisi Proposal Penelitian')]
class Show extends Component
{
    use HasToast;
    use WithFileUploads;

    public ProposalForm $form;

    #[Validate('required|exists:macro_research_groups,id')]
    public string $macroResearchGroupId = '';

    #[Validate('nullable|file|mimes:pdf|max:10240')]
    public $substanceFile = null;

    /**
     * Mount the component with proposal.
     */
    public function mount(Proposal $proposal): void
    {
        // Redirect if wrong type
        if ($proposal->detailable_type !== Research::class) {
            if (str_contains($proposal->detailable_type, 'CommunityService')) {
                $this->redirect(route('community-service.proposal-revision.show', $proposal->id), navigate: true);
            } else {
                abort(404);
            }

            return;
        }

        // Eager load all required relationships for the show page
        $proposal->load([
            'submitter.identity',
            'focusArea',
            'researchScheme',
            'detailable.macroResearchGroup',
            'budgetItems.budgetGroup',
            'budgetItems.budgetComponent',
            'reviewers' => function ($q) {
                $q->where('status', 'completed')
                    ->with(['user', 'scores.criteria']);
            },
            'reviewLogs.user',
            'reviewLogs.scores.criteria',
        ]);

        $this->form->setProposal($proposal);

        // Initialize form values
        $this->macroResearchGroupId = (string) ($proposal->detailable->macro_research_group_id ?? '');
    }

    /**
     * Get all macro research groups.
     */
    #[Computed]
    public function macroResearchGroups()
    {
        return MacroResearchGroup::orderBy('name')->get();
    }

    /**
     * Check if current user can edit the proposal.
     */
    public function canEdit(): bool
    {
        if ($this->form->proposal->submitter_id !== Auth::id()) {
            return false;
        }

        /** @var LecturerEligibilityService $service */
        $service = app(LecturerEligibilityService::class);

        return $service->isRevisionOpen('research');
    }

    /**
     * Save the revision changes.
     */
    public function save(): void
    {
        if (! $this->canEdit()) {
            $message = 'Anda tidak memiliki akses untuk mengedit proposal ini';
            session()->flash('error', $message);
            $this->toastError($message);
            $this->dispatch('show-alert', type: 'error', message: $message);

            return;
        }

        $this->validate();

        try {
            /** @var Research $research */
            $research = $this->form->proposal->detailable;

            // Update macro research group
            $research->macro_research_group_id = $this->macroResearchGroupId;

            $hasChanges = false;
            $changedFields = [];

            // Check if macro research group changed
            if ($research->wasChanged('macro_research_group_id') || $research->isDirty('macro_research_group_id')) {
                $hasChanges = true;
                $changedFields[] = 'Kelompok Makro Riset';
            }

            // Handle file upload
            if ($this->substanceFile) {
                $research
                    ->addMedia($this->substanceFile->getRealPath())
                    ->usingName($this->substanceFile->getClientOriginalName())
                    ->usingFileName($this->substanceFile->hashName())
                    ->withCustomProperties(['uploaded_by' => Auth::id()])
                    ->toMediaCollection('substance_file');

                $hasChanges = true;
                $changedFields[] = 'File Substansi';
            }

            $research->save();

            // Refresh proposal data
            $this->form->setProposal($this->form->proposal->fresh());
            $this->macroResearchGroupId = (string) ($research->macro_research_group_id ?? '');

            // Flash message
            $message = 'Perubahan berhasil disimpan';
            session()->flash('success', $message);
            $this->toastSuccess($message);

            // Dispatch update events for UI refresh
            $this->dispatch('content-updated', fields: $changedFields);
            $this->dispatch('show-update-notification', message: 'Perubahan berhasil disimpan: '.implode(', ', $changedFields));
            $this->dispatch('proposal-refreshed');

            // Reset file input
            $this->substanceFile = null;
        } catch (\Exception $e) {
            $message = 'Gagal menyimpan perubahan: '.$e->getMessage();
            session()->flash('error', $message);
            $this->toastError($message);
            $this->dispatch('show-alert', type: 'error', message: $message);
        }
    }

    public function render(): View
    {
        return view('livewire.research.proposal-revision.show', [
            'proposal' => $this->form->proposal,
        ]);
    }
}
