<?php

namespace App\Livewire\Traits;

use App\Enums\ReportStatus;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait WithReportApproval
{
    public string $approvalNotes = '';

    protected function notificationService(): NotificationService
    {
        return app(NotificationService::class);
    }

    public function approve(): void
    {
        $report = $this->progressReport;
        if (! $report) {
            return;
        }

        $activeRole = active_role();
        $newStatus = null;

        if ($activeRole === 'dekan') {
            if ($report->status !== ReportStatus::SUBMITTED) {
                $this->toastError('Laporan harus berstatus Diajukan sebelum disetujui Dekan.');

                return;
            }

            // Faculty check
            $dekanFacultyId = Auth::user()?->identity?->faculty_id;
            $submitterFacultyId = $report->proposal->submitter->identity?->faculty_id;
            if (! $dekanFacultyId || $dekanFacultyId !== $submitterFacultyId) {
                $this->toastError('Maaf, Anda bukan dekan dosen tersebut.');

                return;
            }

            // Self-approval check
            if ($report->proposal->submitter_id === Auth::id()) {
                $this->toastError('Anda tidak dapat menyetujui laporan Anda sendiri.');

                return;
            }

            $newStatus = ReportStatus::APPROVED_BY_DEKAN;
        } elseif ($activeRole === 'kepala lppm') {
            if ($report->status !== ReportStatus::APPROVED_BY_DEKAN) {
                $this->toastError('Laporan harus disetujui Dekan terlebih dahulu sebelum disetujui Kepala LPPM.');

                return;
            }
            $newStatus = ReportStatus::APPROVED;
        }

        if (! $newStatus) {
            $this->toastError('Anda tidak memiliki wewenang untuk menyetujui laporan ini.');

            return;
        }

        try {
            DB::transaction(function () use ($report, $newStatus) {
                $report->update([
                    'status' => $newStatus->value,
                    // Bersihkan catatan penolakan jika sebelumnya ditolak lalu diajukan ulang & disetujui
                    'rejection_notes' => null,
                    'rejected_by' => null,
                    'rejected_at' => null,
                ]);

                // Special logic for barcode: Barcode should only appear after APPROVED (Kepala LPPM)
                // This is handled in the PDF service.
            });

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

        $activeRole = active_role();
        if ($activeRole === 'dekan') {
            $dekanFacultyId = Auth::user()?->identity?->faculty_id;
            $submitterFacultyId = $report->proposal->submitter->identity?->faculty_id;
            if (! $dekanFacultyId || $dekanFacultyId !== $submitterFacultyId) {
                $this->toastError('Maaf, Anda bukan dekan dosen tersebut.');

                return;
            }
        }

        try {
            $rejector = Auth::user();
            $notes = $this->approvalNotes;

            DB::transaction(function () use ($report, $notes, $rejector) {
                $report->update([
                    'status' => ReportStatus::REJECTED->value,
                    'rejection_notes' => $notes,
                    'rejected_by' => $rejector->id,
                    'rejected_at' => now(),
                ]);
            });

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
            $this->toastSuccess('Laporan telah ditolak. Dosen akan menerima notifikasi.');
            $this->dispatch('report-rejected');

            if ($activeRole === 'dekan') {
                $this->redirect(route('dekan.reports.index'), navigate: true);
            } elseif ($activeRole === 'kepala lppm') {
                $this->redirect(route('kepala-lppm.report-approval'), navigate: true);
            } else {
                $this->redirect(route('dashboard'), navigate: true);
            }
        } catch (\Exception $e) {
            $this->toastError('Gagal menolak laporan: '.$e->getMessage());
        }
    }
}
