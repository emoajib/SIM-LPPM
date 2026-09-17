<?php

namespace App\Livewire\Dekan;

use App\Enums\ReportStatus;
use App\Models\CommunityService;
use App\Models\ProgressReport;
use App\Models\Research;
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

    public function resetFilters(): void
    {
        $this->search = '';
        $this->typeFilter = 'all';
        $this->statusFilter = 'all';
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
                $q->where('faculty_id', $facultyId);
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
            ->orderBy('submitted_at', 'desc')
            ->paginate(15);
    }

    #[Computed]
    public function facultyName(): ?string
    {
        return Auth::user()?->identity?->faculty?->name;
    }
}
