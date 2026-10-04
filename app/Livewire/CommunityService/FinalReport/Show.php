<?php

declare(strict_types=1);

namespace App\Livewire\CommunityService\FinalReport;

use App\Livewire\FinalReport\BaseFinalReportShow;
use App\Models\ProgressReport;
use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager
class Show extends BaseFinalReportShow
{
    // PKM-specific file upload properties
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
     * Get report type
     */
    protected function getReportType(): string
    {
        return 'community_service';
    }

    /**
     * Get schedule templates for PKM
     *
     * @return array<int, array{activity_name: string, start_month: int, end_month: int}>
     */
    protected function getScheduleTemplates(): array
    {
        return [
            ['activity_name' => 'Sosialisasi & Koordinasi Mitra', 'start_month' => 1, 'end_month' => 3],
            ['activity_name' => 'Pelaksanaan Kegiatan / Pelatihan', 'start_month' => 4, 'end_month' => 7],
            ['activity_name' => 'Pendampingan & Evaluasi Mitra', 'start_month' => 8, 'end_month' => 10],
            ['activity_name' => 'Penyusunan Laporan & Publikasi PKM', 'start_month' => 11, 'end_month' => 12],
        ];
    }

    /**
     * Get PKM-specific completeness rules
     *
     * @return array<string>
     */
    protected function getCompletenessRules(): array
    {
        $missing = [];

        // PKM Attachments (Lampiran 3 s.d. 12)
        if (! ($this->partnerAgreementFile instanceof TemporaryUploadedFile || $this->partnerAgreementFile instanceof UploadedFile)
            && (! $this->progressReport || ! $this->progressReport->hasMedia('partner_agreement_letter'))) {
            $missing[] = 'Lampiran 3: Surat Kesediaan Mitra';
        }

        if (! ($this->chairpersonStatementFile instanceof TemporaryUploadedFile || $this->chairpersonStatementFile instanceof UploadedFile)
            && (! $this->progressReport || ! $this->progressReport->hasMedia('chairperson_statement_letter'))) {
            $missing[] = 'Lampiran 4: Surat Pernyataan Ketua';
        }

        if (! ($this->serviceLocationMapFile instanceof TemporaryUploadedFile || $this->serviceLocationMapFile instanceof UploadedFile)
            && (! $this->progressReport || ! $this->progressReport->hasMedia('service_location_map'))) {
            $missing[] = 'Lampiran 5: Peta Lokasi Pengabdian';
        }

        if (! ($this->officialReportPkmFile instanceof TemporaryUploadedFile || $this->officialReportPkmFile instanceof UploadedFile)
            && (! $this->progressReport || ! $this->progressReport->hasMedia('official_report_pkm'))) {
            $missing[] = 'Lampiran 6: Berita Acara Pelaksanaan PKM';
        }

        if (! ($this->assignmentLetterPkmFile instanceof TemporaryUploadedFile || $this->assignmentLetterPkmFile instanceof UploadedFile)
            && (! $this->progressReport || ! $this->progressReport->hasMedia('assignment_letter_pkm'))) {
            $missing[] = 'Lampiran 7: Surat Tugas Pelaksanaan PKM';
        }

        if (! ($this->questionnairePkmFile instanceof TemporaryUploadedFile || $this->questionnairePkmFile instanceof UploadedFile)
            && (! $this->progressReport || ! $this->progressReport->hasMedia('questionnaire_pkm'))) {
            $missing[] = 'Lampiran 8: Kuisioner Pengabdian';
        }

        if (! ($this->teamAttendanceFile instanceof TemporaryUploadedFile || $this->teamAttendanceFile instanceof UploadedFile)
            && (! $this->progressReport || ! $this->progressReport->hasMedia('team_attendance_list'))) {
            $missing[] = 'Lampiran 9: Daftar Hadir Tim PKM';
        }

        if (! ($this->participantAttendanceFile instanceof TemporaryUploadedFile || $this->participantAttendanceFile instanceof UploadedFile)
            && (! $this->progressReport || ! $this->progressReport->hasMedia('participant_attendance_list'))) {
            $missing[] = 'Lampiran 10: Daftar Hadir Peserta PKM';
        }

        if (! ($this->trainingMaterialFile instanceof TemporaryUploadedFile || $this->trainingMaterialFile instanceof UploadedFile)
            && (! $this->progressReport || ! $this->progressReport->hasMedia('training_material_pkm'))) {
            $missing[] = 'Lampiran 11: Materi Kegiatan PKM';
        }

        // Activity photos (can be multiple files)
        $hasActivityPhotos = ! empty($this->activityPhotosFiles)
            || ($this->progressReport && $this->progressReport->hasMedia('activity_photos_pkm'));
        if (! $hasActivityPhotos) {
            $missing[] = 'Lampiran 12: Foto Kegiatan PKM';
        }

        return $missing;
    }

