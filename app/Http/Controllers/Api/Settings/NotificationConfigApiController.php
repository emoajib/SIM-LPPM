<?php

// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Services\NotificationConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationConfigApiController extends Controller
{
    public function __construct(
        private NotificationConfigService $service
    ) {}

    /**
     * Get notification configuration (SMTP + WhatsApp).
     * Passwords and API keys are NEVER returned for security.
     */
    public function show(): JsonResponse
    {
        abort_unless(Auth::user()?->activeHasAnyRole(['admin lppm', 'superadmin']), 403);

        return response()->json([
            'smtp' => [
                'host' => $this->service->get('MAIL_HOST'),
                'port' => $this->service->get('MAIL_PORT'),
                'username' => $this->service->get('MAIL_USERNAME'),
                'encryption' => $this->service->get('MAIL_ENCRYPTION'),
                'from_address' => $this->service->get('MAIL_FROM_ADDRESS'),
                'from_name' => $this->service->get('MAIL_FROM_NAME'),
                // password NEVER returned
            ],
            'whatsapp' => [
                'api_url' => $this->service->get('WHATSAPP_API_URL'),
                'sender_id' => $this->service->get('WHATSAPP_SENDER_ID'),
                // api_key NEVER returned
            ],
        ]);
    }

    /**
     * Update notification configuration.
     * Empty password/api_key means "don't change".
     */
    public function update(Request $request): JsonResponse
    {
        abort_unless(Auth::user()?->activeHasAnyRole(['admin lppm', 'superadmin']), 403);

        $validated = $request->validate([
            'smtp.host' => 'nullable|string|max:255',
            'smtp.port' => 'nullable|integer|min:1|max:65535',
            'smtp.username' => 'nullable|string|max:255',
            'smtp.password' => 'nullable|string',
            'smtp.encryption' => 'nullable|in:ssl,tls,none',
            'smtp.from_address' => 'nullable|email|max:255',
            'smtp.from_name' => 'nullable|string|max:255',
            'whatsapp.api_url' => 'nullable|url|max:500',
            'whatsapp.api_key' => 'nullable|string',
            'whatsapp.sender_id' => 'nullable|string|max:50',
        ]);

        $this->service->save($validated);

        return response()->json(['message' => 'Konfigurasi notifikasi disimpan.']);
    }

    /**
     * Send test email.
     */
    public function testEmail(Request $request): JsonResponse
    {
        abort_unless(Auth::user()?->activeHasAnyRole(['admin lppm', 'superadmin']), 403);

        $request->validate(['email' => 'required|email']);

        $result = $this->service->sendTestEmail($request->email);

        return response()->json($result);
    }

    /**
     * Send test WhatsApp message.
     */
    public function testWhatsApp(Request $request): JsonResponse
    {
        abort_unless(Auth::user()?->activeHasAnyRole(['admin lppm', 'superadmin']), 403);

        $request->validate(['phone' => 'required|string|regex:/^62[0-9]{9,12}$/']);

        $result = $this->service->sendTestWhatsApp($request->phone);

        return response()->json($result);
    }
}
