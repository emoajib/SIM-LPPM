<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Notification as LaravelNotification;

class HybridNotificationChannel
{
    /**
     * Send the notification.
     *
     * @param  mixed  $notifiable
     * @param  mixed  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        $whatsappNumber = $notifiable->whatsapp_number;
        $recipient = $notifiable->email;
        $channel = 'mail';

        // 1. Try WhatsApp if available
        if ($whatsappNumber) {
            try {
                // In a real implementation, you would call your WhatsApp service here
                // e.g., $this->whatsappService->send($whatsappNumber, $notification->toWhatsApp($notifiable));
                
                // For now, we simulate success logic or you can integrate an actual API.
                // If you have a WhatsApp driver, call it here.
                
                // Example logic (placeholder):
                // $success = $this->whatsappService->send($whatsappNumber, $notification->toWhatsapp($notifiable));
                // if ($success) {
                //     $this->log($notifiable, 'whatsapp', $whatsappNumber, $notification, 'success');
                //     return;
                // }

                // If we reach here, WhatsApp failed or wasn't configured correctly
                throw new \Exception('WhatsApp service unavailable or failed');

            } catch (\Exception $e) {
                Log::error("WhatsApp notification failed: " . $e->getMessage());
                $this->log($notifiable, 'whatsapp', $whatsappNumber, $notification, 'failed', $e->getMessage());
                // Fallback to Email is implicit as we won't 'return' here
            }
        }

        // 2. Fallback/Default to Email
        try {
            LaravelNotification::route('mail', $recipient)->notify($notification);
            $this->log($notifiable, 'mail', $recipient, $notification, 'success');
        } catch (\Exception $e) {
            Log::error("Email notification failed: " . $e->getMessage());
            $this->log($notifiable, 'mail', $recipient, $notification, 'failed', $e->getMessage());
        }
    }

    /**
     * Log the notification attempt.
     */
    protected function log($notifiable, string $channel, ?string $recipient, Notification $notification, string $status, ?string $error = null): void
    {
        NotificationLog::create([
            'user_id' => $notifiable->id,
            'channel' => $channel,
            'recipient' => $recipient,
            'notification_type' => get_class($notification),
            'payload' => [
                'data' => $notification->toMail($notifiable)->toArray(), // Basic payload extraction
            ],
            'status' => $status,
            'error_message' => $error,
            'sent_at' => ($status === 'success') ? now() : null,
        ]);
    }
}
