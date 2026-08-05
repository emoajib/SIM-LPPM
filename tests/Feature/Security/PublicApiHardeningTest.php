<?php

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use App\Models\Research;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'Database\Seeders\RoleSeeder'])->run();
});

/**
 * Regression tests: public API endpoints must cap their page size so an
 * unauthenticated caller cannot force unbounded resource consumption.
 */
it('caps the public research api page size at 100', function () {
    // Seed 120 completed research proposals (status must be COMPLETED for
    // the research to appear in this endpoint).
    Research::factory()->count(120)
        ->afterCreating(fn (Research $research) => Proposal::factory()->create([
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'status' => ProposalStatus::COMPLETED,
        ]))
        ->create();

    $response = $this->getJson('/api/v1/public/research?limit=9999');

    $response->assertOk()
        ->assertJsonPath('meta.per_page', 100)
        ->assertJsonPath('meta.total', 120);
});

it('defaults the public research api page size to 10', function () {
    Research::factory()->count(15)
        ->afterCreating(fn (Research $research) => Proposal::factory()->create([
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'status' => ProposalStatus::COMPLETED,
        ]))
        ->create();

    $response = $this->getJson('/api/v1/public/research');

    $response->assertOk()
        ->assertJsonPath('meta.per_page', 10);
});

it('rejects a zero or negative page size', function () {
    Research::factory()->count(5)
        ->afterCreating(fn (Research $research) => Proposal::factory()->create([
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'status' => ProposalStatus::COMPLETED,
        ]))
        ->create();

    // max(1, ...) floors the limit at 1 instead of erroring.
    $response = $this->getJson('/api/v1/public/research?limit=0');

    $response->assertOk()
        ->assertJsonPath('meta.per_page', 1);
});

/**
 * Regression tests for sql_year(): the helper interpolates a column name
 * into raw SQL, so it must refuse anything but a bare identifier.
 */
it('rejects unsafe column names in sql_year', function () {
    expect(fn () => sql_year('created_at; DROP TABLE proposals;--'))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => sql_year('created_at FROM users'))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => sql_year('`created_at`'))
        ->toThrow(InvalidArgumentException::class);
});

it('accepts safe column names in sql_year', function () {
    expect(sql_year('created_at'))->toBeString();
    expect(sql_year('year'))->toBeString();
});
