<?php

namespace App\Observers;

use App\Models\ProposalStatusLog;
use Illuminate\Support\Facades\Cache;

class ProposalStatusLogObserver
{
    /**
     * Handle the ProposalStatusLog "created" event.
     */
    public function created(ProposalStatusLog $proposalStatusLog): void
    {
        $this->bustDashboardCache();
    }

    /**
     * Handle the ProposalStatusLog "updated" event.
     */
    public function updated(ProposalStatusLog $proposalStatusLog): void
    {
        $this->bustDashboardCache();
    }

    /**
     * Handle the ProposalStatusLog "deleted" event.
     */
    public function deleted(ProposalStatusLog $proposalStatusLog): void
    {
        $this->bustDashboardCache();
    }

    /**
     * Bust the dashboard cache by incrementing the version.
     */
    private function bustDashboardCache(): void
    {
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        Cache::forever('dashboard.cache_version', time());
    }
}
