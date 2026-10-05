<?php

declare(strict_types=1);

namespace App\Livewire\FinalReport;

use App\Enums\ProposalStatus;
use App\Enums\ReportStatus;
use App\Livewire\Concerns\HasToast;
use App\Livewire\Forms\ReportForm;
use App\Livewire\Traits\HasFileUploads;
use App\Livewire\Traits\HasReportTemplates;
use App\Livewire\Traits\ManagesAdditionalOutputs;
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
use App\Traits\HandlesReportStateTransitions;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager
abstract class BaseFinalReportShow extends Component
{
    use HandlesReportStateTransitions;
    use HasFileUploads;
    use HasReportTemplates;
    use HasToast;
    use ManagesAdditionalOutputs;
    use ReportAccess;
    use ReportAuthorization;
    use WithFileUploads;
    use WithReportApproval;

    // Form instance - Livewire v3 Form pattern
    public ReportForm $form;

    // State to track if final report draft exists
    #[Locked]
    public bool $isFinalReportDraft = false;

    // Completeness check results
    public array $completenessMissing = [];

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

    // File upload properties (common to both)
    public $substanceFile;

    public $realizationFile;

    public $signatureFile;

    public $cooperationProofFile;

    public $implementationProofFile;

    // Research-specific
    public $teachingMaterialFile;

    // PKM-specific
    public $presentationFile;

    public $partnerAgreementFile;

    public $chairpersonStatementFile;

    public $serviceLocationMapFile;

    public $officialReportPkmFile;

    public $assignmentLetterPkmFile;

    public $questionnairePkmFile;

    public $teamAttendanceFile;

    public $participantAttendanceFile;

    public $trainingMaterialFile;

    public $activityPhotosFiles = [];

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

        // Load existing final report FIRST
        /** @var ProgressReport|null $finalReport */
        $finalReport = $proposal->progressReports()
            ->where('reporting_period', 'final')
            ->latest()
            ->first();

        if ($finalReport) {
            $this->progressReport = $finalReport;
            // isFinalReportDraft controls visibility of the "Ajukan" button.
            // Must be true for DRAFT (new) and REJECTED (needs re-submission after revision).
            $this->isFinalReportDraft = in_array($finalReport->status, [
                ReportStatus::DRAFT,
                ReportStatus::REJECTED,
            ]);
        } else {
            // Fallback to latest progress report for pre-filling data, but it's NOT a final draft
            /** @var ProgressReport|null $latestReport */
            $latestReport = $proposal->progressReports()->latest()->first();
            $this->progressReport = $latestReport;
            $this->isFinalReportDraft = false;
        }

        // Check access (evaluates canEditReport with loaded progressReport)
        $this->checkAccess();

        // Enforce schedule: only block NEW submissions if period is closed
        // Allow access to existing drafts even if period is closed
        $type = $this->getReportType();
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

    /**
     * Generate template default schedule based on duration_in_years
     */
    public function generateDefaultSchedule(): array
    {
        $duration = max((int) ($this->proposal->duration_in_years ?: 1), 1);
        $items = [];

        $templates = $this->getScheduleTemplates();

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
            abort(403, 'Laporan akhir sedang dalam proses peninjauan atau telah disahkan, perubahan tidak diperbolehkan.');
        }

