<?php

namespace Tests\Feature;

use App\Livewire\Forms\ProposalForm;
use App\Livewire\Forms\TeamMembersForm;
use App\Models\BudgetCap;
use App\Models\Identity;
use App\Models\Institution;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\StudyProgram;
use App\Models\User;
use App\Support\AcademicPeriod;
use Database\Seeders\InstitutionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Component;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AcademicPeriodAndTeamMemberTest extends TestCase
{
    use RefreshDatabase;

    protected User $ketua;

    protected StudyProgram $prodi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed([RoleSeeder::class, InstitutionSeeder::class]);

        $institution = Institution::first();

        $this->prodi = StudyProgram::create([
            'institution_id' => $institution->id,
            'name' => 'Teknik Informatika',
            'code' => 'TI01',
            'strata' => 'S1',
        ]);

        $this->ketua = User::factory()->create([
            'name' => 'Ketua Peneliti',
            'email' => 'ketua@itsnu.ac.id',
        ]);
        $this->ketua->assignRole('dosen');

        Identity::create([
            'user_id' => $this->ketua->id,
            'identity_id' => '1111111101',
            'type' => 'dosen',
            'institution_id' => $institution->id,
            'study_program_id' => $this->prodi->id,
        ]);
    }

    public function test_academic_year_accessors_and_parser(): void
    {
        $research = Research::create([
            'tkt_type' => 'Software',
            'tkt_current' => 2,
            'tkt_target' => 3,
        ]);

        $ganjil = Proposal::create([
            'title' => 'Usulan Ganjil',
            'submitter_id' => $this->ketua->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'duration_in_years' => 1,
            'start_year' => 2026,
            'semester' => 'ganjil',
            'summary' => 'Ringkasan usulan ganjil untuk pengujian periode akademik.',
        ]);

        $this->assertSame('2026/2027', $ganjil->academic_year);
        $this->assertSame(2026, $ganjil->academic_year_start);
        $this->assertSame('1 Sep 2026 - 28/29 Feb 2027', $ganjil->semester_period_label);

        $genap = Proposal::create([
            'title' => 'Usulan Genap',
            'submitter_id' => $this->ketua->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'duration_in_years' => 1,
            'start_year' => 2027,
            'semester' => 'genap',
            'summary' => 'Ringkasan usulan genap untuk pengujian periode akademik.',
        ]);

        $this->assertSame('2026/2027', $genap->academic_year);
        $this->assertSame(2026, $genap->academic_year_start);
        $this->assertSame('1 Mar - 31 Agt 2027', $genap->semester_period_label);

        $this->assertSame(2026, Proposal::parseAcademicYear('2026/2027'));
        $this->assertNull(Proposal::parseAcademicYear('2026/2028'));
        $this->assertNull(Proposal::parseAcademicYear('2026'));
    }

    public function test_archive_scope_and_academic_year_filter_are_isolated(): void
    {
        $research = Research::create([
            'tkt_type' => 'Software',
            'tkt_current' => 2,
            'tkt_target' => 3,
        ]);

        $active = Proposal::create([
            'title' => 'Usulan Aktif',
            'submitter_id' => $this->ketua->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'duration_in_years' => 1,
            'start_year' => 2026,
            'semester' => 'ganjil',
            'summary' => 'Usulan operasional.',
        ]);

        $archive = Proposal::create([
            'title' => 'Usulan Arsip',
            'submitter_id' => $this->ketua->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'duration_in_years' => 1,
            'start_year' => 2027,
            'semester' => 'ganjil',
            'is_archive_only' => true,
            'summary' => 'Usulan historis.',
        ]);

        $this->assertFalse(Proposal::query()->whereKey($archive->id)->exists());
        $this->assertTrue(Proposal::withArchived()->whereKey($archive->id)->exists());
        $this->assertSame([$archive->id], Proposal::onlyArchived()->pluck('id')->all());

        $this->assertFalse(Proposal::query()->forAcademicYear('2027/2028')->whereKey($archive->id)->exists());
        $this->assertTrue(Proposal::withArchived()->forAcademicYear('2027/2028')->whereKey($archive->id)->exists());
        $this->assertSame([$active->id], Proposal::query()->forAcademicYear('2026/2027')->pluck('id')->all());
    }

    public function test_genap_and_ganjil_share_start_year_but_stay_separate_academic_years(): void
    {
        $research = Research::create([
            'tkt_type' => 'Software',
            'tkt_current' => 2,
            'tkt_target' => 3,
        ]);

        $ganjil = Proposal::create([
            'title' => 'Usulan Ganjil 2026',
            'submitter_id' => $this->ketua->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'duration_in_years' => 1,
            'start_year' => 2026,
            'semester' => 'ganjil',
            'summary' => 'Ringkasan usulan ganjil pada start_year yang sama.',
        ]);

        $genap = Proposal::create([
            'title' => 'Usulan Genap 2025/2026',
            'submitter_id' => $this->ketua->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'duration_in_years' => 1,
            'start_year' => 2026,
            'semester' => 'genap',
            'summary' => 'Ringkasan usulan genap pada start_year yang sama.',
        ]);

        $legacy = Proposal::create([
            'title' => 'Usulan Tanpa Semester',
            'submitter_id' => $this->ketua->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'duration_in_years' => 1,
            'start_year' => 2026,
            'semester' => null,
            'summary' => 'Ringkasan usulan lama tanpa semester.',
        ]);

        $this->assertSame('2026/2027', $ganjil->academic_year);
        $this->assertSame('2025/2026', $genap->academic_year);
        $this->assertSame('2026/2027', $legacy->academic_year);

        $ganjilIds = Proposal::query()->forAcademicYear('2026/2027')->pluck('id')->all();
        $genapIds = Proposal::query()->forAcademicYear('2025/2026')->pluck('id')->all();

        sort($ganjilIds);
        sort($genapIds);

        $expectedGanjil = [$ganjil->id, $legacy->id];
        sort($expectedGanjil);

        $this->assertSame($expectedGanjil, $ganjilIds);
        $this->assertSame([$genap->id], $genapIds);

        $this->assertSame(
            ['2026/2027', '2025/2026'],
            AcademicPeriod::optionsFromPeriods([
                ['start_year' => 2026, 'semester' => 'ganjil'],
                ['start_year' => 2026, 'semester' => 'genap'],
            ])
        );
    }

    public function test_budget_cap_prefers_academic_period_and_falls_back(): void
    {
        $academic = BudgetCap::create([
            'year' => 2026,
            'semester' => 'genap',
            'research_budget_cap' => 9_000_000,
        ]);

        $legacy = BudgetCap::create([
            'year' => 2027,
            'semester' => 'genap',
            'research_budget_cap' => 5_000_000,
        ]);

        $this->assertSame($academic->id, BudgetCap::findForPeriod(2027, 'genap')?->id);
        $this->assertSame('2026/2027', BudgetCap::academicYearLabel(2027, 'genap'));

        $academic->delete();

        $this->assertSame($legacy->id, BudgetCap::findForPeriod(2027, 'genap')?->id);
    }

    public function test_team_member_form_defaults_to_external_and_allows_empty_email(): void
    {
        $this->actingAs($this->ketua);

        Livewire::test(TeamMembersForm::class)
            ->call('toggleManual')
            ->assertSet('memberFound', true)
            ->assertSet('member_type', 'dosen')
            ->set('member_nidn', '9999999909')
            ->set('member_name', 'Anggota Eksternal')
            ->set('member_email', '')
            ->set('member_institution', 'Universitas Lain')
            ->set('member_study_program', 'Informatika')
            ->set('member_tugas', 'Membantu analisis data')
            ->call('addMember')
            ->assertCount('members', 1)
            ->assertSet('members.0.email', '')
            ->assertSet('members.0.role', 'anggota')
            ->assertSet('members.0.identity_type', 'dosen');
    }

    public function test_manual_member_without_email_gets_placeholder_shadow_user(): void
    {
        $research = Research::create([
            'tkt_type' => 'Software',
            'tkt_current' => 2,
            'tkt_target' => 3,
        ]);

        $proposal = Proposal::create([
            'title' => 'Usulan dengan Anggota Eksternal',
            'submitter_id' => $this->ketua->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'duration_in_years' => 1,
            'start_year' => 2026,
            'semester' => 'ganjil',
            'summary' => 'Ringkasan usulan untuk pengujian anggota eksternal tanpa email.',
        ]);

        $component = new class extends Component
        {
            public function render()
            {
                return '<div></div>';
            }
        };

        $form = new ProposalForm($component, 'form');
        $form->proposal = $proposal;
        $form->author_tasks = 'Penanggung jawab utama';
        $form->members = [[
            'name' => 'Anggota Eksternal',
            'nidn' => '8888888808',
            'tugas' => 'Membantu analisis',
            'role' => 'anggota',
            'identity_type' => 'dosen',
            'is_manual' => true,
            'study_program' => 'Teknik Informatika',
            'institution' => 'ITSNU Pekalongan',
            'email' => '',
        ]];

        $method = new \ReflectionMethod(ProposalForm::class, 'attachTeamMembers');
        $method->setAccessible(true);
        $method->invoke($form, $proposal, (string) $this->ketua->id);

        $shadow = User::where('email', 'external-8888888808@no-email.local')->first();

        $this->assertNotNull($shadow);
        $this->assertSame('Anggota Eksternal', $shadow->name);
        $this->assertSame('8888888808', $shadow->identity?->identity_id);
        $this->assertSame($this->prodi->id, $shadow->identity?->study_program_id);
        $this->assertTrue($proposal->refresh()->teamMembers->contains($shadow->id));
    }

    public function test_proposal_form_syncs_academic_and_start_year(): void
    {
        $component = new class extends Component
        {
            public function render()
            {
                return '<div></div>';
            }
        };

        $form = new ProposalForm($component, 'form');

        $form->setAcademicPeriod(2026, 'genap');
        $this->assertSame('2026/2027', $form->academic_year);
        $this->assertSame('2027', $form->start_year);

        $form->semester = 'ganjil';
        $form->start_year = '2026';
        $form->updatedStartYear();
        $this->assertSame('2026/2027', $form->academic_year);
    }
}
