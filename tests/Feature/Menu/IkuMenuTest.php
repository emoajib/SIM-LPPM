<?php

/**
 * Regression: BC-01 — menu IKU tunggal.
 *
 * Menu IKU harus berupa tautan langsung ke /iku tanpa dropdown
 * (tidak ada "IKU & Luaran" / child "Dashboard IKU").
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

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin lppm');
    Identity::factory()->create([
        'user_id' => $this->admin->id,
        'faculty_id' => $faculty->id,
        'study_program_id' => $prodi->id,
    ]);
});

it('menampilkan IKU sebagai tautan langsung ke /iku', function () {
    $response = $this->actingAs($this->admin)
        ->withSession(['active_role' => 'admin lppm', 'sim_lppm_app_theme' => 'modern'])
        ->get('/dashboard');

    $response->assertOk();
    $response->assertSee('IKU');
    $response->assertDontSee('IKU &amp; Luaran', false);
    $response->assertDontSee('IKU & Luaran', false);
    $response->assertDontSee('Dashboard IKU');
    $response->assertSee(url('/iku'), false);
});

it('halaman /iku berjudul IKU', function () {
    $response = $this->actingAs($this->admin)
        ->withSession(['active_role' => 'admin lppm', 'sim_lppm_app_theme' => 'classic'])
        ->get('/iku');

    $response->assertOk();
    $response->assertSee('IKU');
    $response->assertDontSee('Dashboard IKU');
});
