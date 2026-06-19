<?php

namespace App\Livewire\Notifications;

use App\Enums\ProposalUserStatus;
use App\Models\Proposal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class NotificationDropdown extends Component
{
    public function render()
    {
        $user = Auth::user();

        $unreadNotifications = $user->unreadNotifications()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $unreadCount = $user->unreadNotifications()->count();

        $pendingInvitationsCount = Proposal::whereHas('teamMembers', function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->where('status', ProposalUserStatus::PENDING->value);
        })->count();

        $totalBadgeCount = $unreadCount + $pendingInvitationsCount;

        return view('livewire.notifications.notification-dropdown', [
            'unreadNotifications' => $unreadNotifications,
            'unreadCount' => $unreadCount,
            'pendingInvitationsCount' => $pendingInvitationsCount,
            'totalBadgeCount' => $totalBadgeCount,
        ]);
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
    }

    public function getIconAttribute(string $type): string
    {
        return match ($type) {
            'proposal_submitted' => 'file-text',
            'dekan_approval_decision' => 'check-circle',
            'reviewer_assigned' => 'user-check',
            'review_completed', 'all_reviews_completed' => 'check-square',
            'final_decision_made' => 'award',
            'team_invitation_sent' => 'user-plus',
            default => 'bell',
        };
    }
}
