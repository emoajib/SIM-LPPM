<?php

namespace App\Notifications;

use App\Models\Proposal;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LpjReturned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Proposal $proposal,
        public User $returnedBy,
        public string $returnNotes,
        public string $returnedByRole = 'Kepala LPPM'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    protected function logbookLink(): string
    {
        $isResearch = $this->proposal->detailable_type === 'App\Models\Research';

        return $isResearch
            ? route('research.daily-note.show', $this->proposal)
            : route('community-service.daily-note.show', $this->proposal);
    }

    public function toDatabase(object $notifiable): array
    {
        $role = $this->returnedByRole;

        return [
            'type' => 'lpj_returned',
            'title' => 'Laporan Keuangan Dikembalikan',
            'message' => "Laporan Keuangan (LPJ) untuk proposal \"{$this->proposal->title}\" dikembalikan oleh {$role} untuk diperbaiki.",
            'body' => "Laporan Keuangan (LPJ) Anda untuk proposal \"{$this->proposal->title}\" dikembalikan oleh {$role} ({$this->returnedBy->name}) dengan catatan:\n\n{$this->returnNotes}\n\nSilakan perbaiki catatan/berkas LPJ sesuai catatan tersebut.",
            'return_notes' => $this->returnNotes,
            'returned_by' => $this->returnedBy->name,
            'role' => $role,
            'proposal_id' => $this->proposal->id,
            'link' => $this->logbookLink(),
            'icon' => 'rotate-ccw',
            'created_at' => now()->toISOString(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $role = $this->returnedByRole;

        return (new MailMessage)
            ->subject('[SIM LPPM] LPJ Dikembalikan — '.$this->proposal->title)
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line("Laporan Keuangan (LPJ) Anda telah dikembalikan oleh **{$role}** untuk diperbaiki.")
            ->line('**Judul Proposal:** '.$this->proposal->title)
            ->line('**Dikembalikan oleh:** '.$this->returnedBy->name.' ('.$role.')')
            ->line('**Catatan Pengembalian:**')
            ->line('> '.$this->returnNotes)
            ->line('Silakan perbaiki catatan/berkas LPJ sesuai catatan di atas.')
            ->action('Buka Halaman Logbook', $this->logbookLink())
            ->line('Terima kasih atas partisipasi Anda dalam sistem LPPM ITSNU.');
    }
}
