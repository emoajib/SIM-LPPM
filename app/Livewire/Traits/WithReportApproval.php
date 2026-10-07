<?php

namespace App\Livewire\Traits;

use App\Enums\ReportStatus;
use App\Models\MandatoryOutput;
use App\Services\LecturerEligibilityService;
use App\Services\NotificationService;
use App\Traits\HandlesReportStateTransitions;
use Illuminate\Support\Facades\Auth;

trait WithReportApproval
{
    use HandlesReportStateTransitions;

    public string $approvalNotes = '';

    protected function notificationService(): NotificationService
    {
        return app(NotificationService::class);
    }

    /**
     * Jenis luaran wajib proposal yang belum memiliki data luaran
     * pada laporan ini. Kosong = semua luaran wajib sudah terisi.
     *
     * @return array<int, string>
     */
    protected function missingReportOutputs($report): array
    {
        $wajibOutputs = $report->proposal->outputs->where('category', 'Wajib');

        if ($wajibOutputs->isEmpty()) {
            return [];
        }

        // Baris tanpa bukti isi (mis. hanya status tanpa URL/judul, termasuk
        // luaran video yang baru diisi statusnya) TIDAK dihitung terpenuhi.
        $filledIds = MandatoryOutput::where('progress_report_id', $report->id)
            ->whereNotNull('status_type')
            ->get()
            ->filter(fn ($record) => LecturerEligibilityService::mandatoryOutputHasEvidence($record))
            ->map(fn ($record) => $record->proposal_output_id)
            ->all();

        $missing = [];
        foreach ($wajibOutputs as $output) {
            if (! in_array($output->id, $filledIds)) {
                $missing[] = $output->type ?? "Luaran #{$output->id}";
            }
        }

        return $missing;
    }

    public function approve(): void
    {
        $report = $this->progressReport;
        if (! $report) {
            return;
        }

        // Verifikasi wewenang berbasis LaporanPolicy (activeHasRole-based)
        // Pejabat (Dekan, Kaprodi, Kepala LPPM, Rektor) boleh approve laporan sendiri
        // karena approval adalah wewenang jabatan, bukan personal.
        if (! auth()->user()->can('approve', $report)) {
            $this->toastError('Anda tidak memiliki wewenang untuk menyetujui laporan ini.');

            return;
        }

        $activeRole = active_role();
        $expectedStatus = null;
        $newStatus = null;

        if ($activeRole === 'dekan') {
            $expectedStatus = ReportStatus::SUBMITTED;
            if ($report->status !== $expectedStatus) {
                $this->toastError('Laporan harus berstatus Diajukan sebelum disetujui Dekan.');

                return;
            }

            // Faculty check: Dekan hanya bisa approve laporan dari fakultasnya
            $dekanFacultyId = Auth::user()?->identity?->faculty_id;
            $submitterFacultyId = $report->proposal->submitter->identity?->faculty_id;
            if (! $dekanFacultyId || $dekanFacultyId !== $submitterFacultyId) {
                $this->toastError('Maaf, Anda bukan dekan dosen tersebut.');

                return;
            }

            // Catatan: self-approval diizinkan — Dekan boleh approve laporan dirinya sendiri
            // karena approval dilakukan atas nama jabatan Dekan, bukan kapasitas personal.

            $newStatus = ReportStatus::APPROVED_BY_DEKAN;
        } elseif ($activeRole === 'kepala lppm') {
            $expectedStatus = ReportStatus::APPROVED_BY_DEKAN;
            if ($report->status !== $expectedStatus) {
                $this->toastError('Laporan harus disetujui Dekan terlebih dahulu sebelum disetujui Kepala LPPM.');

                return;
            }

            // Jangan sahkan laporan yang luaran wajibnya belum dilengkapi.
            // Tolak (kembalikan) agar dosen melengkapi terlebih dahulu.
            $missingOutputs = $this->missingReportOutputs($report);
            if (! empty($missingOutputs)) {
                $this->toastError('Laporan belum bisa disahkan. Luaran wajib belum dilengkapi: '.implode(', ', $missingOutputs).'. Kembalikan ke dosen bila perlu.');

                return;
            }

            $newStatus = ReportStatus::APPROVED;
        }

        if (! $newStatus || ! $expectedStatus) {
            $this->toastError('Anda tidak memiliki wewenang untuk menyetujui laporan ini.');

            return;
        }

        try {
            $approved = $this->transitionReport(
                $report,
                $expectedStatus,
                $newStatus,
                function ($updatedReport) {
                    // Bersihkan catatan penolakan jika sebelumnya ditolak lalu diajukan ulang & disetujui
                    $updatedReport->update([
                        'rejection_notes' => null,
                        'rejected_by' => null,
                        'rejected_at' => null,
                    ]);

                    // Special logic for barcode: Barcode should only appear after APPROVED (Kepala LPPM)
                    // This is handled in the PDF service.
                }
            );

            if (! $approved) {
                return;
            }

            $this->toastSuccess('Laporan berhasil disetujui.');
            $this->dispatch('report-approved');

            // Redirect based on role
            if ($activeRole === 'dekan') {
                $this->redirect(route('dekan.reports.index'), navigate: true);
            } elseif ($activeRole === 'kepala lppm') {
                $this->redirect(route('kepala-lppm.report-approval'), navigate: true);
            } else {
                $this->redirect(route('dashboard'), navigate: true);
            }
        } catch (\Exception $e) {
            $this->toastError('Gagal menyetujui laporan: '.$e->getMessage());
        }
    }

