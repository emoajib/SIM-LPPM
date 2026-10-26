<?php

namespace App\Livewire\Kaprodi;

use App\Enums\ProposalStatus;
use App\Models\CommunityService;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ProposalValidation extends Component
{
    use WithPagination;

    public function mount(): void
    {
        $isKaprodiValidationActive = Setting::get('feature_kaprodi_validation', false);

        if (! $isKaprodiValidationActive) {
            abort(403, 'Maaf Anda tidak memiliki akses ini');
        }
    }

    #[Url]
    public string $search = '';

    #[Url]
    public string $typeFilter = 'all';

    #[Url]
    public string $yearFilter = '';

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
        $this->yearFilter = '';
        $this->resetPage();
    }

    #[Layout('components.layouts.app')]
    public function render(): View
    {
        return view('livewire.kaprodi.proposal-validation');
    }

    /**
     * Get Kaprodi's study program ID for scoping proposals.
     */
    #[Computed]
    public function kaprodiStudyProgramId(): ?int
    {
        return Auth::user()?->identity?->study_program_id;
    }

    /**
     * Available academic years for proposals from Kaprodi's study program.
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    #[Computed]
    public function availableYears(): array
    {
        $studyProgramId = $this->kaprodiStudyProgramId();
        $query = Proposal::where('status', ProposalStatus::SUBMITTED);

        if ($studyProgramId) {
            $query->whereHas('submitter.identity', fn ($q) => $q->where('study_program_id', $studyProgramId));
        }

        $periods = $query->whereNotNull('start_year')
            ->select('start_year', 'semester')
            ->distinct()
            ->get();

        return \App\Support\AcademicPeriod::optionsFromPeriods($periods);
    }

    /**
     * Apply study program scope to a query (only proposals from Kaprodi's study program).
     *
     * @param  Builder<Proposal>  $query
     * @return Builder<Proposal>
     */
    protected function applyStudyProgramScope(Builder $query): Builder
    {
        $studyProgramId = $this->kaprodiStudyProgramId();

        if (! $studyProgramId) {
            $query->whereRaw('1 = 0');
        } else {
            $query->whereHas('submitter.identity', function ($q) use ($studyProgramId) {
                $q->where('study_program_id', $studyProgramId);
            });
        }

        return $query;
    }

    /**
     * Get proposals for validation.
     *
     * @return LengthAwarePaginator<int, Proposal>
     */
    #[Computed]
    public function proposals(): LengthAwarePaginator
    {
        $query = Proposal::query()
            ->where('status', ProposalStatus::SUBMITTED);

        // Apply study program scoping: Kaprodi only sees proposals from their study program
        $this->applyStudyProgramScope($query);

        $paginator = $query
            ->with(['submitter.identity.studyProgram', 'detailable', 'focusArea', 'researchScheme', 'communityServiceScheme', 'studyProgramRoadmap'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'like', "%{$this->search}%")
                        ->orWhere('summary', 'like', "%{$this->search}%")
                        ->orWhereHas('submitter', fn ($sq) => $sq->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->typeFilter !== 'all', function ($query) {
                $detailableType = $this->typeFilter === 'research'
                    ? Research::class
                    : CommunityService::class;
                $query->where('detailable_type', $detailableType);
            })
            ->when($this->yearFilter, function ($query) {
                $query->forAcademicYear($this->yearFilter);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Self-healing pagination clamp
        if ($paginator->currentPage() > $paginator->lastPage() && $paginator->total() > 0) {
            $this->setPage(1);

            return $query->paginate(15);
        }

        return $paginator;
    }

    /**
     * Get status stats.
     *
     * @return array{all: int, unvalidated: int, validated: int}
     */
    #[Computed]
    public function statusStats(): array
    {
        $studyProgramId = $this->kaprodiStudyProgramId();

        $baseQuery = Proposal::where('status', ProposalStatus::SUBMITTED);

        if ($studyProgramId) {
            $baseQuery->whereHas('submitter.identity', function ($q) use ($studyProgramId) {
                $q->where('study_program_id', $studyProgramId);
            });
        }

        return [
            'all' => (clone $baseQuery)->count(),
            'unvalidated' => (clone $baseQuery)->where('is_roadmap_validated_by_kaprodi', false)->count(),
            'validated' => (clone $baseQuery)->where('is_roadmap_validated_by_kaprodi', true)->count(),
        ];
    }

    /**
     * Get the study program name for display.
     */
    #[Computed]
    public function studyProgramName(): ?string
    {
        return Auth::user()?->identity?->studyProgram?->name;
    }

    public function validateProposal(string $proposalId): void
    {
        $proposal = Proposal::where('id', $proposalId)
            ->where('status', ProposalStatus::SUBMITTED)
            ->firstOrFail();

        // Ensure Kaprodi is validating their own study program
        if ($proposal->submitter->identity->study_program_id !== $this->kaprodiStudyProgramId()) {
            abort(403, 'Unauthorized action.');
        }

        $proposal->is_roadmap_validated_by_kaprodi = true;
        $proposal->save();

        session()->flash('success', 'Proposal berhasil divalidasi kesesuaiannya dengan pohon penelitian prodi.');
    }
}