    /**
     * Save PKM-specific attachments
     */
    protected function saveTypeAttachments(ProgressReport $report): void
    {
        $this->savePkmAttachments($report);
    }

    /**
     * Get index route for redirect after submit
     */
    protected function getIndexRoute(): string
    {
        return route('community-service.final-report.index');
    }

    /**
     * Get view path for render()
     */
    protected function getViewPath(): string
    {
        return 'livewire.community-service.final-report.show';
    }

    // ============================================================
    // PKM-specific file upload handlers
    // ============================================================

    public function updatedPresentationFile(): void
    {
        if (! $this->canEdit) {
            $this->presentationFile = null;

            return;
        }
        $this->validatePresentationFile();
    }

    public function updatedPartnerAgreementFile(): void
    {
        if (! $this->canEdit) {
            $this->partnerAgreementFile = null;

            return;
        }
        $this->validatePartnerAgreementFile();
    }

    public function updatedChairpersonStatementFile(): void
    {
        if (! $this->canEdit) {
            $this->chairpersonStatementFile = null;

            return;
        }
        $this->validateChairpersonStatementFile();
    }

    public function updatedServiceLocationMapFile(): void
    {
        if (! $this->canEdit) {
            $this->serviceLocationMapFile = null;

            return;
        }
        $this->validateServiceLocationMapFile();
    }

    public function updatedOfficialReportPkmFile(): void
    {
        if (! $this->canEdit) {
            $this->officialReportPkmFile = null;

            return;
        }
        $this->validateOfficialReportPkmFile();
    }

    public function updatedAssignmentLetterPkmFile(): void
    {
        if (! $this->canEdit) {
            $this->assignmentLetterPkmFile = null;

            return;
        }
        $this->validateAssignmentLetterPkmFile();
    }

    public function updatedQuestionnairePkmFile(): void
    {
        if (! $this->canEdit) {
            $this->questionnairePkmFile = null;

            return;
        }
        $this->validateQuestionnairePkmFile();
    }

    public function updatedTeamAttendanceFile(): void
    {
        if (! $this->canEdit) {
            $this->teamAttendanceFile = null;

            return;
        }
        $this->validateTeamAttendanceFile();
    }

    public function updatedParticipantAttendanceFile(): void
    {
        if (! $this->canEdit) {
            $this->participantAttendanceFile = null;

            return;
        }
        $this->validateParticipantAttendanceFile();
    }

    public function updatedTrainingMaterialFile(): void
    {
        if (! $this->canEdit) {
            $this->trainingMaterialFile = null;

            return;
        }
        $this->validateTrainingMaterialFile();
    }

    public function updatedActivityPhotosFiles(): void
    {
        if (! $this->canEdit) {
            $this->activityPhotosFiles = [];

            return;
        }
        $this->validateActivityPhotosFiles();
    }

    // ============================================================
    // PKM-specific file removal handlers
    // ============================================================

