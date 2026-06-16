<?php

namespace Tests\Feature;

use App\Enums\ProposalStatus;
use App\Models\Faculty;
use App\Models\Identity;
use App\Models\Institution;
use App\Models\Proposal;
use App\Models\ProposalStatusLog;
use App\Models\Research;
use App\Models\User;
use Database\Seeders\InstitutionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProposalSignatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $dosen;

    protected User $dekan;

    protected User $kepalaLppm;

    protected function setUp(): void
    {
        parent::setUp();

        if (! file_exists(storage_path('app/.installed'))) {
            file_put_contents(storage_path('app/.installed'), '');
        }

        config(['document-signatures.current_kid' => 'v1']);
        config(['document-signatures.keys.v1' => 'test-secret-standard-itsnu']);

        $this->seed(RoleSeeder::class);
        $this->seed(InstitutionSeeder::class);

        $institution = Institution::first();
        $faculty = Faculty::factory()->create(['institution_id' => $institution->id]);

        $this->dosen = User::factory()->create(['name' => 'Dosen']);
        $this->dosen->assignRole('dosen');
        $this->dosen->markEmailAsVerified();
        Identity::factory()->create(['user_id' => $this->dosen->id, 'faculty_id' => $faculty->id]);

        $this->dekan = User::factory()->create(['name' => 'Dekan']);
        $this->dekan->assignRole('dekan');
        $this->dekan->markEmailAsVerified();
        $idn = Identity::factory()->create(['user_id' => $this->dekan->id, 'faculty_id' => $faculty->id]);
        $faculty->update([
            'dean_id' => $idn->id,
            'dean_user_id' => $this->dekan->id,
        ]);

        $this->kepalaLppm = User::factory()->create(['name' => 'Kepala LPPM']);
        $this->kepalaLppm->assignRole('kepala lppm');
        $this->kepalaLppm->markEmailAsVerified();
        Identity::factory()->create(['user_id' => $this->kepalaLppm->id, 'faculty_id' => $faculty->id]);
    }

    public function test_export_proposal_creates_digital_signatures()
    {
        $research = Research::factory()->create();
        $proposal = Proposal::factory()->create([
            'submitter_id' => $this->dosen->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'status' => ProposalStatus::SUBMITTED,
        ]);

        $this->actingAs($this->dosen);

        $response = $this->get(route('proposals.export-pdf', $proposal));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');

        // Verify DocumentSignature created for lecturer
        $this->assertDatabaseHas('document_signatures', [
            'document_id' => $proposal->id,
            'signed_role' => 'lecturer',
            'action' => 'submitted',
        ]);

        // Verify signature has signed_at (important for QR code display)
        $signature = $proposal->signatures()->where('signed_role', 'lecturer')->where('action', 'submitted')->first();
        $this->assertNotNull($signature->signed_at, 'Signature signed_at should not be null');
    }

    public function test_draft_proposal_has_no_lecturer_signature()
    {
        $research = Research::factory()->create();
        $proposal = Proposal::factory()->create([
            'submitter_id' => $this->dosen->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'status' => ProposalStatus::DRAFT,
        ]);

        $this->actingAs($this->dosen);

        $response = $this->get(route('proposals.export-pdf', $proposal));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');

        // Verify NO DocumentSignature created for lecturer when draft
        $this->assertDatabaseMissing('document_signatures', [
            'document_id' => $proposal->id,
            'document_type' => get_class($proposal),
            'signed_role' => 'lecturer',
            'action' => 'submitted',
        ]);
    }

    public function test_approved_proposal_has_dekan_signature()
    {
        $research = Research::factory()->create();
        $proposal = Proposal::factory()->create([
            'submitter_id' => $this->dosen->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'status' => ProposalStatus::APPROVED,
        ]);

        // Mock status log for dekan approval
        ProposalStatusLog::create([
            'proposal_id' => $proposal->id,
            'status_before' => ProposalStatus::SUBMITTED,
            'status_after' => ProposalStatus::APPROVED,
            'at' => now(),
            'user_id' => $this->dekan->id,
        ]);

        $this->actingAs($this->dosen);

        $this->get(route('proposals.export-pdf', $proposal));

        $this->assertDatabaseHas('document_signatures', [
            'document_id' => $proposal->id,
            'signed_role' => 'dekan',
            'action' => 'approved',
        ]);

        $this->assertDatabaseHas('document_signatures', [
            'document_id' => $proposal->id,
            'signed_role' => 'lecturer',
            'action' => 'submitted',
        ]);
    }

    public function test_revision_needed_proposal_has_no_lecturer_signature()
    {
        $research = Research::factory()->create();
        $proposal = Proposal::factory()->create([
            'submitter_id' => $this->dosen->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'status' => ProposalStatus::REVISION_NEEDED,
        ]);

        $this->actingAs($this->dosen);

        $response = $this->get(route('proposals.export-pdf', $proposal));

        $response->assertStatus(200);

        // Verify NO DocumentSignature created for lecturer when revision_needed
        $this->assertDatabaseMissing('document_signatures', [
            'document_id' => $proposal->id,
            'document_type' => get_class($proposal),
            'signed_role' => 'lecturer',
            'action' => 'submitted',
        ]);
    }

    public function test_lecturer_signed_at_uses_status_log_timestamp()
    {
        $research = Research::factory()->create();
        $proposal = Proposal::factory()->create([
            'submitter_id' => $this->dosen->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'status' => ProposalStatus::SUBMITTED,
            'created_at' => now()->subDays(7),
        ]);

        $submittedAt = now()->subDay();
        ProposalStatusLog::create([
            'proposal_id' => $proposal->id,
            'user_id' => $this->dosen->id,
            'status_before' => ProposalStatus::DRAFT,
            'status_after' => ProposalStatus::SUBMITTED,
            'at' => $submittedAt,
        ]);

        $this->actingAs($this->dosen);
        $this->get(route('proposals.export-pdf', $proposal));

        $signature = $proposal->signatures()
            ->where('signed_role', 'lecturer')
            ->where('action', 'submitted')
            ->first();

        $this->assertNotNull($signature);
        $this->assertNotNull($signature->signed_at);

        $this->assertEquals(
            $submittedAt->format('Y-m-d H:i:s'),
            $signature->signed_at->format('Y-m-d H:i:s'),
            'signed_at harus dari status log, bukan created_at'
        );

        $this->assertNotEquals(
            $proposal->created_at->format('Y-m-d H:i:s'),
            $signature->signed_at->format('Y-m-d H:i:s'),
            'signed_at tidak boleh sama dengan created_at'
        );
    }

    public function test_lecturer_signed_at_falls_back_to_created_at_when_no_status_log()
    {
        $research = Research::factory()->create();
        $proposal = Proposal::factory()->create([
            'submitter_id' => $this->dosen->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'status' => ProposalStatus::SUBMITTED,
            'created_at' => now()->subDays(30),
        ]);

        $this->actingAs($this->dosen);
        $this->get(route('proposals.export-pdf', $proposal));

        $signature = $proposal->signatures()
            ->where('signed_role', 'lecturer')
            ->where('action', 'submitted')
            ->first();

        $this->assertNotNull($signature);
        $this->assertNotNull($signature->signed_at);

        $this->assertEquals(
            $proposal->created_at->format('Y-m-d H:i:s'),
            $signature->signed_at->format('Y-m-d H:i:s'),
            'signed_at harus fallback ke created_at ketika tidak ada status log'
        );
    }

    public function test_lecturer_signed_at_uses_latest_submission_log()
    {
        $research = Research::factory()->create();
        $proposal = Proposal::factory()->create([
            'submitter_id' => $this->dosen->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'status' => ProposalStatus::SUBMITTED,
            'created_at' => now()->subDays(30),
        ]);

        $firstSubmitAt = now()->subDays(20);
        ProposalStatusLog::create([
            'proposal_id' => $proposal->id,
            'user_id' => $this->dosen->id,
            'status_before' => ProposalStatus::DRAFT,
            'status_after' => ProposalStatus::SUBMITTED,
            'at' => $firstSubmitAt,
        ]);

        $secondSubmitAt = now()->subDay();
        ProposalStatusLog::create([
            'proposal_id' => $proposal->id,
            'user_id' => $this->dosen->id,
            'status_before' => ProposalStatus::REVISION_NEEDED,
            'status_after' => ProposalStatus::REVISION_SUBMITTED,
            'at' => $secondSubmitAt,
        ]);

        $this->actingAs($this->dosen);
        $this->get(route('proposals.export-pdf', $proposal));

        $signature = $proposal->signatures()
            ->where('signed_role', 'lecturer')
            ->where('action', 'submitted')
            ->first();

        $this->assertNotNull($signature);
        $this->assertNotNull($signature->signed_at);

        $this->assertEquals(
            $firstSubmitAt->format('Y-m-d H:i:s'),
            $signature->signed_at->format('Y-m-d H:i:s'),
            'signed_at tetap menggunakan submission log SUBMITTED terbaru,'
            .' bukan REVISION_SUBMITTED'
        );
    }
}
