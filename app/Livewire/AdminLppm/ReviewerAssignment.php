<?php

namespace App\Livewire\AdminLppm;

use App\Enums\ProposalStatus;
use App\Livewire\Actions\AssignReviewersAction;
use App\Livewire\Concerns\HasToast;
use App\Models\CommunityService;
use App\Models\CommunityServiceScheme;
use App\Models\Faculty;
use App\Models\Proposal;
use App\Models\ProposalReviewer;
use App\Models\Research;
use App\Models\ResearchScheme;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

class ReviewerAssignment extends Component
{
    use HasToast;
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $typeFilter = 'all';

    #[Url(history: true)]
    public string $yearFilter = '';

    #[Url(history: true)]
    public string $assignmentFilter = 'all'; // all, assigned, unassigned

    #[Url(history: true)]
    public string $facultyFilter = '';

    #[Url(history: true)]
    public string $studyProgramFilter = '';

    #[Url(history: true)]
    public string $schemeFilter = '';

    public string $selectedProposalId = '';

    #[Validate('required')]
    public string $selectedReviewer = '';

    public string $confirmingRemoveReviewerId = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
        $this->schemeFilter = '';
    }

    public function updatedYearFilter(): void
    {
        $this->resetPage();
    }

    public function updatedAssignmentFilter(): void
    {
        $this->resetPage();
    }

    public function updatedFacultyFilter(): void
    {
        $this->resetPage();
        $this->studyProgramFilter = '';
    }

    public function updatedStudyProgramFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSchemeFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->typeFilter = 'all';
        $this->yearFilter = '';
        $this->assignmentFilter = 'all';
        $this->facultyFilter = '';
        $this->studyProgramFilter = '';
        $this->schemeFilter = '';
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.admin-lppm.reviewer-assignment');
    }

    #[Computed]
    public function proposals()
    {
        // Include both WAITING_REVIEWER (new) and UNDER_REVIEW (existing) statuses
        $query = Proposal::query()
            ->whereIn('status', [
                ProposalStatus::WAITING_REVIEWER,
                ProposalStatus::UNDER_REVIEW,
            ]);

        return $query
            ->with([
                'submitter.identity',
                'detailable',
                'focusArea',
                'researchScheme',
                'reviewers.user',
            ])
            ->when($this->search, function ($query) {
                $search = (string) $this->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('summary', 'like', "%{$search}%");
                });
            })
            ->when($this->typeFilter !== 'all', function ($query) {
                $type = (string) $this->typeFilter;
                if (in_array($type, ['research', 'community_service'])) {
                    $detailableType = $type === 'research'
                        ? Research::class
                        : CommunityService::class;
                    $query->where('detailable_type', $detailableType);
                }
            })
            ->when($this->yearFilter, function ($query) {
                $year = (int) $this->yearFilter;
                if ($year > 2000 && $year < 2100) {
                    $query->whereYear('created_at', $year);
                }
            })
            ->when($this->assignmentFilter === 'assigned', function ($query) {
                $query->has('reviewers');
            })
            ->when($this->assignmentFilter === 'unassigned', function ($query) {
                $query->doesntHave('reviewers');
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
            ->when($this->schemeFilter, function ($query) {
                if ($this->typeFilter === 'research') {
                    $query->whereHas('detailable', function ($q) {
                        $q->where('research_scheme_id', $this->schemeFilter);
                    });
                } elseif ($this->typeFilter === 'community_service') {
                    $query->whereHas('detailable', function ($q) {
                        $q->where('community_service_scheme_id', $this->schemeFilter);
                    });
                } else {
                    // If no type filter, check both using polymorphic relation
                    $query->where(function ($q) {
                        $q->where(function ($q1) {
                            $q1->where('detailable_type', Research::class)
                                ->whereHas('detailable', function ($q2) {
                                    $q2->where('research_scheme_id', $this->schemeFilter);
                                });
                        })->orWhere(function ($q1) {
                            $q1->where('detailable_type', CommunityService::class)
                                ->whereHas('detailable', function ($q2) {
                                    $q2->where('community_service_scheme_id', $this->schemeFilter);
                                });
                        });
                    });
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    #[Computed]
    public function statusStats(): array
    {
        $statuses = [ProposalStatus::WAITING_REVIEWER, ProposalStatus::UNDER_REVIEW];

        return [
            'all' => Proposal::whereIn('status', $statuses)->count(),
            'waiting_reviewer' => Proposal::where('status', ProposalStatus::WAITING_REVIEWER)->count(),
            'under_review' => Proposal::where('status', ProposalStatus::UNDER_REVIEW)->count(),
            'research' => Proposal::whereIn('status', $statuses)
                ->where('detailable_type', Research::class)
                ->count(),
            'community_service' => Proposal::whereIn('status', $statuses)
                ->where('detailable_type', CommunityService::class)
                ->count(),
            'assigned' => Proposal::whereIn('status', $statuses)
                ->has('reviewers')
                ->count(),
            'unassigned' => Proposal::whereIn('status', $statuses)
                ->doesntHave('reviewers')
                ->count(),
        ];
    }

    #[Computed]
    public function availableYears(): array
    {
        $statuses = [ProposalStatus::WAITING_REVIEWER, ProposalStatus::UNDER_REVIEW];

        $years = Proposal::whereIn('status', $statuses)
            ->selectRaw(sql_year().' as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        return $years;
    }

    #[Computed]
    public function faculties(): Collection
    {
        return Faculty::orderBy('name')->get();
    }

    #[Computed]
    public function studyPrograms(): Collection
    {
        if (! $this->facultyFilter) {
            return collect();
        }

        return StudyProgram::where('faculty_id', $this->facultyFilter)->orderBy('name')->get();
    }

    #[Computed]
    public function schemes(): Collection
    {
        if ($this->typeFilter === 'research') {
            return ResearchScheme::where('is_active', true)->orderBy('name')->get();
        } elseif ($this->typeFilter === 'community_service') {
            return CommunityServiceScheme::where('is_active', true)->orderBy('name')->get();
        }

        // If type is not selected, return empty because IDs might overlap and meaning is ambiguous
        return collect();
    }

    #[Computed]
    public function availableReviewers(): Collection
    {
        return User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['reviewer']);
        })
            ->with('identity:id,user_id,identity_id')
            ->get();
    }

    public function openAssignModal(string $proposalId): void
    {
        $this->selectedProposalId = $proposalId;
        $this->selectedReviewer = '';
        $this->resetValidation();
    }

    public function assignReviewers(): void
    {
        $this->validate();

        $proposal = Proposal::find($this->selectedProposalId);
        if (! $proposal) {
            return;
        }

        $action = app(AssignReviewersAction::class);
        $result = $action->execute($proposal, $this->selectedReviewer);

        if ($result['success']) {
            session()->flash('success', $result['message']);
            $this->toastSuccess($result['message']);
            $this->selectedReviewer = '';
            // Close modal is handled by Bootstrap data-bs-dismiss
        } else {
            session()->flash('error', $result['message']);
            $this->toastError($result['message']);
        }
    }

    public function confirmRemoveReviewer(string $proposalId, string $reviewerId): void
    {
        $this->selectedProposalId = $proposalId;
        $this->confirmingRemoveReviewerId = $reviewerId;
    }

    public function removeReviewer(): void
    {
        $proposal = Proposal::find($this->selectedProposalId);
        if (! $proposal) {
            return;
        }

        $reviewer = $proposal->reviewers()
            ->where('user_id', $this->confirmingRemoveReviewerId)
            ->first();

        if ($reviewer instanceof ProposalReviewer) {
            $reviewer->delete();
            session()->flash('success', 'Reviewer berhasil dihapus');
            $this->toastSuccess('Reviewer berhasil dihapus');
        }

        $this->confirmingRemoveReviewerId = '';
    }

    public function cancelRemoveReviewer(): void
    {
        $this->confirmingRemoveReviewerId = '';
    }

    public function resetReviewerForm(): void
    {
        $this->selectedReviewer = '';
        $this->resetValidation();
    }
}
