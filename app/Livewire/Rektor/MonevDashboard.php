<?php

namespace App\Livewire\Rektor;

use App\Models\MonevReview;
use App\Models\Proposal;
use App\Support\AcademicPeriod;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class MonevDashboard extends Component
{
    #[Url]
    public $academicYear = '';

    #[Url]
    public $semester = 'all';

    public function mount()
    {
        if (! Auth::user()->hasAnyRole(['rektor', 'kepala lppm'])) {
            abort(403);
        }
        $this->academicYear = $this->academicYear ?: AcademicPeriod::current();
    }

    #[Computed]
    public function stats()
    {
        $baseQuery = MonevReview::query()
            ->forAcademicPeriod($this->academicYear, $this->semester)
            ->whereNotNull('reported_to_rektor_at');

        return [
            'total' => $baseQuery->count(),
            'sangat_baik' => (clone $baseQuery)->where('status', 'Sangat Baik')->count(),
            'baik' => (clone $baseQuery)->where('status', 'Baik')->count(),
            'cukup' => (clone $baseQuery)->where('status', 'Cukup')->count(),
        ];
    }

    #[Computed]
    public function academicYears()
    {
        $periods = Proposal::query()
            ->whereNotNull('start_year')
            ->distinct()
            ->get(['start_year', 'semester']);

        return AcademicPeriod::optionsFromPeriods($periods);
    }

    #[Computed]
    public function recentReports()
    {
        return MonevReview::query()
            ->with(['proposal.submitter', 'reviewer'])
            ->forAcademicPeriod($this->academicYear, $this->semester)
            ->whereNotNull('reported_to_rektor_at')
            ->latest('reported_to_rektor_at')
            ->take(10)
            ->get();
    }

    public function render()
    {
        return view('livewire.rektor.monev-dashboard');
    }
}
