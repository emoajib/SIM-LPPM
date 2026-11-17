<?php

// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Security\CaptchaAudioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Modern-theme API for CAPTCHA audio settings.
 * Mirrors App\Livewire\Settings\CaptchaSettings.
 */
class CaptchaApiController extends Controller
{
    public function __construct(
        private CaptchaAudioService $captcha
    ) {}

    public function show(): JsonResponse
    {
        $this->authorizeAdmin();

        return response()->json([
            'audio_enabled' => (bool) Setting::get('captcha_audio_enabled', true),
            'audio_voice' => $this->captcha->voice(),
            'voices' => $this->captcha->voices(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'audio_enabled' => 'sometimes|boolean',
            'audio_voice' => 'sometimes|string',
        ]);

        if (array_key_exists('audio_enabled', $validated)) {
            Setting::set('captcha_audio_enabled', (bool) $validated['audio_enabled'], 'boolean');
        }

        if (array_key_exists('audio_voice', $validated)) {
            $voice = collect($this->captcha->voices())->firstWhere('key', $validated['audio_voice']);

            if ($voice === null || ! $voice['available']) {
                return response()->json([
                    'message' => 'Jenis suara tersebut tidak tersedia.',
                    'errors' => ['audio_voice' => ['Jenis suara tersebut tidak tersedia.']],
                ], 422);
            }

            Setting::set('captcha_audio_voice', $validated['audio_voice'], 'string');
        }

        return response()->json([
            'message' => 'Pengaturan audio CAPTCHA diperbarui.',
            'data' => $this->show()->getData(true),
        ]);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(
            Auth::user()?->activeHasAnyRole(['admin lppm', 'superadmin']),
            403
        );
    }
}
