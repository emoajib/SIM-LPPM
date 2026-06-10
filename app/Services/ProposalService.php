<?php

namespace App\Services;

use App\Enums\ProposalStatus;
use App\Livewire\Forms\ProposalForm;
use App\Models\CommunityService;
use App\Models\Proposal;
use App\Models\Research;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ProposalService
{
    public function createProposal(ProposalForm $form, string $type, ?string $submitterId = null): Proposal
    {
        $submitterId = $submitterId ?? (string) Auth::id();

        return DB::transaction(function () use ($form, $type, $submitterId) {
            $proposal = $type === 'research'
                ? $form->storeResearch($submitterId)
                : $form->storeCommunityService($submitterId);

            return $proposal->load(['detailable.media', 'submitter.identity', 'outputs', 'budgetItems', 'partners']);
        });
    }

    public function updateProposal(Proposal $proposal, ProposalForm $form, bool $validate = true): void
    {
        Gate::authorize('update', $proposal);

        $form->proposal = $proposal;
        $form->update($validate);
    }

    public function deleteProposal(Proposal $proposal): void
    {
        Gate::authorize('delete', $proposal);

        if ($proposal->status !== ProposalStatus::DRAFT) {
            throw new \Exception('Hanya proposal dengan status draft yang dapat dihapus.');
        }

        DB::transaction(function () use ($proposal) {
            $proposal->teamMembers()->detach();

            if ($proposal->detailable) {
                $proposal->detailable->delete();
            }

            $proposal->delete();
        });
    }

    public function getProposalsWithFilters(array $filters): LengthAwarePaginator
    {
        $type = $filters['type'] ?? 'research';

        // Sanitize type
        $type = in_array($type, ['research', 'community-service']) ? $type : 'research';

        $query = $this->getBaseProposalQuery($type);
        $user = Auth::user();

        // Security & Role-based filtering
        if ($user->activeHasRole('dosen')) {
            $role = $filters['role'] ?? 'ketua';
            if (! in_array($role, ['ketua', 'anggota'])) {
                $role = 'ketua';
            }
            $this->applyRoleFilter($query, $role);
        } elseif ($user->activeHasRole('reviewer')) {
            $this->applyRoleFilter($query, 'reviewer');
        } elseif ($user->activeHasRole('dekan')) {
            $facultyId = $user->identity?->faculty_id;
            $query->whereHas('submitter.identity', function ($q) use ($facultyId) {
                $q->where('faculty_id', $facultyId);
            });

            if (isset($filters['role']) && $filters['role'] !== '') {
                $this->applyRoleFilter($query, (string) $filters['role']);
            }
        } else {
            // Admin roles
            if (isset($filters['role']) && $filters['role'] !== '') {
                $role = (string) $filters['role'];
                if (in_array($role, ['submitter', 'ketua', 'team_member', 'anggota', 'reviewer'])) {
                    $this->applyRoleFilter($query, $role);
                }
            }
        }

        if (isset($filters['search']) && $filters['search'] !== '') {
            $search = (string) $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('submitter', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('detailable', function ($dq) use ($search) {
                        $dq->where('id', 'like', "%{$search}%");
                    });
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $statusValue = (string) $filters['status'];
            // Validate status against ProposalStatus enum values
            $allStatusValues = ['draft', 'submitted', 'need_assignment', 'approved', 'waiting_reviewer', 'under_review', 'reviewed', 'revision_needed', 'completed', 'rejected'];
            if (in_array($statusValue, $allStatusValues)) {
                $query->where('status', $statusValue);
            }
        }

        if (isset($filters['year']) && $filters['year'] !== '') {
            $year = (int) $filters['year'];
            if ($year > 2000 && $year < 2100) {
                $query->where(function ($q) use ($year) {
                    $q->where('start_year', $year)
                        ->orWhere(function ($sq) use ($year) {
                            $sq->whereNull('start_year')
                                ->whereYear('created_at', $year);
                        });
                });
            }
        }

        return $query->latest()->paginate(15);
    }

    public function getProposalsForReviewer(string $reviewerId): LengthAwarePaginator
    {
        return Proposal::query()
            ->whereHas('reviewers', function ($query) use ($reviewerId) {
                $query->where('user_id', $reviewerId);
            })
            ->with(['submitter.identity', 'detailable', 'reviewers'])
            ->latest()
            ->paginate(15);
    }

    public function getProposalStatistics(array $filters): array
    {
        $type = $filters['type'] ?? 'research';
        $query = $this->getBaseProposalQuery($type);
        $user = Auth::user();

        // Apply restrictions to stats as well
        if ($user->activeHasRole('dosen')) {
            // For stats, we might want to show based on current active role filter or combined?
            // Usually, tabs show stats for that specific tab.
            $role = $filters['role'] ?? 'ketua';
            if (! in_array($role, ['ketua', 'anggota'])) {
                $role = 'ketua';
            }
            $this->applyRoleFilter($query, $role);
        } elseif ($user->activeHasRole('reviewer')) {
            $this->applyRoleFilter($query, 'reviewer');
        } elseif ($user->activeHasRole('dekan')) {
            $facultyId = $user->identity?->faculty_id;
            $query->whereHas('submitter.identity', function ($q) use ($facultyId) {
                $q->where('faculty_id', $facultyId);
            });
        }

        $totalCount = $query->count();

        $statusStats = $query
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $allStatuses = ['draft', 'submitted', 'need_assignment', 'approved', 'waiting_reviewer', 'under_review', 'reviewed', 'revision_needed', 'completed', 'rejected'];
        $emptyStats = array_fill_keys($allStatuses, 0);

        return [
            'total' => $totalCount,
            'by_status' => array_merge($emptyStats, $statusStats),
        ];
    }

    public function getAvailableYears(string $type): array
    {
        return $this->getBaseProposalQuery($type)
            ->selectRaw('DISTINCT IFNULL(start_year, '.sql_year().') as year')
            ->orderByDesc('year')
            ->pluck('year')
            ->toArray();
    }

    public function validateProposalBeforeSubmit(Proposal $proposal, bool $strictTeamValidation = true): void
    {
        if ($proposal->status !== ProposalStatus::DRAFT) {
            throw new \Exception('Hanya proposal dengan status draft yang dapat disubmit.');
        }

        if (! $strictTeamValidation) {
            return;
        }

        if ($proposal->teamMembers()->where('status', '!=', 'accepted')->exists()) {
            throw new \Exception('Semua anggota tim harus menerima undangan sebelum proposal dapat disubmit.');
        }

        if ($proposal->teamMembers()->count() < 2) {
            throw new \Exception('Proposal harus memiliki minimal 2 anggota tim.');
        }
    }

    public function submitProposal(Proposal $proposal): void
    {
        $this->validateProposalBeforeSubmit($proposal);

        $notificationService = app(NotificationService::class);
        $submitter = $proposal->submitter;
        $recipients = $notificationService->getUsersByRole('dekan');

        DB::transaction(function () use ($proposal, $notificationService, $submitter, $recipients) {
            $proposal->update(['status' => ProposalStatus::SUBMITTED->value]);

            $notificationService->notifyProposalSubmitted($proposal, $submitter, $recipients);
        });
    }

    public function getProposalType(Proposal $proposal): string
    {
        return match ($proposal->detailable_type) {
            Research::class => 'research',
            CommunityService::class => 'community-service',
            default => 'research',
        };
    }

    protected function getBaseProposalQuery(string $type): Builder
    {
        $detailableType = match ($type) {
            'research' => Research::class,
            'community-service' => CommunityService::class,
            default => Research::class,
        };

        $relations = ['submitter.identity.faculty', 'detailable', 'focusArea'];

        if ($type === 'research') {
            $relations[] = 'researchScheme';
        } else {
            $relations[] = 'communityServiceScheme';
        }

        return Proposal::query()
            ->with($relations)
            ->where('detailable_type', $detailableType);
    }

    protected function applyRoleFilter(Builder $query, string $role): void
    {
        $userId = (string) Auth::id();

        match ($role) {
            'submitter', 'ketua' => $query->where('submitter_id', $userId),
            'team_member', 'anggota' => $query->whereHas('teamMembers', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            }),
            'reviewer' => $query->whereHas('reviewers', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            }),
            default => null,
        };
    }
}
