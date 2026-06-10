<?php

namespace App\Observers;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

use App\Models\Proposal;
use Illuminate\Support\Facades\Cache;

class ProposalObserver
{
    /**
     * Handle the Proposal "saved" event.
     */
    public function saved(Proposal $proposal): void
    {
        // Bump cache version to invalidate all cached dashboard metrics
        Cache::forever('dashboard.cache_version', time());
    }

    /**
     * Handle the Proposal "deleted" event.
     */
    public function deleted(Proposal $proposal): void
    {
        // Bump cache version to invalidate all cached dashboard metrics
        Cache::forever('dashboard.cache_version', time());
    }
}
