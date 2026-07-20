<?php

declare(strict_types=1);

namespace App\Livewire\CommunityService\FinalReport;

use App\Enums\ProposalStatus;
use App\Livewire\Concerns\HasToast;
use App\Livewire\Forms\ReportForm;
use App\Livewire\Traits\HasFileUploads;
use App\Livewire\Traits\HasReportTemplates;
use App\Livewire\Traits\ManagesOutputs;
use App\Livewire\Traits\ReportAccess;
use App\Livewire\Traits\ReportAuthorization;
use App\Livewire\Traits\WithReportApproval;
use App\Models\AdditionalOutput;
use App\Models\Keyword;
use App\Models\MandatoryOutput;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Services\LecturerEligibilityService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Show extends Component
{
    use HasFileUploads;
    use HasReportTemplates;
    use HasToast;
    use ManagesOutputs;
    use ReportAccess;
    use ReportAuthorization;
    use WithFileUploads;
    use WithReportApproval;

    // Form instance - Livewire v3 Form pattern
    public ReportForm $form;

    // State to track if final report draft exists
    public bool $isFinalReportDraft = false;

    /**
     * Mount the component
     */
    public function mount(Proposal $proposal): void
    {
        $this->proposal = $proposal;

        // Check if proposal is completed
        if ($this->proposal->status !== ProposalStatus::COMPLETED) {
            abort(403, 'Laporan akhir hanya dapat diakses untuk proposal yang sudah selesai.');
        }

        // Check access
        $this->checkAccess();

        // Enforce schedule
        $type = 'community_service';
        /** @var LecturerEligibilityService $service */
        $service = app(LecturerEligibilityService::class);

        if ($this->canEdit && ! $service->isFinalReportOpen($type)) {
            $this->canEdit = false;
        }

        // Load existing final report
        /** @var ProgressReport|null $finalReport */
        $finalReport = $proposal->progressReports()->where('reporting_period', 'final')->latest()->first();

        if ($finalReport) {
            $this->progressReport = $finalReport;
            $this->isFinalReportDraft = true;
        } else {
            // Fallback to latest progress report for pre-filling data
            /** @var ProgressReport|null $latestReport */
            $latestReport = $proposal->progressReports()->latest()->first();
            $this->progressReport = $latestReport;
            $this->isFinalReportDraft = false;
        }

        // Initialize Livewire Form
        $this->form->type = 'final';
        $this->form->initWithProposal($this->proposal);

        if ($this->progressReport) {
            // Load existing report data into form
            $this->form->setReport($this->progressReport);
        } else {
            // Initialize new report structure
            $this->form->initializeNewReport();
        }
    }

    /**
     * Save the report as draft
     */
    public function save(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }

        try {
            DB::transaction(function () {
                // Save report via form
                $report = $this->form->save($this->progressReport);
                $this->progressReport = $report;

                // Mark as existing draft
                $this->isFinalReportDraft = true;

                // Save report files
                $this->saveSubstanceFile($report, 'final');
                $this->saveRealizationFile($report, 'final');
                $this->savePresentationFile($report, 'final');

                // Save output files
                $this->saveOutputFiles($report);
            });

            $this->dispatch('report-saved');
            $message = 'Laporan akhir berhasil disimpan.';
            session()->flash('success', $message);
            $this->toastSuccess($message);
        } catch (ValidationException $e) {
            // Let Livewire handle validation errors
            throw $e;
        } catch (\Exception $e) {
            $message = 'Gagal menyimpan laporan: '.$e->getMessage();
            session()->flash('error', $message);
            $this->toastError($message);
        }
    }

    /**
     * Submit the report
     */
    public function submit(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }

        // Validate 100% Budget Usage
        $totalProposedBudget = (float) $this->proposal->budgetItems()->sum('total_price');
        $totalUsedBudget = (float) $this->proposal->dailyNotes()->sum('amount');

        if ($totalProposedBudget > 0 && $totalUsedBudget != $totalProposedBudget) {
            $message = 'Gagal mengajukan: Total pemakaian anggaran di Catatan Harian (Rp '.number_format($totalUsedBudget, 0, ',', '.').') belum mencapai 100% dari total RAB yang disetujui (Rp '.number_format($totalProposedBudget, 0, ',', '.').').';
            session()->flash('error', $message);
            $this->toastError($message);

            return;
        }

        // Validate that substance file exists (either in DB or newly uploaded)
        $hasFileInDatabase = $this->progressReport && $this->progressReport->hasMedia('substance_file');
        $hasNewUploadedFile = $this->substanceFile && $this->substanceFile instanceof TemporaryUploadedFile;

        if (! $hasFileInDatabase && ! $hasNewUploadedFile) {
            $message = 'Gagal mengajukan: Anda wajib mengunggah File Substansi (PDF) laporan akhir.';
            $this->addError('substanceFile', $message);
            $this->toastError($message);

            return;
        }

        // Validate that realization file exists (either in DB or newly uploaded)
        $hasRealizationInDb = $this->progressReport && $this->progressReport->hasMedia('realization_file');
        $hasNewRealization = $this->realizationFile && $this->realizationFile instanceof TemporaryUploadedFile;

        if (! $hasRealizationInDb && ! $hasNewRealization) {
            $message = 'Gagal mengajukan: Anda wajib mengunggah File Bukti Realisasi Anggaran.';
            $this->addError('realizationFile', $message);
            $this->toastError($message);

            return;
        }

        // Validate that presentation/poster file exists (either in DB or newly uploaded)
        $hasPresentationInDb = $this->progressReport && $this->progressReport->hasMedia('presentation_file');
        $hasNewPresentation = $this->presentationFile && $this->presentationFile instanceof TemporaryUploadedFile;

        if (! $hasPresentationInDb && ! $hasNewPresentation) {
            $message = 'Gagal mengajukan: Anda wajib mengunggah File Poster/Presentasi.';
            $this->addError('presentationFile', $message);
            $this->toastError($message);

            return;
        }

        try {
            DB::transaction(function () {
                // Submit report via form
                $report = $this->form->submit($this->progressReport);
                $this->progressReport = $report;
                $this->isFinalReportDraft = true;

                // Save report files
                $this->saveSubstanceFile($report, 'final');
                $this->saveRealizationFile($report, 'final');
                $this->savePresentationFile($report, 'final');

                // Save output files
                $this->saveOutputFiles($report);
            });

            $message = 'Laporan akhir berhasil diajukan.';
            session()->flash('success', $message);
            $this->toastSuccess($message);
            $this->redirect(route('community-service.final-report.index'), navigate: true);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            $message = 'Gagal mengajukan laporan: '.$e->getMessage();
            session()->flash('error', $message);
            $this->toastError($message);
        }
    }

    /**
     * Save all output files
     */
    protected function saveOutputFiles($report): void
    {
        // Save mandatory output files
        foreach ($this->form->mandatoryOutputs as $proposalOutputId => $data) {
            if (empty($proposalOutputId)) {
                continue;
            }

            if (empty($data['status_type']) && empty($data['journal_title'])) {
                continue;
            }

            // Find the mandatory output
            $mandatoryOutput = MandatoryOutput::where('progress_report_id', $report->id)
                ->where('proposal_output_id', $proposalOutputId)
                ->first();

            if ($mandatoryOutput) {
                $this->saveMandatoryOutputFile($mandatoryOutput, $proposalOutputId, 'final');
            }
        }

        // Save additional output files
        foreach ($this->form->additionalOutputs as $proposalOutputId => $data) {
            if (empty($proposalOutputId)) {
                continue;
            }

            if (empty($data['status']) && empty($data['book_title'])) {
                continue;
            }

            // Find the additional output
            $additionalOutput = AdditionalOutput::where('progress_report_id', $report->id)
                ->where('proposal_output_id', $proposalOutputId)
                ->first();

            if ($additionalOutput) {
                $this->saveAdditionalOutputFile($additionalOutput, $proposalOutputId, 'final');
                $this->saveAdditionalOutputCert($additionalOutput, $proposalOutputId, 'final');
            }
        }
    }

    /**
     * Handle substance file upload (real-time)
     */
    public function updatedSubstanceFile(): void
    {
        if (! $this->canEdit) {
            $this->substanceFile = null;

            return;
        }

        // Validate file
        $this->validateSubstanceFile();
    }

    /**
     * Handle realization file upload (real-time)
     */
    public function updatedRealizationFile(): void
    {
        if (! $this->canEdit) {
            $this->realizationFile = null;

            return;
        }

        // Validate file
        $this->validateRealizationFile();
    }

    /**
     * Handle presentation file upload (real-time)
     */
    public function updatedPresentationFile(): void
    {
        if (! $this->canEdit) {
            $this->presentationFile = null;

            return;
        }

        // Validate file
        $this->validatePresentationFile();
    }

    /**
     * Remove substance file
     */
    public function removeSubstanceFile(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }

        if ($this->progressReport) {
            $this->progressReport->clearMediaCollection('substance_file');
            $message = 'File substansi berhasil dihapus.';
            session()->flash('success', $message);
            $this->toastSuccess($message);
        }
    }

    /**
     * Remove realization file
     */
    public function removeRealizationFile(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }

        if ($this->progressReport) {
            $this->progressReport->clearMediaCollection('realization_file');
            $message = 'File realisasi berhasil dihapus.';
            session()->flash('success', $message);
            $this->toastSuccess($message);
        }
    }

    /**
     * Remove presentation file
     */
    public function removePresentationFile(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }

        if ($this->progressReport) {
            $this->progressReport->clearMediaCollection('presentation_file');
            $message = 'File presentasi berhasil dihapus.';
            session()->flash('success', $message);
            $this->toastSuccess($message);
        }
    }

    /**
     * Save mandatory output after validation
     */
    public function saveMandatoryOutput(int $proposalOutputId): void
    {
        if (! $this->canEdit) {
            abort(403);
        }

        try {
            $this->form->saveMandatoryOutput($proposalOutputId);

            $this->dispatch('close-modal', modalId: 'modalMandatoryOutput');

            $message = 'Data luaran wajib berhasil disimpan.';
            session()->flash('success', $message);
            $this->toastSuccess($message);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            $message = 'Gagal menyimpan: '.$e->getMessage();
            session()->flash('error', $message);
            $this->toastError($message);
        }
    }

    /**
     * Save additional output after validation
     */
    public function saveAdditionalOutput(int $proposalOutputId): void
    {
        if (! $this->canEdit) {
            abort(403);
        }

        try {
            $this->form->saveAdditionalOutput($proposalOutputId);

            $this->dispatch('close-modal', modalId: 'modalAdditionalOutput');

            $message = 'Data luaran tambahan berhasil disimpan.';
            session()->flash('success', $message);
            $this->toastSuccess($message);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            $message = 'Gagal menyimpan: '.$e->getMessage();
            session()->flash('error', $message);
            $this->toastError($message);
        }
    }

    /**
     * Handle mandatory output file upload (real-time)
     */
    public function updatedTempMandatoryFiles($value, $key): void
    {
        if ($value instanceof UploadedFile) {
            $this->validateMandatoryFile((int) $key);

            $this->form->tempMandatoryFiles[(int) $key] = $value;
            $this->form->saveMandatoryOutputWithFile((int) $key, validate: false);

            $this->progressReport = $this->form->progressReport;
            unset($this->tempMandatoryFiles[$key]);

            $message = 'Data luaran wajib berhasil disimpan.';
            session()->flash('success', $message);
            $this->toastSuccess($message);
        }
    }

    /**
     * Handle additional output file upload (real-time)
     */
    public function updatedTempAdditionalFiles($value, $key): void
    {
        if ($value instanceof UploadedFile) {
            $this->validateAdditionalFile((int) $key);

            $this->form->tempAdditionalFiles[(int) $key] = $value;
            $this->form->saveAdditionalOutputWithFile((int) $key, validate: false);

            $this->progressReport = $this->form->progressReport;
            unset($this->tempAdditionalFiles[$key]);

            $message = 'File luaran tambahan berhasil disimpan.';
            session()->flash('success', $message);
            $this->toastSuccess($message);
        }
    }

    /**
     * Handle additional output certificate upload (real-time)
     */
    public function updatedTempAdditionalCerts($value, $key): void
    {
        if ($value instanceof UploadedFile) {
            $this->validateAdditionalCert((int) $key);

            $this->form->tempAdditionalCerts[(int) $key] = $value;
            $this->form->saveAdditionalOutputWithFile((int) $key, validate: false);

            $this->progressReport = $this->form->progressReport;
            unset($this->tempAdditionalCerts[$key]);

            $message = 'Sertifikat berhasil disimpan.';
            session()->flash('success', $message);
            $this->toastSuccess($message);
        }
    }

    /**
     * Validate additional output
     */
    public function validateAdditionalOutput(int $proposalOutputId): void
    {
        $this->form->validateAdditionalOutput($proposalOutputId);
    }

    /**
     * Get mandatory output model for editing
     */
    #[Computed]
    public function mandatoryOutput(): ?MandatoryOutput
    {
        if (! $this->progressReport || ! $this->form->editingMandatoryId) {
            return null;
        }

        return MandatoryOutput::where('progress_report_id', $this->progressReport->id)
            ->where('proposal_output_id', $this->form->editingMandatoryId)
            ->first();
    }

    /**
     * Get additional output model for editing
     */
    #[Computed]
    public function additionalOutput(): ?AdditionalOutput
    {
        if (! $this->progressReport || ! $this->form->editingAdditionalId) {
            return null;
        }

        return AdditionalOutput::where('progress_report_id', $this->progressReport->id)
            ->where('proposal_output_id', $this->form->editingAdditionalId)
            ->first();
    }

    /**
     * Override ManagesOutputs trait method to use form object
     */
    public function editMandatoryOutput(int $proposalOutputId): void
    {
        $this->form->editMandatoryOutput($proposalOutputId);
    }

    /**
     * Override ManagesOutputs trait method to use form object
     */
    public function editAdditionalOutput(int $proposalOutputId): void
    {
        $this->form->editAdditionalOutput($proposalOutputId);
    }

    /**
     * Override ManagesOutputs trait method to use form object
     */
    public function closeMandatoryModal(): void
    {
        $this->form->closeMandatoryModal();
    }

    /**
     * Override ManagesOutputs trait method to use form object
     */
    public function closeAdditionalModal(): void
    {
        $this->form->closeAdditionalModal();
    }

    /**
     * Get all keywords for the view
     */
    public function getAllKeywords(): Collection
    {
        return Keyword::orderBy('name')->get();
    }

    /**
     * Render the view
     */
    public function render()
    {
        $mandatoryOutputsMap = collect();
        $additionalOutputsMap = collect();

        if ($this->progressReport) {
            $this->progressReport->loadMissing(['mandatoryOutputs', 'additionalOutputs']);

            $mandatoryOutputsMap = $this->progressReport->mandatoryOutputs->keyBy('proposal_output_id');
            $additionalOutputsMap = $this->progressReport->additionalOutputs->keyBy('proposal_output_id');
        }

        return view('livewire.community-service.final-report.show', [
            'allKeywords' => $this->getAllKeywords(),
            'mandatoryOutputsMap' => $mandatoryOutputsMap,
            'additionalOutputsMap' => $additionalOutputsMap,
        ]);
    }
}
