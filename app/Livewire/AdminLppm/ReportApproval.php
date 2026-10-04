<?php

namespace App\Livewire\AdminLppm;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

use App\Enums\ReportStatus;
use App\Models\CommunityService;
use App\Models\Faculty;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\Research;
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

    public function resetFilters(): void
    {
        $this->search = '';
        $this->typeFilter = 'all';
        $this->statusFilter = 'all';
        $this->facultyFilter = 'all';
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.admin-lppm.report-approval', [
            'faculties' => Faculty::orderBy('name')->get(),
        ]);
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
            return Proposal::query()
                ->whereIn('status', ['approved', 'completed'])
                ->whereDoesntHave('progressReports', fn ($q) => $q->where('reporting_period', 'final'))
                ->with(['submitter.identity.studyProgram', 'submitter.identity.faculty', 'detailable', 'researchScheme'])
                ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
                ->when($this->typeFilter !== 'all', fn ($q) => $q->where('detailable_type', $this->typeFilter === 'research' ? Research::class : CommunityService::class)
                )
                ->when($this->facultyFilter !== 'all', fn ($q) => $q->whereHas('submitter.identity', fn ($u) => $u->where('faculty_id', $this->facultyFilter))
                )
                ->latest()
                ->paginate(15);
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

        return $query
            ->with(['proposal.submitter.identity.studyProgram', 'proposal.submitter.identity.faculty', 'proposal.detailable', 'proposal.researchScheme'])
            ->when($this->search, fn ($q) => $q->whereHas('proposal', fn ($p) => $p->where('title', 'like', "%{$this->search}%"))
            )
            ->when($this->typeFilter !== 'all', fn ($q) => $q->whereHas('proposal', fn ($p) => $p->where(
                'detailable_type', $this->typeFilter === 'research' ? Research::class : CommunityService::class
            ))
            )
            ->when($this->facultyFilter !== 'all', fn ($q) => $q->whereHas('proposal.submitter.identity', fn ($u) => $u->where('faculty_id', $this->facultyFilter))
            )
            ->orderByRaw("CASE
                WHEN status = '".ReportStatus::APPROVED_BY_DEKAN->value."' THEN 1
                WHEN status = '".ReportStatus::SUBMITTED->value."' THEN 2
                WHEN status = '".ReportStatus::REJECTED->value."' THEN 3
                ELSE 4 END")
            ->latest('updated_at')
            ->paginate(15);
    }
}
