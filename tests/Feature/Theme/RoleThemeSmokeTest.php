<?php

/**
 * Regression: BC-01 — matriks peran × tema.
 *
 * Memastikan setiap halaman ekosistem (Usulan, Perbaikan Usulan, Laporan Akhir,
 * Realisasi Anggaran, Verifikasi Luaran) dapat dibuka oleh semua peran pada
 * kedua tema tanpa error 500, dan tab bar modern tidak bocor ke tema klasik.
 */

use App\Models\Faculty;
use App\Models\Identity;
use App\Models\Institution;
use App\Models\StudyProgram;
use App\Models\User;
use Database\Seeders\InstitutionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(InstitutionSeeder::class);
});

const MODERN_TAB_MARKER = 'bg-white border border-slate-200 rounded-xl shadow-sm px-4 pt-3 mb-3';

function makeUserForRole(string $role): User
{
    $faculty = Faculty::factory()->create(['institution_id' => Institution::first()->id]);
    $prodi = StudyProgram::factory()->create(['faculty_id' => $faculty->id]);

    $user = User::factory()->create();
    $user->assignRole($role);
    Identity::factory()->create([
        'user_id' => $user->id,
        'faculty_id' => $faculty->id,
        'study_program_id' => $prodi->id,
    ]);

    return $user;
}

it('opens every ecosystem page for every role in both themes', function (string $role, string $theme, string $routeName) {
    $user = makeUserForRole($role);

    $response = $this->actingAs($user)
        ->withSession(['active_role' => $role, 'sim_lppm_app_theme' => $theme])
        ->get(route($routeName));

    // Verifikasi Luaran punya halaman modern-only: tema klasik dialihkan.
    if ($theme === 'classic' && $routeName === 'research.verifikasi-luaran.page') {
        $response->assertRedirect(route('accreditation.verification'));

        return;
    }

    $response->assertStatus(200);

    if ($theme === 'modern') {
        $response->assertSee('id="modern-root"', false);
    } else {
        $response->assertDontSee('id="modern-root"', false);
        // BC-01: tab bar modern tidak boleh muncul di tema klasik.
        $response->assertDontSee(MODERN_TAB_MARKER, false);
    }
})->with(['dosen', 'admin lppm', 'kepala lppm', 'dekan', 'kaprodi', 'reviewer', 'rektor', 'superadmin'])
    ->with(['classic', 'modern'])
    ->with([
        'research.proposal.index',
        'research.proposal-revision.index',
        'research.final-report.index',
        'research.daily-note.index',
        'community-service.proposal.index',
        'community-service.proposal-revision.index',
        'community-service.final-report.index',
        'community-service.daily-note.index',
        'research.verifikasi-luaran.page',
    ]);
