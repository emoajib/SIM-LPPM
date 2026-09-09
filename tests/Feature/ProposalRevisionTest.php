<?php

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

namespace Tests\Feature;

use App\Enums\ProposalStatus;
use App\Enums\ReviewStatus;
use App\Livewire\Research\ProposalRevision\Show;
use App\Models\MacroResearchGroup;
use App\Models\Proposal;
use App\Models\ProposalReviewer;
use App\Models\Research;
use App\Models\ResearchScheme;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\InstitutionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProposalRevisionTest extends TestCase
{
    use RefreshDatabase;

    protected User $dosen;

    protected User $admin;

    protected Proposal $proposal;

    protected Research $research;

    protected ResearchScheme $scheme;

    protected MacroResearchGroup $macroGroup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(InstitutionSeeder::class);

        $this->dosen = User::factory()->create(['name' => 'Dosen Peneliti']);
        $this->dosen->assignRole('dosen');

        $this->admin = User::factory()->create(['name' => 'Admin LPPM']);
        $this->admin->assignRole('admin lppm');

        $this->scheme = ResearchScheme::firstOrCreate(
            ['name' => 'Skema Dasar'],
            ['strata' => 'binaan', 'duration_in_years' => 1]
        );

        $this->macroGroup = MacroResearchGroup::firstOrCreate(
            ['name' => 'Kesehatan & Pangan'],
            ['code' => 'KP']
        );

        $this->research = Research::create([
            'macro_research_group_id' => $this->macroGroup->id,
            'tkt_type' => 'Software',
            'background' => 'Background text',
            'methodology' => 'Methodology text',
        ]);

        $this->proposal = Proposal::create([
            'title' => 'Proposal Riset Inovasi',
            'status' => ProposalStatus::REVISION_NEEDED,
            'submitter_id' => $this->dosen->id,
            'detailable_type' => Research::class,
            'detailable_id' => $this->research->id,
            'research_scheme_id' => $this->scheme->id,
            'semester' => 'ganjil',
            'start_year' => 2026,
            'duration_in_years' => 1,
        ]);
    }

    public function test_read_only_user_can_navigate_steps_without_validation_error()
    {
        // Admin LPPM is not the submitter -> canEdit() is false
        $this->actingAs($this->admin);
        Session::put('active_role', 'admin lppm');

        Livewire::test(Show::class, ['proposal' => $this->proposal])
            ->assertSet('currentStep', 1)
            ->call('setStep', 2)
            ->assertHasNoErrors()
            ->assertSet('currentStep', 2);
    }

    public function test_submitter_can_navigate_steps_in_read_only_when_schedule_is_closed()
    {
        // Set revision dates in the past so isRevisionOpen() returns false
        Setting::updateOrCreate(
            ['key' => 'research_revision_start_date'],
            ['value' => now()->subDays(10)->toDateString()]
        );
        Setting::updateOrCreate(
            ['key' => 'research_revision_end_date'],
            ['value' => now()->subDays(2)->toDateString()]
        );

        $this->actingAs($this->dosen);
        Session::put('active_role', 'dosen');

        Livewire::test(Show::class, ['proposal' => $this->proposal])
            ->assertSet('currentStep', 1)
            ->call('setStep', 2)
            ->assertHasNoErrors()
            ->assertSet('currentStep', 2);
    }

    public function test_submitter_can_advance_to_step_2_when_all_reviewers_approved_and_media_exists()
    {
        // Set revision schedule open
        Setting::updateOrCreate(
            ['key' => 'research_revision_start_date'],
            ['value' => now()->subDays(1)->toDateString()]
        );
        Setting::updateOrCreate(
            ['key' => 'research_revision_end_date'],
            ['value' => now()->addDays(5)->toDateString()]
        );

        // Add a fake substance media file
        Storage::fake('public');
        $this->research->addMedia(UploadedFile::fake()->createWithContent('substansi.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF"))
            ->toMediaCollection('substance_file');

        // Add completed reviewer with 'approved' recommendation
        $reviewerUser = User::factory()->create();
        $reviewerUser->assignRole('reviewer');
        ProposalReviewer::create([
            'proposal_id' => $this->proposal->id,
            'user_id' => $reviewerUser->id,
            'status' => ReviewStatus::COMPLETED,
            'recommendation' => 'approved',
            'review_notes' => 'Proposal sangat baik',
        ]);

        $this->actingAs($this->dosen);
        Session::put('active_role', 'dosen');

        Livewire::test(Show::class, ['proposal' => $this->proposal])
            ->assertSet('currentStep', 1)
            ->call('setStep', 2)
            ->assertHasNoErrors(['substanceFile'])
            ->assertSet('currentStep', 2);
    }

    public function test_submitter_is_blocked_when_reviewer_requested_revision_and_no_file_uploaded()
    {
        // Set revision schedule open
        Setting::updateOrCreate(
            ['key' => 'research_revision_start_date'],
            ['value' => now()->subDays(1)->toDateString()]
        );
        Setting::updateOrCreate(
            ['key' => 'research_revision_end_date'],
            ['value' => now()->addDays(5)->toDateString()]
        );

        // Add completed reviewer with 'revision_needed' recommendation
        $reviewerUser = User::factory()->create();
        $reviewerUser->assignRole('reviewer');
        ProposalReviewer::create([
            'proposal_id' => $this->proposal->id,
            'user_id' => $reviewerUser->id,
            'status' => ReviewStatus::COMPLETED,
            'recommendation' => 'revision_needed',
            'review_notes' => 'Harap perbaiki metode penelitian',
        ]);

        $this->actingAs($this->dosen);
        Session::put('active_role', 'dosen');

        Livewire::test(Show::class, ['proposal' => $this->proposal])
            ->assertSet('currentStep', 1)
            ->call('setStep', 2)
            ->assertHasErrors(['substanceFile'])
            ->assertSet('currentStep', 1);
    }
}