    public function reject(): void
    {
        $this->validate([
            'approvalNotes' => 'required|string|min:10|max:2000',
        ], [
            'approvalNotes.required' => 'Catatan penolakan wajib diisi.',
            'approvalNotes.min' => 'Catatan minimal 10 karakter agar dosen memahami yang perlu diperbaiki.',
            'approvalNotes.max' => 'Catatan maksimal 2000 karakter.',
        ]);

        $report = $this->progressReport;
        if (! $report) {
            return;
        }

        // Verifikasi wewenang berbasis LaporanPolicy
        if (! auth()->user()->can('approve', $report)) {
            $this->toastError('Anda tidak memiliki wewenang untuk menolak laporan ini.');

            return;
        }

        $activeRole = active_role();
        $expectedStatuses = [];

        if ($activeRole === 'dekan') {
            // Dekan boleh menolak dari Diajukan, atau mengoreksi persetujuannya sendiri.
            $expectedStatuses = [ReportStatus::SUBMITTED, ReportStatus::APPROVED_BY_DEKAN];
            $dekanFacultyId = Auth::user()?->identity?->faculty_id;
            $submitterFacultyId = $report->proposal->submitter->identity?->faculty_id;
            if (! $dekanFacultyId || $dekanFacultyId !== $submitterFacultyId) {
                $this->toastError('Maaf, Anda bukan dekan dosen tersebut.');

                return;
            }
        } elseif ($activeRole === 'kepala lppm') {
            // Kepala LPPM boleh menolak dari Disetujui Dekan, atau mengembalikan
            // laporan yang sudah Disetujui LPPM agar dosen melengkapi kekurangan.
            $expectedStatuses = [ReportStatus::APPROVED_BY_DEKAN, ReportStatus::APPROVED];
        } else {
            $this->toastError('Anda tidak memiliki wewenang untuk menolak laporan ini.');

            return;
        }

        if (! in_array($report->status, $expectedStatuses, true)) {
            $this->toastError('Status laporan tidak sesuai untuk ditolak.');

            return;
        }

        try {
            $rejector = Auth::user();
            $notes = $this->approvalNotes;
            $wasApproved = in_array($report->status, [ReportStatus::APPROVED_BY_DEKAN, ReportStatus::APPROVED], true);

            $rejected = $this->transitionReport(
                $report,
                $report->status,
                ReportStatus::REJECTED,
                function ($updatedReport) use ($rejector, $notes) {
                    $updatedReport->update([
                        'rejection_notes' => $notes,
                        'rejected_by' => $rejector->id,
                        'rejected_at' => now(),
                    ]);
                }
            );

            if (! $rejected) {
                return;
            }

            // Kirim notifikasi ke dosen (ketua + anggota tim)
            try {
                $roleTitle = ($activeRole === 'dekan') ? 'Dekan' : 'Kepala LPPM';
                $this->notificationService()->notifyReportRejected($report, $rejector, $notes, $roleTitle);
            } catch (\Throwable $e) {
                // Log error notifikasi tapi jangan batalkan penolakan
                \Log::warning('Gagal kirim notifikasi penolakan laporan: '.$e->getMessage(), [
                    'report_id' => $report->id,
                ]);
            }

            $this->approvalNotes = '';
            $this->toastSuccess($wasApproved ? 'Laporan dikembalikan ke dosen untuk dilengkapi. Dosen akan menerima notifikasi.' : 'Laporan telah ditolak. Dosen akan menerima notifikasi.');
            $this->dispatch('report-rejected');

            // Hanya 'dekan' atau 'kepala lppm' yang sampai di sini
            // (peran lain sudah return lebih awal).
            if ($activeRole === 'dekan') {
                $this->redirect(route('dekan.reports.index'), navigate: true);
            } else {
                $this->redirect(route('kepala-lppm.report-approval'), navigate: true);
            }
        } catch (\Exception $e) {
            $this->toastError('Gagal menolak laporan: '.$e->getMessage());
        }
    }
}
