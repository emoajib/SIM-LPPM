<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\CommunityService;
use App\Models\CommunityServiceScheme;
use App\Models\FocusArea;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\ResearchScheme;
use App\Models\User;
use Database\Seeders\InstitutionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tabel "Daftar Usulan Terdaftar" pada dashboard tema modern.
 *
 * Kolom Skema/Kategori harus menampilkan nama skema dari data master
 * (research_schemes / community_service_schemes), bukan area fokus
 * (SAINTEK/DEKABITA) dan bukan label karangan. Kolom Peran dan Status
 * juga harus berisi nilai nyata, bukan string default palsu.
 */
class DashboardProposalTableTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(InstitutionSeeder::class);

        $this->admin = User::factory()->create(['name' => 'Admin LPPM']);
        $this->admin->assignRole('admin lppm');

        session(['active_role' => 'admin lppm']);
        session(['sim_lppm_app_theme' => 'modern']);
        session(['sim_lppm_dashboard_theme' => 'modern']);
        $this->actingAs($this->admin);
    }

    public function test_skema_column_shows_master_scheme_names_for_both_types(): void
    {
        $dosen = User::factory()->create(['name' => 'Dosen Pengusul']);
        $dosen->assignRole('dosen');

        $focus = FocusArea::factory()->create(['name' => 'SAINTEK']);

        $researchScheme = ResearchScheme::factory()->create([
            'name' => 'Skema 1: Reguler Uji',
            'strata' => 'Reguler',
        ]);

        $communityScheme = CommunityServiceScheme::create([
            'name' => 'PKM-Reguler Uji',
            'strata' => 'PKM-Reguler',
        ]);

        Proposal::factory()->create([
            'title' => 'Usulan Penelitian Uji',
            'submitter_id' => $dosen->id,
            'detailable_id' => Research::factory()->create()->id,
            'detailable_type' => Research::class,
            'research_scheme_id' => $researchScheme->id,
            'community_service_scheme_id' => null,
            'focus_area_id' => $focus->id,
            'start_year' => 2026,
        ]);

        Proposal::factory()->create([
            'title' => 'Usulan Pengabdian Uji',
            'submitter_id' => $dosen->id,
            'detailable_id' => CommunityService::factory()->create()->id,
            'detailable_type' => CommunityService::class,
            'research_scheme_id' => $researchScheme->id,
            'community_service_scheme_id' => $communityScheme->id,
            'focus_area_id' => $focus->id,
            'start_year' => 2026,
        ]);

        $html = Livewire::test(Dashboard::class)->html();
        $table = $this->tableBody($html);

        // Nama skema asli tampil untuk kedua jenis usulan.
        $this->assertStringContainsString('Skema 1: Reguler Uji', $table);
        $this->assertStringContainsString('PKM-Reguler Uji', $table);

        // Area fokus tetap tampil, tetapi sebagai keterangan pendukung,
        // bukan sebagai isi kolom skema.
        $this->assertStringContainsString('Penelitian · SAINTEK', $table);
        $this->assertStringContainsString('Pengabdian · SAINTEK', $table);

        // Tidak ada lagi label karangan.
        $this->assertStringNotContainsString('Penelitian Terapan', $table);
        $this->assertStringNotContainsString('Pengabdian Masyarakat', $table);
        $this->assertStringNotContainsString('Didanai', $table);
    }

    public function test_year_role_and_status_columns_use_real_values(): void
    {
        $dosen = User::factory()->create(['name' => 'Dosen Pengusul']);
        $dosen->assignRole('dosen');

        $anggota = User::factory()->create(['name' => 'Dosen Anggota']);
        $anggota->assignRole('dosen');

        $proposal = Proposal::factory()->create([
            'title' => 'Usulan Status Nyata',
            'submitter_id' => $dosen->id,
            'detailable_id' => Research::factory()->create()->id,
            'detailable_type' => Research::class,
            'status' => 'waiting_reviewer',
            'start_year' => 2027,
        ]);

        $proposal->teamMembers()->attach($dosen->id, ['role' => 'ketua', 'status' => 'accepted']);
        $proposal->teamMembers()->attach($anggota->id, ['role' => 'anggota', 'status' => 'accepted']);

        // Admin (bukan bagian tim): peran yang tampil = peran pengusul.
        $table = $this->tableBody(Livewire::test(Dashboard::class)->html());
        $this->assertStringContainsString('2027', $table);
        $this->assertStringContainsString('Ketua', $table);
        $this->assertStringContainsString('Menunggu Penugasan Reviewer', $table);
        $this->assertStringNotContainsString('Didanai', $table);

        // Anggota tim: peran yang tampil = peran dirinya pada usulan tersebut.
        $this->actingAs($anggota);
        $table = $this->tableBody(Livewire::test(Dashboard::class)->html());
        $this->assertStringContainsString('Anggota', $table);
        $this->assertStringNotContainsString('Ketua', $table);
    }

    public function test_modern_page_width_is_widened_two_centimetres_per_side(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression(
            '/--sli-page-max:\s*1472px/',
            $css,
            'Lebar halaman modern harus 1472px (1320px + 2 x 1 cm per sisi).'
        );
        $this->assertStringContainsString('max-width: var(--sli-page-max)', $css);
    }

    private function tableBody(string $html): string
    {
        $this->assertMatchesRegularExpression('/<tbody id="sli-proposal-table-body">/', $html);

        preg_match('/<tbody id="sli-proposal-table-body">(.*?)<\/tbody>/s', $html, $matches);

        return $matches[1] ?? '';
    }
}
