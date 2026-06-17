<?php

use App\Livewire\Settings\PdfModuleCard;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Feature Tests
// ---------------------------------------------------------------------------

it('denies access to non-admin users', function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder'])->run();
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(PdfModuleCard::class, [
        'moduleKey' => 'test-module',
        'moduleName' => 'Test Module',
        'family' => 'A',
        'viewType' => 'letter',
    ])->assertForbidden();
});

it('loads with global defaults when no overrides exist', function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder'])->run();
    $admin = User::factory()->create();
    $admin->assignRole('admin lppm');

    $component = Livewire::actingAs($admin)->test(PdfModuleCard::class, [
        'moduleKey' => 'test-module',
        'moduleName' => 'Test Module',
        'family' => 'A',
        'viewType' => 'letter',
    ]);

    $component->assertSet('fontFamily', '')
        ->assertSet('fontSize', '')
        ->assertSet('paperSize', '')
        ->assertSet('orientation', '')
        ->assertSet('marginTop', '')
        ->assertSet('marginRight', '')
        ->assertSet('marginBottom', '')
        ->assertSet('marginLeft', '')
        ->assertSet('introText', '')
        ->assertSet('outroText', '')
        ->assertSet('showLogo', '')
        ->assertSet('coverTitle', '')
        ->assertSet('coverSubtitle', '')
        ->assertSet('coverShowTeam', '');

    expect($component->instance()->hasOverrides())->toBeFalse();
});

it('can save font family override', function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder'])->run();
    $admin = User::factory()->create();
    $admin->assignRole('admin lppm');

    $component = Livewire::actingAs($admin)->test(PdfModuleCard::class, [
        'moduleKey' => 'test-module',
        'moduleName' => 'Test Module',
        'family' => 'A',
        'viewType' => 'letter',
    ])->set('fontFamily', 'Arial');

    $component->assertSet('fontFamily', 'Arial');
    expect($component->instance()->hasOverrides())->toBeTrue();

    $this->assertDatabaseHas('settings', [
        'key' => 'pdf_override_test-module_font_family',
        'value' => 'Arial',
    ]);

    Setting::clearCache();
    expect(Setting::get('pdf_override_test-module_font_family'))->toBe('Arial');
});

it('can save font size override', function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder'])->run();
    $admin = User::factory()->create();
    $admin->assignRole('admin lppm');

    $component = Livewire::actingAs($admin)->test(PdfModuleCard::class, [
        'moduleKey' => 'test-module',
        'moduleName' => 'Test Module',
        'family' => 'A',
        'viewType' => 'letter',
    ])->set('fontSize', '14');

    $component->assertSet('fontSize', '14');
    expect($component->instance()->hasOverrides())->toBeTrue();

    $this->assertDatabaseHas('settings', [
        'key' => 'pdf_override_test-module_font_size',
        'value' => '14',
    ]);

    Setting::clearCache();
    expect(Setting::get('pdf_override_test-module_font_size'))->toBe('14');
});

it('can save margin override', function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder'])->run();
    $admin = User::factory()->create();
    $admin->assignRole('admin lppm');

    // Use margin value within validation limits (max:10)
    $component = Livewire::actingAs($admin)->test(PdfModuleCard::class, [
        'moduleKey' => 'test-module',
        'moduleName' => 'Test Module',
        'family' => 'A',
        'viewType' => 'letter',
    ])->set('marginTop', '8');

    $component->assertSet('marginTop', '8');
    expect($component->instance()->hasOverrides())->toBeTrue();

    $this->assertDatabaseHas('settings', [
        'key' => 'pdf_override_test-module_margin_top',
        'value' => '8',
    ]);

    Setting::clearCache();
    expect(Setting::get('pdf_override_test-module_margin_top'))->toBe('8');
});

it('detects overrides exist after saving', function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder'])->run();
    $admin = User::factory()->create();
    $admin->assignRole('admin lppm');

    $component = Livewire::actingAs($admin)->test(PdfModuleCard::class, [
        'moduleKey' => 'test-module',
        'moduleName' => 'Test Module',
        'family' => 'A',
        'viewType' => 'letter',
    ]);

    expect($component->instance()->hasOverrides())->toBeFalse();

    $component->set('fontFamily', 'Arial');

    expect($component->instance()->hasOverrides())->toBeTrue();
});

