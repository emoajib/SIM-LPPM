<?php

use App\Livewire\Settings\RestoreData;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin lppm');

    $this->user = User::factory()->create();
    $this->user->assignRole('dosen');
});

test('guest cannot access restore data component', function () {
    Livewire::test(RestoreData::class)
        ->assertStatus(403);
});

test('regular user cannot access restore data component', function () {
    $this->actingAs($this->user);
    session(['active_role' => 'dosen']);

    Livewire::test(RestoreData::class)
        ->assertStatus(403);
});

test('admin lppm can mount restore data component and scan files', function () {
    $this->actingAs($this->admin);
    session(['active_role' => 'admin lppm']);

    Livewire::test(RestoreData::class)
        ->assertOk()
        ->assertSee('Pulihkan Data', false)
        ->assertSee('File Cadangan di Server', false);
});

test('scanServerFiles detects valid sql and zip files and ignores others', function () {
    $this->actingAs($this->admin);
    session(['active_role' => 'admin lppm']);

    $backupDir = storage_path('app/backup');
    if (! is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }

    $validSql = $backupDir.'/test_backup_scan.sql';
    $validZip = $backupDir.'/test_backup_scan.zip';
    $invalidTxt = $backupDir.'/test_backup_scan.txt';

    file_put_contents($validSql, "CREATE TABLE test_users (id INT);\nINSERT INTO test_users VALUES (1);");
    file_put_contents($validZip, 'PKfakezipcontent');
    file_put_contents($invalidTxt, 'ignore me');

    $component = Livewire::test(RestoreData::class)
        ->call('scanServerFiles');

    $serverFiles = $component->get('serverFiles');
    $filenames = array_column($serverFiles, 'filename');

    expect($filenames)->toContain('test_backup_scan.sql');
    expect($filenames)->toContain('test_backup_scan.zip');
    expect($filenames)->not->toContain('test_backup_scan.txt');

    @unlink($validSql);
    @unlink($validZip);
    @unlink($invalidTxt);
});

test('selectServerFile rejects directory traversal attempts', function () {
    $this->actingAs($this->admin);
    session(['active_role' => 'admin lppm']);

    Livewire::test(RestoreData::class)
        ->call('selectServerFile', '../../.env')
        ->assertSet('selectedServerFile', null)
        ->assertSee('❌ Nama file tidak valid.');
});

test('selectServerFile selects and previews sql file', function () {
    $this->actingAs($this->admin);
    session(['active_role' => 'admin lppm']);

    $backupDir = storage_path('app/backup');
    if (! is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }

    $sqlFile = $backupDir.'/db_test_preview.sql';
    file_put_contents($sqlFile, "CREATE TABLE test_table (id INT);\nINSERT INTO test_table VALUES (1);");

    $component = Livewire::test(RestoreData::class)
        ->call('selectServerFile', 'db_test_preview.sql');

    $component->assertSet('selectedServerFile', 'db_test_preview.sql')
        ->assertSet('hasPreview', true);

    $component->call('resetUpload');
    $component->assertSet('selectedServerFile', null)
        ->assertSet('hasPreview', false);

    @unlink($sqlFile);
});
