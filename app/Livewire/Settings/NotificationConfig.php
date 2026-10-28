<?php

namespace App\Livewire\Settings;

use App\Models\NotificationSetting;
use App\Services\NotificationConfigService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class NotificationConfig extends Component
{
    public string $mailHost = '';
    public string $mailPort = '587';
    public string $mailUsername = '';
    public string $mailPassword = '';
    public string $mailEncryption = 'tls';
    public string $mailFromAddress = '';
    public string $mailFromName = '';
    public string $waApiUrl = '';
    public string $waApiKey = '';
    public string $waSenderId = '';
    public string $testEmail = '';
    public string $testPhone = '';
    public ?string $message = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->activeHasAnyRole(['admin lppm', 'superadmin']), 403);

        $cfg = app(NotificationConfigService::class);
        $this->mailHost = (string) $cfg->get('MAIL_HOST', '');
        $this->mailPort = (string) $cfg->get('MAIL_PORT', '587');
        $this->mailUsername = (string) $cfg->get('MAIL_USERNAME', '');
        $this->mailEncryption = (string) $cfg->get('MAIL_ENCRYPTION', 'tls');
        $this->mailFromAddress = (string) $cfg->get('MAIL_FROM_ADDRESS', '');
        $this->mailFromName = (string) $cfg->get('MAIL_FROM_NAME', '');
        $this->waApiUrl = (string) $cfg->get('WHATSAPP_API_URL', '');
        $this->waSenderId = (string) $cfg->get('WHATSAPP_SENDER_ID', '');
    }

    public function save(): void
    {
        $this->validate([
            'mailHost' => 'required|string',
            'mailPort' => 'required|numeric',
            'mailFromAddress' => 'required|email',
            'mailFromName' => 'required|string',
        ]);

        $pairs = [
            'MAIL_HOST' => [$this->mailHost, false],
            'MAIL_PORT' => [$this->mailPort, false],
            'MAIL_USERNAME' => [$this->mailUsername, false],
            'MAIL_PASSWORD' => [$this->mailPassword, true],
            'MAIL_ENCRYPTION' => [$this->mailEncryption, false],
            'MAIL_FROM_ADDRESS' => [$this->mailFromAddress, false],
            'MAIL_FROM_NAME' => [$this->mailFromName, false],
            'WHATSAPP_API_URL' => [$this->waApiUrl, false],
            'WHATSAPP_API_KEY' => [$this->waApiKey, true],
            'WHATSAPP_SENDER_ID' => [$this->waSenderId, false],
        ];

        foreach ($pairs as $key => [$val, $enc]) {
            NotificationSetting::set($key, $val, $enc);
        }

        $this->message = 'Konfigurasi notifikasi tersimpan.';
    }

    public function sendTest(): void
    {
        $this->validate(['testEmail' => 'required|email']);
        try {
            Mail::raw('Test notifikasi dari SIM LPPM ITSNU.', function ($m) {
                $m->to($this->testEmail)->subject('Test Notifikasi SIM LPPM');
            });
            $this->message = 'Email test terkirim ke '.$this->testEmail;
        } catch (\Throwable $e) {
            $this->message = 'Gagal kirim: '.$e->getMessage();
        }
    }

    public function sendTestWa(): void
    {
        $this->validate(['testPhone' => 'required|string']);
        try {
            $url = (string) app(\App\Services\NotificationConfigService::class)->get('WHATSAPP_API_URL', '');
            if ($url === '') {
                throw new \Exception('WHATSAPP_API_URL belum diisi');
            }
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer '.app(\App\Services\NotificationConfigService::class)->get('WHATSAPP_API_KEY', ''),
            ])->post($url, [
                'to' => $this->testPhone,
                'message' => 'Test notifikasi WhatsApp dari SIM LPPM ITSNU.',
            ]);
            if ($response->successful()) {
                $this->message = 'Pesan test WhatsApp terkirim ke '.$this->testPhone;
            } else {
                $this->message = 'Gagal kirim WA: '.$response->status().' '.$response->body();
            }
        } catch (\Throwable $e) {
            $this->message = 'Gagal kirim WA: '.$e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.settings.notification-config');
    }
}