it('can reset all overrides', function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder'])->run();
    $admin = User::factory()->create();
    $admin->assignRole('admin lppm');

    // Pre-create multiple overrides
    Setting::set('pdf_override_test-module_font_family', 'Arial', 'string');
    Setting::set('pdf_override_test-module_font_size', '14', 'string');
    Setting::set('pdf_content_test-module_intro', 'Some intro', 'string');

    $component = Livewire::actingAs($admin)->test(PdfModuleCard::class, [
        'moduleKey' => 'test-module',
        'moduleName' => 'Test Module',
        'family' => 'A',
        'viewType' => 'letter',
    ]);

    // Confirm overrides are present before reset
    expect($component->instance()->hasOverrides())->toBeTrue();

    $component->call('resetOverrides');

    // After reset, component state should reflect no overrides
    expect($component->instance()->hasOverrides())->toBeFalse();
    $component->assertSet('fontFamily', '')
        ->assertSet('fontSize', '')
        ->assertSet('introText', '');

    // Verify database records are deleted (bypasses Setting static cache)
    $this->assertDatabaseMissing('settings', [
        'key' => 'pdf_override_test-module_font_family',
    ]);
    $this->assertDatabaseMissing('settings', [
        'key' => 'pdf_override_test-module_font_size',
    ]);
    $this->assertDatabaseMissing('settings', [
        'key' => 'pdf_content_test-module_intro',
    ]);

    // Verify via Setting::get() after clearing cache
    Setting::clearCache();
    expect(Setting::get('pdf_override_test-module_font_family'))->toBeNull();
    expect(Setting::get('pdf_override_test-module_font_size'))->toBeNull();
    expect(Setting::get('pdf_content_test-module_intro'))->toBeNull();
});

it('dispatches module-override-updated event on save', function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder'])->run();
    $admin = User::factory()->create();
    $admin->assignRole('admin lppm');

    Livewire::actingAs($admin)->test(PdfModuleCard::class, [
        'moduleKey' => 'test-module',
        'moduleName' => 'Test Module',
        'family' => 'A',
        'viewType' => 'letter',
    ])->set('fontFamily', 'Arial')
      ->assertDispatched('module-override-updated', function (string $event, array $data) {
          return $data['moduleKey'] === 'test-module'
              && $data['hasOverrides'] === true;
      });
});

it('dispatches module-override-updated event on reset', function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder'])->run();
    $admin = User::factory()->create();
    $admin->assignRole('admin lppm');

    // Pre-create an override so reset has something to clear
    Setting::set('pdf_override_test-module_font_family', 'Arial', 'string');

    Livewire::actingAs($admin)->test(PdfModuleCard::class, [
        'moduleKey' => 'test-module',
        'moduleName' => 'Test Module',
        'family' => 'A',
        'viewType' => 'letter',
    ])->call('resetOverrides')
      ->assertDispatched('module-override-updated', function (string $event, array $data) {
          return $data['moduleKey'] === 'test-module'
              && $data['hasOverrides'] === false;
      });
});

it('dispatches open-content-editor event', function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder'])->run();
    $admin = User::factory()->create();
    $admin->assignRole('admin lppm');

    Livewire::actingAs($admin)->test(PdfModuleCard::class, [
        'moduleKey' => 'test-module',
        'moduleName' => 'Test Module',
        'family' => 'A',
        'viewType' => 'letter',
    ])->call('openModalEditor')
      ->assertDispatched('open-content-editor', function (string $event, array $data) {
          return $data['moduleKey'] === 'test-module'
              && $data['moduleName'] === 'Test Module';
      });
});

it('shows content when overrides exist', function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder'])->run();
    $admin = User::factory()->create();
    $admin->assignRole('admin lppm');

    // Pre-create settings via Setting model to simulate persisted overrides
    Setting::set('pdf_override_test-module_font_family', 'Times New Roman', 'string');
    Setting::set('pdf_override_test-module_font_size', '14', 'string');
    Setting::set('pdf_content_test-module_intro', 'Custom intro text', 'string');

    $component = Livewire::actingAs($admin)->test(PdfModuleCard::class, [
        'moduleKey' => 'test-module',
        'moduleName' => 'Test Module',
        'family' => 'A',
        'viewType' => 'letter',
    ]);

    $component->assertSet('fontFamily', 'Times New Roman')
        ->assertSet('fontSize', '14')
        ->assertSet('introText', 'Custom intro text');

    expect($component->instance()->hasOverrides())->toBeTrue();
});

// ---------------------------------------------------------------------------
// Performance Tests
// ---------------------------------------------------------------------------

it('loads overrides from pre-existing settings in database', function () {
    // Create all 14 override keys in the database before mounting the component.
    // This simulates the real-world scenario where overrides were previously saved.
    $keys = [
        'pdf_override_test-module_font_family' => 'Arial',
        'pdf_override_test-module_font_size' => '12',
        'pdf_override_test-module_paper_size' => 'legal',
        'pdf_override_test-module_orientation' => 'landscape',
        'pdf_override_test-module_margin_top' => '8',
        'pdf_override_test-module_margin_right' => '6',
        'pdf_override_test-module_margin_bottom' => '8',
        'pdf_override_test-module_margin_left' => '6',
        'pdf_content_test-module_intro' => 'Introductory paragraph',
        'pdf_content_test-module_outro' => 'Closing paragraph',
        'pdf_override_test-module_show_logo' => '1',
        'pdf_override_test-module_cover_title' => 'Research Report',
        'pdf_override_test-module_cover_subtitle' => 'Q1 2026',
        'pdf_override_test-module_cover_show_team' => '1',
    ];

    foreach ($keys as $key => $value) {
        Setting::set($key, $value);
    }

    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder'])->run();
    $admin = User::factory()->create();
    $admin->assignRole('admin lppm');

    $component = Livewire::actingAs($admin)->test(PdfModuleCard::class, [
        'moduleKey' => 'test-module',
        'moduleName' => 'Test Module',
        'family' => 'A',
        'viewType' => 'letter',
    ]);

    $component->assertSet('fontFamily', 'Arial')
        ->assertSet('fontSize', '12')
        ->assertSet('paperSize', 'legal')
        ->assertSet('orientation', 'landscape')
        ->assertSet('marginTop', '8')
        ->assertSet('marginRight', '6')
        ->assertSet('marginBottom', '8')
        ->assertSet('marginLeft', '6')
        ->assertSet('introText', 'Introductory paragraph')
        ->assertSet('outroText', 'Closing paragraph')
        ->assertSet('showLogo', '1')
        ->assertSet('coverTitle', 'Research Report')
        ->assertSet('coverSubtitle', 'Q1 2026')
        ->assertSet('coverShowTeam', '1');

    expect($component->instance()->hasOverrides())->toBeTrue();
});

