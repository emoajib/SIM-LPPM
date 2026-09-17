<?php

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

use App\Enums\ProposalStatus;
use App\Enums\ReportStatus;
use App\Models\Faculty;
use App\Models\Identity;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\User;
use App\Policies\LaporanPolicy;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Test RBAC multi-role untuk approval laporan akhir.
 *
 * Aturan bisnis yang diuji:
 * - Dosen biasa tidak bisa approve laporan apapun
 * - Dekan/Kaprodi/Kepala LPPM/Rektor bisa approve laporan orang lain
 * - Pejabat (Dekan, dll.) BISA approve laporan sendiri (wewenang jabatan)
 * - Reviewer TIDAK BISA review laporan miliknya sendiri (conflict of interest)
 * - Reviewer BISA review laporan orang lain
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->faculty = Faculty::factory()->create(['name' => 'Fakultas Teknik']);

    // Dosen biasa
    $this->dosen = User::factory()->create();
    $this->dosen->assignRole('dosen');
    Identity::factory()->create([
        'user_id' => $this->dosen->id,
        'type' => 'dosen',
        'faculty_id' => $this->faculty->id,
    ]);

    // Dekan (juga punya role dosen)
    $this->dekan = User::factory()->create();
    $this->dekan->assignRole('dosen');
    $this->dekan->assignRole('dekan');
    Identity::factory()->create([
        'user_id' => $this->dekan->id,
        'type' => 'dosen',
        'faculty_id' => $this->faculty->id,
    ]);

    // Reviewer
    $this->reviewer = User::factory()->create();
    $this->reviewer->assignRole('reviewer');
    Identity::factory()->create([
        'user_id' => $this->reviewer->id,
        'type' => 'dosen',
    ]);

    // Kepala LPPM
    $this->kepala = User::factory()->create();
    $this->kepala->assignRole('kepala lppm');

    // Proposal + Laporan akhir milik dosen biasa
    $research = Research::factory()->create();
    $this->proposal = Proposal::factory()->create([
        'submitter_id' => $this->dosen->id,
        'detailable_type' => 'App\Models\Research',
        'detailable_id' => $research->id,
        'status' => ProposalStatus::COMPLETED,
    ]);

    $this->laporan = ProgressReport::create([
        'proposal_id' => $this->proposal->id,
        'reporting_period' => 'final',
        'reporting_year' => 2026,
        'status' => ReportStatus::SUBMITTED,
        'summary_update' => 'Ringkasan laporan akhir.',
    ]);

    // Proposal + Laporan akhir milik dekan sendiri
    $research2 = Research::factory()->create();
    $this->proposalDekan = Proposal::factory()->create([
        'submitter_id' => $this->dekan->id,
        'detailable_type' => 'App\Models\Research',
        'detailable_id' => $research2->id,
        'status' => ProposalStatus::COMPLETED,
    ]);

    $this->laporanDekan = ProgressReport::create([
        'proposal_id' => $this->proposalDekan->id,
        'reporting_period' => 'final',
        'reporting_year' => 2026,
        'status' => ReportStatus::SUBMITTED,
        'summary_update' => 'Ringkasan laporan dekan.',
    ]);

    $this->policy = new LaporanPolicy;
});

// =====================================================================
// SKENARIO 1: Dosen biasa TIDAK bisa approve laporan apapun
// =====================================================================
test('dosen biasa tidak bisa approve laporan orang lain', function () {
    session(['active_role' => 'dosen']);

    $result = $this->policy->approve($this->dosen, $this->laporan);

    expect($result)->toBeFalse();
});

test('dosen biasa tidak bisa approve laporan sendiri', function () {
    session(['active_role' => 'dosen']);

    // Buat laporan milik dosen sendiri
    $ownLaporan = ProgressReport::create([
        'proposal_id' => $this->proposal->id,
        'reporting_period' => 'final',
        'reporting_year' => 2025,
        'status' => ReportStatus::SUBMITTED,
    ]);

    $result = $this->policy->approve($this->dosen, $ownLaporan);

    expect($result)->toBeFalse();
});

