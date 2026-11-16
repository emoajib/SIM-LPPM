<?php

// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager

namespace App\Services;

use App\Models\NotificationSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class NotificationConfigService
{
    public function get(string $key, $default = null)
    {
        $db = NotificationSetting::get($key);
        if ($db !== null && $db !== '') {
            return $db;
        }

        return getenv($key) ?: $default;
    }

    /**
     * Get all notification settings (without sensitive values).
     */
    public function getAll(): array
    {
        return [
            'MAIL_HOST' => $this->get('MAIL_HOST'),
            'MAIL_PORT' => $this->get('MAIL_PORT'),
            'MAIL_USERNAME' => $this->get('MAIL_USERNAME'),
            'MAIL_ENCRYPTION' => $this->get('MAIL_ENCRYPTION'),
            'MAIL_FROM_ADDRESS' => $this->get('MAIL_FROM_ADDRESS'),
            'MAIL_FROM_NAME' => $this->get('MAIL_FROM_NAME'),
            'WHATSAPP_API_URL' => $this->get('WHATSAPP_API_URL'),
            'WHATSAPP_SENDER_ID' => $this->get('WHATSAPP_SENDER_ID'),
        ];
    }

    /**
     * Save notification settings from validated API request.
     * Empty password/api_key means "don't change".
     */
    public function save(array $validated): void
    {
        // SMTP settings
        if (isset($validated['smtp'])) {
            $this->set('MAIL_HOST', $validated['smtp']['host'] ?? '');
            $this->set('MAIL_PORT', $validated['smtp']['port'] ?? '587');
            $this->set('MAIL_USERNAME', $validated['smtp']['username'] ?? '');
            if (! empty($validated['smtp']['password'])) {
                $this->set('MAIL_PASSWORD', $validated['smtp']['password'], true);
            }
            $this->set('MAIL_ENCRYPTION', $validated['smtp']['encryption'] ?? 'tls');
            $this->set('MAIL_FROM_ADDRESS', $validated['smtp']['from_address'] ?? '');
            $this->set('MAIL_FROM_NAME', $validated['smtp']['from_name'] ?? '');
        }

        // WhatsApp settings
        if (isset($validated['whatsapp'])) {
            $this->set('WHATSAPP_API_URL', $validated['whatsapp']['api_url'] ?? '');
            if (! empty($validated['whatsapp']['api_key'])) {
                $this->set('WHATSAPP_API_KEY', $validated['whatsapp']['api_key'], true);
            }
            $this->set('WHATSAPP_SENDER_ID', $validated['whatsapp']['sender_id'] ?? '');
        }

        // Refresh mail config immediately so it takes effect without restart
        $this->refreshMailConfig();
    }

    /**
     * Set a notification setting with optional encryption.
     */
    private function set(string $key, $value, bool $encrypted = false): void
    {
        NotificationSetting::set($key, $value, $encrypted);
    }

    /**
     * Refresh Laravel mail configuration from current settings.
     * Called after save to make changes take effect immediately.
     */
    public function refreshMailConfig(): void
    {
        Config::set('mail.mailers.smtp.host', $this->get('MAIL_HOST'));
        Config::set('mail.mailers.smtp.port', $this->get('MAIL_PORT'));
        Config::set('mail.mailers.smtp.username', $this->get('MAIL_USERNAME'));
        Config::set('mail.mailers.smtp.password', $this->get('MAIL_PASSWORD'));
        Config::set('mail.mailers.smtp.encryption', $this->get('MAIL_ENCRYPTION'));
        Config::set('mail.from.address', $this->get('MAIL_FROM_ADDRESS'));
        Config::set('mail.from.name', $this->get('MAIL_FROM_NAME'));
    }

    /**
     * Send test email using current SMTP configuration.
     */
    public function sendTestEmail(string $email): array
    {
        try {
            Mail::raw('Test notifikasi dari SIM LPPM ITSNU.', function ($message) use ($email) {
                $message->to($email)->subject('Test Notifikasi SIM LPPM');
            });

            return ['success' => true, 'message' => 'Email test terkirim.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send test WhatsApp message using current WhatsApp configuration.
     */
    public function sendTestWhatsApp(string $phone): array
    {
        try {
            $url = $this->get('WHATSAPP_API_URL');
            $key = $this->get('WHATSAPP_API_KEY');

            if (! $url || ! $key) {
                return ['success' => false, 'message' => 'Konfigurasi WhatsApp belum lengkap.'];
            }

            $res = Http::withHeaders(['Authorization' => 'Bearer '.$key])
                ->asForm()
                ->post($url, ['target' => $phone, 'message' => 'Test notifikasi SIM-LPPM']);

            if ($res->successful()) {
                return ['success' => true, 'message' => 'WhatsApp test terkirim.'];
            }

            return ['success' => false, 'message' => 'Gagal: '.$res->body()];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
