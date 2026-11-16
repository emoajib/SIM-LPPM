<?php

/**
 * Regression: BC-01 — kontrak bentuk data endpoint list modern.
 *
 * Semua endpoint yang dikonsumsi React wajib mengembalikan `data` berupa
 * JSON array (list). Object dengan key non-sekuensial membuat `data.map`
 * di React crash (halaman putih) — lihat bug Verifikasi Luaran 2026-11-16.
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

    $institution = Institution::first();
    $faculty = Faculty::factory()->create(['institution_id' => $institution->id]);
    $prodi = StudyProgram::factory()->create(['faculty_id' => $faculty->id]);

    $this->user = User::factory()->create();
    $this->user->assignRole('admin lppm');
    Identity::factory()->create([
        'user_id' => $this->user->id,
        'faculty_id' => $faculty->id,
        'study_program_id' => $prodi->id,
    ]);
});

it('mengembalikan data list untuk semua sub-modul proposals', function (string $module, string $submodule) {
    $response = $this->actingAs($this->user)
        ->get("/{$module}/api/v1/proposals?submodule={$submodule}&type={$module}");

    $response->assertOk();
    expect(array_is_list($response->json('data')))->toBeTrue();
    expect($response->json('data'))->toBeArray();
})->with([
    ['research', 'proposal'],
    ['research', 'revision'],
    ['research', 'final-report'],
    ['research', 'daily-note'],
    ['community-service', 'proposal'],
    ['community-service', 'revision'],
    ['community-service', 'final-report'],
    ['community-service', 'daily-note'],
]);

it('mengembalikan data list untuk endpoint verifikasi luaran', function (string $module) {
    $response = $this->actingAs($this->user)
        ->get("/{$module}/api/v1/verifikasi-luaran");

    $response->assertOk();
    expect(array_is_list($response->json('data')))->toBeTrue();
})->with(['research', 'community-service']);