// =====================================================================
// SKENARIO 2: Dekan bisa approve laporan orang lain
// =====================================================================
test('dekan dengan active role dekan bisa approve laporan dosen lain', function () {
    session(['active_role' => 'dekan']);

    $result = $this->policy->approve($this->dekan, $this->laporan);

    expect($result)->toBeTrue();
});

// =====================================================================
// SKENARIO 3: Dekan BISA approve laporan SENDIRI (wewenang jabatan)
// =====================================================================
test('dekan bisa approve laporan sendiri dalam kapasitas jabatan dekan', function () {
    session(['active_role' => 'dekan']);

    // Dekan approve laporan yang dia sendiri ajukan — ini DIIZINKAN
    // karena approval adalah wewenang jabatan, bukan personal
    $result = $this->policy->approve($this->dekan, $this->laporanDekan);

    expect($result)->toBeTrue('Dekan seharusnya bisa approve laporan sendiri dalam kapasitas jabatan');
});

// =====================================================================
// SKENARIO 4: Kepala LPPM bisa approve laporan
// =====================================================================
test('kepala lppm bisa approve laporan', function () {
    session(['active_role' => 'kepala lppm']);

    $result = $this->policy->approve($this->kepala, $this->laporan);

    expect($result)->toBeTrue();
});

// =====================================================================
// SKENARIO 5: Reviewer TIDAK bisa review laporan sendiri
// =====================================================================
test('reviewer tidak bisa review laporan sendiri — conflict of interest', function () {
    session(['active_role' => 'reviewer']);

    // Buat laporan milik reviewer sendiri
    $researchOwn = Research::factory()->create();
    $proposalOwn = Proposal::factory()->create([
        'submitter_id' => $this->reviewer->id,
        'detailable_type' => 'App\Models\Research',
        'detailable_id' => $researchOwn->id,
        'status' => ProposalStatus::COMPLETED,
    ]);
    $laporanOwn = ProgressReport::create([
        'proposal_id' => $proposalOwn->id,
        'reporting_period' => 'final',
        'reporting_year' => 2026,
        'status' => ReportStatus::SUBMITTED,
    ]);

    $result = $this->policy->review($this->reviewer, $laporanOwn);

    expect($result)->toBeFalse('Reviewer tidak boleh review laporan miliknya sendiri');
});

// =====================================================================
// SKENARIO 6: Reviewer BISA review laporan orang lain
// =====================================================================
test('reviewer bisa review laporan orang lain', function () {
    session(['active_role' => 'reviewer']);

    // Laporan milik dosen (bukan reviewer)
    $result = $this->policy->review($this->reviewer, $this->laporan);

    expect($result)->toBeTrue('Reviewer boleh review laporan orang lain');
});

// =====================================================================
// SKENARIO 7: User aktif sebagai dosen meski punya role dekan → tidak bisa approve
// =====================================================================
test('user multi-role yang sedang aktif sebagai dosen tidak bisa approve', function () {
    // Dekan switch ke active role dosen
    session(['active_role' => 'dosen']);

    $result = $this->policy->approve($this->dekan, $this->laporan);

    // Harus ditolak karena active_role = dosen, bukan dekan
    expect($result)->toBeFalse('User dengan active_role=dosen tidak boleh approve meski punya role dekan');
});

// =====================================================================
// SKENARIO 8: Dosen murni tidak bisa mengakses review (bukan reviewer)
// =====================================================================
test('dosen biasa tidak bisa review laporan karena bukan reviewer', function () {
    session(['active_role' => 'dosen']);

    $result = $this->policy->review($this->dosen, $this->laporan);

    expect($result)->toBeFalse('Dosen biasa tidak punya wewenang review');
});
