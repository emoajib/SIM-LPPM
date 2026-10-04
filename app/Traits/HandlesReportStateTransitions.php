<?php

namespace App\Traits;

use App\Enums\ReportStatus;
use App\Models\ProgressReport;
use Illuminate\Support\Facades\DB;

trait HandlesReportStateTransitions
{
    protected function transitionReport(
        ProgressReport $report,
        ReportStatus $expectedCurrent,
        ReportStatus $newStatus,
        ?callable $onSuccess = null
    ): bool {
        $affected = DB::table('progress_reports')
            ->where('id', $report->id)
            ->where('status', $expectedCurrent->value)
            ->where('version', $report->version)
            ->update([
                'status' => $newStatus->value,
                'version' => $report->version + 1,
                'updated_at' => now(),
            ]);

        if (! $affected) {
            $this->toastError('Laporan sudah berubah oleh pengguna lain. Silakan refresh.');

            return false;
        }

        $report->refresh();
        if ($onSuccess) {
            $onSuccess($report);
        }

        return true;
    }
}
