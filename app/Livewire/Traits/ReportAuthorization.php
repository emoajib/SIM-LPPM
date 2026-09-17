<?php

declare(strict_types=1);

namespace App\Livewire\Traits;

use App\Enums\ProposalUserStatus;
use App\Enums\ReportStatus;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\StudyProgram;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager
trait ReportAuthorization
{
    protected function filterByUserAccess(Builder $query): Builder
    {
        $user = Auth::user();

        if ($user->activeHasAnyRole(['admin lppm', 'kepala lppm', 'rektor'])) {
            return $query;
        }

        if ($user->activeHasRole('dekan')) {
            $facultyId = $user->identity?->faculty_id;

            return $query->whereHas('submitter.identity', function ($q) use ($facultyId) {
                $q->where('faculty_id', $facultyId);
            });
        }

        if ($user->activeHasRole('kaprodi')) {
            $studyProgramId = StudyProgram::where('kaprodi_user_id', $user->id)->value('id')
                ?? $user->identity?->study_program_id;

            return $query->whereHas('submitter.identity', function ($q) use ($studyProgramId) {
                $q->where('study_program_id', $studyProgramId);
            });
        }

        return $query->where(function ($q) use ($user) {
            $q->where('submitter_id', $user->id)
                ->orWhereHas('teamMembers', function ($subQuery) use ($user) {
                    $subQuery->where('user_id', $user->id)
                        ->where('status', ProposalUserStatus::ACCEPTED->value);
                });
        });
    }

    /**
     * Determine if current user can edit the report.
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     *
     * Rules:
     * 1. Only submitter or accepted team member can edit report content.
     * 2. Admin LPPM and others cannot edit lecturer's report content (Zero Trust).
     * 3. Editing is locked once submitted/approved; only allowed when no report, draft, or rejected (revision needed).
     */
    protected function canEditReport(Proposal $proposal, ?ProgressReport $progressReport = null): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        // Submitter or accepted team member only
        $isAuthor = $proposal->submitter_id === $user->id
            || $proposal->teamMembers()
                ->where('user_id', $user->id)
                ->where('status', ProposalUserStatus::ACCEPTED->value)
                ->exists();

        if (! $isAuthor) {
            return false;
        }

        // If no report exists yet, author can create/edit draft
        if (! $progressReport) {
            return true;
        }

        // Once submitted or approved, report is locked from direct editing.
        // Only draft or rejected (needs revision) can be edited.
        return in_array($progressReport->status, [ReportStatus::DRAFT, ReportStatus::REJECTED]);
    }
}
