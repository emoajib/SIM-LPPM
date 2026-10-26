<?php

namespace App\Livewire\AdminLppm;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

use App\Enums\ReportStatus;
use App\Models\CommunityService;
use App\Models\CommunityServiceScheme;
use App\Models\Faculty;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\ResearchScheme;
use App\Support\AcademicPeriod;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Komponen laporan akhir untuk Admin LPPM.
 * Scope: semua laporan akhir dari seluruh fakultas (full access).
 * Role: view + dapat memonitor semua status + notifikasi belum_laporan
 *
 * @property-read LengthAwarePaginator $reports
 * @property-read array $stats
 */
#[Layout('components.layouts.app', ['title' => 'Monitoring Laporan Akhir', 'pageTitle' => 'Monitoring Laporan Akhir'])]
class ReportApproval extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $typeFilter = 'all';

    #[Url]
    public string $statusFilter = 'all';

    #[Url]
    public string $facultyFilter = 'all';

    #[Url]
    public string $yearFilter = '';

    #[Url(except: 'all')]
    public string $schemeFilter = 'all';

    // Vetted by AI - Manual Review Required by Senior Engineer/Manager
    public function updated(string $property): void
    {
        if ($property === 'typeFilter') {
            $this->schemeFilter = 'all';
        }

        if (str_ends_with($property, 'Filter') || $property === 'search') {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->typeFilter = 'all';
        $this->statusFilter = 'all';
        $this->facultyFilter = 'all';
        $this->yearFilter = '';
        $this->schemeFilter = 'all';
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.admin-lppm.report-approval', [
            'faculties' => Faculty::orderBy('name')->get(),
        ]);
    }

    #[Computed]
    public function schemes()
    {
        if ($this->typeFilter === 'research') {
            return ResearchScheme::orderBy('name')->get();
        } elseif ($this->typeFilter === 'community_service') {
            return CommunityServiceScheme::orderBy('name')->get();
        }

        return collect();
    }

    #[Computed]
    public function availableYears(): array
    {
        $periods = Proposal::query()
            ->whereNotNull('start_year')
            ->select('start_year', 'semester')
            ->distinct()
            ->get();

        return AcademicPeriod::optionsFromPeriods($periods);
    }

    /**
     * Statistik global seluruh laporan akhir (semua fakultas).
     */
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
            'rejected' => $aggregated->rejected ?? 0,
            // Proposal COMPLETED yang belum punya laporan akhir sama sekali
            'belum_laporan' => Proposal::query()
                ->whereIn('status', ['approved', 'completed'])
                ->whereDoesntHave('progressReports', fn ($q) => $q->where('reporting_period', 'final'))
                ->count(),
        ];
    }

    /**
     * Data laporan akhir seluruh sistem dengan filter lengkap.
     * Admin LPPM mendapat filter tambahan: per-fakultas.
     */
    #[Computed]
    public function reports()
    {
        // Filter 'belum_laporan': proposal yang belum mengajukan final report
        if ($this->statusFilter === 'belum_laporan') {
            $proposalQuery = Proposal::query()
                ->whereIn('status', ['approved', 'completed'])
                ->whereDoesntHave('progressReports', fn ($q) => $q->where('reporting_period', 'final'))
                ->with(['submitter.identity.studyProgram', 'submitter.identity.faculty', 'detailable', 'researchScheme', 'communityServiceScheme'])
                ->when($this->search, function ($q) {
                    $q->where(function ($sub) {
                        $sub->where('title', 'like', "%{$this->search}%")
                            ->orWhereHas('submitter', fn ($sq) => $sq->where('name', 'like', "%{$this->search}%"));
                    });
                })
                ->when($this->typeFilter !== 'all', fn ($q) => $q->where('detailable_type', $this->typeFilter === 'research' ? Research::class : CommunityService::class))
                ->when($this->facultyFilter !== 'all', fn ($q) => $q->whereHas('submitter.identity', fn ($u) => $u->where('faculty_id', $this->facultyFilter)))
                ->when($this->schemeFilter !== 'all' && $this->typeFilter !== 'all', function ($q) {
                    $column = $this->typeFilter === 'research' ? 'research_scheme_id' : 'community_service_scheme_id';
                    $q->where($column, $this->schemeFilter);
                })
                ->when($this->yearFilter, fn ($q) => $q->forAcademicYear($this->yearFilter))
                ->latest();

            $paginator = $proposalQuery->paginate(15);
            if ($paginator->currentPage() > $paginator->lastPage() && $paginator->total() > 0) {
                $this->setPage(1);

                return $proposalQuery->paginate(15);
            }

            return $paginator;
        }

        $query = ProgressReport::query()->where('reporting_period', 'final');

        match ($this->statusFilter) {
            'ready' => $query->where('status', ReportStatus::APPROVED_BY_DEKAN),
            'waiting_dekan' => $query->where('status', ReportStatus::SUBMITTED),
            'approved' => $query->where('status', ReportStatus::APPROVED),
            'revision' => $query->where('status', ReportStatus::REJECTED),
            default => $query->whereIn('status', [
                ReportStatus::APPROVED_BY_DEKAN,
                ReportStatus::SUBMITTED,
                ReportStatus::APPROVED,
                ReportStatus::REJECTED,
            ]),
        };

        $paginator = $query
            ->with([
                'proposal.submitter.identity.studyProgram',
                'proposal.submitter.identity.faculty',
                'proposal.detailable',
                'proposal.researchScheme',
                'proposal.communityServiceScheme',
            ])
            ->when($this->search, function ($q) {
                $q->whereHas('proposal', function ($p) {
                    $p->where('title', 'like', "%{$this->search}%")
                        ->orWhereHas('submitter', fn ($sq) => $sq->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->typeFilter !== 'all', fn ($q) => $q->whereHas('proposal', fn ($p) => $p->where(
                'detailable_type', $this->typeFilter === 'research' ? Research::class : CommunityService::class
            )))
            ->when($this->facultyFilter !== 'all', fn ($q) => $q->whereHas('proposal.submitter.identity', fn ($u) => $u->where('faculty_id', $this->facultyFilter)))
            ->when($this->schemeFilter !== 'all' && $this->typeFilter !== 'all', function ($q) {
                $column = $this->typeFilter === 'research' ? 'research_scheme_id' : 'community_service_scheme_id';
                $q->whereHas('proposal', fn ($sq) => $sq->where($column, $this->schemeFilter));
            })
            ->when($this->yearFilter, function ($q) {
                $q->whereHas('proposal', function ($p) {
                    Proposal::applyAcademicYearFilter($p, $this->yearFilter);
                });
            })
            ->orderByRaw("CASE
                WHEN status = '".ReportStatus::APPROVED_BY_DEKAN->value."' THEN 1
                WHEN status = '".ReportStatus::SUBMITTED->value."' THEN 2
                WHEN status = '".ReportStatus::REJECTED->value."' THEN 3
                ELSE 4 END")
            ->latest('updated_at')
            ->paginate(15);

        // Self-healing pagination clamp
        if ($paginator->currentPage() > $paginator->lastPage() && $paginator->total() > 0) {
            $this->setPage(1);

            return $query->paginate(15);
        }

        return $paginator;
    }
}
