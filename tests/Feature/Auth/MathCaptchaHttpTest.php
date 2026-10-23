<?php

use App\Livewire\Auth\Login;
use App\Services\Security\CaptchaAudioService;
use App\Services\Security\MathCaptchaService;
use Livewire\Livewire;

function mathChallenge(): array
{
    return app(MathCaptchaService::class)->issue(session()->getId());
}

function keepSession(object $test, $response): void
{
    foreach ($response->headers->getCookies() as $cookie) {
        $test->withUnencryptedCookie($cookie->getName(), (string) $cookie->getValue());
    }
}

test('login page renders an image captcha with an audio alternative', function () {
    config(['turnstile.site_key' => null]);

    $response = $this->get('/login');

    $response->assertOk()
        ->assertSee('captcha/math/', false)
        ->assertSee('<audio', false)
        ->assertSee('Soal audio', false)
        ->assertSee('Ketik hasil dari gambar di bawah ini.', false)
        ->assertSee('NUPTK / Email / NIDN / NIM', false)
        ->assertDontSee('Berapa hasil dari', false);
});

test('refreshing the captcha recreates the audio player', function () {
    config(['turnstile.site_key' => null]);

    $component = Livewire::test(Login::class);
    $firstId = $component->get('captchaId');

    $component->call('refreshCaptcha');

    $secondId = $component->get('captchaId');

    expect($secondId)->not->toBe($firstId)
        ->and($component->html())->toContain('wire:key="captcha-audio-'.$secondId.'-1"');
});

test('captcha image is served as an uncached image', function () {
    keepSession($this, $this->get('/login'));
    $issued = mathChallenge();

    $response = $this->get(route('captcha.image', ['id' => $issued['id']]));

    $response->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    expect((string) $response->headers->get('Cache-Control'))
        ->toContain('no-store')
        ->toContain('no-cache');

    $type = $response->headers->get('Content-Type');
    $body = (string) $response->getContent();

    if (extension_loaded('gd') && function_exists('imagettftext')) {
        expect($type)->toBe('image/png')
            ->and(substr($body, 0, 8))->toBe("\x89PNG\r\n\x1a\n");
    } else {
        expect($type)->toBe('image/svg+xml')
            ->and($body)->toContain('<svg')
            ->and($body)->not->toContain('<text');
    }
});

test('captcha image is hidden for unknown or foreign challenges', function () {
    $this->get('/login');

    $this->get(route('captcha.image', ['id' => str_repeat('a', 32)]))->assertNotFound();

    $foreign = app(MathCaptchaService::class)->issue('some-other-session');
    $this->get(route('captcha.image', ['id' => $foreign['id']]))->assertNotFound();
});

test('captcha audio is served as wav', function () {
    if (! app(CaptchaAudioService::class)->available()) {
        $this->markTestSkipped('Audio CAPTCHA assets are not installed.');
    }

    keepSession($this, $this->get('/login'));
    $issued = mathChallenge();

    $response = $this->get(route('captcha.audio', ['id' => $issued['id']]));

    $response->assertOk()->assertHeader('Content-Type', 'audio/wav');

    expect(substr((string) $response->getContent(), 0, 4))->toBe('RIFF');
});

test('captcha audio returns not found for expired challenges', function () {
    $this->get('/login');

    $this->get(route('captcha.audio', ['id' => str_repeat('b', 32)]))->assertNotFound();
});
