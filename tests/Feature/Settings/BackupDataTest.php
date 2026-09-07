<?php

use App\Livewire\Settings\BackupData;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin lppm');

    $this->user = User::factory()->create();
});

test('guest cannot access backup data component', function () {
    Livewire::test(BackupData::class)
        ->assertStatus(403);
});

test('admin lppm can mount backup data component', function () {
    $this->actingAs($this->admin);
    session(['active_role' => 'admin lppm']);

    Livewire::test(BackupData::class)
        ->assertOk()
        ->assertSee('Backup Database', false);
});

test('backupStorage executes properly', function () {
    $this->actingAs($this->admin);
    session(['active_role' => 'admin lppm']);

    $testDir = storage_path('app/public/test_folder');
    if (! is_dir($testDir)) {
        mkdir($testDir, 0755, true);
    }
    file_put_contents($testDir.'/test.txt', 'hello');

    $component = Livewire::test(BackupData::class)
        ->set('selectedFolders', ['test_folder'])
        ->call('backupStorage');

    $component->assertOk();

    @unlink($testDir.'/test.txt');
    @rmdir($testDir);
});

test('download storage endpoint supports GET and does not return 405', function () {
    $this->actingAs($this->admin);
    session(['active_role' => 'admin lppm']);

    $backupDir = storage_path('app/backup');
    if (! is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    $testZip = $backupDir.'/storage_20260908_test.zip';
    file_put_contents($testZip, 'PKfakezipcontent');

    cache(['backup_last_storage_file' => 'storage_20260908_test.zip']);

    $response = $this->get(route('settings.download-storage'));
    $response->assertOk();

    @unlink($testZip);
});

test('download storage endpoint supports POST and does not return 405', function () {
    $this->actingAs($this->admin);
    session(['active_role' => 'admin lppm']);

    $backupDir = storage_path('app/backup');
    if (! is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    $testZip = $backupDir.'/storage_20260908_test_post.zip';
    file_put_contents($testZip, 'PKfakezipcontent');

    cache(['backup_last_storage_file' => 'storage_20260908_test_post.zip']);

    $response = $this->post(route('settings.download-storage'));
    $response->assertOk();

    @unlink($testZip);
});

test('download db endpoint supports GET and POST without 405', function () {
    $this->actingAs($this->admin);
    session(['active_role' => 'admin lppm']);

    $backupDir = storage_path('app/backup');
    if (! is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    $testSql = $backupDir.'/db_20260908_test.sql';
    file_put_contents($testSql, '-- fake sql dump');

    cache(['backup_last_db_file' => 'db_20260908_test.sql']);

    $responseGet = $this->get(route('settings.download-db'));
    $responseGet->assertOk();

    $responsePost = $this->post(route('settings.download-db'));
    $responsePost->assertOk();

    @unlink($testSql);
});

test('POST to settings route does not return 405', function () {
    $this->actingAs($this->admin);
    session(['active_role' => 'admin lppm']);

    $response = $this->post(route('settings'));
    $response->assertOk();
});

test('GET to livewire/update redirects to settings without 405', function () {
    $this->actingAs($this->admin);
    session(['active_role' => 'admin lppm']);

    $response = $this->get('/livewire/update');
    $response->assertRedirect(route('settings'));
});

test('custom 405 blade view renders properly', function () {
    $view = view('errors.405', [
        'exception' => new MethodNotAllowedHttpException(['GET']),
    ])->render();

    expect($view)->toContain('405');
    expect($view)->toContain('Metode Tidak Diizinkan');
});
