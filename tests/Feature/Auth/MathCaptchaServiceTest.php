<?php

use App\Livewire\Auth\Login;
use App\Services\Security\MathCaptchaService;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

function mathIssue(): array
{
    return app(MathCaptchaService::class)->issue(session()->getId());
}

test('challenge answers stay server side only', function () {
    $this->get('/login');

    $issued = mathIssue();

    expect($issued['id'])->toHaveLength(32)
        ->and($issued['challenge']['answer'])->toBeInt()
        ->and(Cache::get('captcha:math:'.$issued['id']))->toMatchArray([
            'answer' => $issued['challenge']['answer'],
        ]);
});

test('login snapshot never exposes the arithmetic operands', function () {
    $response = $this->get('/login');

    preg_match('/wire:snapshot="([^"]+)"/', $response->getContent(), $matches);
    $snapshot = json_decode(htmlspecialchars_decode($matches[1] ?? ''), true);

    expect($snapshot)->toBeArray()
        ->and($snapshot['data'] ?? [])->not->toHaveKeys(['n1', 'n2'])
        ->and($snapshot['data']['captchaId'] ?? '')->toHaveLength(32);

    expect(Livewire::test(Login::class)->html())->not->toContain('Berapa hasil dari');
});

test('verify accepts the correct answer exactly once', function () {
    $this->get('/login');
    config(['captcha.min_solve_seconds' => 0]);

    $issued = mathIssue();
    $service = app(MathCaptchaService::class);
    $answer = (string) $issued['challenge']['answer'];

    expect($service->verify($issued['id'], $answer, session()->getId()))->toBe(MathCaptchaService::OK)
        ->and($service->verify($issued['id'], $answer, session()->getId()))->toBe(MathCaptchaService::EXPIRED);
});

test('verify rejects answers from another session', function () {
    $this->get('/login');

    $issued = mathIssue();

    expect(app(MathCaptchaService::class)->verify($issued['id'], '1', 'foreign-session'))
        ->toBe(MathCaptchaService::EXPIRED);
});

test('verify rejects instant answers and burns the challenge', function () {
    $this->get('/login');

    $issued = mathIssue();
    $service = app(MathCaptchaService::class);
    $answer = (string) $issued['challenge']['answer'];

    expect($service->verify($issued['id'], $answer, session()->getId()))->toBe(MathCaptchaService::TOO_FAST)
        ->and($service->verify($issued['id'], $answer, session()->getId()))->toBe(MathCaptchaService::EXPIRED);
});

test('verify locks the challenge after too many wrong answers', function () {
    $this->get('/login');
    config(['captcha.max_attempts' => 2]);

    $issued = mathIssue();
    $service = app(MathCaptchaService::class);

    expect($service->verify($issued['id'], '999', session()->getId()))->toBe(MathCaptchaService::INVALID)
        ->and($service->verify($issued['id'], 'abc', session()->getId()))->toBe(MathCaptchaService::INVALID)
        ->and($service->verify($issued['id'], (string) $issued['challenge']['answer'], session()->getId()))
        ->toBe(MathCaptchaService::EXPIRED);
});

test('svg fallback draws glyph paths without text elements', function () {
    $svg = app(MathCaptchaService::class)->renderSvg([
        'a' => 7,
        'b' => 4,
        'op' => '+',
        'answer' => 11,
    ]);

    expect($svg)->toContain('<svg')
        ->toContain('<polyline')
        ->not->toContain('<text');
});
