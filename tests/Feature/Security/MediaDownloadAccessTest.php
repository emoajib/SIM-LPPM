<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\RecordActivity;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class MediaDownloadAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $ketua;

    protected User $anggota;

    protected User $otherDosen;

    protected User $adminLppm;

    protected Proposal $proposal;

    protected Media $media;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        // Ensure app is "installed" for AppServiceProvider to register policies
        if (! file_exists(storage_path('app/.installed'))) {
            if (! is_dir(storage_path('app'))) {
                mkdir(storage_path('app'), 0755, true);
            }
            file_put_contents(storage_path('app/.installed'), 'installed');
        }

        $this->seed(RoleSeeder::class);

        $this->ketua = User::factory()->create();
        $this->ketua->assignRole('dosen');

        $this->anggota = User::factory()->create();
        $this->anggota->assignRole('dosen');

        $this->otherDosen = User::factory()->create();
        $this->otherDosen->assignRole('dosen');

        $this->adminLppm = User::factory()->create();
        $this->adminLppm->assignRole('admin lppm');

        $research = Research::factory()->create();
        $this->proposal = Proposal::factory()->create([
            'submitter_id' => $this->ketua->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
        ]);

        $this->proposal->teamMembers()->attach($this->anggota->id, [
            'role' => 'anggota',
            'status' => 'accepted',
        ]);

        // Create a dummy file for media with actual content
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        // Ensure the file has content so mime is not x-empty
        file_put_contents($file->getPathname(), "%PDF-1.4\ntest content");

        $this->media = $research->addMedia($file)
            ->preservingOriginal()
            ->toMediaCollection('substance_file');
    }

    /** @test */
    public function test_ketua_can_download_their_own_file()
    {
        $this->actingAs($this->ketua)
            ->get(route('media.download', ['media' => $this->media->uuid]))
            ->assertStatus(200);
    }

    /** @test */
    public function test_ketua_can_download_file_relocated_after_identity_change()
    {
        $oldRelativePath = 'substance_file/0058120320025-hasan-mashuri/'.$this->media->id.'/'.$this->media->file_name;
        Storage::disk('public')->move($this->media->getPathRelativeToRoot(), $oldRelativePath);

        $this->assertFalse(Storage::disk('public')->exists($this->media->getPathRelativeToRoot()));
        $this->assertTrue(Storage::disk('public')->exists($oldRelativePath));

        $this->actingAs($this->ketua)
            ->get(route('media.download', ['media' => $this->media->uuid]))
            ->assertStatus(200);
    }

    /** @test */
    public function test_anggota_can_download_proposal_file()
    {
        $this->actingAs($this->anggota)
            ->get(route('media.download', ['media' => $this->media->uuid]))
            ->assertStatus(200);
    }

    /** @test */
    public function test_admin_lppm_can_download_any_file()
    {
        $this->actingAs($this->adminLppm)
            ->get(route('media.download', ['media' => $this->media->uuid]))
            ->assertStatus(200);
    }

    /** @test */
    public function test_other_dosen_cannot_download_restricted_file()
    {
        $this->actingAs($this->otherDosen)
            ->get(route('media.download', ['media' => $this->media->uuid]))
            ->assertStatus(403);
    }

    /** @test */
    public function test_guest_is_redirected_to_login()
    {
        $this->get(route('media.download', ['media' => $this->media->uuid]))
            ->assertRedirect(route('login'));
    }

    /** @test */
    public function test_malformed_uuid_returns_404()
    {
        $this->actingAs($this->adminLppm)
            ->get('/media/not-a-uuid/download')
            ->assertStatus(404); // Handled by Route Model Binding
    }

    /** @test */
    public function test_system_settings_are_accessible_to_all_authenticated_users()
    {
        $setting = Setting::create(['key' => 'template_borang', 'value' => 'test']);
        $file = UploadedFile::fake()->create('template.pdf', 100, 'application/pdf');
        $settingMedia = $setting->addMedia($file)->toMediaCollection('template');

        $this->actingAs($this->otherDosen)
            ->get(route('media.download', ['media' => $settingMedia->uuid]))
            ->assertStatus(200);
    }

    /** @test */
    public function test_mime_mismatch_prevents_download()
    {
        // Manifesting a mismatch: Database says PDF, physical is Image
        // This is tricky to fake with MediaLibrary as it validates on add.
        // We can manually update the DB to simulate corruption/attack.

        $this->media->update(['mime_type' => 'image/png']);

        // Use withoutMiddleware or disable output buffer checking
        $this->actingAs($this->adminLppm)
            ->withoutMiddleware([RecordActivity::class])
            ->get(route('media.download', ['media' => $this->media->uuid]))
            ->assertStatus(422);
    }
}
