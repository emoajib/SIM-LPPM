<?php

namespace Tests\Feature\Regression;

use App\Models\Proposal;
use App\Models\ResearchScheme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression: BC-05 — mass assignment / silent data loss.
 *
 * Kelas bug ini terjadi saat kolom penting lupa masuk `$fillable`: form
 * menyimpan data, tetapi Eloquent membuangnya tanpa error (silent data loss).
 * Penjaga di sini memastikan kolom yang pernah hilang tetap dapat diisi.
 */
class BC05MassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_proposal_keeps_qualification_snapshot_fillable(): void
    {
        expect((new Proposal)->getFillable())
            ->toContain('qualification_snapshot')
            ->toContain('submitter_id')
            ->toContain('status');
    }

    public function test_research_scheme_keeps_description_fillable(): void
    {
        expect((new ResearchScheme)->getFillable())->toContain('description');
    }

    public function test_proposal_mass_assignment_persists_qualification_snapshot(): void
    {
        $proposal = Proposal::factory()->create();
        $snapshot = ['nidn' => '123456789', 'name' => 'Dosen Uji'];

        $proposal->fill(['qualification_snapshot' => $snapshot]);
        $proposal->save();

        expect($proposal->fresh()->qualification_snapshot)->toBe($snapshot);
    }
}
