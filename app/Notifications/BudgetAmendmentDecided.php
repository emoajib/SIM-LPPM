<?php

namespace App\Notifications;

use App\Models\BudgetAmendment;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BudgetAmendmentDecided extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public BudgetAmendment $amendment,
        public User $decidedBy,
        public bool $approved
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        /** @var Proposal $proposal */
        $proposal = $this->amendment->proposal;
        $verdict = $this->approved ? 'disetujui' : 'ditolak';

        return [
            'type' => 'budget_amendment_decided',
            'title' => 'Amandemen RAB '.($this->approved ? 'Disetujui' : 'Ditolak'),
            'message' => "Amandemen RAB ke-{$this->amendment->version} untuk proposal \"{$proposal->title}\" {$verdict} oleh {$this->decidedBy->name}.",
            'body' => $this->amendment->decision_notes
                ? "Catatan peninjau:\n\n{$this->amendment->decision_notes}"
                : null,
            'proposal_id' => $proposal->id,
            'amendment_id' => $this->amendment->id,
            'icon' => $this->approved ? 'circle-check' : 'x',
            'created_at' => now()->toISOString(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var Proposal $proposal */
        $proposal = $this->amendment->proposal;
        $verdict = $this->approved ? 'disetujui' : 'ditolak';

        return (new MailMessage)
            ->subject('[SIM LPPM] Amandemen RAB '.$verdict.' — '.$proposal->title)
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line("Amandemen RAB ke-{$this->amendment->version} untuk proposal **{$proposal->title}** {$verdict} oleh **{$this->decidedBy->name}**.")
            ->line('Terima kasih atas partisipasi Anda dalam sistem LPPM ITSNU.');
    }
}
