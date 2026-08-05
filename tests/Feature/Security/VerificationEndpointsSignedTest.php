<?php

use App\Enums\InstitutionalReportStatus;
use App\Models\DocumentSignature;
use App\Models\InstitutionalReport;
use App\Models\Letter;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'Database\Seeders\RoleSeeder'])->run();
    Setting::set('module_persuratan_active', true);
});

/**
 * Regression tests for the public verification endpoints
 * (/verify/letters|reports|signatures/...).
 *
 * These routes must only respond to cryptographically signed URLs — the
 * numeric model IDs alone must not be enough to read verification data
 * (PII: letter numbers, submitter names, official names).
 */
it('blocks unsigned access to letter verification endpoint', function () {
    $letter = Letter::factory()->create();

    $this->get(route('letters.verify', ['letter' => $letter->id]))
        ->assertStatus(403);
});

it('allows signed access to letter verification endpoint', function () {
    $letter = Letter::factory()->create();

    $url = URL::signedRoute('letters.verify', ['letter' => $letter->id]);

    $this->get($url)
        ->assertOk();
});

it('blocks unsigned access to report verification endpoint', function () {
    $report = InstitutionalReport::create([
        'id' => (string) Str::uuid(),
        'type' => 'research',
        'year' => 2026,
        'status' => InstitutionalReportStatus::SUBMITTED,
    ]);

    $this->get(route('reports.verify', ['institutionalReport' => $report->id]))
        ->assertStatus(403);
});

it('blocks unsigned access to signature verification endpoint', function () {
    $user = User::factory()->create();

    $signature = DocumentSignature::create([
        'id' => (string) Str::uuid(),
        'document_type' => (new InstitutionalReport)->getMorphClass(),
        'document_id' => (string) Str::uuid(),
        'variant' => 'submitted',
        'action' => 'submitted',
        'signed_role' => 'kepala_lppm',
        'signed_by' => $user->id,
        'signed_at' => now(),
        'kid' => 'v1',
        'signature' => Str::random(64),
        'payload' => ['ver' => 1, 'nonce' => Str::random(32)],
    ]);

    $this->get(route('signatures.verify', ['documentSignature' => $signature->id]))
        ->assertStatus(403);
});

it('allows signed access to signature verification endpoint', function () {
    $user = User::factory()->create();

    $signature = DocumentSignature::create([
        'id' => (string) Str::uuid(),
        'document_type' => (new InstitutionalReport)->getMorphClass(),
        'document_id' => (string) Str::uuid(),
        'variant' => 'submitted',
        'action' => 'submitted',
        'signed_role' => 'kepala_lppm',
        'signed_by' => $user->id,
        'signed_at' => now(),
        'kid' => 'v1',
        'signature' => Str::random(64),
        'payload' => ['ver' => 1, 'nonce' => Str::random(32)],
    ]);

    $url = URL::signedRoute('signatures.verify', ['documentSignature' => $signature->id]);

    $this->get($url)
        ->assertOk();
});

it('rejects a valid signature tampered to another record id', function () {
    $letterA = Letter::factory()->create();
    $letterB = Letter::factory()->create();

    // Sign for letter A, then point the URL at letter B without re-signing.
    $signedForA = URL::signedRoute('letters.verify', ['letter' => $letterA->id]);
    $parsed = parse_url($signedForA);
    parse_str($parsed['query'] ?? '', $query);
    $tampered = route('letters.verify', ['letter' => $letterB->id]).'?'.http_build_query($query);

    $this->get($tampered)
        ->assertStatus(403);
});
