<?php

namespace App\Policies;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProposalPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Filtered at query level in ProposalService
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Proposal $proposal): bool
    {
        // 1. Admin LPPM, Kepala LPPM, Rektor, and Superadmin can view all
        if ($user->activeHasAnyRole(['admin lppm', 'kepala lppm', 'rektor', 'superadmin'])) {
            return true;
        }

        // 2. Submitter (Ketua) can view
        if ($proposal->submitter_id === $user->id) {
            return true;
        }

        // 3. Team members can view
        if ($proposal->teamMembers()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // 4. Assigned reviewers can view
        if ($proposal->reviewers()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // 5. Dekan of the submitter's faculty can view
        if ($user->activeHasRole('dekan')) {
            $dekanFacultyId = $user->identity?->faculty_id;
            $submitterFacultyId = $proposal->submitter?->identity?->faculty_id;

            if (filled($dekanFacultyId) && $dekanFacultyId === $submitterFacultyId) {
                return true;
            }
        }

        // 6. Kaprodi of the submitter's study program can view (parity API)
        if ($user->activeHasRole('kaprodi')) {
            $kaprodiProdiId = StudyProgram::where('kaprodi_user_id', $user->id)->value('id')
                ?? $user->identity?->study_program_id;
            $submitterProdiId = $proposal->submitter?->identity?->study_program_id;

            if (filled($kaprodiProdiId) && $kaprodiProdiId === $submitterProdiId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Otorisasi halaman realisasi anggaran (daily note).
     *
     * Aturan sama dengan `view`, tetapi reviewer yang hanya ditugaskan review
     * proposal tidak boleh membuka data keuangan. Reviewer tetap bisa mengakses
     * bila juga memegang peran lain (admin/dekan/kaprodi/dll).
     */
    public function viewDailyNote(User $user, Proposal $proposal): bool
    {
        if ($user->activeHasAnyRole(['admin lppm', 'kepala lppm', 'rektor', 'superadmin'])) {
            return true;
        }

        if ($user->activeHasRole('dekan')) {
            $dekanFacultyId = $user->identity?->faculty_id;
            $submitterFacultyId = $proposal->submitter?->identity?->faculty_id;

            return filled($dekanFacultyId) && $dekanFacultyId === $submitterFacultyId;
        }

        if ($user->activeHasRole('kaprodi')) {
            $kaprodiProdiId = StudyProgram::where('kaprodi_user_id', $user->id)->value('id')
                ?? $user->identity?->study_program_id;
            $submitterProdiId = $proposal->submitter?->identity?->study_program_id;

            return filled($kaprodiProdiId) && $kaprodiProdiId === $submitterProdiId;
        }

        return $proposal->submitter_id === $user->id
            || $proposal->teamMembers()->where('user_id', $user->id)->exists();
    }

    /**
     * Otorisasi mutasi catatan harian (tambah/edit/hapus).
     */
    public function manageDailyNote(User $user, Proposal $proposal): bool
    {
        return $proposal->submitter_id === $user->id
            || $proposal->teamMembers()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->activeHasRole('dosen');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Proposal $proposal): bool
    {
        // Only submitter can update, and only in certain statuses
        if ($proposal->submitter_id !== $user->id) {
            // Admin LPPM can assist in editing draft/revision
            if ($user->activeHasAnyRole(['admin lppm', 'superadmin'])) {
                return in_array($proposal->status, [
                    ProposalStatus::DRAFT,
                    ProposalStatus::REVISION_NEEDED,
                    ProposalStatus::NEED_ASSIGNMENT,
                ]);
            }

            return false;
        }

        return in_array($proposal->status, [
            ProposalStatus::DRAFT,
            ProposalStatus::REVISION_NEEDED,
            ProposalStatus::NEED_ASSIGNMENT,
        ]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Proposal $proposal): bool
    {
        // Only submitter can delete, and only if status is DRAFT
        return $proposal->submitter_id === $user->id && $proposal->status === ProposalStatus::DRAFT;
    }

    /**
     * Determine whether the user can submit the proposal.
     */
    public function submit(User $user, Proposal $proposal): bool
    {
        return $this->update($user, $proposal);
    }

    /**
     * Determine whether the user can approve the proposal (Dekan).
     */
    public function approveAsDekan(User $user, Proposal $proposal): bool
    {
        if (! $user->activeHasRole('dekan')) {
            return false;
        }

        $submitterFacultyId = $proposal->submitter->identity?->faculty_id;

        return $user->identity?->faculty_id === $submitterFacultyId && $proposal->status === ProposalStatus::SUBMITTED;
    }

    /**
     * Determine whether the user can review the proposal (Reviewer).
     */
    public function review(User $user, Proposal $proposal): bool
    {
        return $proposal->reviewers()->where('user_id', $user->id)->exists()
            && $proposal->status === ProposalStatus::UNDER_REVIEW;
    }
}
