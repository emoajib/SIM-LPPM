<?php

namespace Tests\Feature;

use App\Enums\InstitutionalReportStatus;
use App\Models\DocumentSignature;
use App\Models\InstitutionalReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InstitutionalReportSignatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that InstitutionalReport can have morphMany signatures.
     */
    public function test_institutional_report_can_have_signatures()
    {
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        $report = InstitutionalReport::create([
            'id' => (string) Str::uuid(),
            'type' => 'research',
            'year' => 2026,
            'status' => InstitutionalReportStatus::SUBMITTED,
        ]);

        $user = User::factory()->create();

        $signature = DocumentSignature::create([
            'id' => (string) Str::uuid(),
            'document_type' => $report->getMorphClass(),
            'document_id' => $report->id,
            'variant' => 'submitted',
            'action' => 'submitted',
            'signed_role' => 'kepala_lppm',
            'signed_by' => $user->id,
            'signed_at' => now(),
            'kid' => 'v1',
            'signature' => Str::random(64),
            'payload' => ['ver' => 1, 'nonce' => Str::random(32)],
        ]);

        $this->assertCount(1, $report->signatures);
        $this->assertEquals($signature->id, $report->signatures->first()->id);
    }
}
