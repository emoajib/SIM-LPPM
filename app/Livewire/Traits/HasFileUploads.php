<?php

declare(strict_types=1);

namespace App\Livewire\Traits;

use App\Enums\ReportStatus;
use App\Models\AdditionalOutput;
use App\Models\MandatoryOutput;
use App\Models\ProgressReport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

trait HasFileUploads
{
    // Progress Report document files
    public $substanceFile;

    // Final Report additional files
    public $realizationFile;

    public $presentationFile;

    public $signatureFile;

    // Partner change documentation (final report only)
    public $cooperationProofFile;

    public $implementationProofFile;

    // Vetted by AI - Manual Review Required by Senior Engineer/Manager
    // Research Attachments
    public $teachingMaterialFile; // Lampiran 5: RPS / Bahan Ajar

    // PKM Attachments
    public $partnerAgreementFile; // Lampiran 3: Surat Kesediaan Mitra

    public $chairpersonStatementFile; // Lampiran 4: Surat Pernyataan Ketua

    public $serviceLocationMapFile; // Lampiran 5: Peta Lokasi Pengabdian

    public $officialReportPkmFile; // Lampiran 6: Berita Acara Pelaksanaan PKM

    public $assignmentLetterPkmFile; // Lampiran 7: Surat Tugas Pelaksanaan PKM

    public $questionnairePkmFile; // Lampiran 8: Kuisioner Pengabdian

    public $teamAttendanceFile; // Lampiran 9: Daftar Hadir Tim PKM

    public $participantAttendanceFile; // Lampiran 10: Daftar Hadir Peserta PKM

    public $trainingMaterialFile; // Lampiran 11: Materi Kegiatan PKM

    public $activityPhotosFiles = []; // Lampiran 12: Foto Kegiatan PKM

    // Temporary file uploads
    public array $tempMandatoryFiles = [];

    public array $tempAdditionalFiles = [];

    public array $tempAdditionalCerts = [];

    /**
     * Validate substance file upload
     */
    public function validateSubstanceFile(): void
    {
        $this->validate([
            'substanceFile' => 'nullable|file|mimes:pdf|max:10240',
        ]);
    }

    /**
     * Validate realization file upload
     */
    public function validateRealizationFile(): void
    {
        $this->validate([
            'realizationFile' => 'nullable|file|mimes:pdf,docx|max:10240',
        ]);
    }

    /**
     * Validate presentation file upload
     */
    public function validatePresentationFile(): void
    {
        $this->validate([
            'presentationFile' => 'nullable|file|mimes:pdf,ppt,pptx|max:51200',
        ]);
    }

