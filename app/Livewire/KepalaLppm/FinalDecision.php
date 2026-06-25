<?php

namespace App\Livewire\KepalaLppm;

use App\Enums\ProposalStatus;
use App\Models\CommunityService;
use App\Models\CommunityServiceScheme;
use App\Models\Faculty;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\ResearchScheme;
use App\Models\StudyProgram;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class FinalDecision extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $typeFilter = 'all';

    #[Url]
    public string $yearFilter = '';

    #[Url]
    public string $recommendationFilter = 'all';

    #[Url]
    public string $facultyFilter = '';

    #[Url]
    public string $studyProgramFilter = '';

    #[Url]
    public string $schemeFilter = '';

    public function resetFilters(): void
    {
        $this->search = '';
        $this->typeFilter = 'all';
        $this->yearFilter = '';
        $this->recommendationFilter = 'all';
        $this->facultyFilter = '';
        $this->studyProgramFilter = '';
        $this->schemeFilter = '';
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.kepala-lppm.final-decision');
    }

    #[Computed]
    public function proposals()
    {
        $query = Proposal::query()
            ->where('status', ProposalStatus::REVIEWED);

        return $query
            ->with([
                'submitter.identity',
                'detailable',
                'focusArea',
                'researchScheme',
                'communityServiceScheme',
                'reviewers.user',
            ])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'like', "%{$this->search}%")
                        ->orWhere('summary', 'like', "%{$this->search}%");
                });
            })
            ->when($this->typeFilter !== 'all', function ($query) {
                $detailableType = $this->typeFilter === 'research'
                    ? Research::class
                    : CommunityService::class;
                $query->where('detailable_type', $detailableType);
            })
            ->when($this->schemeFilter && $this->typeFilter !== 'all', function ($query) {
                if ($this->typeFilter === 'research') {
                    $query->where('research_scheme_id', $this->schemeFilter);
                } elseif ($this->typeFilter === 'community_service') {
                    $query->where('community_service_scheme_id', $this->schemeFilter);
                }
            })
            ->when($this->yearFilter, function ($query) {
                $query->whereYear('created_at', $this->yearFilter);
            })
            ->when($this->facultyFilter, function ($query) {
                $query->whereHas('submitter.identity', function ($q) {
                    $q->where('faculty_id', $this->facultyFilter);
                });
            })
            ->when($this->studyProgramFilter, function ($query) {
                $query->whereHas('submitter.identity', function ($q) {
                    $q->where('study_program_id', $this->studyProgramFilter);
                });
            })
            ->when($this->recommendationFilter !== 'all', function ($query) {
                if ($this->recommendationFilter === 'all_approved') {
                    $query->whereHas('reviewers')->whereDoesntHave('reviewers', function ($q) {
                        $q->where('recommendation', '!=', 'approved')->orWhereNull('recommendation');
                    });
                } elseif ($this->recommendationFilter === 'needs_revision') {
                    $query->whereHas('reviewers', function ($q) {
                        $q->where('recommendation', 'revision_needed');
                    });
                } elseif ($this->recommendationFilter === 'rejected') {
                    $query->whereHas('reviewers', function ($q) {
                        $q->where('recommendation', 'rejected');
                    });
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    #[Computed]
    public function statusStats(): array
    {
        return [
            'all' => Proposal::where('status', ProposalStatus::REVIEWED)->count(),
            'research' => Proposal::where('status', ProposalStatus::REVIEWED)
                ->where('detailable_type', Research::class)
                ->count(),
            'community_service' => Proposal::where('status', ProposalStatus::REVIEWED)
                ->where('detailable_type', CommunityService::class)
                ->count(),
        ];
    }

    #[Computed]
    public function availableYears(): array
    {
        $years = Proposal::where('status', ProposalStatus::REVIEWED)
            ->selectRaw(sql_year().' as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        return $years;
    }

    #[Computed]
    public function availableFaculties()
    {
        return Faculty::orderBy('name')->get();
    }

    #[Computed]
    public function availableStudyPrograms()
    {
        $query = StudyProgram::query();
        if ($this->facultyFilter) {
            $query->where('faculty_id', $this->facultyFilter);
        }

        return $query->orderBy('name')->get();
    }

    #[Computed]
    public function availableSchemes()
    {
        if ($this->typeFilter === 'research') {
            return ResearchScheme::orderBy('name')->get();
        } elseif ($this->typeFilter === 'community_service') {
            return CommunityServiceScheme::orderBy('name')->get();
        }

        return collect();
    }
}
