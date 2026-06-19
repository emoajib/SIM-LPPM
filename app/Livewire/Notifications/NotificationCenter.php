<?php

namespace App\Livewire\Notifications;

use App\Enums\ProposalUserStatus;
use App\Livewire\Concerns\HasToast;
use App\Models\Proposal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationCenter extends Component
{
    use HasToast, WithPagination;

    protected $paginationTheme = 'bootstrap';

    #[Url]
    public string $filter = 'all'; // 'all', 'unread', 'read'

    public function render()
    {
        $user = Auth::user();

        $notifications = $user->notifications()
            ->when($this->filter === 'unread', function ($query) {
                $query->whereNull('read_at');
            })
            ->when($this->filter === 'read', function ($query) {
                $query->whereNotNull('read_at');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $unreadCount = $user->unreadNotifications()->count();

        $pendingInvitations = Proposal::whereHas('teamMembers', function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->where('status', ProposalUserStatus::PENDING->value);
        })->with('submitter.identity')->latest()->get();

        return view('livewire.notifications.notification-center', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'pendingInvitations' => $pendingInvitations,
        ]);
    }

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    #[On('notification-received')]
    public function refreshNotifications(): void
    {
        // This will trigger a re-render when new notifications arrive
    }

    public function markAsRead(string $notificationId): void
    {
        $user = Auth::user();
        DB::transaction(function () use ($user, $notificationId): void {
            $user->notifications()->where('id', $notificationId)->update([
                'read_at' => now(),
            ]);
        });

        $this->dispatch('notification-updated');
    }

    public function markAllAsRead(): void
    {
        $user = Auth::user();
        DB::transaction(function () use ($user): void {
            $user->unreadNotifications()->update([
                'read_at' => now(),
            ]);
        });

        $this->dispatch('notification-updated');
        $message = 'Semua notifikasi telah ditandai sebagai sudah dibaca.';
        session()->flash('success', $message);
        $this->toastSuccess($message);
    }

    public function getIconAttribute(string $type): string
    {
        return match ($type) {
            'proposal_submitted' => 'file-text',
            'dekan_approval_decision' => 'check-circle',
            'reviewer_assigned' => 'user-check',
            'review_completed', 'all_reviews_completed' => 'check-square',
            'final_decision_made' => 'award',
            default => 'bell',
        };
    }

    public function getTypeLabelAttribute(string $type): string
    {
        return match ($type) {
            'proposal_submitted' => 'Proposal Disubmit',
            'dekan_approval_decision' => 'Keputusan Dekan',
            'reviewer_assigned' => 'Reviewer Ditugaskan',
            'review_completed' => 'Review Selesai',
            'all_reviews_completed' => 'Semua Review Selesai',
            'final_decision_made' => 'Keputusan Final',
            default => 'Notifikasi',
        };
    }
}
