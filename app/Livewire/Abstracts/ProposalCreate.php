<?php

namespace App\Livewire\Abstracts;

use App\Actions\Proposal\IdentityEligibilityAction;
use App\Constants\ProposalConstants;
use App\Enums\ProposalStatus;
use App\Enums\ReportStatus;
use App\Livewire\Concerns\HasToast;
use App\Livewire\Forms\ProposalForm;
use App\Livewire\Research\Proposal\Components\TktMeasurement;
use App\Livewire\Traits\HasReportTemplates;
use App\Livewire\Traits\WithProposalWizard;
use App\Livewire\Traits\WithStepWizard;
use App\Models\CommunityServiceScheme;
use App\Models\NationalPriority;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\ResearchScheme;
use App\Models\Setting;
use App\Models\StudyProgramRoadmap;
use App\Models\TktLevel;
use App\Models\Topic;
use App\Models\User;
use App\Services\BudgetValidationService;
use App\Services\EligibilityService;
use App\Services\LecturerEligibilityService;
use App\Services\MasterDataService;
use App\Services\ProposalService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * @property-read Collection $schemes
 * @property-read Collection $communityServiceSchemes
 * @property-read Collection $focusAreas
 * @property-read Collection $themes
 * @property-read Collection $topics
 * @property-read Collection $nationalPriorities
 * @property-read Collection $scienceClusters
 * @property-read Collection $clusterLevel1Options
 * @property-read Collection $clusterLevel2Options
 * @property-read Collection $clusterLevel3Options
 * @property-read Collection $macroResearchGroups
 * @property-read Collection $partners
 * @property-read Collection $masterIkus
 * @property-read Collection $sdgs
 * @property-read Collection $budgetGroups
 * @property-read Collection $budgetComponents
 * @property-read Collection $tktTypes
 * @property-read string|null $templateUrl
 * @property-read string|null $partnerCommitmentTemplateUrl
 * @property-read string|null $proposalApprovalPageTemplateUrl
 */
abstract class ProposalCreate extends Component
{
    use HasReportTemplates;
    use HasToast;
    use WithFileUploads;
    use WithProposalWizard;
    use WithStepWizard;

    public ProposalForm $form;

    public int $currentStep = 1;

    public int $fileInputIteration = 0;

    /** Approval mode: 'new', 'revision', or 'resubmission' */
    public string $proposalApprovalMode = 'new';

    public string $author_name = '';

    public array $budgetValidationErrors = [];

    /** Mode modal mitra: 'existing' (pilih yang ada) atau 'new' (buat baru) */
    public string $partnerMode = 'existing';

    /** Kata kunci pencarian mitra existing di modal */
    public string $partnerSearch = '';

    /** ID mitra yang sedang di-upload surat kesediaannya */
    public ?string $commitmentUploadPartnerId = null;

    /** File surat kesediaan yang akan diupload */
    public $commitmentUploadFile = null;

