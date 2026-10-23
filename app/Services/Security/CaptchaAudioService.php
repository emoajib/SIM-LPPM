<?php

namespace App\Services\Security;

use App\Models\Setting;
use RuntimeException;

class CaptchaAudioService
{
    public function available(): bool
    {
        if (! (bool) Setting::get('captcha_audio_enabled', true)) {
            return false;
        }

        return $this->assetsComplete($this->voice());
    }

    /**
     * @return list<array{key: string, label: string, locale: string, available: bool}>
     */
    public function voices(): array
    {
        $voices = [];

        foreach ((array) config('captcha.audio_voices', []) as $key => $meta) {
            if (! is_array($meta)) {
                continue;
            }

            $voices[] = [
                'key' => (string) $key,
                'label' => (string) ($meta['label'] ?? $key),
                'locale' => (string) ($meta['locale'] ?? ''),
                'available' => $this->assetsComplete((string) $key),
            ];
        }

        return $voices;
    }

    public function voice(): string
    {
        $configured = (string) Setting::get(
            'captcha_audio_voice',
            config('captcha.audio_default_voice', 'damayanti')
        );
        $default = (string) config('captcha.audio_default_voice', 'damayanti');

        if ($this->assetsComplete($configured)) {
            return $configured;
        }

        return $this->assetsComplete($default) ? $default : $configured;
    }

    /**
     * @param  array<string, mixed>  $challenge
     */
    public function render(array $challenge): string
    {
        $voice = $this->voice();

        if (! $this->assetsComplete($voice)) {
            throw new RuntimeException('Audio CAPTCHA assets are missing.');
        }

        $rate = (int) config('captcha.audio_sample_rate', 22050);
        $pcm = $this->silence($rate, 140);

        $tokens = [
            'd'.(int) $challenge['a'],
            $this->operatorToken((string) $challenge['op']),
            'd'.(int) $challenge['b'],
        ];

        foreach ($tokens as $index => $token) {
            if ($index > 0) {
                $pcm .= $this->silence($rate, 90);
            }

            $pcm .= (string) file_get_contents($this->path($token, $voice));
        }

        $pcm .= $this->silence($rate, 220);

        return $this->wav($pcm, $rate);
    }

    private function assetsComplete(string $voice): bool
    {
        if (! array_key_exists($voice, (array) config('captcha.audio_voices', []))) {
            return false;
        }

        foreach ($this->tokens() as $token) {
            if (! is_file($this->path($token, $voice))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private function tokens(): array
    {
        return [
            'd0', 'd1', 'd2', 'd3', 'd4', 'd5', 'd6', 'd7', 'd8', 'd9',
            'op_plus', 'op_minus', 'op_times',
        ];
    }

    private function operatorToken(string $op): string
    {
        return match ($op) {
            '+' => 'op_plus',
            '×' => 'op_times',
            default => 'op_minus',
        };
    }

    private function path(string $token, string $voice): string
    {
        return rtrim((string) config('captcha.audio_path'), '/').'/'.$voice.'/'.$token.'.pcm';
    }

    private function silence(int $rate, int $milliseconds): string
    {
        $samples = (int) round($rate * $milliseconds / 1000);

        return str_repeat("\x00\x00", max(1, $samples));
    }

    private function wav(string $pcm, int $rate): string
    {
        $size = strlen($pcm);

        return 'RIFF'.pack('V', 36 + $size).'WAVE'
            .'fmt '.pack('VvvVVvv', 16, 1, 1, $rate, $rate * 2, 2, 16)
            .'data'.pack('V', $size).$pcm;
    }
}
