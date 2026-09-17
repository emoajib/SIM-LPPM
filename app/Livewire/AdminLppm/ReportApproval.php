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

        return [
            'total_submitted' => (clone $base)->whereIn('status', [
                ReportStatus::SUBMITTED,
                ReportStatus::APPROVED_BY_DEKAN,
                ReportStatus::APPROVED,
                ReportStatus::REJECTED,
            ])->count(),
            'ready_lppm' => (clone $base)->where('status', ReportStatus::APPROVED_BY_DEKAN)->count(),
            'waiting_dekan' => (clone $base)->where('status', ReportStatus::SUBMITTED)->count(),
            'approved_lppm' => (clone $base)->where('status', ReportStatus::APPROVED)->count(),
            'rejected' => (clone $base)->where('status', ReportStatus::REJECTED)->count(),
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
