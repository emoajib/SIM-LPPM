<?php

namespace App\Livewire\Dashboard;

use App\Models\Proposal;
use App\Support\AcademicPeriod;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DefaultDashboard extends Component
{
    public $user;

    public $roleName;

    public $stats = [];

    public $recentResearch = [];

    public $recentCommunityService = [];

    public $selectedYear;

    public $availableYears = [];

    public function mount()
    {
        $this->user = Auth::user();
        $this->roleName = active_role();
        $this->availableYears = $this->getAvailableYears();
        $this->selectedYear = $this->availableYears[0] ?? AcademicPeriod::current();

        $this->loadAnalytics();
    }

    public function updatedSelectedYear()
    {
        $this->loadAnalytics();
    }

    private function getAvailableYears(): array
    {
        $periods = Proposal::query()
            ->whereNotNull('start_year')
            ->distinct()
            ->get(['start_year', 'semester']);

        $options = AcademicPeriod::optionsFromPeriods($periods);

        return $options !== [] ? $options : [AcademicPeriod::current()];
    }

    public function loadAnalytics()
    {
        $yearFilter = $this->selectedYear;

        // Statistik Penelitian
        $totalResearch = Proposal::query()
            ->forAcademicYear((string) $yearFilter)
            ->where('detailable_type', 'App\Models\Research')->count();

        // Statistik PKM
        $totalCommunityService = Proposal::query()
            ->forAcademicYear((string) $yearFilter)
            ->where('detailable_type', 'App\Models\CommunityService')->count();

        $this->stats = [
            'total_research' => $totalResearch,
            'total_community_service' => $totalCommunityService,
        ];

        // Data penelitian terbaru
        $this->recentResearch = Proposal::with(['submitter'])
            ->forAcademicYear((string) $yearFilter)
            ->where('detailable_type', 'App\Models\Research')
            ->latest('updated_at')
            ->limit(5)
            ->get();

        // Data PKM terbaru
        $this->recentCommunityService = Proposal::with(['submitter'])
            ->forAcademicYear((string) $yearFilter)
            ->where('detailable_type', 'App\Models\CommunityService')
            ->latest('updated_at')
            ->limit(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.dashboard.default-dashboard');
    }
}
