<?php

declare(strict_types=1);

namespace App\Livewire\Traits;

use App\Enums\ProposalUserStatus;
use App\Models\Proposal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

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

        return $query->where(function ($q) use ($user) {
            $q->where('submitter_id', $user->id)
                ->orWhereHas('teamMembers', function ($subQuery) use ($user) {
                    $subQuery->where('user_id', $user->id)
                        ->where('status', ProposalUserStatus::ACCEPTED->value);
                });
        });
    }

    protected function canEditReport(Proposal $proposal): bool
    {
        $user = Auth::user();

        if ($user->activeHasAnyRole(['admin lppm', 'superadmin'])) {
            return true;
        }

        // Allow submitter or accepted team member to edit
        return $proposal->submitter_id === $user->id
            || $proposal->teamMembers()
                ->where('user_id', $user->id)
                ->where('status', ProposalUserStatus::ACCEPTED->value)
                ->exists();
    }
}
