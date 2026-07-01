<?php

namespace App\Livewire\AdminLppm;

use App\Enums\ReviewStatus;
use App\Models\CommunityService;
use App\Models\CommunityServiceScheme;
use App\Models\Faculty;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\ResearchScheme;
use App\Models\Setting;
use App\Models\StudyProgram;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ReviewMonitoring extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $typeFilter = 'all';

    #[Url]
    public string $reviewerSearch = '';

    #[Url]
    public string $progressFilter = 'all';

    #[Url]
    public string $facultyFilter = 'all';

    #[Url]
    public string $prodiFilter = 'all';

    #[Url]
    public string $schemeFilter = 'all';

    public function mount()
    {
        if (! Auth::user()->hasAnyRole(['admin lppm', 'kepala lppm', 'rektor'])) {
            abort(403);
        }
    }

    public function resetFilters()
    {
        $this->reset(['search', 'typeFilter', 'reviewerSearch', 'progressFilter', 'facultyFilter', 'prodiFilter', 'schemeFilter']);
        $this->resetPage();
    }

    #[Computed]
    public function faculties()
    {
        return Faculty::orderBy('name')->get();
    }

    #[Computed]
    public function studyPrograms()
    {
        if ($this->facultyFilter === 'all') {
            return collect();
        }

        return StudyProgram::where('faculty_id', $this->facultyFilter)->orderBy('name')->get();
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
    public function proposals()
    {
        $requiredCount = (int) Setting::get('reviewer_count_required', 1);

        return Proposal::query()
            ->whereIn('status', ['under_review', 'reviewed', 'revision_needed', 'revision_submitted'])
            ->with(['submitter', 'detailable', 'reviewers.user'])
            ->when($this->search, function ($query) {
                $query->where('title', 'like', "%{$this->search}%");
            })
            ->when($this->reviewerSearch, function ($query) {
                $query->whereHas('reviewers.user', function ($q) {
                    $q->where('name', 'like', "%{$this->reviewerSearch}%");
                });
            })
            ->when($this->typeFilter !== 'all', function ($query) {
                $detailableType = $this->typeFilter === 'research'
                    ? Research::class
                    : CommunityService::class;
                $query->where('detailable_type', $detailableType);

                if ($this->schemeFilter !== 'all') {
                    $query->whereHasMorph('detailable', [$detailableType], function ($q) use ($detailableType) {
                        if ($detailableType === Research::class) {
                            $q->where('research_scheme_id', $this->schemeFilter);
                        } else {
                            $q->where('community_service_scheme_id', $this->schemeFilter);
                        }
                    });
                }
            })
            ->when($this->facultyFilter !== 'all', function ($query) {
                $query->whereHas('submitter.identity', function ($q) {
                    $q->where('faculty_id', $this->facultyFilter);
                });
            })
            ->when($this->prodiFilter !== 'all', function ($query) {
                $query->whereHas('submitter.identity', function ($q) {
                    $q->where('study_program_id', $this->prodiFilter);
                });
            })
            ->when($this->progressFilter !== 'all', function ($query) use ($requiredCount) {
                if ($this->progressFilter === 'unassigned') {
                    $query->has('reviewers', '<', $requiredCount);
                } elseif ($this->progressFilter === 'completed') {
                    // Requires all reviewers to be completed and have at least $requiredCount
                    $query->has('reviewers', '>=', $requiredCount)
                        ->whereDoesntHave('reviewers', function ($q) {
                            $q->where('status', '!=', ReviewStatus::COMPLETED->value);
                        });
                } elseif ($this->progressFilter === 'in_progress') {
                    $query->has('reviewers', '>', 0)
                        ->whereHas('reviewers', function ($q) {
                            $q->where('status', '!=', ReviewStatus::COMPLETED->value);
                        });
                }
            })
            ->latest()
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.admin-lppm.review-monitoring');
    }
}
