<?php

namespace App\Livewire\Kaprodi;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

use App\Enums\ReportStatus;
use App\Models\CommunityService;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\StudyProgram;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Komponen laporan akhir untuk Kaprodi.
 * Scope: hanya proposal dari program studi Kaprodi yang bersangkutan.
 * Role: view-only (tidak bisa approve, hanya monitoring status)
 *
 * @property-read LengthAwarePaginator $reports
 * @property-read array $stats
 * @property-read ?int $kaprodiStudyProgramId
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

    public function resetFilters(): void
    {
        $this->search = '';
        $this->typeFilter = 'all';
        $this->statusFilter = 'all';
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.kaprodi.report-approval');
    }

    /**
     * Dapatkan study_program_id dari Kaprodi yang sedang login.
     */
    #[Computed]
    public function kaprodiStudyProgramId(): ?int
    {
        $user = Auth::user();

        // Cek kaprodi_user_id di study_program, atau dari identity
        return StudyProgram::where('kaprodi_user_id', $user->id)->value('id')
            ?? $user->identity?->study_program_id;
    }

    /**
     * Statistik laporan akhir dari prodi Kaprodi.
     */
    #[Computed]
    public function stats(): array
    {
        $prodiId = $this->kaprodiStudyProgramId;

        $base = ProgressReport::query()
            ->where('reporting_period', 'final')
            ->when($prodiId, fn ($q) => $q->whereHas('proposal.submitter.identity', fn ($u) => $u->where('study_program_id', $prodiId)));

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
            // Proposal dari prodi ini yang belum punya laporan akhir sama sekali
            'belum_laporan' => Proposal::query()
                ->whereIn('status', ['approved', 'completed'])
                ->when($prodiId, fn ($q) => $q->whereHas('submitter.identity', fn ($u) => $u->where('study_program_id', $prodiId)))
                ->whereDoesntHave('progressReports', fn ($q) => $q->where('reporting_period', 'final'))
                ->count(),
        ];
    }

    /**
     * Data laporan akhir dari prodi Kaprodi dengan filter status.
     */
    #[Computed]
    public function reports()
    {
        $prodiId = $this->kaprodiStudyProgramId;

        // Filter 'belum_laporan': proposal belum punya final report
        if ($this->statusFilter === 'belum_laporan') {
            return Proposal::query()
                ->whereIn('status', ['approved', 'completed'])
                ->when($prodiId, fn ($q) => $q->whereHas('submitter.identity', fn ($u) => $u->where('study_program_id', $prodiId)))
                ->whereDoesntHave('progressReports', fn ($q) => $q->where('reporting_period', 'final'))
                ->with(['submitter.identity.studyProgram', 'detailable', 'researchScheme'])
                ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
                ->when($this->typeFilter !== 'all', function ($q) {
                    $q->where('detailable_type', $this->typeFilter === 'research' ? Research::class : CommunityService::class);
                })
                ->latest()
                ->paginate(15);
        }

        $query = ProgressReport::query()
            ->where('reporting_period', 'final')
            ->when($prodiId, fn ($q) => $q->whereHas('proposal.submitter.identity', fn ($u) => $u->where('study_program_id', $prodiId)));

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
            ->when($this->search, fn ($q) => $q->whereHas('proposal', fn ($p) => $p->where('title', 'like', "%{$this->search}%")))
            ->when($this->typeFilter !== 'all', function ($query) {
                $detailableType = $this->typeFilter === 'research' ? Research::class : CommunityService::class;
                $query->whereHas('proposal', fn ($q) => $q->where('detailable_type', $detailableType));
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
