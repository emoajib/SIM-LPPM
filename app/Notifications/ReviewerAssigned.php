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

class ReviewerAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Proposal $proposal,
        public User $reviewer,
        public string $reviewDeadline
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', HybridNotificationChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        $isResearch = $this->proposal->detailable instanceof Research;
        $proposalType = $isResearch ? 'Penelitian' : 'Pengabdian Masyarakat';

        return [
            'type' => 'reviewer_assigned',
            'title' => 'Anda Ditugaskan Sebagai Reviewer',
            'message' => "Anda ditugaskan untuk mereview proposal '{$this->proposal->title}'",
            'body' => "Anda telah ditugaskan sebagai reviewer untuk proposal {$proposalType} dengan judul '{$this->proposal->title}'. Mohon melakukan review sesuai dengan panduan reviewer yang tersedia di sistem. Batas waktu review adalah tanggal {$this->reviewDeadline}. Silakan akses proposal melalui link di bawah ini untuk memulai proses review. Pastikan untuk memberikan evaluasi yang objektif dan konstruktif sesuai dengan kriteria yang telah ditetapkan.",
            'proposal_id' => $this->proposal->id,
            'reviewer_id' => $this->reviewer->id,
            'reviewer_name' => $this->reviewer->name,
            'review_deadline' => $this->reviewDeadline,
            'proposal_type' => $isResearch ? 'research' : 'community_service',
            'link' => route($isResearch ? 'research.proposal.show' : 'community-service.proposal.show', $this->proposal),
            'icon' => 'user-check',
            'created_at' => now()->toISOString(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isResearch = $this->proposal->detailable instanceof Research;
        $proposalType = $isResearch ? 'Penelitian' : 'Pengabdian Masyarakat';
        $url = route($isResearch ? 'research.proposal.show' : 'community-service.proposal.show', $this->proposal);

        return (new MailMessage)
            ->subject('[SIM LPPM] Penugasan Reviewer')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line("Anda telah ditugaskan sebagai reviewer untuk proposal **{$this->proposal->title}**.")
            ->line("**Jenis Proposal:** {$proposalType}")
            ->line("**Batas Waktu Review:** {$this->reviewDeadline}")
            ->line('Mohon melakukan review sesuai dengan panduan reviewer yang tersedia di sistem.')
            ->action('Mulai Review', $url)
            ->line('Pastikan untuk memberikan evaluasi yang objektif dan konstruktif sesuai dengan kriteria yang telah ditetapkan.');
    }
}