    public function mount(?string $proposalId = null, ?Proposal $proposal = null): void
    {
        try {
            $user = Auth::user();
            $this->author_name = ($user instanceof User) ? $user->name : '';

            // Handle route model binding (if passed as object) or ID string
            $proposalToLoad = $proposal ?? ($proposalId ? Proposal::find($proposalId) : null);

            if ($proposalToLoad) {
                $this->authorize('update', $proposalToLoad);

                $this->form->setProposal($proposalToLoad);
            } else {
                // Check eligibility for new proposals
                if ($user instanceof User && $user->activeHasRole('dosen')) {
                    // First check general eligibility
                    $eligibilityService = app(LecturerEligibilityService::class);
                    $eligibility = $eligibilityService->checkEligibility($user);
                    if (! $eligibility['eligible']) {
                        session()->flash('error', 'Anda tidak memenuhi syarat untuk membuat proposal baru. '.implode(', ', $eligibility['reasons']));
                        $this->redirect(route($this->getIndexRoute()));

                        return;
                    }

                    // Then check quota limits for creating new proposals
                    $quotaCheck = app(EligibilityService::class)->canCreateProposal($user, $this->getProposalType());
                    if (! $quotaCheck['can_create']) {
                        session()->flash('error', $quotaCheck['reason']);
                        $this->redirect(route($this->getIndexRoute()));

                        return;
                    }
                }

                // Initial values for new proposals
                $this->form->start_year = date('Y');

                // Add initial empty budget row
                if (empty($this->form->budget_items)) {
                    $this->addBudgetItem();
                }
            }
        } catch (\Exception $e) {
            \Log::error('Mount error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            session()->flash('error', 'Terjadi kesalahan saat memuat halaman. Silakan coba lagi.');
            $this->redirect(route($this->getIndexRoute()));

            return;
        }
    }

    protected function canEditProposal(Proposal $proposal): bool
    {
        $user = Auth::user();

        if ($user instanceof User && $user->hasRole(['admin lppm'])) {
            return true;
        }

        if ($proposal->submitter_id === $user->getAuthIdentifier()) {
            if ($proposal->status === ProposalStatus::DRAFT) {
                // Dosen: enforce submission schedule window
                if ($user instanceof User && $user->activeHasRole('dosen')) {
                    $eligibilityService = app(LecturerEligibilityService::class);
                    $schedule = $eligibilityService->getScheduleStatus();
                    $type = $this->getProposalType();
                    $isOpen = $type === 'research'
                        ? $schedule['research_open']
                        : $schedule['pkm_open'];

                    if (! $isOpen) {
                        return false;
                    }
                }

                return true;
            }

            // Allow editing if proposal needs revision
            if ($proposal->status === ProposalStatus::REVISION_NEEDED) {
                return true;
            }

            // Allow editing if proposal is completed (final report phase)
            // BUT the final report is not yet fully approved
            if ($proposal->status === ProposalStatus::COMPLETED) {
                /** @var ProgressReport|null $finalReport */
                $finalReport = $proposal->progressReports()->where('reporting_period', 'final')->latest()->first();
                if (! $finalReport || $finalReport->status !== ReportStatus::APPROVED) {
                    return true;
                }
            }
        }

        return false;
    }

    abstract protected function getProposalType(): string;

    abstract protected function getIndexRoute(): string;

    abstract protected function getShowRoute(string $proposalId): string;

    abstract protected function getStep2Rules(): array;

    protected function getOutputValidationRules(): array
    {
        return [
            'form.outputs' => [
                'required',
                'array',
                'min:1',
                function ($attribute, $value, $fail) {
                    $wajibCount = collect($value)->where('category', 'Wajib')->count();
                    if ($wajibCount < 1) {
                        $fail('Minimal harus ada 1 luaran wajib untuk proposal penelitian.');
                    }
                },
            ],
            'form.outputs.*.year' => 'required|integer|min:1|max:10',
            'form.outputs.*.category' => ['required', Rule::in(ProposalConstants::OUTPUT_CATEGORIES)],
            'form.outputs.*.group' => ['required', Rule::in(ProposalConstants::RESEARCH_OUTPUT_GROUPS)],
            'form.outputs.*.type' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    // Validate type matches group
                    $index = (int) explode('.', $attribute)[2];
                    $group = $this->form->outputs[$index]['group'] ?? null;
                    if ($group && isset(ProposalConstants::RESEARCH_OUTPUT_TYPES[$group])) {
                        if (! in_array($value, ProposalConstants::RESEARCH_OUTPUT_TYPES[$group])) {
                            $fail('Luaran baris '.($index + 1).' tidak valid untuk kategori yang dipilih.');
                        }
                    }
                },
            ],
            'form.outputs.*.status' => ['required', Rule::in(ProposalConstants::OUTPUT_STATUSES)],
            'form.outputs.*.description' => 'required|string|max:2000',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'form.title' => 'Judul',
            'form.research_scheme_id' => 'Skema Penelitian',
            'form.community_service_scheme_id' => 'Skema Pengabdian',
            'form.focus_area_id' => 'Bidang Fokus',
            'form.theme_id' => 'Tema',
            'form.topic_id' => 'Topik',
            'form.keywords' => 'Kata Kunci',
            'form.national_priority_id' => 'Prioritas Riset Nasional',
            'form.cluster_level1_id' => 'Rumpun Ilmu Level 1',
            'form.cluster_level2_id' => 'Rumpun Ilmu Level 2',
            'form.cluster_level3_id' => 'Rumpun Ilmu Level 3',
            'form.sbk_value' => 'Nilai SBK',
            'form.duration_in_years' => 'Lama Kegiatan',
            'form.start_year' => 'Tahun Usulan',
            'form.summary' => 'Ringkasan',
            'form.asta_cita' => 'Asta Cita',
            'form.author_tasks' => 'Tugas Ketua',
            'form.tkt_type' => 'Jenis TKT',
            'form.macro_research_group_id' => 'Kelompok Makro Riset',
            'form.substance_file' => 'Substansi Usulan (PDF)',
            'form.approval_file' => 'Lembar Pengesahan (PDF)',
            'form.outputs' => 'Luaran Target Capaian',
            'form.budget_items' => 'RAB',
            'form.partner_ids' => 'Mitra',
            'form.new_partner.name' => 'Nama Mitra',
            'form.new_partner.email' => 'Email Mitra',
            'form.new_partner.institution' => 'Institusi Mitra',
            'form.new_partner.country' => 'Negara Mitra',
            'form.new_partner.type' => 'Jenis Mitra',
            'form.new_partner.address' => 'Alamat Mitra',
            'form.new_partner_commitment_file' => 'Surat Kesanggupan Mitra',
        ];
    }

    protected function getProposalTypeForValidation(): string
    {
        return $this->getProposalType();
    }

    #[On('members-updated')]
    public function updateMembers(array $members): void
    {
        $this->form->members = $members;
    }

    public function updatedFormFocusAreaId(): void
    {
        $this->form->theme_id = '';
        $this->form->topic_id = '';
    }

    public function updatedFormThemeId(): void
    {
        $this->form->topic_id = '';
    }

    public function updatedFormClusterLevel1Id(): void
    {
        $this->form->cluster_level2_id = '';
        $this->form->cluster_level3_id = '';
    }

    public function updatedFormClusterLevel2Id(): void
    {
        $this->form->cluster_level3_id = '';
    }

    public function updateTktResults(array $tktResults): void
    {
        $this->form->tkt_results = $tktResults;
    }

    #[On('tkt-calculated')]
    public function onTktCalculated(array $levelResults, array $indicatorScores): void
    {
        // Only update level results with levels that have actual progress (percentage > 0)
        $filteredResults = array_filter($levelResults, fn ($data) => ($data['percentage'] ?? 0) > 0);
        $this->form->tkt_results = $filteredResults;
        $this->form->tkt_indicator_scores = $indicatorScores;
    }

    public function save(): void
    {
        $this->form->validate();

        $schemeId = $this->getProposalType() === 'research'
            ? (int) $this->form->research_scheme_id
            : (int) $this->form->community_service_scheme_id;

        app(BudgetValidationService::class)->validateBudgetGroupPercentages(
            $this->form->budget_items,
            $this->getProposalType(),
            (int) $this->form->start_year,
            $this->form->semester,
            $schemeId ?: null
        );

        app(BudgetValidationService::class)->validateBudgetCap(
            $this->form->budget_items,
            $this->getProposalType(),
            (int) $this->form->start_year,
            $this->form->semester,
            $schemeId ?: null
        );

        if ($this->form->proposal) {
            app(ProposalService::class)->updateProposal(
                $this->form->proposal,
                $this->form,
                false // Disable global validation
            );
            $proposal = $this->form->proposal;
            $message = 'Proposal berhasil diperbarui.';
        } else {
            $proposal = app(ProposalService::class)->createProposal(
                $this->form,
                $this->getProposalType()
            );
            $message = 'Proposal berhasil dibuat.';
        }

        session()->flash('success', $message);
        $this->toastSuccess($message);

        // For drafts, redirect to edit page so data persists on refresh
        $this->redirect(route($this->getProposalType().'.proposal.edit', $proposal->id));
    }

    public function saveDraft(): void
    {
        \Log::info('saveDraft called', [
            'user_id' => Auth::id(),
            'title' => $this->form->title,
            'current_step' => $this->currentStep,
            'has_proposal' => $this->form->proposal ? 'yes' : 'no',
        ]);

        try {
            // For draft, we only strictly require the title
            $this->validate([
                'form.title' => 'required|string|max:255',
            ]);

            // Set draft flag on form to skip strict validations (like budget)
            $this->form->isDraft = true;

            if ($this->form->proposal) {
                app(ProposalService::class)->updateProposal(
                    $this->form->proposal,
                    $this->form,
                    false // Disable global validation
                );
                $proposal = $this->form->proposal;
                $message = 'Proposal berhasil diperbarui.';
            } else {
                $proposal = app(ProposalService::class)->createProposal(
                    $this->form,
                    $this->getProposalType()
                );
                $message = 'Proposal berhasil dibuat.';
            }

            session()->flash('success', $message);
            $this->toastSuccess($message);

            // Reload proposal from database to ensure form has fresh data (including outputs)
            $freshProposal = Proposal::with(['outputs', 'budgetItems', 'partners', 'keywords', 'sdgs', 'targetedIkus'])->find($proposal->id);
            if ($freshProposal) {
                $this->form->setProposal($freshProposal);
            }

            // For drafts, redirect to edit page so data persists on refresh
            $this->redirect(route($this->getProposalType().'.proposal.edit', $proposal->id));
        } catch (\Exception $e) {
            \Log::error('Save draft failed: '.$e->getMessage(), [
                'user_id' => Auth::id(),
                'form_data' => $this->form->toArray(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->toastError('Gagal menyimpan draft: '.$e->getMessage());
        }
    }

    #[Computed]
    public function schemes()
    {
        return app(MasterDataService::class)->schemes();
    }

    #[Computed]
    public function communityServiceSchemes()
    {
        return app(MasterDataService::class)->communityServiceSchemes();
    }

    #[Computed]
    public function focusAreas()
    {
        return app(MasterDataService::class)->focusAreas($this->getProposalType());
    }

    #[Computed]
    public function themes()
    {
        return app(MasterDataService::class)->themes($this->form->focus_area_id ? (int) $this->form->focus_area_id : null, $this->getProposalType());
    }

    /**
     * Get available topics.
     *
     * @return Collection<int, Topic>
     */
    #[Computed]
    public function topics(): Collection
    {
        return app(MasterDataService::class)->topics(
            $this->form->focus_area_id ? (int) $this->form->focus_area_id : null,
            $this->form->theme_id ? (int) $this->form->theme_id : null,
            $this->getProposalType()
        );
    }

    /**
     * Get national priorities.
     *
     * @return Collection<int, NationalPriority>
     */
    #[Computed]
    public function nationalPriorities(): Collection
    {
        return app(MasterDataService::class)->nationalPriorities();
    }

    /**
     * Get study program roadmaps.
     *
     * @return Collection<int, StudyProgramRoadmap>
     */
    #[Computed]
    public function studyProgramRoadmaps(): Collection
    {
        $user = Auth::user();
        if ($user && $user->identity && $user->identity->study_program_id) {
            return StudyProgramRoadmap::where('is_active', true)
                ->where('study_program_id', $user->identity->study_program_id)
                ->get();
        }

        return collect();
    }

    #[Computed]
    public function scienceClusters()
    {
        return app(MasterDataService::class)->scienceClusters($this->getProposalType());
    }

    #[Computed]
    public function clusterLevel1Options()
    {
        return app(MasterDataService::class)->clusterLevel1Options($this->getProposalType());
    }

    #[Computed]
    public function clusterLevel2Options()
    {
        return app(MasterDataService::class)->clusterLevel2Options($this->form->cluster_level1_id ? (int) $this->form->cluster_level1_id : null, $this->getProposalType());
    }

    #[Computed]
    public function clusterLevel3Options()
    {
        return app(MasterDataService::class)->clusterLevel3Options($this->form->cluster_level2_id ? (int) $this->form->cluster_level2_id : null, $this->getProposalType());
    }

    #[Computed]
    public function macroResearchGroups()
    {
        return app(MasterDataService::class)->macroResearchGroups();
    }

    #[Computed]
    public function partners()
    {
        return app(MasterDataService::class)->partners();
    }

    #[Computed]
    public function masterIkus()
    {
        return app(MasterDataService::class)->masterIkus();
    }

    #[Computed]
    public function sdgs()
    {
        return app(MasterDataService::class)->sdgs();
    }

    #[Computed]
    public function budgetGroups()
    {
        return app(MasterDataService::class)->budgetGroups();
    }

    #[Computed]
    public function budgetComponents()
    {
        return app(MasterDataService::class)->budgetComponents();
    }

    #[Computed]
    public function tktTypes()
    {
        return app(MasterDataService::class)->tktTypes($this->getProposalType());
    }

    #[Computed]
    public function templateUrl()
    {
        return app(MasterDataService::class)->getTemplateUrl($this->getProposalType());
    }

    #[Computed]
    public function partnerCommitmentTemplateUrl()
    {
        return app(MasterDataService::class)->getTemplateUrl('partner-commitment');
    }

    public function downloadPartnerCommitmentTemplate()
    {
        try {
            $setting = Setting::where('key', 'partner_commitment_template')->first();
            if ($setting && $setting->hasMedia('template')) {
                $media = $setting->getFirstMedia('template');

                // Try to use URL redirect instead of direct download
                $url = $media->getUrl();
                if ($url) {
                    return redirect($url);
                }
            }

            $this->toastError('Template belum tersedia. Silakan hubungi admin untuk mengunggah ulang template.');
        } catch (\Exception $e) {
            \Log::error('Download template error: '.$e->getMessage());
            $this->toastError('Gagal mengunduh template. Silakan coba lagi.');
        }
    }

    #[Computed]
    public function proposalApprovalPageTemplateUrl()
    {
        try {
            return app(MasterDataService::class)->getTemplateUrl('proposal-approval-page');
        } catch (\Exception $e) {
            \Log::error('Error getting template URL: '.$e->getMessage());

            return null;
        }
    }

    public function downloadProposalApprovalPageTemplate()
    {
        try {
            $setting = Setting::where('key', 'proposal_approval_page_template')->first();
            if ($setting && $setting->hasMedia('template')) {
                $media = $setting->getFirstMedia('template');

                // Try to use URL redirect instead of direct download
                $url = $media->getUrl();
                if ($url) {
                    return redirect($url);
                }
            }

            $this->toastError('Template belum tersedia. Silakan hubungi admin untuk mengunggah ulang template.');
        } catch (\Exception $e) {
            \Log::error('Download template error: '.$e->getMessage());
            $this->toastError('Gagal mengunduh template. Silakan coba lagi.');
        }
    }

    protected function getStepValidationRules(int $step): array
    {
        $type = $this->getProposalType();

        return match ($step) {
            1 => [
                'form.title' => 'required|string|max:255',
                'form.research_scheme_id' => $type === 'research' ? 'required|exists:research_schemes,id' : 'nullable|exists:research_schemes,id',
                'form.community_service_scheme_id' => $type === 'community-service' ? 'required|exists:community_service_schemes,id' : 'nullable|exists:community_service_schemes,id',
                'form.focus_area_id' => 'required|exists:focus_areas,id',
                'form.theme_id' => 'required|exists:themes,id',
                'form.topic_id' => 'required|exists:topics,id',
                'form.study_program_roadmap_id' => Setting::get('feature_roadmap_active', false) ? 'required|exists:study_program_roadmaps,id' : 'nullable',
                'form.keywords' => 'required|array|min:1|max:5',
                'form.sdg_ids' => 'required|array|min:1',
                'form.targeted_iku_ids' => 'required|array|min:1',
                'form.national_priority_id' => 'nullable|exists:national_priorities,id',
                'form.cluster_level1_id' => 'required|exists:science_clusters,id',
                'form.cluster_level2_id' => 'nullable|exists:science_clusters,id',
                'form.cluster_level3_id' => 'nullable|exists:science_clusters,id',
                'form.sbk_value' => 'nullable|numeric|min:0',
                'form.duration_in_years' => 'required|integer|min:1|max:10',
                'form.start_year' => 'required|integer|min:2020|max:2050',
                'form.summary' => 'required|string|min:100',
                'form.asta_cita' => 'nullable|string',
                'form.author_tasks' => 'required|string',
                'form.tkt_type' => $type === 'research' ? ['required', 'string', 'max:255', Rule::in(app(MasterDataService::class)->tktTypes()->toArray())] : 'nullable',
                'form.tkt_results' => $type === 'research' ? [
                    'nullable',
                    'array',
                    function ($attribute, $value, $fail) {
                        if (empty($value)) {
                            return;
                        }

                        // 1. Calculate achieved level
                        $achievedLevel = 0;
                        // Get level models to map IDs to integer levels
                        $levels = TktLevel::whereIn('id', array_keys($value))->get();

                        foreach ($levels as $level) {
                            $data = $value[$level->id] ?? null;
                            // Check if passed (percentage >= 80)
                            if ($data && isset($data['percentage']) && $data['percentage'] >= 80) {
                                $achievedLevel = max($achievedLevel, $level->level);
                            }
                        }

                        // 2. Get required range for the scheme if selected
                        if ($this->form->research_scheme_id) {
                            $scheme = ResearchScheme::find($this->form->research_scheme_id);
                            if ($scheme && $scheme->strata) {
                                $range = TktMeasurement::getTktRangeForStrata($scheme->strata);

                                // If range exists (not PKM), validate
                                if ($range) {
                                    [$min, $max] = $range;

                                    // Check if achieved level is within range
                                    if ($achievedLevel < $min || $achievedLevel > $max) {
                                        $fail("TKT Saat Ini (Level $achievedLevel) tidak sesuai dengan Skema $scheme->strata (Target: Level $min - $max).");
                                    }
                                }
                            }
                        }
                    },
                ] : 'nullable',
                'form.eligibility_check' => [
                    function ($attribute, $value, $fail) {
                        $schemeId = $this->getProposalType() === 'research'
                        ? $this->form->research_scheme_id
                        : $this->form->community_service_scheme_id;

                        if (! $schemeId) {
                            return;
                        }

                        $scheme = $this->getProposalType() === 'research'
                        ? ResearchScheme::find($schemeId)
                        : CommunityServiceScheme::find($schemeId);

                        if (! $scheme) {
                            return;
                        }

                        $result = app(IdentityEligibilityAction::class)->execute(Auth::user(), $scheme);
                        if (! $result['is_eligible']) {
                            $fail($result['reason']);
                        }
                    },
                ],
            ],
            2 => array_merge($this->getStep2Rules(), $type === 'research' ? [
                'form.outputs' => [
                    'required',
                    'array',
                    'min:1',
                    function ($attribute, $value, $fail) {
                        $wajibCount = collect($value)->where('category', 'Wajib')->count();
                        if ($wajibCount < 1) {
                            $fail('Minimal harus ada 1 luaran wajib untuk proposal penelitian.');
                        }

                        // Validate each row has required fields
                        foreach ($value as $index => $item) {
                            $rowNum = $index + 1;
                            $errors = [];

                            if (empty($item['group'])) {
                                $errors[] = 'Kategori Luaran';
                            }
                            if (empty($item['type'])) {
                                $errors[] = 'Luaran';
                            }
                            if (empty($item['status'])) {
                                $errors[] = 'Status';
                            }

                            if (! empty($errors)) {
                                $fail("Baris {$rowNum}: ".implode(', ', $errors).' wajib diisi.');
                            }
                        }
                    },
                ],
            ] : []),
            3 => [
                'form.budget_items' => ['required', 'array', 'min:1'],
                'form.budget_items.*.year' => 'required|integer|min:1|max:10',
                'form.budget_items.*.budget_group_id' => 'required|exists:budget_groups,id',
                'form.budget_items.*.budget_component_id' => 'required|exists:budget_components,id',
                'form.budget_items.*.item' => 'required|string|max:255',
                'form.budget_items.*.volume' => 'required|numeric|min:0.01',
                'form.budget_items.*.unit_price' => 'required|numeric|min:1',
            ],
            4 => [
                'form.partner_ids' => 'nullable|array',
                'form.partner_ids.*' => 'exists:partners,id',
            ],
            default => [],
        };
    }

    public function render()
    {
        try {
            return view($this->getProposalType() === 'research' ? 'livewire.research.proposal.create' : 'livewire.community-service.proposal.create');
        } catch (\Exception $e) {
            \Log::error('Render error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            abort(500, 'Terjadi kesalahan saat memuat halaman. Silakan coba lagi atau hubungi admin.');
        }
    }
}
