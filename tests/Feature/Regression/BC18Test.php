<?php

/**
 * Regression: BC-18 — Kartu "Riwayat Tri Dharma Terakhir" di dashboard modern
 * hanya menampilkan placeholder statis.
 */

use App\Livewire\Dashboard;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RoleSeeder::class));

it('BC-18: kartu Riwayat Tri Dharma menampilkan usulan riil pengguna', function () {
    $admin = User::factory()->create(['name' => 'Admin LPPM']);
    $admin->assignRole('admin lppm');

    $dosen = User::factory()->create(['name' => 'Dosen Pengusul']);
    $dosen->assignRole('dosen');

    Proposal::factory()->create([
        'title' => 'Usulan Riwayat Tri Dharma Uji',
        'submitter_id' => $dosen->id,
        'detailable_id' => Research::factory()->create()->id,
        'detailable_type' => Research::class,
        'start_year' => 2026,
    ]);

    $this->actingAs($admin);
    session(['active_role' => 'admin lppm']);
    session(['sim_lppm_app_theme' => 'modern']);
    session(['sim_lppm_dashboard_theme' => 'modern']);

    $html = Livewire::test(Dashboard::class)->html();
    expect($html)->toContain('Usulan Riwayat Tri Dharma Uji');
});