it('caches hasOverrides result', function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder'])->run();
    $admin = User::factory()->create();
    $admin->assignRole('admin lppm');

    $component = Livewire::actingAs($admin)->test(PdfModuleCard::class, [
        'moduleKey' => 'test-module',
        'moduleName' => 'Test Module',
        'family' => 'A',
        'viewType' => 'letter',
    ]);

    // First call computes the result and populates the private cache
    $firstCall = $component->instance()->hasOverrides();

    // Subsequent call should return consistent (cached) result
    $secondCall = $component->instance()->hasOverrides();

    expect($firstCall)->toBeFalse();
    expect($secondCall)->toBeFalse();
    expect($firstCall)->toEqual($secondCall);

    // Set an override, which should invalidate the cache via updated()
    $component->set('fontFamily', 'Courier');

    // After invalidation, hasOverrides should recompute and return true
    expect($component->instance()->hasOverrides())->toBeTrue();

    // Subsequent calls should return the cached true value
    expect($component->instance()->hasOverrides())->toBeTrue();
});

// ---------------------------------------------------------------------------
// Edge Cases
// ---------------------------------------------------------------------------

it('handles empty moduleKey gracefully', function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder'])->run();
    $admin = User::factory()->create();
    $admin->assignRole('admin lppm');

    $component = Livewire::actingAs($admin)->test(PdfModuleCard::class, [
        'moduleKey' => '',
        'moduleName' => 'Empty Key Module',
        'family' => 'A',
        'viewType' => 'letter',
    ]);

    // Must not crash or throw; all fields should be empty defaults
    $component->assertSet('fontFamily', '')
        ->assertSet('fontSize', '')
        ->assertSet('paperSize', '')
        ->assertSet('orientation', '')
        ->assertSet('marginTop', '')
        ->assertSet('marginRight', '')
        ->assertSet('marginBottom', '')
        ->assertSet('marginLeft', '')
        ->assertSet('introText', '')
        ->assertSet('outroText', '')
        ->assertSet('showLogo', '')
        ->assertSet('coverTitle', '')
        ->assertSet('coverSubtitle', '')
        ->assertSet('coverShowTeam', '');

    expect($component->instance()->hasOverrides())->toBeFalse();
});

it('handles partial overrides correctly', function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder'])->run();
    $admin = User::factory()->create();
    $admin->assignRole('admin lppm');

    // Only set a single override — fontFamily
    Setting::set('pdf_override_test-module_font_family', 'Courier', 'string');

    $component = Livewire::actingAs($admin)->test(PdfModuleCard::class, [
        'moduleKey' => 'test-module',
        'moduleName' => 'Test Module',
        'family' => 'A',
        'viewType' => 'letter',
    ]);

    // fontFamily should have the override value
    $component->assertSet('fontFamily', 'Courier');

    // All other fields should remain empty
    $component->assertSet('fontSize', '')
        ->assertSet('paperSize', '')
        ->assertSet('orientation', '')
        ->assertSet('marginTop', '')
        ->assertSet('marginRight', '')
        ->assertSet('marginBottom', '')
        ->assertSet('marginLeft', '')
        ->assertSet('introText', '')
        ->assertSet('outroText', '')
        ->assertSet('showLogo', '')
        ->assertSet('coverTitle', '')
        ->assertSet('coverSubtitle', '')
        ->assertSet('coverShowTeam', '');

    // hasOverrides should return true because at least one field is overridden
    expect($component->instance()->hasOverrides())->toBeTrue();
});

it('returns false when no overrides exist', function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder'])->run();
    $admin = User::factory()->create();
    $admin->assignRole('admin lppm');

    $component = Livewire::actingAs($admin)->test(PdfModuleCard::class, [
        'moduleKey' => 'test-module',
        'moduleName' => 'Test Module',
        'family' => 'A',
        'viewType' => 'letter',
    ]);

    expect($component->instance()->hasOverrides())->toBeFalse();
});
