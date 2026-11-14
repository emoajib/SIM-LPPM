<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman tema modern yang dirender server (Blade + root React).
 *
 * Sebelumnya logika ini ditulis sebagai closure duplikat di routes/web.php
 * untuk modul penelitian dan pengabdian — sumber divergensi (BC-01/BC-03).
 * Sekarang satu implementasi, modul ditentukan lewat route default 'module'.
 */
class ModernPageController extends Controller
{
    /**
     * Peran yang boleh membuka halaman modern berbasis proposal.
     *
     * @var array<int, string>
     */
    private const PROPOSAL_ROLES = [
        'admin lppm',
        'kepala lppm',
        'rektor',
        'superadmin',
        'dekan',
        'kaprodi',
        'dosen',
        'reviewer',
    ];

    public function verifikasiLuaran(Request $request): View|RedirectResponse
    {
        if (! is_modern_theme()) {
            return redirect()->route('accreditation.verification');
        }

        $isCommunityService = $this->module($request) === 'community-service';

        return view($isCommunityService ? 'modern.verifikasi-luaran-pkm' : 'modern.verifikasi-luaran', [
            'title' => 'Verifikasi Luaran IKU',
            'pageSubtitle' => $isCommunityService
                ? 'Pemantauan dan verifikasi target output pengabdian masyarakat'
                : 'Pemantauan dan verifikasi target output penelitian & pengabdian',
        ]);
    }

    public function dailyNote(Request $request, Proposal $proposal): View|RedirectResponse
    {
        if (! is_modern_theme()) {
            return redirect()->route($this->routeName($request, 'daily-note.show'), $proposal);
        }

        $user = $request->user();
        $this->authorizeProposalAccess($user, $proposal);

        return view('modern.daily-note', [
            'title' => 'Realisasi Anggaran',
            'pageSubtitle' => 'Catatan harian aktivitas dan pengeluaran dana kegiatan',
            'proposal' => $proposal,
            'canManage' => $this->canManageProposal($user, $proposal),
            'isEditable' => ! $proposal->logbook_approved_at,
        ]);
    }

    public function proposalShow(Request $request, Proposal $proposal): View|RedirectResponse
    {
        if (! is_modern_theme()) {
            return redirect()->route($this->routeName($request, 'proposal.show'), $proposal);
        }

        $this->authorizeProposalAccess($request->user(), $proposal);

        return view('modern.proposal-show', [
            'title' => 'Detail Proposal',
            'pageSubtitle' => $proposal->title,
            'proposal' => $proposal,
        ]);
    }

    /**
     * Nama modul dari route default ('research' | 'community-service').
     */
    private function module(Request $request): string
    {
        $module = $request->route()?->defaults['module'] ?? 'research';

        return $module === 'community-service' ? 'community-service' : 'research';
    }

    /**
     * Bentuk nama route lengkap sesuai modul, mis. research.daily-note.show.
     */
    private function routeName(Request $request, string $suffix): string
    {
        return $this->module($request).'.'.$suffix;
    }

    /**
     * Otorisasi baca proposal: peran global, dekan per fakultas, kaprodi per prodi.
     */
    private function authorizeProposalAccess(?User $user, Proposal $proposal): void
    {
        if ($user === null) {
            abort(403);
        }

        if (! $user->activeHasAnyRole(self::PROPOSAL_ROLES)) {
            abort(403);
        }

        if ($user->activeHasRole('dekan') && $user->identity?->faculty_id !== $proposal->submitter?->identity?->faculty_id) {
            abort(403);
        }

        if ($user->activeHasRole('kaprodi')) {
            $kaprodiProdiId = StudyProgram::where('kaprodi_user_id', $user->id)->value('id')
                ?? $user->identity?->study_program_id;

            if ($kaprodiProdiId !== $proposal->submitter?->identity?->study_program_id) {
                abort(403);
            }
        }
    }

    /**
     * Apakah user adalah pengusul atau anggota tim (boleh mengelola catatan/realisasi).
     */
    private function canManageProposal(User $user, Proposal $proposal): bool
    {
        return $proposal->submitter_id === $user->id
            || $proposal->teamMembers()->where('user_id', $user->id)->exists();
    }
}
