<?php

namespace App\Livewire\KepalaLppm\Monev;

use App\Livewire\Concerns\HasToast;
use App\Livewire\Traits\WithInstitutionalApproval;
use App\Models\MonevReview;
use App\Models\Proposal;
use App\Models\User;
use App\Support\AcademicPeriod;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class MonevRecap extends Component
{
    use HasToast, WithInstitutionalApproval, WithPagination;

    #[Url]
    public $academicYear = '';

    #[Url]
    public $semester = 'all';

    public $search = '';

    public function mount()
    {
        $userId = Auth::id();
        $user = $userId ? User::find($userId) : null;
        if (! $user || ! $user->hasRole(['kepala lppm', 'rektor'])) {
            abort(403);
        }
        $this->academicYear = $this->academicYear ?: AcademicPeriod::current();
    }

    public function reportToRektor()
    {
        $reviews = MonevReview::query()
            ->forAcademicPeriod($this->academicYear, $this->semester)
            ->whereNotNull('reviewed_at')
            ->whereNotNull('finalized_by_lppm_at')
            ->whereNotNull('approved_by_kepala_at')
            ->whereNull('reported_to_rektor_at')
            ->get();

        if ($reviews->isEmpty() && ! $this->getInstitutionalReport('monev', $this->institutionalYear())) {
            $this->toastWarning('Tidak ada data baru untuk dilaporkan.');

            return;
        }

        // Call standard trait method
        $this->submitInstitutionalReport('monev', $this->institutionalYear(), [
            'count' => $reviews->count(),
            'semester' => $this->semester,
        ]);

        // Update individual reviews
        if ($reviews->isNotEmpty()) {
            $reviews->each(function (MonevReview $review) {
                $review->update(['reported_to_rektor_at' => now()]);
            });
        }
    }

    public function approveReview($id)
    {
        $review = MonevReview::findOrFail($id);
        $review->update([
            'approved_by_kepala_at' => now(),
            'approved_by_kepala_by' => Auth::id(),
        ]);

        $this->toastSuccess('Monev berhasil disetujui.');
    }

    public function approveAll()
    {
        $count = MonevReview::query()
            ->forAcademicPeriod($this->academicYear, $this->semester)
            ->whereNotNull('finalized_by_lppm_at')
            ->whereNull('approved_by_kepala_at')
            ->count();

        if ($count === 0) {
            $this->toastWarning('Tidak ada data untuk disetujui.');

            return;
        }

        MonevReview::query()
            ->forAcademicPeriod($this->academicYear, $this->semester)
            ->whereNotNull('finalized_by_lppm_at')
            ->whereNull('approved_by_kepala_at')
            ->update([
                'approved_by_kepala_at' => now(),
                'approved_by_kepala_by' => Auth::id(),
            ]);

        $this->toastSuccess($count.' hasil monev telah disetujui.');
    }

    #[Computed]
    public function institutionalYear(): int
    {
        return AcademicPeriod::startYear($this->academicYear) ?? (int) date('Y');
    }

    public function academicYears()
    {
        $periods = Proposal::query()
            ->whereNotNull('start_year')
            ->distinct()
            ->get(['start_year', 'semester']);

        return AcademicPeriod::optionsFromPeriods($periods);
    }

    #[Computed]
    public function reviews()
    {
        return MonevReview::query()
            ->with(['proposal.submitter', 'reviewer'])
            ->when($this->academicYear, function ($query) {
                $query->forAcademicPeriod($this->academicYear, $this->semester);
            })
            ->when($this->search, function ($query) {
                $query->whereHas('proposal', function ($q) {
                    $q->where('title', 'like', "%{$this->search}%")
                        ->orWhereHas('submitter', function ($sq) {
                            $sq->where('name', 'like', "%{$this->search}%");
                        });
                });
            })
            ->whereNotNull('finalized_by_lppm_at')
            ->latest()
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.kepala-lppm.monev.monev-recap', [
            'institutionalReport' => $this->getInstitutionalReport('monev', $this->institutionalYear()),
        ]);
    }
}
