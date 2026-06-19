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

        if ($user->hasAnyRole(['admin lppm', 'kepala lppm', 'rektor'])) {
            return $query;
        }

        if ($user->hasRole('dekan')) {
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

        if ($user->hasRole(['admin lppm', 'superadmin'])) {
            return true;
        }

        return $proposal->submitter_id === $user->id;
    }
}
