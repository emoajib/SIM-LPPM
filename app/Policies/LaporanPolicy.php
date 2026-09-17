<?php

namespace App\Policies;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

use App\Models\ProgressReport;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy RBAC untuk ProgressReport (Laporan Akhir).
 *
 * Aturan Bisnis:
 * - Approval adalah wewenang JABATAN → pejabat (Dekan, Kaprodi, Kepala LPPM, Rektor)
 *   BOLEH menyetujui laporan yang diajukan dirinya sendiri karena laporan tersebut
 *   adalah laporan unit/jabatan, bukan laporan personal.
 * - Review adalah wewenang PERSONAL → Reviewer tidak boleh mereview laporan miliknya
 *   sendiri karena menimbulkan konflik kepentingan.
 */
class LaporanPolicy
{
    use HandlesAuthorization;

    /**
     * Tentukan apakah user boleh menyetujui laporan akhir.
     *
     * Menggunakan activeHasRole (berbasis session active_role) agar konsisten
     * dengan konvensi existing: user harus switch role ke jabatan yang berwenang
     * sebelum bisa melakukan approval.
     *
     * Pejabat BOLEH approve laporan sendiri (tidak ada self-approval guard di sini)
     * karena konteksnya adalah approval jabatan, bukan personal.
     */
    public function approve(User $user, ProgressReport $laporan): bool
    {
        // Superadmin selalu bisa (ditangani oleh Gate::before di AppServiceProvider)
        // Cukup periksa: apakah active role user punya permission ini?
        $approverRoles = ['admin lppm', 'dekan', 'kaprodi', 'kepala lppm', 'rektor'];

        return $user->activeHasAnyRole($approverRoles);
    }

    /**
     * Tentukan apakah user boleh mereview laporan (sebagai Reviewer).
     *
     * Reviewer TIDAK BOLEH mereview laporan yang diajukan dirinya sendiri
     * karena menimbulkan konflik kepentingan.
     */
    public function review(User $user, ProgressReport $laporan): bool
    {
        // Wajib aktif sebagai reviewer
        if (! $user->activeHasRole('reviewer')) {
            return false;
        }

        // Konflik kepentingan: reviewer tidak boleh review laporan sendiri
        if ($laporan->proposal->submitter_id === $user->id) {
            return false;
        }

        return true;
    }

    /**
     * Tentukan apakah user boleh melihat laporan akhir.
     */
    public function view(User $user, ProgressReport $laporan): bool
    {
        // Pejabat & admin bisa lihat semua
        $viewerRoles = ['admin lppm', 'kepala lppm', 'rektor', 'superadmin'];
        if ($user->activeHasAnyRole($viewerRoles)) {
            return true;
        }

        // Dekan hanya laporan dari fakultasnya
        if ($user->activeHasRole('dekan')) {
            $submitterFacultyId = $laporan->proposal->submitter->identity?->faculty_id;

            return $user->identity?->faculty_id === $submitterFacultyId;
        }

        // Kaprodi hanya laporan dari prodinya
        if ($user->activeHasRole('kaprodi')) {
            $submitterProgramId = $laporan->proposal->submitter->identity?->study_program_id;

            return $user->identity?->study_program_id === $submitterProgramId;
        }

        // Submitter dan anggota tim bisa lihat laporan mereka sendiri
        if ($laporan->proposal->submitter_id === $user->id) {
            return true;
        }

        if ($laporan->proposal->teamMembers()->where('user_id', $user->id)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Tentukan apakah user boleh memverifikasi status luaran.
     * Hanya Admin LPPM dan Superadmin.
     */
    public function verifyOutput(User $user, ProgressReport $laporan): bool
    {
        return $user->activeHasAnyRole(['admin lppm', 'superadmin']);
    }
}
