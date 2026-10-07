<?php

namespace App\Notifications;

use App\Models\BudgetAmendment;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BudgetAmendmentSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public BudgetAmendment $amendment,
        public User $requestedBy
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        /** @var Proposal $proposal */
        $proposal = $this->amendment->proposal;

        return [
            'type' => 'budget_amendment_submitted',
            'title' => 'Pengajuan Amandemen RAB',
            'message' => "Amandemen RAB ke-{$this->amendment->version} untuk proposal \"{$proposal->title}\" diajukan oleh {$this->requestedBy->name}.",
            'body' => "Alasan pengajuan:\n\n{$this->amendment->reason}\n\nSilakan tinjau dan putuskan amandemen tersebut.",
            'proposal_id' => $proposal->id,
            'amendment_id' => $this->amendment->id,
            'icon' => 'coins',
            'created_at' => now()->toISOString(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var Proposal $proposal */
        $proposal = $this->amendment->proposal;

        return (new MailMessage)
            ->subject('[SIM LPPM] Pengajuan Amandemen RAB — '.$proposal->title)
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line("Amandemen RAB ke-{$this->amendment->version} untuk proposal **{$proposal->title}** diajukan oleh **{$this->requestedBy->name}**.")
            ->line('**Alasan:**')
            ->line('> '.$this->amendment->reason)
            ->line('Silakan tinjau dan putuskan amandemen tersebut di sistem.');
    }
}