        try {
            DB::transaction(function () {
                // Save report via form
                $report = $this->form->save($this->progressReport);
                $this->progressReport = $report;

                // Mark as existing draft
                // isFinalReportDraft must stay true for DRAFT and REJECTED statuses
                $this->isFinalReportDraft = in_array($report->status, [
                    ReportStatus::DRAFT,
                    ReportStatus::REJECTED,
                ]);
                $this->canEdit = $this->canEditReport($this->proposal, $report);

                // Save report files (common)
                $this->saveSubstanceFile($report, 'final');
                $this->saveRealizationFile($report, 'final');
                $this->saveSignatureFile($report, 'final');
                $this->saveCooperationProofFile($report);
                $this->saveImplementationProofFile($report);

                // Type-specific attachments
                $this->saveTypeAttachments($report);

                // Save output files
                $this->saveOutputFiles($report);

                // Vetted by AI - Manual Review Required by Senior Engineer/Manager
                // Ensure media and outputs relations are freshly loaded in Livewire state
                $this->progressReport = $report->fresh(['media', 'mandatoryOutputs', 'additionalOutputs']);
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
            $missing[] = 'File Substansi (PDF)';
        }

        // Budget
        if ($this->proposal->budgetItems->count() === 0) {
            $missing[] = 'Rencana Anggaran (RAB)';
        }

        // Team
        if ($this->proposal->teamMembers->count() === 0) {
            $missing[] = 'Data Tim Pelaksana';
        }

        // Type-specific completeness rules
        $typeMissing = $this->getCompletenessRules();
        $missing = array_merge($missing, $typeMissing);

        return $missing;
    }

    /**
     * Submit the report
     */
    public function submit(): void
    {
        if (! $this->canEdit) {
            abort(403, 'Laporan akhir sedang dalam proses peninjauan atau telah disahkan, pengajuan tidak diperbolehkan.');
        }

        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        // Laporan Keuangan (LPJ) dipisahkan dari Laporan Akhir sehingga dosen tidak diblokir.

        // Validate that substance file exists (either in DB or newly uploaded)
        $hasFileInDatabase = $this->progressReport && $this->progressReport->hasMedia('substance_file');
        $hasNewUploadedFile = $this->substanceFile && $this->substanceFile instanceof TemporaryUploadedFile;

        if (! $hasFileInDatabase && ! $hasNewUploadedFile) {
            $message = 'Gagal mengajukan: Anda wajib mengunggah File Substansi (PDF) laporan akhir.';
            $this->addError('substanceFile', $message);
            $this->toastError($message);

            return;
        }

        // Realization file and presentation file are optional for final report submission
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager

        // Luaran wajib harus dilengkapi sebelum laporan akhir dapat diajukan.
        // Mencegah laporan disetujui dalam keadaan luaran kosong (deadlock eligibility).
        $missingOutputs = $this->missingMandatoryOutputs();
        if (! empty($missingOutputs)) {
            $message = 'Gagal mengajukan: luaran wajib berikut belum diisi — '.implode(', ', $missingOutputs).'. Lengkapi pada bagian Luaran Wajib terlebih dahulu.';
            $this->addError('mandatoryOutputs', $message);
            $this->toastError($message);

            return;
        }

        // Determine expected current status for optimistic locking
        $expectedStatus = $this->progressReport->status ?? ReportStatus::DRAFT;
        $allowedFrom = [ReportStatus::DRAFT, ReportStatus::REJECTED];
        if (! in_array($expectedStatus, $allowedFrom, true)) {
            $this->toastError('Laporan tidak dapat diajukan dari status saat ini.');

            return;
        }

        try {
            $submitted = $this->transitionReport(
                $this->progressReport ?? $this->form->save($this->progressReport),
                $expectedStatus,
                ReportStatus::SUBMITTED,
                function ($report) {
                    // Save report files (common)
                    $this->saveSubstanceFile($report, 'final');
                    $this->saveRealizationFile($report, 'final');
                    $this->saveSignatureFile($report, 'final');
                    $this->saveCooperationProofFile($report);
                    $this->saveImplementationProofFile($report);

                    // Type-specific attachments
                    $this->saveTypeAttachments($report);

                    // Save output files
                    $this->saveOutputFiles($report);

                    $this->progressReport = $report;
                    $this->isFinalReportDraft = false;
                    $this->canEdit = false;
                }
            );

            if (! $submitted) {
                return;
            }

            $message = 'Laporan akhir berhasil diajukan.';
            session()->flash('success', $message);
            $this->toastSuccess($message);
            $this->redirect($this->getIndexRoute(), navigate: true);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            $message = 'Gagal mengajukan laporan: '.$e->getMessage();
            session()->flash('error', $message);
            $this->toastError($message);
        }
    }

    /**
     * Daftar jenis luaran wajib proposal yang belum dilengkapi datanya
     * pada form laporan akhir. Kosong = semua luaran wajib sudah terisi.
     *
     * @return array<int, string>
     */
    protected function missingMandatoryOutputs(): array
    {
        $wajibOutputs = $this->proposal->outputs->where('category', 'Wajib');

        if ($wajibOutputs->isEmpty()) {
            return [];
        }

        $missing = [];
        foreach ($wajibOutputs as $output) {
            $data = $this->form->mandatoryOutputs[$output->id] ?? [];
            if (empty($data['status_type']) && empty($data['journal_title'])) {
                $missing[] = $output->type ?? "Luaran #{$output->id}";
            }
        }

        return $missing;
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

    // ============================================================
    // File upload handlers (common)
    // ============================================================

    public function updatedSubstanceFile(): void
    {
        if (! $this->canEdit) {
            $this->substanceFile = null;

            return;
        }
        $this->validateSubstanceFile();
    }

    public function updatedRealizationFile(): void
    {
        if (! $this->canEdit) {
            $this->realizationFile = null;

            return;
        }
        $this->validateRealizationFile();
    }

    public function updatedSignatureFile(): void
    {
        if (! $this->canEdit) {
            $this->signatureFile = null;

            return;
        }
        $this->validateSignatureFile();
    }

    public function updatedCooperationProofFile(): void
    {
        if (! $this->canEdit) {
            $this->cooperationProofFile = null;

            return;
        }
        $this->validateCooperationProofFile();
    }

    public function updatedImplementationProofFile(): void
    {
        if (! $this->canEdit) {
            $this->implementationProofFile = null;

            return;
        }
        $this->validateImplementationProofFile();
    }

    // ============================================================
    // File removal handlers (common)
    // ============================================================

    public function removeSubstanceFile(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }
        if ($this->progressReport) {
            $this->progressReport->clearMediaCollection('substance_file');
            $this->toastSuccess('File substansi berhasil dihapus.');
        }
    }

    public function removeRealizationFile(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }
        if ($this->progressReport) {
            $this->progressReport->clearMediaCollection('realization_file');
            $this->toastSuccess('File realisasi berhasil dihapus.');
        }
    }