    public function removePresentationFile(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }
        if ($this->progressReport) {
            $this->progressReport->clearMediaCollection('presentation_file');
            $this->toastSuccess('File presentasi berhasil dihapus.');
        }
    }

    public function removePartnerAgreementFile(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }
        if ($this->progressReport) {
            $this->progressReport->clearMediaCollection('partner_agreement_letter');
            $this->progressReport->unsetRelation('media');
            $this->progressReport->load('media');
            $this->toastSuccess('Lampiran surat kesediaan mitra berhasil dihapus.');
        }
    }

    public function removeChairpersonStatementFile(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }
        if ($this->progressReport) {
            $this->progressReport->clearMediaCollection('chairperson_statement_letter');
            $this->progressReport->unsetRelation('media');
            $this->progressReport->load('media');
            $this->toastSuccess('Lampiran surat pernyataan ketua berhasil dihapus.');
        }
    }

    public function removeServiceLocationMapFile(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }
        if ($this->progressReport) {
            $this->progressReport->clearMediaCollection('service_location_map');
            $this->progressReport->unsetRelation('media');
            $this->progressReport->load('media');
            $this->toastSuccess('Lampiran peta lokasi pengabdian berhasil dihapus.');
        }
    }

    public function removeOfficialReportPkmFile(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }
        if ($this->progressReport) {
            $this->progressReport->clearMediaCollection('official_report_pkm');
            $this->progressReport->unsetRelation('media');
            $this->progressReport->load('media');
            $this->toastSuccess('Lampiran berita acara pelaksanaan PKM berhasil dihapus.');
        }
    }

    public function removeAssignmentLetterPkmFile(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }
        if ($this->progressReport) {
            $this->progressReport->clearMediaCollection('assignment_letter_pkm');
            $this->progressReport->unsetRelation('media');
            $this->progressReport->load('media');
            $this->toastSuccess('Lampiran surat tugas pelaksanaan PKM berhasil dihapus.');
        }
    }

    public function removeQuestionnairePkmFile(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }
        if ($this->progressReport) {
            $this->progressReport->clearMediaCollection('questionnaire_pkm');
            $this->progressReport->unsetRelation('media');
            $this->progressReport->load('media');
            $this->toastSuccess('Lampiran kuisioner pengabdian berhasil dihapus.');
        }
    }

    public function removeTeamAttendanceFile(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }
        if ($this->progressReport) {
            $this->progressReport->clearMediaCollection('team_attendance_list');
            $this->progressReport->unsetRelation('media');
            $this->progressReport->load('media');
            $this->toastSuccess('Lampiran daftar hadir tim PKM berhasil dihapus.');
        }
    }

    public function removeParticipantAttendanceFile(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }
        if ($this->progressReport) {
            $this->progressReport->clearMediaCollection('participant_attendance_list');
            $this->progressReport->unsetRelation('media');
            $this->progressReport->load('media');
            $this->toastSuccess('Lampiran daftar hadir peserta PKM berhasil dihapus.');
        }
    }

    public function removeTrainingMaterialFile(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }
        if ($this->progressReport) {
            $this->progressReport->clearMediaCollection('training_material_pkm');
            $this->progressReport->unsetRelation('media');
            $this->progressReport->load('media');
            $this->toastSuccess('Lampiran materi kegiatan PKM berhasil dihapus.');
        }
    }

    public function removeActivityPhotosFiles(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }
        if ($this->progressReport) {
            $this->progressReport->clearMediaCollection('activity_photos_pkm');
            $this->progressReport->unsetRelation('media');
            $this->progressReport->load('media');
            $this->toastSuccess('Lampiran foto kegiatan PKM berhasil dihapus.');
        }
    }

    // ============================================================
    // Output file upload handlers (real-time)
    // ============================================================

    public function updatedTempMandatoryFiles($value, $key): void
    {
        if (! $this->canEdit) {
            return;
        }

        try {
            $this->validateMandatoryFile((int) $key);

            $this->form->tempMandatoryFiles[(int) $key] = $value;
            $this->form->saveMandatoryOutputWithFile((int) $key, validate: false);

            $this->progressReport = $this->form->progressReport;
            unset($this->tempMandatoryFiles[$key]);

            $message = 'Data luaran wajib berhasil disimpan.';
            session()->flash('success', $message);
            $this->toastSuccess($message);
        } catch (\Exception $e) {
            $message = 'Gagal mengunggah file: '.$e->getMessage();
            session()->flash('error', $message);
            $this->toastError($message);
        }
    }

    public function updatedTempAdditionalFiles($value, $key): void
    {
        if (! $this->canEdit) {
            return;
        }

        try {
            $this->validateAdditionalFile((int) $key);

            $this->form->tempAdditionalFiles[(int) $key] = $value;
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

    public function updatedTempAdditionalCerts($value, $key): void
    {
        if (! $this->canEdit) {
            return;
        }

        try {
            $this->validateAdditionalCert((int) $key);

            $this->form->tempAdditionalCerts[(int) $key] = $value;
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
     * Validate additional output
     */
    public function validateAdditionalOutput(int $proposalOutputId): void
    {
        $this->form->validateAdditionalOutput($proposalOutputId);
    }
}
