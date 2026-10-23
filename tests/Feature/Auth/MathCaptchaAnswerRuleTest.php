<?php

use App\Rules\MathCaptchaAnswer;
use App\Services\Security\MathCaptchaService;
use Illuminate\Support\Facades\Validator;

function mathRuleChallenge(): array
{
    return app(MathCaptchaService::class)->issue(session()->getId());
}

function mathRuleResult(string $id, mixed $value): Illuminate\Contracts\Validation\Validator
{
    return Validator::make(['answer' => $value], ['answer' => [new MathCaptchaAnswer($id)]]);
}

test('rule accepts the stored answer', function () {
    $this->get('/login');
    config(['captcha.min_solve_seconds' => 0]);

    $issued = mathRuleChallenge();

    expect(mathRuleResult($issued['id'], (string) $issued['challenge']['answer'])->passes())->toBeTrue();
});

test('rule rejects a wrong answer', function () {
    $this->get('/login');
    config(['captcha.min_solve_seconds' => 0]);

    $issued = mathRuleChallenge();
    $validator = mathRuleResult($issued['id'], '999');

    expect($validator->passes())->toBeFalse()
        ->and($validator->errors()->first('answer'))->toBe('Jawaban keamanan salah.');
});

test('rule blocks verification after too many attempts', function () {
    $this->get('/login');
    config([
        'captcha.min_solve_seconds' => 0,
        'captcha.throttle.verify' => 2,
        'captcha.throttle.verify_decay' => 300,
    ]);

    $issued = mathRuleChallenge();

    mathRuleResult($issued['id'], '999')->passes();
    mathRuleResult($issued['id'], '998')->passes();

    $blocked = mathRuleResult($issued['id'], '997');

    expect($blocked->passes())->toBeFalse()
        ->and($blocked->errors()->first('answer'))->toContain('Terlalu banyak percobaan');
});

test('rule rejects expired challenges', function () {
    $this->get('/login');

    expect(mathRuleResult(str_repeat('c', 32), '5')->passes())->toBeFalse();
});
