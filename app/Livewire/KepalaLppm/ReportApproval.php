<?php

namespace App\Livewire\KepalaLppm;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

use App\Enums\ReportStatus;
use App\Models\CommunityService;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\Research;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property-read LengthAwarePaginator $reports
 * @property-read array $stats
 */
class ReportApproval extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $typeFilter = 'all';

    #[Url]
    public string $statusFilter = 'all';

    public function resetFilters(): void
    {
        $this->search = '';
        $this->typeFilter = 'all';
        $this->statusFilter = 'all';
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.kepala-lppm.report-approval');
    }

    #[Computed]
    public function stats(): array
    {
        $base = ProgressReport::query()->where('reporting_period', 'final');

        /** @var object{total: int, submitted: int, approved_by_dekan: int, approved: int, rejected: int}|null $aggregated */
        $aggregated = (clone $base)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as submitted,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved_by_dekan,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as rejected
            ', [
                ReportStatus::SUBMITTED->value,
                ReportStatus::APPROVED_BY_DEKAN->value,
                ReportStatus::APPROVED->value,
                ReportStatus::REJECTED->value,
            ])
            ->first();

        return [
            'total_submitted' => $aggregated->total ?? 0,
            'ready_lppm' => $aggregated->approved_by_dekan ?? 0,
            'waiting_dekan' => $aggregated->submitted ?? 0,
            'approved_lppm' => $aggregated->approved ?? 0,
            // Proposal COMPLETED yang belum punya laporan akhir sama sekali
            'belum_laporan' => Proposal::query()
                ->whereIn('status', ['approved', 'completed'])
                ->whereDoesntHave('progressReports', fn ($q) => $q->where('reporting_period', 'final'))
                ->count(),
        ];
    }

    #[Computed]
    public function reports()
    {
        // Filter 'belum_laporan': tampilkan Proposal yang sudah disetujui/selesai
        // tapi belum punya ProgressReport final sama sekali (status real, bukan enum tambahan)
        if ($this->statusFilter === 'belum_laporan') {
            $proposalQuery = Proposal::query()
                ->whereIn('status', ['approved', 'completed'])
                ->whereDoesntHave('progressReports', fn ($q) => $q->where('reporting_period', 'final'))
                ->with(['submitter.identity.studyProgram', 'detailable', 'researchScheme'])
                ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
                ->when($this->typeFilter !== 'all', function ($q) {
                    $detailableType = $this->typeFilter === 'research' ? Research::class : CommunityService::class;
                    $q->where('detailable_type', $detailableType);
                })
                ->latest();

            // Return wrapped in a paginator-compatible structure
            // We return proposals directly for this special filter
            return $proposalQuery->paginate(15);
        }

        $query = ProgressReport::query()
            ->where('reporting_period', 'final');

        if ($this->statusFilter === 'ready') {
            $query->where('status', ReportStatus::APPROVED_BY_DEKAN);
        } elseif ($this->statusFilter === 'waiting_dekan') {
            $query->where('status', ReportStatus::SUBMITTED);
        } elseif ($this->statusFilter === 'approved') {
            $query->where('status', ReportStatus::APPROVED);
        } elseif ($this->statusFilter === 'revision') {
            $query->where('status', ReportStatus::REJECTED);
        } else {
            $query->whereIn('status', [
                ReportStatus::APPROVED_BY_DEKAN,
                ReportStatus::SUBMITTED,
                ReportStatus::APPROVED,
                ReportStatus::REJECTED,
            ]);
        }

        return $query
            ->with(['proposal.submitter.identity.studyProgram', 'proposal.detailable', 'proposal.researchScheme'])
            ->when($this->search, function ($query) {
                $query->whereHas('proposal', function ($q) {
                    $q->where('title', 'like', "%{$this->search}%");
                });
            })
            ->when($this->typeFilter !== 'all', function ($query) {
                $detailableType = $this->typeFilter === 'research'
                    ? Research::class
                    : CommunityService::class;
                $query->whereHas('proposal', function ($q) use ($detailableType) {
                    $q->where('detailable_type', $detailableType);
                });
            })
            ->orderByRaw("CASE 
                WHEN status = '".ReportStatus::APPROVED_BY_DEKAN->value."' THEN 1 
                WHEN status = '".ReportStatus::SUBMITTED->value."' THEN 2 
                WHEN status = '".ReportStatus::REJECTED->value."' THEN 3 
                ELSE 4 END")
            ->latest('updated_at')
            ->paginate(15);
    }
}
