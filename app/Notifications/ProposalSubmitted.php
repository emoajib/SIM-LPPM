<?php

namespace App\Notifications;

use App\Models\Proposal;
use App\Models\Research;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Channels\HybridNotificationChannel;
use Illuminate\Notifications\Messages\MailMessage;

class ProposalSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Proposal $proposal,
        public User $submitter
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', HybridNotificationChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        $isResearch = $this->proposal->detailable instanceof Research;
        $proposalType = $isResearch ? 'Penelitian' : 'Pengabdian Masyarakat';
        $routeName = $isResearch
            ? 'research.proposal.show'
            : 'community-service.proposal.show';

        return [
            'type' => 'proposal_submitted',
            'title' => 'Proposal Baru Disubmit',
            'message' => "Proposal '{$this->proposal->title}' telah disubmit oleh {$this->submitter->name}",
            'body' => "Proposal {$proposalType} dengan judul '{$this->proposal->title}' telah berhasil disubmit oleh {$this->submitter->name} pada tanggal ".now()->format('d/m/Y H:i').'. Proposal ini sedang menunggu review dan persetujuan dari Dekan. Tim reviewer akan ditentukan kemudian oleh Kepala LPPM. Mohon menunggu informasi selanjutnya melalui sistem notifikasi.',
            'proposal_id' => $this->proposal->id,
            'submitter_id' => $this->submitter->id,
            'submitter_name' => $this->submitter->name,
            'proposal_type' => $isResearch ? 'research' : 'community_service',
            'link' => route($routeName, $this->proposal),
            'icon' => 'file-text',
            'created_at' => now()->toISOString(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isResearch = $this->proposal->detailable instanceof Research;
        $proposalType = $isResearch ? 'Penelitian' : 'Pengabdian Masyarakat';
        $routeName = $isResearch
            ? 'research.proposal.show'
            : 'community-service.proposal.show';
        $url = route($routeName, $this->proposal);

        return (new MailMessage)
            ->subject('[SIM LPPM] Proposal Baru Diajukan')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line("Proposal baru dengan judul **{$this->proposal->title}** telah diajukan oleh {$this->submitter->name}.")
            ->line("**Jenis Proposal:** {$proposalType}")
            ->line("**Diajukan oleh:** {$this->submitter->name}")
            ->line('Proposal ini perlu ditinjau dan disetujui sesuai dengan prosedur yang berlaku.')
            ->action('Lihat Detail Proposal', $url)
            ->line('Silakan akses proposal untuk melihat detail lengkap dan melakukan tindakan yang diperlukan.');
    }
}
