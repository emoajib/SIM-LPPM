<?php

namespace App\Livewire\Reports;

use App\Models\CommunityService;
use App\Models\MonevReview;
use App\Models\Research;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Laporan Monev', 'pageTitle' => 'Laporan Monitoring & Evaluasi'])]
class MonevReport extends Component
{
    use WithPagination;

    public string $search = '';

    public string $period = '';

    public string $selectedSemester = 'all';

    public string $selectedType = 'all'; // all, research, community_service

    public string $selectedStatus = 'all'; // all, pending, reviewed, verified, approved

    public function mount(): void
    {
        $this->period = (string) date('Y');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPeriod(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedSemester(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedType(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedStatus(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->period = (string) date('Y');
        $this->selectedSemester = 'all';
        $this->selectedType = 'all';
        $this->selectedStatus = 'all';
        $this->resetPage();
    }

    #[Computed]
    public function summaryMetrics(): array
    {
        $base = $this->baseQuery();

        $total = (clone $base)->count();
        $reviewed = (clone $base)->whereNotNull('reviewed_at')->count();
        $verified = (clone $base)->whereNotNull('finalized_by_lppm_at')->count();
        $approved = (clone $base)->whereNotNull('approved_by_kepala_at')->count();
        $avgScore = round((clone $base)->whereNotNull('score')->avg('score') ?? 0, 1);
        $pending = $total - $reviewed;

        return [
            ['label' => 'Total Monev', 'value' => $total, 'icon' => 'ti-clipboard-list', 'color' => 'bg-blue-lt', 'text' => 'text-blue'],
            ['label' => 'Belum Direview', 'value' => $pending, 'icon' => 'ti-clock', 'color' => 'bg-yellow-lt', 'text' => 'text-yellow'],
            ['label' => 'Sudah Direview', 'value' => $reviewed, 'icon' => 'ti-check', 'color' => 'bg-teal-lt', 'text' => 'text-teal'],
            ['label' => 'Diverifikasi LPPM', 'value' => $verified, 'icon' => 'ti-shield-check', 'color' => 'bg-purple-lt', 'text' => 'text-purple'],
            ['label' => 'Disahkan Kepala LPPM', 'value' => $approved, 'icon' => 'ti-circle-check', 'color' => 'bg-green-lt', 'text' => 'text-green'],
            ['label' => 'Rata-rata Skor', 'value' => $avgScore, 'icon' => 'ti-chart-bar', 'color' => 'bg-orange-lt', 'text' => 'text-orange'],
        ];
    }

    #[Computed]
    public function availablePeriods(): array
    {
        return MonevReview::query()
            ->distinct()
            ->whereNotNull('academic_year')
            ->orderBy('academic_year', 'desc')
            ->pluck('academic_year')
            ->map(fn ($y) => (string) $y)
            ->toArray() ?: [(string) date('Y')];
    }

    protected function baseQuery()
    {
        $user = auth()->user();
        $facultyId = $user->activeHasRole('dekan') ? $user->identity?->faculty_id : null;

        return MonevReview::query()
            ->when($facultyId, fn ($q) => $q->whereHas('proposal.submitter.identity', fn ($i) => $i->where('faculty_id', $facultyId)))
            ->when($this->period, fn ($q) => $q->where('academic_year', $this->period))
            ->when($this->selectedSemester !== 'all', fn ($q) => $q->where('semester', $this->selectedSemester))
            ->when($this->selectedType !== 'all', function ($q) {
                $type = $this->selectedType === 'research'
                    ? Research::class
                    : CommunityService::class;
                $q->whereHas('proposal', fn ($pq) => $pq->where('detailable_type', $type));
            })
            ->when($this->selectedStatus !== 'all', function ($q) {
                match ($this->selectedStatus) {
                    'pending' => $q->whereNull('reviewed_at'),
                    'reviewed' => $q->whereNotNull('reviewed_at')->whereNull('finalized_by_lppm_at'),
                    'verified' => $q->whereNotNull('finalized_by_lppm_at')->whereNull('approved_by_kepala_at'),
                    'approved' => $q->whereNotNull('approved_by_kepala_at'),
                    default => null,
                };
            })
            ->when($this->search, function ($q) {
                $q->whereHas('proposal', function ($pq) {
                    $pq->where('title', 'like', "%{$this->search}%")
                        ->orWhereHas('submitter', fn ($uq) => $uq->where('name', 'like', "%{$this->search}%"));
                })->orWhereHas('reviewer', fn ($rq) => $rq->where('name', 'like', "%{$this->search}%"));
            });
    }

    public function render(): View
    {
        $reviews = $this->baseQuery()
            ->with([
                'proposal.submitter.identity.faculty',
                'proposal.detailable',
                'reviewer',
            ])
            ->orderByDesc('updated_at')
            ->paginate(15);

        return view('livewire.reports.monev-report', [
            'reviews' => $reviews,
        ]);
    }
}
