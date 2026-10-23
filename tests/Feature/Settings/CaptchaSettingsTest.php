<?php

use App\Livewire\Settings\CaptchaSettings;
use App\Livewire\Settings\SettingsIndex;
use App\Models\Setting;
use App\Models\User;
use App\Services\Security\CaptchaAudioService;
use App\Services\Security\MathCaptchaService;
use Livewire\Livewire;

beforeEach(function () {
    Setting::clearCache();
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder'])->run();
});

function captchaAdmin(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    test()->actingAs($user);
    session(['active_role' => $role]);

    return $user;
}

test('admin lppm and superadmin can manage audio captcha', function () {
    foreach (['admin lppm', 'superadmin'] as $role) {
        captchaAdmin($role);

        Livewire::test(CaptchaSettings::class)->assertSee('Jenis suara');

        Livewire::test(SettingsIndex::class)
            ->call('setActiveTab', 'captcha')
            ->assertSee('Keamanan CAPTCHA');
    }
});

test('other users cannot manage audio captcha', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(CaptchaSettings::class)->assertForbidden();

    Livewire::test(SettingsIndex::class)
        ->call('setActiveTab', 'captcha')
        ->assertForbidden();
});
test('audio captcha settings are persisted', function () {
    captchaAdmin('admin lppm');

    Livewire::test(CaptchaSettings::class)
        ->set('audioEnabled', false)
        ->set('audioVoice', 'daniel')
        ->assertHasNoErrors();

    expect(Setting::get('captcha_audio_enabled'))->toBeFalse()
        ->and(Setting::get('captcha_audio_voice'))->toBe('daniel');
});
test('selected voice changes the rendered audio', function () {
    $challenge = app(MathCaptchaService::class)->issue('settings-voice-test')['challenge'];
    $service = app(CaptchaAudioService::class);

    Setting::set('captcha_audio_voice', 'damayanti');
    $indonesian = $service->render($challenge);

    Setting::set('captcha_audio_voice', 'daniel');
    $english = $service->render($challenge);

    expect($english)->not->toBe($indonesian);
});
test('disabled audio is hidden from the login page', function () {
    config(['turnstile.site_key' => null]);
    Setting::set('captcha_audio_enabled', false, 'boolean');

    expect(app(CaptchaAudioService::class)->available())->toBeFalse();

    $this->get('/login')
        ->assertOk()
        ->assertDontSee('<audio', false);
});
test('unknown voice values are rejected', function () {
    captchaAdmin('admin lppm');

    Livewire::test(CaptchaSettings::class)
        ->set('audioVoice', '../../etc/passwd')
        ->assertHasErrors('audioVoice');

    expect(Setting::get('captcha_audio_voice'))->not->toBe('../../etc/passwd');
});
