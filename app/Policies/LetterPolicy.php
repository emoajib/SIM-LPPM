<?php

namespace App\Policies;

use App\Models\Letter;
use App\Models\User;

class LetterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->activeHasAnyRole(['kepala lppm', 'rektor', 'admin lppm', 'superadmin']);
    }

    public function view(User $user, Letter $letter): bool
    {
        if ($user->activeHasAnyRole(['kepala lppm', 'rektor', 'admin lppm', 'superadmin'])) {
            return true;
        }

        return $letter->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->activeHasRole('dosen');
    }

    public function approve(User $user, Letter $letter): bool
    {
        return $user->activeHasAnyRole(['kepala lppm', 'rektor']);
    }

    public function reject(User $user, Letter $letter): bool
    {
        return $user->activeHasAnyRole(['kepala lppm', 'rektor']);
    }

    public function cancel(User $user, Letter $letter): bool
    {
        return $letter->user_id === $user->id
            && in_array($letter->status, ['pending_approval', 'rejected']);
    }

    public function resubmit(User $user, Letter $letter): bool
    {
        return $letter->user_id === $user->id
            && $letter->status === 'rejected';
    }

    public function download(User $user, Letter $letter): bool
    {
        if ($user->activeHasAnyRole(['kepala lppm', 'rektor', 'admin lppm', 'superadmin'])) {
            return true;
        }

        return $letter->user_id === $user->id;
    }

    public function manageLetterType(User $user): bool
    {
        return $user->activeHasRole('admin lppm');
    }
}
