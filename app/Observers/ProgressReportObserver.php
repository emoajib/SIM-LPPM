<?php

namespace App\Observers;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

use App\Models\ProgressReport;
use Illuminate\Support\Facades\Cache;

class ProgressReportObserver
{
    /**
     * Handle the ProgressReport "saved" event.
     */
    public function saved(ProgressReport $report): void
    {
        // Bump cache version to invalidate all cached dashboard metrics
        Cache::forever('dashboard.cache_version', time());
    }

    /**
     * Handle the ProgressReport "deleted" event.
     */
    public function deleted(ProgressReport $report): void
    {
        // Bump cache version to invalidate all cached dashboard metrics
        Cache::forever('dashboard.cache_version', time());
    }
}
