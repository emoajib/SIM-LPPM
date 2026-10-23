<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Security\CaptchaAudioService;
use App\Services\Security\MathCaptchaService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class MathCaptchaController extends Controller
{
    public function image(Request $request, string $id, MathCaptchaService $captcha): Response
    {
        $challenge = $captcha->find($id, $request->session()->getId());

        if ($challenge === null) {
            return $this->missing();
        }

        try {
            $rendered = $captcha->render($challenge);
        } catch (Throwable $e) {
            Log::critical('CAPTCHA image could not be rendered', [
                'exception' => class_basename($e),
            ]);

            return $this->missing();
        }

        return $this->reply($rendered['body'], $rendered['mime']);
    }

    public function audio(
        Request $request,
        string $id,
        MathCaptchaService $captcha,
        CaptchaAudioService $audio
    ): Response {
        $challenge = $captcha->find($id, $request->session()->getId());

        if ($challenge === null || ! $audio->available()) {
            return $this->missing();
        }

        try {
            return $this->reply($audio->render($challenge), 'audio/wav');
        } catch (Throwable $e) {
            Log::critical('CAPTCHA audio could not be rendered', [
                'exception' => class_basename($e),
            ]);

            return $this->missing();
        }
    }

    private function missing(): Response
    {
        return response('', 404, $this->headers('text/plain'));
    }

    private function reply(string $body, string $mime): Response
    {
        return response($body, 200, $this->headers($mime));
    }

    /**
     * @return array<string, string>
     */
    private function headers(string $mime): array
    {
        return [
            'Content-Type' => $mime,
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Content-Disposition' => 'inline',
        ];
    }
}
