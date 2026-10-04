<?php

declare(strict_types=1);

namespace App\Livewire\Research\FinalReport;

use App\Livewire\FinalReport\BaseFinalReportShow;
use App\Models\ProgressReport;
use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager
class Show extends BaseFinalReportShow
{
    // Research-specific file upload properties
    public $teachingMaterialFile;

    /**
     * Get report type
     */
    protected function getReportType(): string
    {
        return 'research';
    }

    /**
     * Get schedule templates for research
     *
     * @return array<int, array{activity_name: string, start_month: int, end_month: int}>
     */
    protected function getScheduleTemplates(): array
    {
        return [
            ['activity_name' => 'Studi Literatur & Persiapan', 'start_month' => 1, 'end_month' => 3],
            ['activity_name' => 'Pengumpulan Data / Observasi', 'start_month' => 4, 'end_month' => 7],
            ['activity_name' => 'Analisis & Pengolahan Data', 'start_month' => 8, 'end_month' => 10],
            ['activity_name' => 'Penyusunan Laporan & Publikasi', 'start_month' => 11, 'end_month' => 12],
        ];
    }

    /**
     * Get research-specific completeness rules
     *
     * @return array<string>
     */
    protected function getCompletenessRules(): array
    {
        $missing = [];

        // Teaching Material (Lampiran 5: RPS/Bahan Ajar)
        $hasTeachingMaterial = $this->teachingMaterialFile instanceof TemporaryUploadedFile || $this->teachingMaterialFile instanceof UploadedFile;
        if (! $hasTeachingMaterial && (! $this->progressReport || ! $this->progressReport->hasMedia('teaching_material_file'))) {
            $missing[] = 'Lampiran 5: RPS / Bahan Ajar';
        }

        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        // Note: Laporan Keuangan (LPJ) & Logbook dikelola terpisah di menu Catatan Harian.
        // Dosen tetap dapat mengajukan laporan akhir substansi tanpa terhambat LPJ.

        return $missing;
    }

    /**
     * Save research-specific attachments
     */
    protected function saveTypeAttachments(ProgressReport $report): void
    {
        $this->saveResearchAttachments($report);
    }

    /**
     * Get index route for redirect after submit
     */
    protected function getIndexRoute(): string
    {
        return route('research.final-report.index');
    }

    /**
     * Get view path for render()
     */
    protected function getViewPath(): string
    {
        return 'livewire.research.final-report.show';
    }

    // ============================================================
    // Research-specific file upload handlers
    // ============================================================

    public function updatedTeachingMaterialFile(): void
    {
        if (! $this->canEdit) {
            $this->teachingMaterialFile = null;

            return;
        }
        $this->validateTeachingMaterialFile();
    }

    // ============================================================
    // Research-specific file removal handlers
    // ============================================================

    public function removeTeachingMaterialFile(): void
    {
        if (! $this->canEdit) {
            abort(403);
        }
        if ($this->progressReport) {
            $this->progressReport->clearMediaCollection('teaching_material_file');
            $this->toastSuccess('Berkas bahan ajar berhasil dihapus.');
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
}