    public function removeSignatureFile(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }
        if ($this->progressReport) {
            $this->progressReport->clearMediaCollection('signature_page');
            $this->toastSuccess('Halaman pengesahan berhasil dihapus.');
        }
    }

    public function removeCooperationProofFile(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }
        if ($this->progressReport) {
            $this->progressReport->clearMediaCollection('partner_cooperation_proof');
            $this->toastSuccess('Dokumen bukti kerjasama mitra berhasil dihapus.');
        }
    }

    public function removeImplementationProofFile(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }
        if ($this->progressReport) {
            $this->progressReport->clearMediaCollection('partner_implementation_proof');
            $this->toastSuccess('Dokumen bukti implementasi mitra berhasil dihapus.');
        }
    }

    // ============================================================
    // Output handlers (common)
    // ============================================================

    public function editMandatoryOutput(int $proposalOutputId): void
    {
        $this->form->editMandatoryOutput($proposalOutputId);
    }

    public function saveMandatoryOutput(int $proposalOutputId): void
    {
        if (! $this->canEdit) {
            abort(403);
        }

        $this->form->saveMandatoryOutput($proposalOutputId);
        $this->dispatch('close-modal', modalId: 'modalMandatoryOutput');
        $this->toastSuccess('Data luaran wajib berhasil disimpan.');
    }

    public function editAdditionalOutput(int $proposalOutputId): void
    {
        $this->form->editAdditionalOutput($proposalOutputId);
    }

    public function saveAdditionalOutput(int $proposalOutputId): void
    {
        if (! $this->canEdit) {
            abort(403);
        }

        if (! $this->progressReport) {
            $this->toastError('Laporan belum dibuat. Silakan upload file substansi terlebih dahulu.');

            return;
        }

        try {
            $this->form->progressReport = $this->progressReport;
            $this->form->saveAdditionalOutputWithFile($proposalOutputId);
            $this->toastSuccess('Data luaran tambahan berhasil disimpan.');
            $this->dispatch('close-modal', modalId: 'modalAdditionalOutput');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->toastError('Gagal menyimpan: '.$e->getMessage());
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

    public function getAllKeywords(): Collection
    {
        return Keyword::orderBy('name')->get();
    }

    // ============================================================
    // Abstract methods that MUST be implemented by children
    // ============================================================

    /**
     * Get report type: 'research' or 'community_service'
     */
    abstract protected function getReportType(): string;

    /**
     * Get schedule templates for this report type
     *
     * @return array<int, array{activity_name: string, start_month: int, end_month: int}>
     */
    abstract protected function getScheduleTemplates(): array;

    /**
     * Get type-specific completeness rules
     *
     * @return array<string>
     */
    abstract protected function getCompletenessRules(): array;

    /**
     * Save type-specific attachments
     */
    abstract protected function saveTypeAttachments(ProgressReport $report): void;

    /**
     * Get index route name for redirect after submit
     */
    abstract protected function getIndexRoute(): string;

    /**
     * Get view path for render()
     */
    abstract protected function getViewPath(): string;

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

        return view($this->getViewPath(), [
            'allKeywords' => $this->getAllKeywords(),
            'editingMandatoryId' => $this->form->editingMandatoryId,
            'editingAdditionalId' => $this->form->editingAdditionalId,
            'isFinalReportDraft' => $this->isFinalReportDraft,
            'mandatoryOutputsMap' => $mandatoryOutputsMap,
            'additionalOutputsMap' => $additionalOutputsMap,
        ]);
    }
}