    /**
     * Validate mandatory output file upload (journal article)
     */
    public function validateMandatoryFile(int $proposalOutputId): void
    {
        $this->validate([
            "tempMandatoryFiles.{$proposalOutputId}" => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);
    }

    /**
     * Validate additional output file upload (book document)
     */
    public function validateAdditionalFile(int $proposalOutputId): void
    {
        $this->validate([
            "tempAdditionalFiles.{$proposalOutputId}" => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);
    }

    /**
     * Validate additional output certificate upload
     */
    public function validateAdditionalCert(int $proposalOutputId): void
    {
        $this->validate([
            "tempAdditionalCerts.{$proposalOutputId}" => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);
    }

    /**
     * Validate partner cooperation proof file upload
     */
    public function validateCooperationProofFile(): void
    {
        $this->validate([
            'cooperationProofFile' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);
    }

    /**
     * Validate partner implementation proof file upload
     */
    public function validateImplementationProofFile(): void
    {
        $this->validate([
            'implementationProofFile' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);
    }

    /**
     * Validate signature page file upload
     */
    public function validateSignatureFile(): void
    {
        $this->validate([
            'signatureFile' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);
    }

    /**
     * Validate research teaching material file upload
     */
    public function validateTeachingMaterialFile(): void
    {
        $this->validate([
            'teachingMaterialFile' => 'nullable|file|mimes:pdf,docx|max:10240',
        ]);
    }

    /**
     * Validate PKM partner agreement file upload
     */
    public function validatePartnerAgreementFile(): void
    {
        $this->validate([
            'partnerAgreementFile' => 'nullable|file|mimes:pdf|max:10240',
        ]);
    }

    /**
     * Validate PKM chairperson statement file upload
     */
    public function validateChairpersonStatementFile(): void
    {
        $this->validate([
            'chairpersonStatementFile' => 'nullable|file|mimes:pdf|max:10240',
        ]);
    }

    /**
     * Validate PKM service location map file upload
     */
    public function validateServiceLocationMapFile(): void
    {
        $this->validate([
            'serviceLocationMapFile' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);
    }

    /**
     * Validate PKM official report file upload
     */
    public function validateOfficialReportPkmFile(): void
    {
        $this->validate([
            'officialReportPkmFile' => 'nullable|file|mimes:pdf|max:10240',
        ]);
    }

    /**
     * Validate PKM assignment letter file upload
     */
    public function validateAssignmentLetterPkmFile(): void
    {
        $this->validate([
            'assignmentLetterPkmFile' => 'nullable|file|mimes:pdf|max:10240',
        ]);
    }

    /**
     * Validate PKM questionnaire file upload
     */
    public function validateQuestionnairePkmFile(): void
    {
        $this->validate([
            'questionnairePkmFile' => 'nullable|file|mimes:pdf|max:10240',
        ]);
    }

    /**
     * Validate PKM team attendance file upload
     */
    public function validateTeamAttendanceFile(): void
    {
        $this->validate([
            'teamAttendanceFile' => 'nullable|file|mimes:pdf|max:10240',
        ]);
    }

    /**
     * Validate PKM participant attendance file upload
     */
    public function validateParticipantAttendanceFile(): void
    {
        $this->validate([
            'participantAttendanceFile' => 'nullable|file|mimes:pdf|max:10240',
        ]);
    }

    /**
     * Validate PKM training material file upload
     */
    public function validateTrainingMaterialFile(): void
    {
        $this->validate([
            'trainingMaterialFile' => 'nullable|file|mimes:pdf|max:10240',
        ]);
    }

    /**
     * Validate PKM activity photos file upload
     */
    public function validateActivityPhotosFiles(): void
    {
        $this->validate([
            'activityPhotosFiles' => 'nullable|array|max:10',
            'activityPhotosFiles.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);
    }

    /**
     * Save substance file to media collection
     */
    protected function saveSubstanceFile(ProgressReport $report, string $reportType = 'progress'): void
    {
        if (! $this->substanceFile || ! ($this->substanceFile instanceof TemporaryUploadedFile || $this->substanceFile instanceof UploadedFile)) {
            return;
        }

        try {
            $report->clearMediaCollection('substance_file');
            $report
                ->addMedia($this->substanceFile->getRealPath())
                ->usingName($this->substanceFile->getClientOriginalName())
                ->usingFileName($this->substanceFile->hashName())
                ->withCustomProperties([
                    'uploaded_by' => Auth::id(),
                    'proposal_id' => $report->proposal_id,
                    'report_type' => $reportType,
                ])
                ->toMediaCollection('substance_file');
        } catch (\Exception $e) {
            Log::error('Upload report substance file failed: '.$e->getMessage());
        }
    }

    /**
     * Save realization file to media collection
     */
    protected function saveRealizationFile(ProgressReport $report, string $reportType = 'final'): void
    {
        if (! $this->realizationFile || ! ($this->realizationFile instanceof TemporaryUploadedFile || $this->realizationFile instanceof UploadedFile)) {
            return;
        }

        try {
            $report->clearMediaCollection('realization_file');
            $report
                ->addMedia($this->realizationFile->getRealPath())
                ->usingName($this->realizationFile->getClientOriginalName())
                ->usingFileName($this->realizationFile->hashName())
                ->withCustomProperties([
                    'uploaded_by' => Auth::id(),
                    'proposal_id' => $report->proposal_id,
                    'report_type' => $reportType,
                ])
                ->toMediaCollection('realization_file');
        } catch (\Exception $e) {
            Log::error('Upload report realization file failed: '.$e->getMessage());
        }
    }

    /**
     * Save presentation file to media collection
     */
    protected function savePresentationFile(ProgressReport $report, string $reportType = 'final'): void
    {
        if (! $this->presentationFile || ! ($this->presentationFile instanceof TemporaryUploadedFile || $this->presentationFile instanceof UploadedFile)) {
            return;
        }

        try {
            $report->clearMediaCollection('presentation_file');
            $report
                ->addMedia($this->presentationFile->getRealPath())
                ->usingName($this->presentationFile->getClientOriginalName())
                ->usingFileName($this->presentationFile->hashName())
                ->withCustomProperties([
                    'uploaded_by' => Auth::id(),
                    'proposal_id' => $report->proposal_id,
                    'report_type' => $reportType,
                ])
                ->toMediaCollection('presentation_file');
        } catch (\Exception $e) {
            Log::error('Upload report presentation file failed: '.$e->getMessage());
        }
    }

    /**
     * Save signature file to media collection
     */
    protected function saveSignatureFile(ProgressReport $report, string $reportType = 'final'): void
    {
        if (! $this->signatureFile || ! ($this->signatureFile instanceof TemporaryUploadedFile || $this->signatureFile instanceof UploadedFile)) {
            return;
        }

        try {
            $report->clearMediaCollection('signature_page');
            $report
                ->addMedia($this->signatureFile->getRealPath())
                ->usingName($this->signatureFile->getClientOriginalName())
                ->usingFileName($this->signatureFile->hashName())
                ->withCustomProperties([
                    'uploaded_by' => Auth::id(),
                    'proposal_id' => $report->proposal_id,
                    'report_type' => $reportType,
                ])
                ->toMediaCollection('signature_page');
        } catch (\Exception $e) {
            Log::error('Upload report signature file failed: '.$e->getMessage());
        }
    }

    /**
     * Save partner cooperation proof file to media collection (final report only)
     */
    protected function saveCooperationProofFile(ProgressReport $report): void
    {
        if (! $this->cooperationProofFile || ! ($this->cooperationProofFile instanceof TemporaryUploadedFile || $this->cooperationProofFile instanceof UploadedFile)) {
            return;
        }

        try {
            $report->clearMediaCollection('partner_cooperation_proof');
            $report
                ->addMedia($this->cooperationProofFile->getRealPath())
                ->usingName($this->cooperationProofFile->getClientOriginalName())
                ->usingFileName($this->cooperationProofFile->hashName())
                ->withCustomProperties([
                    'uploaded_by' => Auth::id(),
                    'proposal_id' => $report->proposal_id,
                    'report_type' => 'final',
                ])
                ->toMediaCollection('partner_cooperation_proof');
        } catch (\Exception $e) {
            Log::error('Upload partner cooperation proof file failed: '.$e->getMessage());
        }
    }

    /**
     * Save partner implementation proof file to media collection (final report only)
     */
    protected function saveImplementationProofFile(ProgressReport $report): void
    {
        if (! $this->implementationProofFile || ! ($this->implementationProofFile instanceof TemporaryUploadedFile || $this->implementationProofFile instanceof UploadedFile)) {
            return;
        }

        try {
            $report->clearMediaCollection('partner_implementation_proof');
            $report
                ->addMedia($this->implementationProofFile->getRealPath())
                ->usingName($this->implementationProofFile->getClientOriginalName())
                ->usingFileName($this->implementationProofFile->hashName())
                ->withCustomProperties([
                    'uploaded_by' => Auth::id(),
                    'proposal_id' => $report->proposal_id,
                    'report_type' => 'final',
                ])
                ->toMediaCollection('partner_implementation_proof');
        } catch (\Exception $e) {
            Log::error('Upload partner implementation proof file failed: '.$e->getMessage());
        }
    }

    // Vetted by AI - Manual Review Required by Senior Engineer/Manager
    protected function saveSingleAttachment(ProgressReport $report, mixed $file, string $collectionName): void
    {
        if (! $file || ! ($file instanceof TemporaryUploadedFile || $file instanceof UploadedFile)) {
            return;
        }

        try {
            $report->clearMediaCollection($collectionName);

            // Resolve path: if Livewire temp file has been cleaned up, store permanently first
            if ($file instanceof TemporaryUploadedFile) {
                $realPath = $file->getRealPath();
                if (! file_exists($realPath)) {
                    $storedPath = $file->store('attachments', 'public');
                    $realPath = storage_path('app/public/'.$storedPath);
                }
            } else {
                $realPath = $file->getRealPath();
            }

            $report
                ->addMedia($realPath)
                ->usingName($file->getClientOriginalName())
                ->usingFileName($file->hashName())
                ->withCustomProperties([
                    'uploaded_by' => Auth::id(),
                    'proposal_id' => $report->proposal_id,
                    'report_type' => 'final',
                ])
                ->toMediaCollection($collectionName);
        } catch (\Exception $e) {
            Log::error("Upload {$collectionName} failed: ".$e->getMessage());
        }
    }

    protected function saveResearchAttachments(ProgressReport $report): void
    {
        if ($this->teachingMaterialFile instanceof TemporaryUploadedFile || $this->teachingMaterialFile instanceof UploadedFile) {
            $this->saveSingleAttachment($report, $this->teachingMaterialFile, 'teaching_material_file');
        }

        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        $this->teachingMaterialFile = null;
        $report->unsetRelation('media');
        $report->load('media');
    }

    protected function savePkmAttachments(ProgressReport $report): void
    {
        if ($this->partnerAgreementFile instanceof TemporaryUploadedFile || $this->partnerAgreementFile instanceof UploadedFile) {
            $this->saveSingleAttachment($report, $this->partnerAgreementFile, 'partner_agreement_letter');
        }
        if ($this->chairpersonStatementFile instanceof TemporaryUploadedFile || $this->chairpersonStatementFile instanceof UploadedFile) {
            $this->saveSingleAttachment($report, $this->chairpersonStatementFile, 'chairperson_statement_letter');
        }
        if ($this->serviceLocationMapFile instanceof TemporaryUploadedFile || $this->serviceLocationMapFile instanceof UploadedFile) {
            $this->saveSingleAttachment($report, $this->serviceLocationMapFile, 'service_location_map');
        }
        if ($this->officialReportPkmFile instanceof TemporaryUploadedFile || $this->officialReportPkmFile instanceof UploadedFile) {
            $this->saveSingleAttachment($report, $this->officialReportPkmFile, 'official_report_pkm');
        }
        if ($this->assignmentLetterPkmFile instanceof TemporaryUploadedFile || $this->assignmentLetterPkmFile instanceof UploadedFile) {
            $this->saveSingleAttachment($report, $this->assignmentLetterPkmFile, 'assignment_letter_pkm');
        }
        if ($this->questionnairePkmFile instanceof TemporaryUploadedFile || $this->questionnairePkmFile instanceof UploadedFile) {
            $this->saveSingleAttachment($report, $this->questionnairePkmFile, 'questionnaire_pkm');
        }
        if ($this->teamAttendanceFile instanceof TemporaryUploadedFile || $this->teamAttendanceFile instanceof UploadedFile) {
            $this->saveSingleAttachment($report, $this->teamAttendanceFile, 'team_attendance_list');
        }
        if ($this->participantAttendanceFile instanceof TemporaryUploadedFile || $this->participantAttendanceFile instanceof UploadedFile) {
            $this->saveSingleAttachment($report, $this->participantAttendanceFile, 'participant_attendance_list');
        }
        if ($this->trainingMaterialFile instanceof TemporaryUploadedFile || $this->trainingMaterialFile instanceof UploadedFile) {
            $this->saveSingleAttachment($report, $this->trainingMaterialFile, 'training_material_pkm');
        }

        // Multiple photo uploads
        if (! empty($this->activityPhotosFiles)) {
            foreach ($this->activityPhotosFiles as $photo) {
                if ($photo instanceof TemporaryUploadedFile || $photo instanceof UploadedFile) {
                    try {
                        // Resolve path: if Livewire temp file has been cleaned up, store permanently first
                        if ($photo instanceof TemporaryUploadedFile) {
                            $realPath = $photo->getRealPath();
                            if (! file_exists($realPath)) {
                                $storedPath = $photo->store('attachments', 'public');
                                $realPath = storage_path('app/public/'.$storedPath);
                            }
                        } else {
                            $realPath = $photo->getRealPath();
                        }

                        $report
                            ->addMedia($realPath)
                            ->usingName($photo->getClientOriginalName())
                            ->usingFileName($photo->hashName())
                            ->withCustomProperties([
                                'uploaded_by' => Auth::id(),
                                'proposal_id' => $report->proposal_id,
                                'report_type' => 'final',
                            ])
                            ->toMediaCollection('activity_photos_pkm');
                    } catch (\Exception $e) {
                        Log::error('Upload activity_photos_pkm failed: '.$e->getMessage());
                    }
                }
            }
        }

        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        // Reset temporary attachment properties after persisting to Spatie
        $this->partnerAgreementFile = null;
        $this->chairpersonStatementFile = null;
        $this->serviceLocationMapFile = null;
        $this->officialReportPkmFile = null;
        $this->assignmentLetterPkmFile = null;
        $this->questionnairePkmFile = null;
        $this->teamAttendanceFile = null;
        $this->participantAttendanceFile = null;
        $this->trainingMaterialFile = null;
        $this->activityPhotosFiles = [];

        // Refresh media relation on report model instance
        $report->unsetRelation('media');
        $report->load('media');
    }

    /**
     * Save mandatory output file (journal article)
     */
    protected function saveMandatoryOutputFile(MandatoryOutput $output, int $proposalOutputId, string $reportType = 'progress'): void
    {
        if (! isset($this->tempMandatoryFiles[$proposalOutputId])) {
            return;
        }

        $file = $this->tempMandatoryFiles[$proposalOutputId];

        if (! $file instanceof TemporaryUploadedFile) {
            return;
        }

        try {
            $output->clearMediaCollection('journal_article');
            $output
                ->addMedia($file->getRealPath())
                ->usingName($file->getClientOriginalName())
                ->usingFileName($file->hashName())
                ->withCustomProperties([
                    'uploaded_by' => Auth::id(),
                    'proposal_id' => $output->progressReport->proposal_id,
                    'report_type' => $reportType,
                ])
                ->toMediaCollection('journal_article');
        } catch (\Exception $e) {
            Log::error('Upload report mandatory output file failed: '.$e->getMessage());
        }
    }

    /**
     * Save additional output file (book document)
     */
    protected function saveAdditionalOutputFile(AdditionalOutput $output, int $proposalOutputId, string $reportType = 'progress'): void
    {
        if (! isset($this->tempAdditionalFiles[$proposalOutputId])) {
            return;
        }

        $file = $this->tempAdditionalFiles[$proposalOutputId];

        if (! $file instanceof TemporaryUploadedFile) {
            return;
        }

        try {
            $output->clearMediaCollection('book_document');
            $output
                ->addMedia($file->getRealPath())
                ->usingName($file->getClientOriginalName())
                ->usingFileName($file->hashName())
                ->withCustomProperties([
                    'uploaded_by' => Auth::id(),
                    'proposal_id' => $output->progressReport->proposal_id,
                    'report_type' => $reportType,
                ])
                ->toMediaCollection('book_document');
        } catch (\Exception $e) {
            Log::error('Upload report additional output file failed: '.$e->getMessage());
        }
    }

    /**
     * Save additional output certificate
     */
    protected function saveAdditionalOutputCert(AdditionalOutput $output, int $proposalOutputId, string $reportType = 'progress'): void
    {
        if (! isset($this->tempAdditionalCerts[$proposalOutputId])) {
            return;
        }

        $file = $this->tempAdditionalCerts[$proposalOutputId];

        if (! $file instanceof TemporaryUploadedFile) {
            return;
        }

        try {
            $output->clearMediaCollection('publication_certificate');
            $output
                ->addMedia($file->getRealPath())
                ->usingName($file->getClientOriginalName())
                ->usingFileName($file->hashName())
                ->withCustomProperties([
                    'uploaded_by' => Auth::id(),
                    'proposal_id' => $output->progressReport->proposal_id,
                    'report_type' => $reportType,
                ])
                ->toMediaCollection('publication_certificate');
        } catch (\Exception $e) {
            Log::error('Upload report additional output certificate failed: '.$e->getMessage());
        }
    }

    /**
     * Clear substance file
     */
    public function clearSubstanceFile(): void
    {
        $this->reset('substanceFile');
    }

    /**
     * Clear realization file
     */
    public function clearRealizationFile(): void
    {
        $this->reset('realizationFile');
    }

    /**
     * Clear presentation file
     */
    public function clearPresentationFile(): void
    {
        $this->reset('presentationFile');
    }

    /**
     * Clear mandatory file
     */
    public function clearMandatoryFile(int $proposalOutputId): void
    {
        unset($this->tempMandatoryFiles[$proposalOutputId]);
    }

    /**
     * Clear additional file
     */
    public function clearAdditionalFile(int $proposalOutputId): void
    {
        unset($this->tempAdditionalFiles[$proposalOutputId]);
    }

    /**
     * Clear additional certificate
     */
    public function clearAdditionalCert(int $proposalOutputId): void
    {
        unset($this->tempAdditionalCerts[$proposalOutputId]);
    }

    /**
     * Reset all file uploads
     */
    public function resetFileUploads(): void
    {
        $this->reset([
            'substanceFile',
            'realizationFile',
            'presentationFile',
            'signatureFile',
            'cooperationProofFile',
            'implementationProofFile',
            'teachingMaterialFile',
            'partnerAgreementFile',
            'chairpersonStatementFile',
            'serviceLocationMapFile',
            'officialReportPkmFile',
            'assignmentLetterPkmFile',
            'questionnairePkmFile',
            'teamAttendanceFile',
            'participantAttendanceFile',
            'trainingMaterialFile',
            'activityPhotosFiles',
            'tempMandatoryFiles',
            'tempAdditionalFiles',
            'tempAdditionalCerts',
        ]);
    }

    /**
     * Save only the substance file immediately (without submitting the whole report).
     * Creates a final draft report if none exists yet.
     */
    public function saveSubstanceFileNow(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }

        if (! $this->substanceFile instanceof TemporaryUploadedFile) {
            $message = 'Pilih file substansi (PDF) terlebih dahulu.';
            session()->flash('error', $message);
            $this->toastError($message);

            return;
        }

        $this->validateSubstanceFile();

        try {
            DB::transaction(function () {
                $report = $this->progressReport;

                // Create a final draft report if it does not exist yet
                if (! $report || $report->reporting_period !== 'final') {
                    $report = ProgressReport::create([
                        'proposal_id' => $this->proposal->id,
                        'summary_update' => $this->form->summaryUpdate ?: ($this->proposal->summary ?? 'Draft Report'),
                        'reporting_year' => $this->form->reportingYear ?: (int) date('Y'),
                        'reporting_period' => 'final',
                        'status' => ReportStatus::DRAFT->value,
                    ]);
                    $this->isFinalReportDraft = true;
                }

                $this->saveSubstanceFile($report, 'final');
                $this->progressReport = $report;
            });

            $this->substanceFile = null;

            $this->dispatch('report-saved');

            $message = 'File substansi berhasil disimpan.';
            session()->flash('success', $message);
            $this->toastSuccess($message);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            $message = 'Gagal menyimpan file substansi: '.$e->getMessage();
            session()->flash('error', $message);
            $this->toastError($message);
        }
    }
}
