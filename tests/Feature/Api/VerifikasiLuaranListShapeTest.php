<?php

use App\Models\CommunityService;
use App\Models\Faculty;
use App\Models\Identity;
use App\Models\Institution;
use App\Models\MandatoryOutput;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\ProposalOutput;
use App\Models\Research;
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

    $research = Research::factory()->create();
    $proposal = Proposal::factory()->create([
        'submitter_id' => $this->admin->id,
        'detailable_id' => $research->id,
        'detailable_type' => Research::class,
    ]);
    $report = ProgressReport::factory()->create([
        'proposal_id' => $proposal->id,
        'reporting_year' => (int) date('Y'),
        'reporting_period' => 'annual',
    ]);
    $pOutput = ProposalOutput::factory()->create(['category' => 'journal', 'type' => 'article']);
    MandatoryOutput::factory()->create([
        'progress_report_id' => $report->id,
        'proposal_output_id' => $pOutput->id,
        'is_verified' => false,
    ]);

    $community = CommunityService::factory()->create();
    $proposal2 = Proposal::factory()->create([
        'submitter_id' => $this->admin->id,
        'detailable_id' => $community->id,
        'detailable_type' => CommunityService::class,
    ]);
    $report2 = ProgressReport::factory()->create([
        'proposal_id' => $proposal2->id,
        'reporting_year' => (int) date('Y'),
        'reporting_period' => 'annual',
    ]);
    $pOutput2 = ProposalOutput::factory()->create(['category' => 'journal', 'type' => 'article']);
    MandatoryOutput::factory()->create([
        'progress_report_id' => $report2->id,
        'proposal_output_id' => $pOutput2->id,
        'is_verified' => false,
    ]);
});

it('mengembalikan data berupa list (berisi, 2 modul)', function () {
    $r = $this->actingAs($this->admin)->get('/research/api/v1/verifikasi-luaran');
    $r->assertOk();
    expect(array_is_list($r->json('data')))->toBeTrue();
    expect(count($r->json('data')))->toBeGreaterThan(0);

    $r = $this->actingAs($this->admin)->get('/community-service/api/v1/verifikasi-luaran');
    $r->assertOk();
    expect(array_is_list($r->json('data')))->toBeTrue();
});

it('pagination tetap list', function () {
    $r = $this->actingAs($this->admin)->get('/research/api/v1/verifikasi-luaran?page=1&per_page=1');
    $r->assertOk();
    expect(array_is_list($r->json('data')))->toBeTrue();
    expect($r->json('meta.current_page'))->toBe(1);
});
