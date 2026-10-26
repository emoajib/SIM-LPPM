<?php

namespace App\Livewire\Dekan;

use App\Enums\ReportStatus;
use App\Models\CommunityService;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\StudyProgram;
use App\Support\AcademicPeriod;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property-read ?int $dekanFacultyId
 * @property-read LengthAwarePaginator $reports
 * @property-read ?string $facultyName
 */
class ReportIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $typeFilter = 'all';

    #[Url]
    public string $statusFilter = 'all';

    #[Url]
    public string $yearFilter = '';

    #[Url(except: 'all')]
    public string $studyProgramFilter = 'all';

    // Vetted by AI - Manual Review Required by Senior Engineer/Manager
    public function updated(string $property): void
    {
        if (str_ends_with($property, 'Filter') || $property === 'search') {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->typeFilter = 'all';
        $this->statusFilter = 'all';
        $this->yearFilter = '';
        $this->studyProgramFilter = 'all';
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.dekan.report-index');
    }

    #[Computed]
    public function dekanFacultyId(): ?int
    {
        return Auth::user()?->identity?->faculty_id;
    }

    /**
     * Available study programs in Dekan's faculty for filtering.
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    #[Computed]
    public function availableStudyPrograms()
    {
        $facultyId = $this->dekanFacultyId;

        if (! $facultyId) {
            return collect();
        }

        return StudyProgram::where('faculty_id', $facultyId)->orderBy('name')->get();
    }

    /**
     * Available academic years for reports in Dekan's faculty.
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    #[Computed]
    public function availableYears(): array
    {
        $facultyId = $this->dekanFacultyId;

        $periods = Proposal::whereHas('progressReports', function ($q) use ($facultyId) {
            $q->where('reporting_period', 'final');
            if ($facultyId) {
                $q->whereHas('proposal.submitter.identity', fn ($iq) => $iq->where('faculty_id', $facultyId));
            }
        })
            ->whereNotNull('start_year')
            ->select('start_year', 'semester')
            ->distinct()
            ->get();

        return AcademicPeriod::optionsFromPeriods($periods);
    }

    #[Computed]
    public function reports()
    {
        $facultyId = $this->dekanFacultyId;

        // Query semua laporan akhir dari fakultas dekan (tidak hanya SUBMITTED)
        // agar dekan dapat melihat riwayat approval (disetujui, ditolak, dll.)
        $query = ProgressReport::query()
            ->where('reporting_period', 'final');

        if (! $facultyId) {
            $query->whereRaw('1 = 0');
        } else {
            $query->whereHas('proposal.submitter.identity', function ($q) use ($facultyId) {
                // Lock 1: Selalu terisolasi di fakultas Dekan
                $q->where('faculty_id', $facultyId);

                // Lock 2: Filter prodi hanya berlaku di dalam fakultas tersebut
                if ($this->studyProgramFilter !== 'all' && is_numeric($this->studyProgramFilter)) {
                    $q->where('study_program_id', (int) $this->studyProgramFilter);
                }
            });
        }

        // Filter status: default tampilkan SUBMITTED saja (butuh action dari dekan)
        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        } else {
            // Default: tampilkan laporan yang perlu action atau sudah diproses Dekan
            $query->whereIn('status', [
                ReportStatus::SUBMITTED->value,
                ReportStatus::APPROVED_BY_DEKAN->value,
                ReportStatus::APPROVED->value,
                ReportStatus::REJECTED->value,
            ]);
        }

        $paginator = $query
            ->with([
                'proposal.submitter.identity.studyProgram',
                'proposal.detailable',
                'proposal.researchScheme',
                'proposal.communityServiceScheme',
            ])
            ->when($this->search, function ($query) {
                $query->whereHas('proposal', function ($q) {
                    $q->where('title', 'like', "%{$this->search}%")
                        ->orWhereHas('submitter', fn ($sq) => $sq->where('name', 'like', "%{$this->search}%"));
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
            ->when($this->yearFilter, function ($query) {
                $query->whereHas('proposal', function ($q) {
                    Proposal::applyAcademicYearFilter($q, $this->yearFilter);
                });
            })
            ->orderBy('submitted_at', 'desc')
            ->paginate(15);

        // Self-healing pagination: jika page out-of-range saat filter aktif
        if ($paginator->currentPage() > $paginator->lastPage() && $paginator->total() > 0) {
            $this->setPage(1);

            return $query->paginate(15);
        }

        return $paginator;
    }

    #[Computed]
    public function facultyName(): ?string
    {
        return Auth::user()?->identity?->faculty?->name;
    }
}
