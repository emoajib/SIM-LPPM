<?php

declare(strict_types=1);

namespace App\Livewire\CommunityService\ProposalRevision;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

use App\Livewire\Concerns\HasToast;
use App\Livewire\Forms\ProposalForm;
use App\Livewire\Traits\WithProposalWizard;
use App\Models\CommunityService;
use App\Models\Partner;
use App\Models\Proposal;
use App\Services\BudgetValidationService;
use App\Services\LecturerEligibilityService;
use App\Services\MasterDataService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\HasMedia;

#[Layout('components.layouts.app')]
#[Title('Detail Revisi Proposal Pengabdian')]
class Show extends Component
{
    use HasToast;
    use WithFileUploads;
    use WithProposalWizard;

    public ProposalForm $form;

    #[Validate('required|exists:partners,id')]
    public string $partnerId = '';

    #[Validate('required|string|min:50')]
    public string $partnerIssueSummary = '';

    #[Validate('required|string|min:50')]
    public string $solutionOffered = '';

    #[Validate('nullable|file|mimes:pdf|max:10240')]
    public $substanceFile = null;

    public string $communityServiceSchemeId = '';

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
                'communityServiceSchemeId' => 'required|exists:community_service_schemes,id',
                'form.semester' => 'required|in:ganjil,genap',
                'form.start_year' => 'required|integer|min:2020|max:2030',
                'partnerId' => 'required|exists:partners,id',
                'partnerIssueSummary' => 'required|string|min:50',
                'solutionOffered' => 'required|string|min:50',
            ]);

            // Cek apakah semua reviewer sudah approved
            $completedRevs = $this->form->proposal->reviewers->where('status', 'completed');
            $allApproved = $completedRevs->isNotEmpty()
                && ! $completedRevs->contains('recommendation', 'revision_needed')
                && ! $completedRevs->contains('recommendation', 'rejected');

            // Validate substance file (wajib upload baru jika ada revisi/penolakan dari reviewer)
            $hasNewUploadedFile = $this->substanceFile && $this->substanceFile instanceof TemporaryUploadedFile;

            if (! $allApproved && ! $hasNewUploadedFile) {
                $message = 'Anda belum mengunggah dokumen PDF Substansi Usulan yang baru. Silakan unggah dokumen perbaikan Anda.';
                $this->addError('substanceFile', $message);
                $this->toastError($message);

                return;
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
        if ($proposal->detailable_type !== CommunityService::class) {
            if (str_contains($proposal->detailable_type, 'Research')) {
                $this->redirect(route('research.proposal-revision.show', $proposal->id), navigate: true);
            } else {
                abort(404);
            }

            return;
        }

        // Eager load all required relationships for the show page
        $proposal->load([
            'submitter.identity',
            'focusArea',
            'communityServiceScheme',
            'detailable.partner',
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

        /** @var CommunityService|null $communityService */
        $communityService = $proposal->detailable;
        $this->partnerId = (string) ($communityService->partner_id ?? '');
        $this->partnerIssueSummary = $communityService->partner_issue_summary ?? '';
        $this->solutionOffered = $communityService->solution_offered ?? '';
        $this->communityServiceSchemeId = (string) ($proposal->community_service_scheme_id ?? '');
    }

    /**
     * Get all partners.
     */
    #[Computed]
    public function partners()
    {
        return Partner::orderBy('name')->get();
    }

    /**
     * Get all community service schemes from LPPM master data.
     */
    #[Computed]
    public function communityServiceSchemes()
    {
        return app(MasterDataService::class)->communityServiceSchemes();
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
        return 'community-service';
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

        return $service->isRevisionOpen('community_service');
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

            return;
        }

        // Validate basic fields
        $this->validate();

        // Cek apakah semua reviewer sudah approved (tidak ada revisi diminta)
        $completedReviewers = $this->form->proposal->reviewers->where('status', 'completed');
        $allReviewersApproved = $completedReviewers->isNotEmpty()
            && ! $completedReviewers->contains('recommendation', 'revision_needed')
            && ! $completedReviewers->contains('recommendation', 'rejected');

        // Validate substance file:
        // - Wajib upload baru jika ada reviewer yang meminta revisi (revision_needed / rejected)
        // - Opsional jika semua reviewer menyetujui (approved) — cukup konfirmasi
        $hasNewUploadedFile = $this->substanceFile && $this->substanceFile instanceof TemporaryUploadedFile;
        $detailable = $this->form->proposal->detailable;
        $hasExistingFile = $detailable instanceof HasMedia && $detailable->hasMedia('substance_file');

        if (! $allReviewersApproved && ! $hasNewUploadedFile) {
            $message = 'Anda belum mengunggah dokumen PDF Substansi Usulan yang baru. Silakan unggah dokumen perbaikan Anda sesuai catatan reviewer.';
            $this->addError('substanceFile', $message);
            $this->toastError($message);

            return;
        }

        if ($allReviewersApproved && ! $hasNewUploadedFile && ! $hasExistingFile) {
            $message = 'Dokumen PDF Substansi Usulan tidak ditemukan. Silakan unggah dokumen terlebih dahulu.';
            $this->addError('substanceFile', $message);
            $this->toastError($message);

            return;
        }

        // Validate budget items and scheme
        $this->validate([
            'communityServiceSchemeId' => 'required|exists:community_service_schemes,id',
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

            // Validate budget caps and percentages using the updated year/semester
            $type = 'community-service';
            app(BudgetValidationService::class)->validateBudgetGroupPercentages(
                $this->form->budget_items,
                $type,
                (int) $this->form->start_year,
                $this->form->semester,
                (int) $this->communityServiceSchemeId
            );

            app(BudgetValidationService::class)->validateBudgetCap(
                $this->form->budget_items,
                $type,
                (int) $this->form->start_year,
                $this->form->semester,
                (int) $this->communityServiceSchemeId
            );

            /** @var CommunityService $communityService */
            $communityService = $proposal->detailable;

            // Update community service data
            $communityService->partner_id = $this->partnerId;
            $communityService->partner_issue_summary = $this->partnerIssueSummary;
            $communityService->solution_offered = $this->solutionOffered;

            $hasChanges = false;
            $changedFields = [];

            // Check what changed
            if ($communityService->isDirty('partner_id')) {
                $changedFields[] = 'Mitra';
            }
            if ($communityService->isDirty('partner_issue_summary')) {
                $changedFields[] = 'Ringkasan Masalah Mitra';
            }
            if ($communityService->isDirty('solution_offered')) {
                $changedFields[] = 'Solusi yang Ditawarkan';
            }
            if ($proposal->community_service_scheme_id != $this->communityServiceSchemeId) {
                $changedFields[] = 'Skema Pengabdian';
            }
            if ($proposal->semester != $this->form->semester || $proposal->start_year != $this->form->start_year) {
                $changedFields[] = 'Periode Pelaksanaan';
            }

            // Handle file upload
            if ($this->substanceFile) {
                $communityService
                    ->addMedia($this->substanceFile->getRealPath())
                    ->usingName($this->substanceFile->getClientOriginalName())
                    ->usingFileName($this->substanceFile->hashName())
                    ->withCustomProperties(['uploaded_by' => Auth::id()])
                    ->toMediaCollection('substance_file');

                $changedFields[] = 'File Substansi';
            }

            DB::transaction(function () use ($proposal, $communityService) {
                $communityService->save();

                // Update proposal scheme, semester, and year
                $proposal->update([
                    'community_service_scheme_id' => $this->communityServiceSchemeId,
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
                        'volume' => $item['volume'] ?? 0,
                        'unit_price' => $item['unit_price'] ?? 0,
                        'total_price' => $item['total'] ?? 0,
                    ]);
                }
            });

            // Always count RAB as changed since it is submitted
            $changedFields[] = 'Rencana Anggaran Biaya (RAB)';

            $message = 'Perubahan berhasil disimpan: '.implode(', ', $changedFields);
            session()->flash('success', $message);
            $this->toastSuccess($message);

            // Refresh proposal data
            $this->form->setProposal($proposal->fresh());
            $this->partnerId = (string) ($communityService->partner_id ?? '');
            $this->partnerIssueSummary = $communityService->partner_issue_summary ?? '';
            $this->solutionOffered = $communityService->solution_offered ?? '';
            $this->communityServiceSchemeId = (string) ($proposal->community_service_scheme_id ?? '');

            // Reset file input
            $this->substanceFile = null;
        } catch (\Exception $e) {
            $message = 'Gagal menyimpan perubahan: '.$e->getMessage();
            session()->flash('error', $message);
            $this->toastError($message);
        }
    }

    public function render(): View
    {
        return view('livewire.community-service.proposal-revision.show', [
            'proposal' => $this->form->proposal,
        ]);
    }
}
