<?php

namespace App\Notifications;

use App\Models\Proposal;
use App\Models\Research;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Channels\HybridNotificationChannel;
use Illuminate\Notifications\Messages\MailMessage;

class ProposalRevised extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Proposal $proposal,
        public int $round,
        public bool $isAdmin = false
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', HybridNotificationChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        $isResearch = $this->proposal->detailable instanceof Research;
        $proposalType = $isResearch ? 'Penelitian' : 'Pengabdian Masyarakat';

        if ($this->isAdmin) {
            return [
                'type' => 'proposal_revised_admin',
                'title' => 'Proposal Direvisi dan Diajukan Ulang',
                'message' => "Proposal '{$this->proposal->title}' telah direvisi dan diajukan ulang (putaran {$this->round})",
                'body' => "Proposal {$proposalType} dengan judul '{$this->proposal->title}' telah direvisi oleh pengusul dan diajukan ulang untuk review. Ini adalah putaran review ke-{$this->round}. Reviewer yang telah ditugaskan sebelumnya akan diminta untuk melakukan review ulang.",
                'proposal_id' => $this->proposal->id,
                'proposal_type' => $isResearch ? 'research' : 'community_service',
                'round' => $this->round,
                'link' => route($isResearch ? 'research.proposal.show' : 'community-service.proposal.show', $this->proposal),
                'icon' => 'refresh-cw',
                'created_at' => now()->toISOString(),
            ];
        }

        return [
            'type' => 'proposal_revised',
            'title' => 'Permintaan Review Ulang',
            'message' => "Proposal '{$this->proposal->title}' telah direvisi dan memerlukan review ulang",
            'body' => "Proposal {$proposalType} dengan judul '{$this->proposal->title}' telah direvisi oleh pengusul. Anda diminta untuk melakukan review ulang terhadap proposal ini. Ini adalah putaran review ke-{$this->round}. Silakan periksa perubahan yang telah dilakukan dan berikan penilaian baru.",
            'proposal_id' => $this->proposal->id,
            'proposal_type' => $isResearch ? 'research' : 'community_service',
            'round' => $this->round,
            'link' => route($isResearch ? 'research.proposal.show' : 'community-service.proposal.show', $this->proposal),
            'icon' => 'refresh-cw',
            'created_at' => now()->toISOString(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isResearch = $this->proposal->detailable instanceof Research;
        $proposalType = $isResearch ? 'Penelitian' : 'Pengabdian Masyarakat';
        $url = route($isResearch ? 'research.proposal.show' : 'community-service.proposal.show', $this->proposal);

        if ($this->isAdmin) {
            return (new MailMessage)
                ->subject('[SIM LPPM] Proposal Direvisi - Putaran '.$this->round)
                ->greeting('Halo, '.$notifiable->name.'!')
                ->line("Proposal **{$this->proposal->title}** telah direvisi dan diajukan ulang.")
                ->line("**Jenis Proposal:** {$proposalType}")
                ->line("**Putaran Review:** {$this->round}")
                ->line('Reviewer yang telah ditugaskan sebelumnya akan diminta untuk melakukan review ulang.')
                ->action('Lihat Proposal', $url)
                ->line('Anda menerima pemberitahuan ini karena Anda adalah administrator sistem.');
        }

        return (new MailMessage)
            ->subject('[SIM LPPM] Permintaan Review Ulang - Putaran '.$this->round)
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line("Proposal **{$this->proposal->title}** telah direvisi oleh pengusul.")
            ->line("**Jenis Proposal:** {$proposalType}")
            ->line("**Putaran Review:** {$this->round}")
            ->line('Anda diminta untuk melakukan review ulang terhadap proposal ini.')
            ->action('Review Ulang', $url)
            ->line('Silakan periksa perubahan yang telah dilakukan dan berikan penilaian baru sesuai dengan kriteria yang telah ditetapkan.');
    }
}
