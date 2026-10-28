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

class ReviewerAssignment extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Proposal $proposal,
        public User $kepalaLppm
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
            'type' => 'reviewer_assignment',
            'title' => 'Penugasan Reviewer',
            'message' => "Proposal '{$this->proposal->title}' siap untuk penugasan reviewer. Kepala LPPM {$this->kepalaLppm->name} telah menyetujui proposal ini dan menyarankan untuk segera melakukan penugasan reviewer yang qualified.",
            'body' => "Proposal {$proposalType} dengan judul '{$this->proposal->title}' telah disetujui oleh Kepala LPPM {$this->kepalaLppm->name}. Proposal ini sekarang berada dalam status 'Under Review' dan memerlukan penugasan reviewer yang kompeten dan berpengalaman di bidangnya.\n\n**Detail Proposal:**\n- Judul: {$this->proposal->title}\n- Submitter: {$this->proposal->submitter->name}\n- Status: Under Review\n\n**Aksi yang Diperlukan:**\n1. Login ke sistem SIM LPPM\n2. Akses menu 'Research' > 'Review Management'\n3. Pilih proposal '{$this->proposal->title}'\n4. Klik 'Assign Reviewers'\n\nMohon segera melakukan penugasan reviewer untuk memastikan proses review berlangsung tepat waktu.",
            'proposal_id' => $this->proposal->id,
            'kepala_lppm_id' => $this->kepalaLppm->id,
            'kepala_lppm_name' => $this->kepalaLppm->name,
            'proposal_type' => $isResearch ? 'research' : 'community_service',
            'proposal_title' => $this->proposal->title,
            'link' => route($isResearch ? 'research.proposal.show' : 'community-service.proposal.show', $this->proposal),
            'icon' => 'users',
            'created_at' => now()->toISOString(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isResearch = $this->proposal->detailable instanceof Research;
        $proposalType = $isResearch ? 'Penelitian' : 'Pengabdian Masyarakat';
        $assignmentUrl = route($isResearch ? 'research.proposal.show' : 'community-service.proposal.show', $this->proposal->id);

        return (new MailMessage)
            ->subject("[SIM LPPM] Penugasan Reviewer - {$proposalType}")
            ->greeting('Halo, Admin LPPM!')
            ->line("Proposal dengan judul **{$this->proposal->title}** telah siap untuk penugasan reviewer.")
            ->line('')
            ->line('**Detail Proposal:**')
            ->line("- Judul: {$this->proposal->title}")
            ->line("- Submitter: {$this->proposal->submitter->name}")
            ->line('- Status: Under Review')
            ->line("- Disetujui oleh: {$this->kepalaLppm->name}")
            ->line('')
            ->line('**Aksi yang Diperlukan:**')
            ->line('Silahkan segera menyelesaikan kualifikasi reviewer dan lakukan penugasan yang sesuai dengan keahlian masing-masing reviewer.')
            ->line('Pastikan bahwa reviewer yang ditugaskan telah menerima undangan dan siap untuk melakukan review.')
            ->line('')
            ->line('Terima kasih atas kerjasama Anda dalam menjamin kualitas review proposal.')
            ->action('Pilih Reviewer', $assignmentUrl);
    }
}
