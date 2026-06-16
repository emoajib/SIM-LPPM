<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Livewire\Concerns\HasToast;
use App\Livewire\Forms\ReportForm;
use App\Livewire\Traits\HasFileUploads;
use App\Livewire\Traits\HasReportTemplates;
use App\Livewire\Traits\ReportAccess;
use App\Livewire\Traits\ReportAuthorization;
use App\Models\AdditionalOutput;
use App\Models\Keyword;
use App\Models\MandatoryOutput;
use App\Models\Proposal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class Show extends Component
{
    use HasFileUploads;
    use HasReportTemplates;
    use HasToast;
    use ReportAccess;
    use ReportAuthorization;
    use WithFileUploads;

    // Form instance - Livewire v3 Form pattern
    public ReportForm $form;

    // Report configuration
    protected array $config = [];

    public function mount(Proposal $proposal, string $type = 'research-final'): void
    {
        $this->proposal = $proposal;
        $this->config = $this->getConfig($type);

        $this->checkAccess();
        $this->loadReport($type);

        // Determine report type for ReportForm
        $reportType = str_contains($type, 'final') ? 'final' : 'progress';

        // Initialize Livewire Form
        $this->form->type = $reportType;
        $this->form->initWithProposal($this->proposal);

        if ($this->progressReport) {
            // Load existing report data into form
            $this->form->setReport($this->progressReport);
        } else {
            // Initialize new report structure
            $this->form->initializeNewReport();
        }
    }

    protected function getConfig(string $type): array
    {
        $configs = [
            'research-final' => [
                'view' => 'livewire.research.final-report.show',
                'route' => 'research.final-report.index',
            ],
            'community-service-final' => [
                'view' => 'livewire.community-service.final-report.show',
                'route' => 'community-service.final-report.index',
            ],
        ];

        return $configs[$type] ?? $configs['research-final'];
    }

    public function save(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }

        // Validate substance file if present
        if ($this->form->substanceFile) {
            $this->validate([
                'form.substanceFile' => 'nullable|file|mimes:pdf|max:10240',
            ]);
        }

        DB::transaction(function () {
            // Save report via form
            $report = $this->form->save($this->progressReport);
            $this->progressReport = $report;

            // Save substance file if provided
            $this->saveSubstanceFile($report);
        });

        $this->dispatch('report-saved');
        $message = 'Laporan berhasil disimpan.';
        session()->flash('success', $message);
        $this->toastSuccess($message);
    }

    public function submit(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }

        DB::transaction(function () {
            // Submit report via form
            $report = $this->form->submit($this->progressReport);
            $this->progressReport = $report;

            // Save substance file
            $this->saveSubstanceFile($report);
        });

        // Ensure config is initialized before redirect
        if (empty($this->config) || ! isset($this->config['route'])) {
            $this->config = $this->getConfig('research-final');
        }

        $message = 'Laporan berhasil diajukan.';
        session()->flash('success', $message);
        $this->toastSuccess($message);
        $this->redirect(route($this->config['route']), navigate: true);
    }

    public function updatedTempMandatoryFiles($value, $key): void
    {
        $this->saveMandatoryOutput((int) $key, closeModal: false, validate: false);
    }

    public function updatedTempAdditionalFiles($value, $key): void
    {
        $this->saveAdditionalOutput((int) $key, closeModal: false, validate: false);
    }

    public function updatedTempAdditionalCerts($value, $key): void
    {
        $this->saveAdditionalOutput((int) $key, closeModal: false, validate: false);
    }

    public function editMandatoryOutput(int $proposalOutputId): void
    {
        $this->form->editMandatoryOutput($proposalOutputId);
    }

    public function saveMandatoryOutput(?int $proposalOutputId = null, bool $closeModal = true, bool $validate = true): void
    {
        if (! $this->canEdit || ! $this->progressReport) {
            return;
        }

        // Use editing ID if no parameter provided (for backward compatibility)
        $proposalOutputId = $proposalOutputId ?? $this->form->editingMandatoryId;

        if (! $proposalOutputId) {
            return;
        }

        try {
            // Ensure form has the progress report reference
            $this->form->progressReport = $this->progressReport;

            // Transfer files from component to form
            if (isset($this->tempMandatoryFiles[$proposalOutputId])) {
                $this->form->tempMandatoryFiles[$proposalOutputId] = $this->tempMandatoryFiles[$proposalOutputId];
            }

            // Save via form
            $this->form->saveMandatoryOutputWithFile($proposalOutputId, $validate);

            // Clear the component's temp files
            unset($this->tempMandatoryFiles[$proposalOutputId]);

            if ($closeModal) {
                $this->dispatch('close-modal', modalId: 'modalMandatoryOutput');
            }

            if ($closeModal) {
                $message = 'Data luaran wajib berhasil disimpan.';
                session()->flash('success', $message);
                $this->toastSuccess($message);
            } else {
                // For auto-save, maybe a toast? or just silent success + UI update
                // We can skip flash to avoid annoying popups, or use a different key.
                $message = 'File berhasil diupload.';
                session()->flash('success', $message);
                $this->toastSuccess($message);
            }

            // Refresh parent report to update UI status
            if ($this->progressReport) {
                $this->progressReport->refresh();
                $this->progressReport->load('mandatoryOutputs');
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            // If auto-saving (closeModal = false), we might want to suppress some errors or just show them
            $message = 'Gagal menyimpan: '.$e->getMessage();
            session()->flash('error', $message);
            $this->toastError($message);
        }
    }

    public function editAdditionalOutput(int $proposalOutputId): void
    {
        $this->form->editAdditionalOutput($proposalOutputId);
    }

    public function saveAdditionalOutput(?int $proposalOutputId = null, bool $closeModal = true, bool $validate = true): void
    {
        if (! $this->canEdit || ! $this->progressReport) {
            return;
        }

        // Use editing ID if no parameter provided (for backward compatibility)
        $proposalOutputId = $proposalOutputId ?? $this->form->editingAdditionalId;

        if (! $proposalOutputId) {
            return;
        }

        try {
            // Ensure form has the progress report reference
            $this->form->progressReport = $this->progressReport;

            // Transfer files from component to form
            if (isset($this->tempAdditionalFiles[$proposalOutputId])) {
                $this->form->tempAdditionalFiles[$proposalOutputId] = $this->tempAdditionalFiles[$proposalOutputId];
            }
            if (isset($this->tempAdditionalCerts[$proposalOutputId])) {
                $this->form->tempAdditionalCerts[$proposalOutputId] = $this->tempAdditionalCerts[$proposalOutputId];
            }

            // Save via form
            $this->form->saveAdditionalOutputWithFile($proposalOutputId, $validate);

            // Clear the component's temp files
            unset($this->tempAdditionalFiles[$proposalOutputId]);
            unset($this->tempAdditionalCerts[$proposalOutputId]);

            if ($closeModal) {
                $this->dispatch('close-modal', modalId: 'modalAdditionalOutput');
            }

            if ($closeModal) {
                $message = 'Data luaran tambahan berhasil disimpan.';
                session()->flash('success', $message);
                $this->toastSuccess($message);
            } else {
                $message = 'File berhasil diupload.';
                session()->flash('success', $message);
                $this->toastSuccess($message);
            }

            // Refresh parent report to update UI status
            if ($this->progressReport) {
                $this->progressReport->refresh();
                $this->progressReport->load('additionalOutputs');
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            $message = 'Gagal menyimpan: '.$e->getMessage();
            session()->flash('error', $message);
            $this->toastError($message);
        }
    }

    public function closeMandatoryModal(): void
    {
        $this->form->closeMandatoryModal();
    }

    public function closeAdditionalModal(): void
    {
        $this->form->closeAdditionalModal();
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

    public function render()
    {
        if (empty($this->config) || ! isset($this->config['view'])) {
            // Re-initialize config if missing (e.g., after component hydration)
            $this->config = $this->getConfig('research-progress');
        }

        $allKeywords = Keyword::orderBy('name')->get();

        return view($this->config['view'], [
            'allKeywords' => $allKeywords,
            'editingMandatoryId' => $this->form->editingMandatoryId,
            'editingAdditionalId' => $this->form->editingAdditionalId,
        ]);
    }
}
