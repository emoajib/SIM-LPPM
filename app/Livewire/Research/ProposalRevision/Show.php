<?php

declare(strict_types=1);

namespace App\Livewire\Research\ProposalRevision;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

use App\Livewire\Concerns\HasToast;
use App\Livewire\Forms\ProposalForm;
use App\Livewire\Research\Proposal\Components\TktMeasurement;
use App\Livewire\Traits\WithProposalWizard;
use App\Models\MacroResearchGroup;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\ResearchScheme;
use App\Models\TktLevel;
use App\Services\BudgetValidationService;
use App\Services\LecturerEligibilityService;
use App\Services\MasterDataService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Detail Revisi Proposal Penelitian')]
class Show extends Component
{
    use HasToast;
    use WithFileUploads;
    use WithProposalWizard;

    public ProposalForm $form;

    #[Validate('required|exists:macro_research_groups,id')]
    public string $macroResearchGroupId = '';

    #[Validate('nullable|file|mimes:pdf|max:10240')]
    public $substanceFile = null;

    public string $researchSchemeId = '';

    public array $budgetValidationErrors = [];

    public int $currentStep = 1;

    public ?string $commitmentUploadPartnerId = null;

    public $commitmentUploadFile = null;

    /**
     * Set the current active step.
     */
    public function setStep(int $step): void
    {
        if ($step === 2) {
            $this->validate([
                'researchSchemeId' => 'required|exists:research_schemes,id',
                'form.semester' => 'required|in:ganjil,genap',
                'form.start_year' => 'required|integer|min:2020|max:2030',
                'macroResearchGroupId' => 'required|exists:macro_research_groups,id',
            ]);

            // Cek apakah semua reviewer sudah approved
            $completedRevs = $this->form->proposal->reviewers->where('status', 'completed');
            $allApproved = $completedRevs->isNotEmpty()
                && ! $completedRevs->contains('recommendation', 'revision_needed')
                && ! $completedRevs->contains('recommendation', 'rejected');

            // Validate substance file (wajib upload baru untuk perbaikan usulan)
            $hasNewUploadedFile = $this->substanceFile && $this->substanceFile instanceof TemporaryUploadedFile;

            /** @var Research $detailable */
            $detailable = $this->form->proposal->detailable;
            $media = $detailable->getFirstMedia('substance_file');

            $isRevisionUploaded = $hasNewUploadedFile || ($media && $media->getCustomProperty('is_revision') === true);

            if (! $isRevisionUploaded) {
                $message = 'Anda belum mengunggah dokumen PDF Substansi Usulan yang baru. Silakan unggah dokumen perbaikan Anda.';
                $this->addError('substanceFile', $message);
                $this->toastError($message);

                return;
            }

            // Validate TKT level compatibility with the new scheme
            $tktResults = $this->form->tkt_results;
            if (! empty($tktResults)) {
                $achievedLevel = 0;
                $levels = TktLevel::whereIn('id', array_keys($tktResults))->get();
                foreach ($levels as $level) {
                    $data = $tktResults[$level->id] ?? null;
                    if ($data && isset($data['percentage']) && $data['percentage'] >= 80) {
                        $achievedLevel = max($achievedLevel, $level->level);
                    }
                }

                $newScheme = ResearchScheme::find($this->researchSchemeId);
                if ($newScheme) {
                    $range = TktMeasurement::getTktRangeForScheme($newScheme->id, $newScheme->strata);
                    if ($range) {
                        [$min, $max] = $range;
                        if ($achievedLevel < $min || $achievedLevel > $max) {
                            $message = "TKT Saat Ini (Level $achievedLevel) tidak sesuai dengan Skema {$newScheme->name} (Target: Level $min - $max).";
                            $this->addError('researchSchemeId', $message);
                            $this->toastError($message);

                            return;
                        }
                    }
                }
            }
        }

        $this->currentStep = $step;
    }

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
        $this->researchSchemeId = (string) ($proposal->research_scheme_id ?? '');
    }

    /**
     * Get available TKT types.
     *
     * @return Collection<int, string>
     */
    #[Computed]
    public function tktTypes()
    {
        return app(MasterDataService::class)->tktTypes('research');
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
     * Get all research schemes from LPPM master data.
     */
    #[Computed]
    public function schemes()
    {
        return app(MasterDataService::class)->schemes();
    }

    /**
     * Get budget groups.
     */
    #[Computed]
    public function budgetGroups()
    {
        return app(MasterDataService::class)->budgetGroups();
    }

    /**
     * Get budget components.
     */
    #[Computed]
    public function budgetComponents()
    {
        return app(MasterDataService::class)->budgetComponents();
    }

    /**
     * Required by WithProposalWizard trait.
     */
    protected function getProposalTypeForValidation(): string
    {
        return 'research';
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
     * Handle TKT measurement calculation from the modal component.
     */
    #[On('tkt-calculated')]
    public function onTktCalculated(array $levelResults, array $indicatorScores): void
    {
        // Only update level results with levels that have actual progress (percentage > 0)
        $filteredResults = array_filter($levelResults, fn ($data) => ($data['percentage'] ?? 0) > 0);
        $this->form->tkt_results = $filteredResults;
        $this->form->tkt_indicator_scores = $indicatorScores;
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

        // Validate basic fields
        $this->validate();

        // Cek apakah semua reviewer sudah approved (tidak ada revisi diminta)
        $completedReviewers = $this->form->proposal->reviewers->where('status', 'completed');
        $allReviewersApproved = $completedReviewers->isNotEmpty()
            && ! $completedReviewers->contains('recommendation', 'revision_needed')
            && ! $completedReviewers->contains('recommendation', 'rejected');

        // Validate substance file: wajib upload file baru untuk semua perbaikan usulan
        $hasNewUploadedFile = $this->substanceFile instanceof TemporaryUploadedFile;

        /** @var Research $detailable */
        $detailable = $this->form->proposal->detailable;
        $media = $detailable->getFirstMedia('substance_file');

        $isRevisionUploaded = $hasNewUploadedFile || ($media && $media->getCustomProperty('is_revision') === true);

        if (! $isRevisionUploaded) {
            $message = 'Anda belum mengunggah dokumen PDF Substansi Usulan yang baru. Silakan unggah dokumen perbaikan Anda.';
            $this->addError('substanceFile', $message);
            $this->toastError($message);

            return;
        }

        // Validate budget items and scheme
        $this->validate([
            'researchSchemeId' => 'required|exists:research_schemes,id',
            'form.semester' => 'required|in:ganjil,genap',
            'form.start_year' => 'required|integer|min:2020|max:2030',
            'form.budget_items' => ['required', 'array', 'min:1'],
            'form.budget_items.*.year' => 'required|integer|min:1|max:10',
            'form.budget_items.*.budget_group_id' => 'required|exists:budget_groups,id',
            'form.budget_items.*.budget_component_id' => 'required|exists:budget_components,id',
            'form.budget_items.*.item' => 'required|string|max:255',
            'form.budget_items.*.volume' => 'required|numeric|min:0.01',
            'form.budget_items.*.unit_price' => 'required|numeric|min:1',
        ]);

        try {
            $proposal = $this->form->proposal;

            // Validate TKT level compatibility with the new scheme
            $tktResults = $this->form->tkt_results;
            if (! empty($tktResults)) {
                $achievedLevel = 0;
                $levels = TktLevel::whereIn('id', array_keys($tktResults))->get();
                foreach ($levels as $level) {
                    $data = $tktResults[$level->id] ?? null;
                    if ($data && isset($data['percentage']) && $data['percentage'] >= 80) {
                        $achievedLevel = max($achievedLevel, $level->level);
                    }
                }

                $newScheme = ResearchScheme::find($this->researchSchemeId);
                if ($newScheme) {
                    $range = TktMeasurement::getTktRangeForScheme($newScheme->id, $newScheme->strata);
                    if ($range) {
                        [$min, $max] = $range;
                        if ($achievedLevel < $min || $achievedLevel > $max) {
                            $message = "TKT Saat Ini (Level $achievedLevel) tidak sesuai dengan Skema {$newScheme->name} (Target: Level $min - $max).";
                            $this->addError('researchSchemeId', $message);
                            $this->toastError($message);

                            return;
                        }
                    }
                }
            }

            // Validate budget caps and percentages using the updated year/semester
            $type = 'research';
            app(BudgetValidationService::class)->validateBudgetGroupPercentages(
                $this->form->budget_items,
                $type,
                (int) $this->form->start_year,
                $this->form->semester,
                (int) $this->researchSchemeId
            );

            app(BudgetValidationService::class)->validateBudgetCap(
                $this->form->budget_items,
                $type,
                (int) $this->form->start_year,
                $this->form->semester,
                (int) $this->researchSchemeId
            );

            /** @var Research $research */
            $research = $proposal->detailable;

            // Update macro research group and TKT type
            $research->macro_research_group_id = $this->macroResearchGroupId;
            $research->tkt_type = $this->form->tkt_type ?: null;

            $hasChanges = false;
            $changedFields = [];

            // Check if macro research group changed
            if ($research->wasChanged('macro_research_group_id') || $research->isDirty('macro_research_group_id')) {
                $hasChanges = true;
                $changedFields[] = 'Kelompok Makro Riset';
            }

            // Check if scheme changed
            if ($proposal->research_scheme_id != $this->researchSchemeId) {
                $hasChanges = true;
                $changedFields[] = 'Skema Penelitian';
            }

            // Check if semester or year changed
            if ($proposal->semester != $this->form->semester || $proposal->start_year != $this->form->start_year) {
                $hasChanges = true;
                $changedFields[] = 'Periode Pelaksanaan';
            }

            // Handle file upload
            if ($this->substanceFile) {
                $research->clearMediaCollection('substance_file');
                $research
                    ->addMedia($this->substanceFile)
                    ->usingName($this->substanceFile->getClientOriginalName())
                    ->usingFileName($this->substanceFile->hashName())
                    ->withCustomProperties(['uploaded_by' => Auth::id(), 'is_revision' => true])
                    ->toMediaCollection('substance_file');

                $hasChanges = true;
                $changedFields[] = 'File Substansi';
            }

            DB::transaction(function () use ($proposal, $research) {
                $research->save();

                // Update proposal scheme, semester, and year
                $proposal->update([
                    'research_scheme_id' => $this->researchSchemeId,
                    'semester' => $this->form->semester,
                    'start_year' => (int) $this->form->start_year,
                ]);

                // Delete old budget items and create new ones
                $proposal->budgetItems()->delete();

                foreach ($this->form->budget_items as $item) {
                    if (empty($item['budget_group_id']) && empty($item['item']) && (empty($item['unit_price']) || $item['unit_price'] == 0)) {
                        continue;
                    }

                    $groupId = ! empty($item['budget_group_id']) ? $item['budget_group_id'] : null;
                    $componentId = ! empty($item['budget_component_id']) ? $item['budget_component_id'] : null;

                    $proposal->budgetItems()->create([
                        'year' => $item['year'] ?? 1,
                        'budget_group_id' => $groupId,
                        'budget_component_id' => $componentId,
                        'group' => $item['group'] ?? '',
                        'component' => $item['component'] ?? '',
                        'item_description' => $item['item'] ?? '',
                        'volume' => $item['volume'] ?? 1,
                        'unit_price' => $item['unit_price'] ?? 0,
                        'total_price' => ($item['volume'] ?? 1) * ($item['unit_price'] ?? 0),
                    ]);
                }

                // Sync TKT Levels and Indicators
                if (! empty($this->form->tkt_results)) {
                    $research->tktLevels()->sync($this->form->tkt_results);
                }

                if (! empty($this->form->tkt_indicator_scores)) {
                    $indicatorSyncData = [];
                    foreach ($this->form->tkt_indicator_scores as $indicatorId => $score) {
                        $indicatorSyncData[$indicatorId] = ['score' => $score];
                    }
                    $research->tktIndicators()->sync($indicatorSyncData);
                }
            });

            // Always count RAB as changed since it is submitted
            $hasChanges = true;
            $changedFields[] = 'Rencana Anggaran Biaya (RAB)';

            // Refresh proposal data
            $this->form->setProposal($proposal->fresh());
            $this->macroResearchGroupId = (string) ($research->macro_research_group_id ?? '');
            $this->researchSchemeId = (string) ($proposal->research_scheme_id ?? '');

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
