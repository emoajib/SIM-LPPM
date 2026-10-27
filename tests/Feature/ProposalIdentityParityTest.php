<?php

namespace Tests\Feature;

use App\Enums\ProposalStatus;
use App\Models\CommunityService;
use App\Models\Keyword;
use App\Models\MasterIku;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\Sdg;
use App\Models\User;
use Database\Seeders\InstitutionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProposalIdentityParityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(InstitutionSeeder::class);
    }

    private function makeProposal(string $type): Proposal
    {
        $detail = $type === 'research' ? Research::factory()->create() : CommunityService::factory()->create();
        $submitter = User::factory()->create(['name' => 'Pengusul Uji']);

        $proposal = Proposal::factory()->create([
            'submitter_id' => $submitter->id,
            'detailable_id' => $detail->id,
            'detailable_type' => $detail::class,
            'status' => ProposalStatus::SUBMITTED,
            'summary' => 'Ringkasan uji paritas identitas.',
        ]);

        $proposal->keywords()->attach(Keyword::create(['name' => 'KataKunciUji']));
        $proposal->sdgs()->attach(Sdg::create(['name' => 'SDGUji', 'description' => 'Deskripsi SDG uji']));
        $proposal->targetedIkus()->attach(MasterIku::create([
            'code' => 'IKU-UJI',
            'name' => 'IKU Uji',
            'description' => 'Deskripsi IKU uji',
        ]));
        $proposal->teamMembers()->attach($submitter, [
            'role' => 'ketua',
            'tasks' => 'TugasKetuaUji',
            'status' => 'accepted',
        ]);

        return $proposal->fresh();
    }

    public function test_research_detail_renders_all_form_sections(): void
    {
        $html = view('livewire.proposals.components.identitas-usulan-detail', [
            'proposal' => $this->makeProposal('research'),
            'type' => 'research',
            'mode' => 'detail',
        ])->render();

        $this->assertStringContainsString('1.1 Informasi Dasar Proposal', $html);
        $this->assertStringContainsString('1.2 Rumpun Ilmu', $html);
        $this->assertStringContainsString('1.3 Ringkasan & Kata Kunci', $html);
        $this->assertStringContainsString('1.4 Sustainable Development Goals (SDGs)', $html);
        $this->assertStringContainsString('1.5 Pemetaan Target IKU', $html);
        $this->assertStringContainsString('Detail Penelitian (TKT)', $html);
        $this->assertStringContainsString('KataKunciUji', $html);
        $this->assertStringContainsString('SDGUji', $html);
        $this->assertStringContainsString('IKU-UJI', $html);
    }

    public function test_community_service_detail_has_no_tkt_and_shows_partner_problem(): void
    {
        $html = view('livewire.proposals.components.identitas-usulan-detail', [
            'proposal' => $this->makeProposal('community-service'),
            'type' => 'community-service',
            'mode' => 'detail',
        ])->render();

        $this->assertStringContainsString('1.2 Klasifikasi Ilmu', $html);
        $this->assertStringContainsString('Masalah Mitra', $html);
        $this->assertStringNotContainsString('Detail Penelitian (TKT)', $html);
    }

    public function test_revision_mode_hides_basic_card_and_keeps_readonly_sections(): void
    {
        $html = view('livewire.proposals.components.identitas-usulan-detail', [
            'proposal' => $this->makeProposal('research'),
            'type' => 'research',
            'mode' => 'revision',
        ])->render();

        $this->assertStringNotContainsString('1.1 Informasi Dasar Proposal', $html);
        $this->assertStringContainsString('Ringkasan & Kata Kunci', $html);
        $this->assertStringContainsString('Tugas Peneliti', $html);
        $this->assertStringContainsString('TugasKetuaUji', $html);
    }

    public function test_final_report_mode_renders_identity_and_tasks(): void
    {
        $html = view('livewire.proposals.components.identitas-usulan-detail', [
            'proposal' => $this->makeProposal('community-service'),
            'type' => 'community-service',
            'mode' => 'final-report',
        ])->render();

        $this->assertStringContainsString('Informasi Dasar Proposal', $html);
        $this->assertStringContainsString('Pemetaan Target IKU', $html);
        $this->assertStringContainsString('Tugas Pelaksana', $html);
    }
}
