<?php

namespace Tests\Feature;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

use App\Enums\ProposalStatus;
use App\Enums\ProposalUserStatus;
use App\Livewire\Forms\ProposalForm;
use App\Livewire\Forms\TeamMembersForm;
use App\Models\FocusArea;
use App\Models\Identity;
use App\Models\Institution;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\ResearchScheme;
use App\Models\ScienceCluster;
use App\Models\StudyProgram;
use App\Models\Theme;
use App\Models\Topic;
use App\Models\User;
use App\Services\EligibilityService;
use Database\Seeders\InstitutionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProposalTeamMemberRoleCheckTest extends TestCase
{
    use RefreshDatabase;

    protected User $ketua;

    protected User $anggotaDosen;

    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed([RoleSeeder::class, InstitutionSeeder::class]);

        $this->institution = Institution::first();

        $prodi = StudyProgram::create([
            'institution_id' => $this->institution->id,
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
            'institution_id' => $this->institution->id,
            'study_program_id' => $prodi->id,
        ]);

        $this->anggotaDosen = User::factory()->create([
            'name' => 'Anggota Dosen',
            'email' => 'anggota@itsnu.ac.id',
        ]);
        $this->anggotaDosen->assignRole('dosen');

        Identity::create([
            'user_id' => $this->anggotaDosen->id,
            'identity_id' => '2222222202',
            'type' => 'dosen',
            'institution_id' => $this->institution->id,
            'study_program_id' => $prodi->id,
        ]);
    }

    public function test_team_members_form_sets_valid_role_anggota_for_search_and_manual(): void
    {
        $this->actingAs($this->ketua);

        // 1. Test search NIDN addition
        Livewire::test(TeamMembersForm::class)
            ->set('member_nidn', '2222222202')
            ->call('checkMember')
            ->set('member_tugas', 'Membantu analisa data')
            ->call('addMember')
            ->assertSet('members.0.role', 'anggota')
            ->assertSet('members.0.identity_type', 'dosen');

        // 2. Test manual addition (mahasiswa)
        Livewire::test(TeamMembersForm::class)
            ->call('toggleManual')
            ->set('member_nidn', 'MHS001')
            ->set('member_type', 'mahasiswa')
            ->set('member_name', 'Budi Mahasiswa')
            ->set('member_email', 'budi@mhs.itsnu.ac.id')
            ->set('member_institution', 'ITSNU Pekalongan')
            ->set('member_study_program', 'Teknik Informatika')
            ->set('member_tugas', 'Pengumpulan data primer')
            ->call('addMember')
            ->assertSet('members.0.role', 'anggota')
            ->assertSet('members.0.identity_type', 'mahasiswa');
    }

    public function test_proposal_form_normalizes_member_roles_and_avoids_db_constraint_failure(): void
    {
        $scheme = ResearchScheme::create([
            'name' => 'Penelitian Dosen Pemula',
            'code' => 'PDP',
            'strata' => 'dasar',
            'description' => 'Skema PDP',
        ]);
        $cluster = ScienceCluster::create(['name' => 'Ilmu Komputer', 'level' => 1]);
        $focusArea = FocusArea::create(['name' => 'TI']);
        $theme = Theme::create(['focus_area_id' => $focusArea->id, 'name' => 'AI']);
        $topic = Topic::create(['theme_id' => $theme->id, 'name' => 'Machine Learning']);

        $research = Research::create([
            'tkt_type' => 'Software',
            'tkt_current' => 2,
            'tkt_target' => 3,
        ]);

        $proposal = Proposal::create([
            'title' => 'Implementasi AI dalam LPPM',
            'submitter_id' => $this->ketua->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'research_scheme_id' => $scheme->id,
            'focus_area_id' => $focusArea->id,
            'theme_id' => $theme->id,
            'topic_id' => $topic->id,
            'cluster_level1_id' => $cluster->id,
            'status' => ProposalStatus::DRAFT->value,
            'duration_in_years' => 1,
            'start_year' => 2026,
            'semester' => 'ganjil',
            'summary' => 'Ringkasan penelitian untuk pengujian sistem usulan LPPM',
        ]);

        // Concrete dummy component to instantiate ProposalForm
        $dummyComponent = new class extends Component
        {
            public function render()
            {
                return '<div></div>';
            }
        };

        $form = new ProposalForm($dummyComponent, 'form');
        $form->proposal = $proposal;
        $form->author_tasks = 'Penanggung jawab utama penelitian';
        $form->members = [
            [
                'name' => $this->anggotaDosen->name,
                'nidn' => '2222222202',
                'tugas' => 'MEMBANTU KETUA PENELITI',
                'role' => 'dosen', // incoming legacy/invalid role
                'identity_type' => 'dosen',
                'status' => 'pending',
            ],
            [
                'name' => 'Mahasiswa Test',
                'nidn' => 'MHS999',
                'tugas' => 'Membantu observasi',
                'role' => 'mahasiswa',
                'identity_type' => 'mahasiswa',
                'is_manual' => true,
                'study_program' => 'TI',
                'institution' => 'ITSNU Pekalongan',
            ],
        ];

        // Save draft via reflection on private attachTeamMembers
        $method = new \ReflectionMethod(ProposalForm::class, 'attachTeamMembers');
        $method->setAccessible(true);

        // This must NOT throw SQLSTATE[23000] proposal_user_role_check constraint failure
        $method->invoke($form, $proposal, $this->ketua->id);

        // Verify ketua record in proposal_user
        $ketuaRow = DB::table('proposal_user')
            ->where('proposal_id', $proposal->id)
            ->where('user_id', $this->ketua->id)
            ->first();
        $this->assertNotNull($ketuaRow);
        $this->assertEquals('ketua', $ketuaRow->role);

        // Verify anggota record in proposal_user: role must be 'anggota', not 'dosen'
        $anggotaRow = DB::table('proposal_user')
            ->where('proposal_id', $proposal->id)
            ->where('user_id', $this->anggotaDosen->id)
            ->first();
        $this->assertNotNull($anggotaRow);
        $this->assertEquals('anggota', $anggotaRow->role);

        // Verify mahasiswa is in student_members JSON, not in proposal_user
        $proposal->refresh();
        $this->assertNotEmpty($proposal->student_members);
        $this->assertEquals('Mahasiswa Test', $proposal->student_members[0]['name']);

        $mhsRow = DB::table('proposal_user')
            ->where('proposal_id', $proposal->id)
            ->where('tasks', 'Membantu observasi')
            ->first();
        $this->assertNull($mhsRow);
    }

    public function test_eligibility_service_counts_anggota_role(): void
    {
        $scheme = ResearchScheme::create([
            'name' => 'Penelitian Fundamental',
            'code' => 'PF',
            'strata' => 'dasar',
        ]);
        $research = Research::create([]);
        $proposal = Proposal::create([
            'title' => 'Proposal Riset Aktif',
            'submitter_id' => $this->ketua->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'research_scheme_id' => $scheme->id,
            'status' => ProposalStatus::SUBMITTED->value,
            'duration_in_years' => 1,
            'start_year' => 2026,
            'semester' => 'ganjil',
            'summary' => 'Ringkasan proposal aktif',
        ]);

        // Ketua role in proposal_user
        DB::table('proposal_user')->insert([
            'proposal_id' => $proposal->id,
            'user_id' => $this->ketua->id,
            'role' => 'ketua',
            'status' => ProposalUserStatus::ACCEPTED->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Anggota role in proposal_user
        DB::table('proposal_user')->insert([
            'proposal_id' => $proposal->id,
            'user_id' => $this->anggotaDosen->id,
            'role' => 'anggota',
            'status' => ProposalUserStatus::ACCEPTED->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(EligibilityService::class);
        $quotaKetua = $service->canCreateProposal($this->ketua, 'research')['quota_info'];
        $quotaAnggota = $service->canCreateProposal($this->anggotaDosen, 'research')['quota_info'];

        // Ketua has 1 as head, 0 as member
        $this->assertEquals(1, $quotaKetua['head_current']);
        $this->assertEquals(0, $quotaKetua['member_current']);

        // Anggota has 0 as head, 1 as member
        $this->assertEquals(0, $quotaAnggota['head_current']);
        $this->assertEquals(1, $quotaAnggota['member_current']);
    }
}
