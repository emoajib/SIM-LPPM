<?php

declare(strict_types=1);

namespace App\Livewire\Research\FinalReport;

use App\Enums\ProposalStatus;
use App\Livewire\Concerns\HasToast;
use App\Livewire\Forms\ReportForm;
use App\Livewire\Traits\HasFileUploads;
use App\Livewire\Traits\HasReportTemplates;
use App\Livewire\Traits\ReportAccess;
use App\Livewire\Traits\ReportAuthorization;
use App\Livewire\Traits\WithReportApproval;
use App\Models\AdditionalOutput;
use App\Models\Keyword;
use App\Models\MandatoryOutput;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\User;
use App\Services\LecturerEligibilityService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
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
    use ReportAccess;
    use ReportAuthorization;
    use WithFileUploads;
    use WithReportApproval;

    // Form instance - Livewire v3 Form pattern
    public ReportForm $form;

    // State to track if final report draft exists
    public bool $isFinalReportDraft = false;

    // Completeness check results
    public array $completenessMissing = [];

    /**
     * Run completeness check and store results
     */
    public function doCheckCompleteness(): void
    {
        $this->completenessMissing = $this->checkCompleteness();

        if (empty($this->completenessMissing)) {
            $this->toastSuccess('Semua lampiran dan dokumen sudah lengkap. Anda bisa langsung mengajukan laporan.');
        } else {
            $list = collect($this->completenessMissing)->map(fn ($item) => '• '.$item)->implode('<br>');
            $this->dispatch('banner-message', [
                'style' => 'warning',
                'message' => 'Berikut dokumen/lampiran yang belum lengkap:<br>'.$list,
            ]);
        }
    }

    // Contract info for Admin LPPM
    public string $contractNumber = '';

    public ?string $contractDate = null;

    // Title Change Request properties
    public bool $isRequestingTitleChange = false;

    public ?string $proposedTitle = null;

    public ?string $titleChangeReason = null;

    public ?string $titleChangeReviewNotes = null;

    // Schedule editing properties
    public array $scheduleItems = [];

    /**
     * Mount the component
     */
    public function mount(Proposal $proposal): void
    {
        $this->proposal = $proposal;
        $this->contractNumber = $proposal->contract_number ?? '';
        $this->contractDate = $proposal->contract_date ? Carbon::parse($proposal->contract_date)->format('Y-m-d') : null;

        // Check if proposal is completed
        if ($this->proposal->status !== ProposalStatus::COMPLETED) {
            abort(403, 'Laporan akhir hanya dapat diakses untuk proposal yang sudah selesai.');
        }

        // Check access
        $this->checkAccess();

        // Load existing final report FIRST
        /** @var ProgressReport|null $finalReport */
        $finalReport = $proposal->progressReports()
            ->where('reporting_period', 'final')
            ->latest()
            ->first();

        if ($finalReport) {
            $this->progressReport = $finalReport;
            $this->isFinalReportDraft = true;
        } else {
            // Fallback to latest progress report for pre-filling data, but it's NOT a final draft
            /** @var ProgressReport|null $latestReport */
            $latestReport = $proposal->progressReports()->latest()->first();
            $this->progressReport = $latestReport;
            $this->isFinalReportDraft = false;
        }

        // Enforce schedule: only block NEW submissions if period is closed
        // Allow access to existing drafts even if period is closed
        $type = 'research';
        /** @var LecturerEligibilityService $service */
        $service = app(LecturerEligibilityService::class);

        if ($this->canEdit && ! $service->isFinalReportOpen($type) && ! $this->isFinalReportDraft) {
            $this->canEdit = false;
        }

        // Initialize Livewire Form
        $this->form->type = 'final';
        $this->form->initWithProposal($this->proposal);

        if ($this->progressReport) {
            // Load existing report data into form
            $this->form->setReport($this->progressReport);
            $this->proposedTitle = $this->progressReport->proposed_title;
            $this->titleChangeReason = $this->progressReport->title_change_reason;
            $this->titleChangeReviewNotes = $this->progressReport->title_change_review_notes;
            $this->isRequestingTitleChange = ! empty($this->progressReport->proposed_title) || ! empty($this->progressReport->title_change_status);
        } else {
            // Initialize new report structure
            $this->form->initializeNewReport();
        }

        // Load existing schedule items or generate defaults
        $this->proposal->loadMissing('activitySchedules');
        $existingSchedules = $this->proposal->activitySchedules;

        if ($existingSchedules->isNotEmpty()) {
            $this->scheduleItems = $existingSchedules->map(fn ($s) => [
                'activity_name' => $s->activity_name,
                'year' => (int) ($s->year ?? 1),
                'start_month' => (int) ($s->start_month ?? 1),
                'end_month' => (int) ($s->end_month ?? 12),
            ])->toArray();
        } else {
            $this->scheduleItems = $this->generateDefaultSchedule();
        }
    }

    /**
     * Generate template default schedule based on duration_in_years
     */
    public function generateDefaultSchedule(): array
    {
        $duration = max((int) ($this->proposal->duration_in_years ?: 1), 1);
        $items = [];

        $templates = [
            ['activity_name' => 'Studi Literatur & Persiapan', 'start_month' => 1, 'end_month' => 3],
            ['activity_name' => 'Pengumpulan Data / Observasi', 'start_month' => 4, 'end_month' => 7],
            ['activity_name' => 'Analisis & Pengolahan Data', 'start_month' => 8, 'end_month' => 10],
            ['activity_name' => 'Penyusunan Laporan & Publikasi', 'start_month' => 11, 'end_month' => 12],
        ];

        for ($year = 1; $year <= $duration; $year++) {
            foreach ($templates as $t) {
                $items[] = array_merge($t, ['year' => $year]);
            }
        }

        return $items;
    }

    /**
     * Add a new schedule row
     */
    public function addScheduleItem(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }

        $this->scheduleItems[] = [
            'activity_name' => '',
            'year' => 1,
            'start_month' => 1,
            'end_month' => 3,
        ];
    }

    /**
     * Remove a schedule row
     */
    public function removeScheduleItem(int $index): void
    {
        if (! $this->canEdit) {
            abort(403);
        }

        unset($this->scheduleItems[$index]);
        $this->scheduleItems = array_values($this->scheduleItems);
    }

    /**
     * Reset schedule to default template
     */
    public function resetScheduleToDefault(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }

        $this->scheduleItems = $this->generateDefaultSchedule();
        $this->toastInfo('Jadwal diatur ulang ke template default. Klik "Simpan Jadwal" untuk menerapkan.');
    }

    /**
     * Save schedule items to database
     */
    public function saveScheduleItems(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }

        $this->validate([
            'scheduleItems' => 'nullable|array|max:50',
            'scheduleItems.*.activity_name' => 'required|string|max:255',
            'scheduleItems.*.year' => 'required|integer|min:1|max:10',
            'scheduleItems.*.start_month' => 'required|integer|min:1|max:12',
            'scheduleItems.*.end_month' => 'required|integer|min:1|max:12',
        ], [
            'scheduleItems.*.activity_name.required' => 'Nama kegiatan/tahapan wajib diisi.',
        ]);

        DB::transaction(function (): void {
            $this->proposal->activitySchedules()->delete();

            foreach ($this->scheduleItems as $item) {
                if (empty(trim($item['activity_name'] ?? ''))) {
                    continue;
                }

                $this->proposal->activitySchedules()->create([
                    'activity_name' => trim($item['activity_name']),
                    'year' => (int) ($item['year'] ?? 1),
                    'start_month' => (int) ($item['start_month'] ?? 1),
                    'end_month' => (int) ($item['end_month'] ?? 12),
                ]);
            }
        });

        // Touch proposal to invalidate PDF cache
        $this->proposal->touch();

        $this->toastSuccess('Jadwal pelaksanaan kegiatan berhasil disimpan.');
    }

    /**
     * Save / submit title change request by Lecturer
     */
    public function saveTitleChangeRequest(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }

        $this->validate([
            'proposedTitle' => 'required|string|min:10|max:500',
            'titleChangeReason' => 'required|string|min:10|max:1000',
        ], [
            'proposedTitle.required' => 'Judul baru yang diajukan wajib diisi.',
            'proposedTitle.min' => 'Judul baru minimal 10 karakter.',
            'titleChangeReason.required' => 'Alasan/justifikasi perubahan judul wajib diisi.',
            'titleChangeReason.min' => 'Alasan perubahan minimal 10 karakter.',
        ]);

        if (! $this->progressReport) {
            $this->progressReport = $this->form->save($this->progressReport);
            $this->isFinalReportDraft = true;
        }

        $this->progressReport->update([
            'proposed_title' => $this->proposedTitle,
            'title_change_reason' => $this->titleChangeReason,
            'title_change_status' => 'pending',
            'title_change_reviewed_at' => null,
            'title_change_reviewer_id' => null,
            'title_change_review_notes' => null,
        ]);

        $this->isRequestingTitleChange = true;

        $this->dispatch('banner-message', [
            'style' => 'success',
            'message' => 'Pengajuan perubahan judul berhasil dikirim ke LPPM untuk ditinjau.',
        ]);
    }

    /**
     * Cancel title change request by Lecturer
     */
    public function cancelTitleChangeRequest(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }

        if ($this->progressReport && $this->progressReport->title_change_status === 'pending') {
            $this->progressReport->update([
                'proposed_title' => null,
                'title_change_reason' => null,
                'title_change_status' => null,
            ]);

            $this->proposedTitle = null;
            $this->titleChangeReason = null;
            $this->isRequestingTitleChange = false;

            $this->dispatch('banner-message', [
                'style' => 'info',
                'message' => 'Pengajuan perubahan judul telah dibatalkan.',
            ]);
        }
    }

    /**
     * Approve title change by Admin LPPM
     */
    public function approveTitleChange(): void
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->activeHasAnyRole(['admin lppm', 'admin lppm saintek', 'admin lppm dekabita', 'kepala lppm', 'superadmin'])) {
            abort(403, 'Hanya Admin LPPM yang berwenang menyetujui perubahan judul.');
        }

        if (! $this->progressReport || ! $this->progressReport->proposed_title) {
            $this->dispatch('banner-message', [
                'style' => 'danger',
                'message' => 'Tidak ada pengajuan judul baru untuk disetujui.',
            ]);

            return;
        }

        $newTitle = $this->progressReport->proposed_title;

        $this->progressReport->update([
            'title_change_status' => 'approved',
            'title_change_reviewed_at' => now(),
            'title_change_reviewer_id' => $user->id,
            'title_change_review_notes' => $this->titleChangeReviewNotes,
        ]);

        $this->proposal->update([
            'title' => $newTitle,
        ]);

        $this->dispatch('banner-message', [
            'style' => 'success',
            'message' => 'Perubahan judul berhasil disetujui dan diterapkan pada sistem.',
        ]);
    }

    /**
     * Reject title change by Admin LPPM
     */
    public function rejectTitleChange(): void
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->activeHasAnyRole(['admin lppm', 'admin lppm saintek', 'admin lppm dekabita', 'kepala lppm', 'superadmin'])) {
            abort(403, 'Hanya Admin LPPM yang berwenang menolak perubahan judul.');
        }

        if (! $this->progressReport) {
            return;
        }

        $this->validate([
            'titleChangeReviewNotes' => 'required|string|min:5|max:500',
        ], [
            'titleChangeReviewNotes.required' => 'Catatan alasan penolakan wajib diisi.',
        ]);

        $this->progressReport->update([
            'title_change_status' => 'rejected',
            'title_change_reviewed_at' => now(),
            'title_change_reviewer_id' => $user->id,
            'title_change_review_notes' => $this->titleChangeReviewNotes,
        ]);

        $this->dispatch('banner-message', [
            'style' => 'warning',
            'message' => 'Pengajuan perubahan judul telah ditolak.',
        ]);
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

                // Save report files (presentation file only for Community Service/PKM)
                $this->saveSubstanceFile($report, 'final');
                $this->saveRealizationFile($report, 'final');
                $this->saveSignatureFile($report, 'final');
                $this->saveCooperationProofFile($report);
                $this->saveImplementationProofFile($report);
                $this->saveResearchAttachments($report);

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
     * Check if all required report components are complete
     */
    public function checkCompleteness(): array
    {
        $missing = [];

        // Substance file
        $hasSubstance = $this->progressReport && $this->progressReport->hasMedia('substance_file');
        $hasNewSubstance = $this->substanceFile && $this->substanceFile instanceof TemporaryUploadedFile;
        if (! $hasSubstance && ! $hasNewSubstance) {
            $missing[] = 'File Substansi (PDF) laporan akhir';
        }

        // Budget
        if ($this->proposal->budgetItems->count() === 0) {
            $missing[] = 'Rencana Anggaran (RAB)';
        }

        // Team
        if ($this->proposal->teamMembers->count() === 0) {
            $missing[] = 'Data Tim Pelaksana';
        }

        // Outputs (mandatory)
        if ($this->progressReport) {
            $hasMandatoryOutputs = $this->progressReport->mandatoryOutputs->some(fn ($o) => $o->hasMedia('journal_article') || $o->hasMedia('book_document') || $o->hasMedia('publication_certificate') || $o->hasMedia('output_file')
            );
            if (! $hasMandatoryOutputs) {
                $missing[] = 'Luaran Wajib (bukti dokumen)';
            }
        }

        // Teaching Material (Lampiran 5: RPS/Bahan Ajar)
        $hasTeachingMaterial = $this->teachingMaterialFile instanceof TemporaryUploadedFile || $this->teachingMaterialFile instanceof UploadedFile;
        if (! $hasTeachingMaterial && (! $this->progressReport || ! $this->progressReport->hasMedia('teaching_material_file'))) {
            $missing[] = 'Lampiran 5: RPS / Bahan Ajar';
        }

        // Logbook
        if ($this->proposal->dailyNotes->count() === 0) {
            $missing[] = 'Logbook Harian';
        }

        return $missing;
    }

    /**
     * Submit the report
     */
    public function submit(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }

        // Validate Budget Usage (Threshold 70% if Daily Notes exist)
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        $totalProposedBudget = (float) $this->proposal->budgetItems()->sum('total_price');
        $totalUsedBudget = (float) $this->proposal->dailyNotes()->sum('amount');

        if ($totalProposedBudget > 0 && $totalUsedBudget > 0) {
            $percentage = ($totalUsedBudget / $totalProposedBudget) * 100;

            if ($percentage < 70) {
                $message = sprintf(
                    'Gagal mengajukan: Realisasi anggaran di Catatan Harian baru %.1f%% (Rp %s dari Rp %s). Minimal 70%% diperlukan untuk mengajukan laporan akhir.',
                    $percentage,
                    number_format($totalUsedBudget, 0, ',', '.'),
                    number_format($totalProposedBudget, 0, ',', '.')
                );
                session()->flash('error', $message);
                $this->toastError($message);

                return;
            }
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

        try {
            DB::transaction(function () {
                // Submit report via form
                $report = $this->form->submit($this->progressReport);
                $this->progressReport = $report;
                $this->isFinalReportDraft = true;

                // Save report files (presentation file only for Community Service/PKM)
                $this->saveSubstanceFile($report, 'final');
                $this->saveRealizationFile($report, 'final');
                $this->saveSignatureFile($report, 'final');
                $this->saveCooperationProofFile($report);
                $this->saveImplementationProofFile($report);
                $this->saveResearchAttachments($report);

                // Save output files
                $this->saveOutputFiles($report);
            });

            $message = 'Laporan akhir berhasil diajukan.';
            session()->flash('success', $message);
            $this->toastSuccess($message);
            $this->redirect(route('research.final-report.index'), navigate: true);
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

            if ($mandatoryOutput && isset($this->tempMandatoryFiles[$proposalOutputId])) {
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
                if (isset($this->tempAdditionalFiles[$proposalOutputId])) {
                    $this->saveAdditionalOutputFile($additionalOutput, $proposalOutputId, 'final');
                }
                if (isset($this->tempAdditionalCerts[$proposalOutputId])) {
                    $this->saveAdditionalOutputCert($additionalOutput, $proposalOutputId, 'final');
                }
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
     * Handle signature page file upload (real-time)
     */
    public function updatedSignatureFile(): void
    {
        if (! $this->canEdit) {
            $this->signatureFile = null;

            return;
        }

        $this->validateSignatureFile();
    }

    /**
     * Handle research teaching material file upload (real-time)
     */
    public function updatedTeachingMaterialFile(): void
    {
        if (! $this->canEdit) {
            $this->teachingMaterialFile = null;

            return;
        }

        $this->validateTeachingMaterialFile();
    }

    /**
     * Handle partner cooperation proof file upload (real-time)
     */
    public function updatedCooperationProofFile(): void
    {
        if (! $this->canEdit) {
            $this->cooperationProofFile = null;

            return;
        }

        $this->validateCooperationProofFile();
    }

    /**
     * Handle partner implementation proof file upload (real-time)
     */
    public function updatedImplementationProofFile(): void
    {
        if (! $this->canEdit) {
            $this->implementationProofFile = null;

            return;
        }

        $this->validateImplementationProofFile();
    }

    /**
     * Handle mandatory output file upload (real-time)
     */
    public function updatedTempMandatoryFiles($value, $key): void
    {
        if (! $this->canEdit) {
            return;
        }

        try {
            $this->validateMandatoryFile((int) $key);

            $this->form->tempMandatoryFiles[$key] = $value;
            $this->form->saveMandatoryOutputWithFile((int) $key, validate: false);

            // Sync report
            $this->progressReport = $this->form->progressReport;

            unset($this->tempMandatoryFiles[$key]);

            $message = 'File luaran wajib berhasil disimpan.';
            session()->flash('success', $message);
            $this->toastSuccess($message);
        } catch (\Exception $e) {
            $message = 'Gagal mengunggah file: '.$e->getMessage();
            session()->flash('error', $message);
            $this->toastError($message);
        }
    }

    /**
     * Handle additional output file upload (real-time)
     */
    public function updatedTempAdditionalFiles($value, $key): void
    {
        if (! $this->canEdit) {
            return;
        }

        try {
            $this->validateAdditionalFile((int) $key);

            $this->form->tempAdditionalFiles[$key] = $value;
            $this->form->saveAdditionalOutputWithFile((int) $key, validate: false);

            $this->progressReport = $this->form->progressReport;

            unset($this->tempAdditionalFiles[$key]);

            $message = 'File luaran tambahan berhasil disimpan.';
            session()->flash('success', $message);
            $this->toastSuccess($message);
        } catch (\Exception $e) {
            $message = 'Gagal mengunggah file: '.$e->getMessage();
            session()->flash('error', $message);
            $this->toastError($message);
        }
    }

    /**
     * Handle additional output certificate upload (real-time)
     */
    public function updatedTempAdditionalCerts($value, $key): void
    {
        if (! $this->canEdit) {
            return;
        }

        try {
            $this->validateAdditionalCert((int) $key);

            $this->form->tempAdditionalCerts[$key] = $value;
            $this->form->saveAdditionalOutputWithFile((int) $key, validate: false);

            $this->progressReport = $this->form->progressReport;

            unset($this->tempAdditionalCerts[$key]);

            $message = 'Sertifikat berhasil disimpan.';
            session()->flash('success', $message);
            $this->toastSuccess($message);
        } catch (\Exception $e) {
            $message = 'Gagal mengunggah file: '.$e->getMessage();
            session()->flash('error', $message);
            $this->toastError($message);
        }
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
     * Remove signature page file
     */
    public function removeSignatureFile(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }

        if ($this->progressReport) {
            $this->progressReport->clearMediaCollection('signature_page');
            $message = 'Halaman pengesahan berhasil dihapus.';
            session()->flash('success', $message);
            $this->toastSuccess($message);
        }
    }

    /**
     * Remove research teaching material file
     */
    public function removeTeachingMaterialFile(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }

        if ($this->progressReport) {
            $this->progressReport->clearMediaCollection('teaching_material_file');
            $message = 'Berkas bahan ajar berhasil dihapus.';
            session()->flash('success', $message);
            $this->toastSuccess($message);
        }
    }

    /**
     * Remove partner cooperation proof file
     */
    public function removeCooperationProofFile(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }

        if ($this->progressReport) {
            $this->progressReport->clearMediaCollection('partner_cooperation_proof');
            $message = 'Dokumen bukti kerjasama mitra berhasil dihapus.';
            session()->flash('success', $message);
            $this->toastSuccess($message);
        }
    }

    /**
     * Remove partner implementation proof file
     */
    public function removeImplementationProofFile(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }

        if ($this->progressReport) {
            $this->progressReport->clearMediaCollection('partner_implementation_proof');
            $message = 'Dokumen bukti implementasi mitra berhasil dihapus.';
            session()->flash('success', $message);
            $this->toastSuccess($message);
        }
    }

    /**
     * Edit mandatory output - open modal
     */
    public function editMandatoryOutput(int $proposalOutputId): void
    {
        $this->form->editMandatoryOutput($proposalOutputId);
    }

    /**
     * Save mandatory output (journal article)
     */
    public function saveMandatoryOutput(int $proposalOutputId): void
    {
        if (! $this->canEdit) {
            abort(403);
        }

        $this->form->saveMandatoryOutput($proposalOutputId);
        $this->dispatch('close-modal', modalId: 'modalMandatoryOutput');
        $message = 'Data luaran wajib berhasil disimpan.';
        session()->flash('success', $message);
        $this->toastSuccess($message);
    }

    /**
     * Edit additional output - open modal
     */
    public function editAdditionalOutput(int $proposalOutputId): void
    {
        $this->form->editAdditionalOutput($proposalOutputId);
    }

    /**
     * Save additional output (book)
     */
    public function saveAdditionalOutput(int $proposalOutputId): void
    {
        if (! $this->canEdit) {
            abort(403);
        }

        if (! $this->progressReport) {
            $message = 'Laporan belum dibuat. Silakan upload file substansi terlebih dahulu.';
            session()->flash('error', $message);
            $this->toastError($message);

            return;
        }

        try {
            // Ensure form has the progress report reference
            $this->form->progressReport = $this->progressReport;

            // Save via form
            $this->form->saveAdditionalOutputWithFile($proposalOutputId);

            $message = 'Data luaran tambahan berhasil disimpan.';
            session()->flash('success', $message);
            $this->toastSuccess($message);
            $this->dispatch('close-modal', modalId: 'modalAdditionalOutput');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->toastError('Gagal menyimpan: '.$e->getMessage());
        }
    }

    /**
     * Close mandatory modal
     */
    public function closeMandatoryModal(): void
    {
        $this->form->closeMandatoryModal();
    }

    /**
     * Close additional modal
     */
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

        return view('livewire.research.final-report.show', [
            'allKeywords' => $this->getAllKeywords(),
            'editingMandatoryId' => $this->form->editingMandatoryId,
            'editingAdditionalId' => $this->form->editingAdditionalId,
            'isFinalReportDraft' => $this->isFinalReportDraft,
            'mandatoryOutputsMap' => $mandatoryOutputsMap,
            'additionalOutputsMap' => $additionalOutputsMap,
        ]);
    }
}
