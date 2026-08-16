<?php

namespace Tests\Feature\Settings;

use App\Enums\ProposalStatus;
use App\Livewire\Settings\ContractManager;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Vetted by AI - Manual Review Required by Senior Engineer/Manager
 */
class ContractManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder'])->run();
    }

    public function test_page_loads_for_admin_lppm(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin lppm');
        session(['active_role' => 'admin lppm']);

        $this->actingAs($admin)
            ->get(route('settings.contracts'))
            ->assertOk()
            ->assertSee('Manajemen Nomor Kontrak');
    }

    public function test_non_admin_cannot_access(): void
    {
        $user = User::factory()->create();
        $user->assignRole('dosen');
        session(['active_role' => 'dosen']);

        $this->actingAs($user)
            ->get(route('settings.contracts'))
            ->assertForbidden();
    }

    public function test_can_update_single_contract_number(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin lppm');
        session(['active_role' => 'admin lppm']);

        $research = Research::factory()->create();
        $proposal = Proposal::factory()->create([
            'detailable_type' => 'App\Models\Research',
            'detailable_id' => $research->id,
            'status' => ProposalStatus::COMPLETED,
            'contract_number' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(ContractManager::class)
            ->call('openEdit', (string) $proposal->id)
            ->set('editingContractNumber', '005/ITSNU/LPPM/KTR-L/VIII/2026')
            ->set('editingContractDate', '2026-08-16')
            ->call('saveSingle');

        $freshProposal = $proposal->fresh();
        $this->assertEquals('005/ITSNU/LPPM/KTR-L/VIII/2026', $freshProposal->contract_number);
        $this->assertEquals('2026-08-16', $freshProposal->contract_date?->format('Y-m-d'));
    }

    public function test_can_generate_batch_contract_numbers(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin lppm');
        session(['active_role' => 'admin lppm']);

        $research1 = Research::factory()->create();
        $proposal1 = Proposal::factory()->create([
            'detailable_type' => 'App\Models\Research',
            'detailable_id' => $research1->id,
            'status' => ProposalStatus::COMPLETED,
        ]);

        $research2 = Research::factory()->create();
        $proposal2 = Proposal::factory()->create([
            'detailable_type' => 'App\Models\Research',
            'detailable_id' => $research2->id,
            'status' => ProposalStatus::COMPLETED,
        ]);

        Livewire::actingAs($admin)
            ->test(ContractManager::class)
            ->set('selectedProposals', [(string) $proposal1->id, (string) $proposal2->id])
            ->set('batchStartNumber', 1)
            ->set('batchNumberDigits', 3)
            ->set('batchPattern', '{num}/ITSNU/LPPM/KTR-{type}/{month}/{year}')
            ->set('batchContractDate', '2026-08-16')
            ->call('generateBatch');

        $fresh1 = $proposal1->fresh();
        $this->assertEquals('001/ITSNU/LPPM/KTR-L/VIII/2026', $fresh1->contract_number);
        $this->assertEquals('2026-08-16', $fresh1->contract_date?->format('Y-m-d'));

        $fresh2 = $proposal2->fresh();
        $this->assertEquals('002/ITSNU/LPPM/KTR-L/VIII/2026', $fresh2->contract_number);
        $this->assertEquals('2026-08-16', $fresh2->contract_date?->format('Y-m-d'));
    }

    public function test_can_generate_batch_for_all_filtered_proposals(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin lppm');
        session(['active_role' => 'admin lppm']);

        $research = Research::factory()->create();
        $proposal = Proposal::factory()->create([
            'detailable_type' => 'App\Models\Research',
            'detailable_id' => $research->id,
            'status' => ProposalStatus::COMPLETED,
            'start_year' => 2026,
        ]);

        Livewire::actingAs($admin)
            ->test(ContractManager::class)
            ->set('selectedProposals', [])
            ->set('batchTarget', 'all_filtered')
            ->set('year', '2026')
            ->set('batchStartNumber', 10)
            ->set('batchNumberDigits', 3)
            ->set('batchPattern', '{num}/ITSNU/LPPM/KTR-{type}/{month}/{year}')
            ->set('batchContractDate', '2026-08-16')
            ->call('generateBatch');

        $fresh = $proposal->fresh();
        $this->assertEquals('010/ITSNU/LPPM/KTR-L/VIII/2026', $fresh->contract_number);
        $this->assertEquals('2026-08-16', $fresh->contract_date?->format('Y-m-d'));
    }
}
