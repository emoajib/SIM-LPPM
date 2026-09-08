<?php

namespace App\Notifications;

use App\Models\ProgressReport;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager
class ReportRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ProgressReport $report,
        public User $rejectedBy,
        public string $rejectionNotes
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $proposal = $this->report->proposal;
        $isResearch = $proposal?->detailable_type === 'App\Models\Research';
        $role = $this->rejectedBy->activeHasRole('dekan') ? 'Dekan' : 'Kepala LPPM';
        $link = $isResearch
            ? route('research.final-report.show', $proposal)
            : route('community-service.final-report.show', $proposal);

        return [
            'type' => 'report_rejected',
            'title' => 'Laporan Akhir Ditolak',
            'message' => "Laporan Akhir untuk proposal \"{$proposal?->title}\" telah ditolak oleh {$role}.",
            'body' => "Laporan Akhir Anda untuk proposal \"{$proposal?->title}\" ditolak oleh {$role} ({$this->rejectedBy->name}) dengan catatan:\n\n{$this->rejectionNotes}\n\nSilakan perbaiki laporan sesuai catatan tersebut dan ajukan kembali.",
            'rejection_notes' => $this->rejectionNotes,
            'rejected_by' => $this->rejectedBy->name,
            'role' => $role,
            'report_id' => $this->report->id,
            'proposal_id' => $proposal?->id,
            'link' => $link,
            'icon' => 'x-circle',
            'created_at' => now()->toISOString(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $proposal = $this->report->proposal;
        $isResearch = $proposal?->detailable_type === 'App\Models\Research';
        $role = $this->rejectedBy->activeHasRole('dekan') ? 'Dekan' : 'Kepala LPPM';
        $type = $isResearch ? 'Penelitian' : 'Pengabdian kepada Masyarakat';
        $link = $isResearch
            ? route('research.final-report.show', $proposal)
            : route('community-service.final-report.show', $proposal);

        return (new MailMessage)
            ->subject('[SIM LPPM] Laporan Akhir Ditolak — '.$proposal?->title)
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line("Laporan Akhir **{$type}** Anda telah ditolak oleh **{$role}**.")
            ->line('**Judul Proposal:** '.$proposal?->title)
            ->line('**Ditolak oleh:** '.$this->rejectedBy->name.' ('.$role.')')
            ->line('**Catatan Penolakan:**')
            ->line('> '.$this->rejectionNotes)
            ->line('Silakan perbaiki laporan sesuai catatan di atas, kemudian ajukan kembali melalui sistem.')
            ->action('Buka Halaman Laporan', $link)
            ->line('Terima kasih atas partisipasi Anda dalam sistem LPPM ITSNU.');
    }
}
