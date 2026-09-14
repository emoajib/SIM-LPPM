<?php

namespace Tests\Feature;

use App\Livewire\Research\FinalReport\Show;
use App\Models\DocumentSignature;
use App\Models\Faculty;
use App\Models\Identity;
use App\Models\Institution;
use App\Models\Proposal;
use App\Models\ProposalOutput;
use App\Models\Research;
use App\Models\User;
use App\Services\DocumentSignatureService;
use Database\Seeders\InstitutionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Test dynamic additional output management (Feature 4) and verification page metadata (Feature 5).
 * Vetted by AI - Manual Review Required by Senior Engineer/Manager
 */
class DynamicAdditionalOutputTest extends TestCase
{
    use RefreshDatabase;

    protected User $dosen;

    protected Proposal $proposal;

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

        $this->dosen = User::factory()->create(['name' => 'Budi Santoso']);
        $this->dosen->assignRole('dosen');
        $this->dosen->markEmailAsVerified();

        Identity::factory()->create([
            'user_id' => $this->dosen->id,
            'faculty_id' => $faculty->id,
            'identity_id' => '0612345678',
            'title_prefix' => 'Dr.',
            'title_suffix' => 'M.Kom.',
        ]);

        $research = Research::factory()->create();
        $this->proposal = Proposal::factory()->create([
            'submitter_id' => $this->dosen->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'title' => 'Sistem Rekomendasi Pintar',
            'contract_number' => '01/LPPM/ITSNU/2026',
            'status' => 'completed',
        ]);
    }

    public function test_dosen_can_add_additional_output_from_final_report(): void
    {
        Livewire::actingAs($this->dosen)
            ->test(Show::class, ['proposal' => $this->proposal])
            ->call('openAddOutputModal')
            ->assertSet('showOutputModal', true)
            ->set('outputType', 'Jurnal Nas. Terakreditasi (Sinta 3-4)')
            ->set('outputYear', 1)
            ->set('outputTargetStatus', 'Published')
            ->set('outputDescription', 'Publikasi di Jurnal Sinta 3')
            ->call('saveProposalOutputPlan')
            ->assertSet('showOutputModal', false);

        $this->assertDatabaseHas('proposal_outputs', [
            'proposal_id' => $this->proposal->id,
            'category' => 'Tambahan',
            'type' => 'Jurnal Nas. Terakreditasi (Sinta 3-4)',
            'target_status' => 'Published',
        ]);
    }

    public function test_dosen_can_edit_additional_output_from_final_report(): void
    {
        $output = ProposalOutput::create([
            'proposal_id' => $this->proposal->id,
            'category' => 'Tambahan',
            'type' => 'Buku Referensi (ISBN)',
            'output_year' => 1,
            'target_status' => 'Draft',
        ]);

        Livewire::actingAs($this->dosen)
            ->test(Show::class, ['proposal' => $this->proposal])
            ->call('openEditProposalOutputModal', $output->id)
            ->assertSet('showOutputModal', true)
            ->assertSet('editingProposalOutputId', $output->id)
            ->set('outputTargetStatus', 'Published')
            ->call('saveProposalOutputPlan')
            ->assertSet('showOutputModal', false);

        $this->assertDatabaseHas('proposal_outputs', [
            'id' => $output->id,
            'target_status' => 'Published',
        ]);
    }

    public function test_dosen_can_delete_additional_output_from_final_report(): void
    {
        $output = ProposalOutput::create([
            'proposal_id' => $this->proposal->id,
            'category' => 'Tambahan',
            'type' => 'HKI Hak Cipta',
            'output_year' => 1,
            'target_status' => 'Draft',
        ]);

        Livewire::actingAs($this->dosen)
            ->test(Show::class, ['proposal' => $this->proposal])
            ->call('deleteProposalOutput', $output->id);

        $this->assertDatabaseMissing('proposal_outputs', [
            'id' => $output->id,
        ]);
    }

    public function test_external_assessor_can_verify_signature_without_login(): void
    {
        /** @var DocumentSignatureService $sigService */
        $sigService = app(DocumentSignatureService::class);

        $kid = $sigService->currentKid();
        $payload = [
            'ver' => 1,
            'doc_type' => Proposal::class,
            'doc_id' => (string) $this->proposal->id,
            'action' => 'approved',
            'signed_role' => 'kepala_lppm',
            'signed_by' => (string) $this->dosen->id,
            'signed_at' => now()->toIso8601String(),
            'pdf_hash' => hash('sha256', 'dummy content'),
            'kid' => $kid,
            'nonce' => 'test-nonce',
        ];

        $sig = $sigService->signPayload($payload, $kid);

        $documentSignature = DocumentSignature::create([
            'document_type' => Proposal::class,
            'document_id' => $this->proposal->id,
            'action' => 'approved',
            'signed_role' => 'kepala_lppm',
            'signed_by' => $this->dosen->id,
            'signed_at' => now(),
            'hash_alg' => 'sha256',
            'document_hash' => $payload['pdf_hash'],
            'kid' => $kid,
            'signature' => $sig,
            'payload' => $payload,
        ]);

        $url = URL::signedRoute('signatures.verify', ['documentSignature' => $documentSignature->id]);

        // Access as GUEST without authentication (simulating external BAN-PT / LAM assessor)
        $response = $this->get($url);

        $response->assertOk();
        $response->assertSee('STATUS: VALID');
        $response->assertSee('DOKUMEN RESMI TERVERIFIKASI');
        $response->assertSee('Dr. Budi Santoso, M.Kom.');
        $response->assertSee('Kepala LPPM');
        $response->assertSee('Sistem Rekomendasi Pintar');
        $response->assertSee('01/LPPM/ITSNU/2026');
        $response->assertSee('HMAC-SHA256');
    }
}
