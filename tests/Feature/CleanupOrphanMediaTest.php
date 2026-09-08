<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class CleanupOrphanMediaTest extends TestCase
{
    // Vetted by AI - Manual Review Required by Senior Engineer/Manager
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_cleanup_orphans_dry_run_does_not_delete_media()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg');
        $media = $user->addMedia($file)->toMediaCollection('avatar');

        $this->artisan('media:cleanup-orphans', [
            '--ids' => (string) $media->id,
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('DRY-RUN')
            ->assertExitCode(0);

        $this->assertDatabaseHas('media', ['id' => $media->id]);
    }

    public function test_cleanup_orphans_with_ids_and_force_deletes_media()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg');
        $media = $user->addMedia($file)->toMediaCollection('avatar');

        $this->artisan('media:cleanup-orphans', [
            '--ids' => (string) $media->id,
            '--force' => true,
        ])
            ->expectsOutputToContain('Berhasil dihapus')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    public function test_cleanup_orphans_aborts_without_force_when_cancelled()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg');
        $media = $user->addMedia($file)->toMediaCollection('avatar');

        $this->artisan('media:cleanup-orphans', [
            '--ids' => (string) $media->id,
        ])
            ->expectsConfirmation('Tindakan ini akan menghapus record media dan file fisiknya. Lanjutkan?', 'no')
            ->expectsOutput('Dibatalkan.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('media', ['id' => $media->id]);
    }

    public function test_cleanup_orphans_detects_missing_model_when_scanning()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg');
        $media = $user->addMedia($file)->toMediaCollection('avatar');

        // Force delete user directly bypassing model events so media becomes orphaned
        User::withoutEvents(function () use ($user) {
            $user->forceDelete();
        });

        $this->artisan('media:cleanup-orphans', [
            '--force' => true,
        ])
            ->expectsOutputToContain('Berhasil dihapus')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }
}
