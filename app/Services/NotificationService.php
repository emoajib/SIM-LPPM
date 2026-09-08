<?php

namespace App\Services;

use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\User;
use App\Notifications\DailySummaryReport;
use App\Notifications\DekanApprovalDecision;
use App\Notifications\FinalDecisionMade;
use App\Notifications\ProposalRevised;
use App\Notifications\ProposalSubmitted;
use App\Notifications\ReportRejected;
use App\Notifications\ReviewCompleted;
use App\Notifications\ReviewerAssigned;
use App\Notifications\ReviewOverdue;
use App\Notifications\ReviewReminder;
use App\Notifications\System\EmailVerification;
use App\Notifications\System\PasswordReset;
use App\Notifications\System\RoleAssigned;
use App\Notifications\System\TwoFactorAuthentication;
use App\Notifications\TeamInvitationAccepted;
use App\Notifications\TeamInvitationRejected;
use App\Notifications\TeamInvitationSent;
use App\Notifications\WeeklySummaryReport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    /**
     * Send notification to a single user or multiple users (batch)
     */
    public function send(User|Collection|array $recipients, mixed $notification): void
    {
        try {
            Notification::send($recipients, $notification);
        } catch (\Exception $e) {
            \Log::error('Failed to send notification: '.$e->getMessage(), [
                'notification' => get_class($notification),
                'exception' => $e,
            ]);

            // If we are in local, we might want to know. In production, we don't want to crash.
            if (config('app.debug')) {
                // throw $e; // Optional: rethrow in debug mode
            }
        }
    }

    /**
     * Send notification to multiple users (Alias for send for backward compatibility)
     */
    public function sendToMany(Collection|array $users, mixed $notification): void
    {
        $this->send($users, $notification);
    }

    /**
     * Send Proposal Submitted notification
     */
    public function notifyProposalSubmitted($proposal, $submitter, Collection|array $recipients): void
    {
        $notification = new ProposalSubmitted($proposal, $submitter);
        $this->sendToMany($recipients, $notification);
    }

    /**
     * Send Dekan Approval Decision notification
     */
    public function notifyDekanApprovalDecision($proposal, string $decision, $dekan, Collection|array $recipients): void
    {
        $notification = new DekanApprovalDecision($proposal, $decision, $dekan);
        $this->sendToMany($recipients, $notification);
    }

    /**
     * Send Reviewer Assignment notification
     */
    public function notifyReviewerAssigned($proposal, $reviewer, string $deadline, Collection|array $recipients): void
    {
        $notification = new ReviewerAssigned($proposal, $reviewer, $deadline);
        $this->sendToMany($recipients, $notification);
    }

    /**
     * Send Review Completion notification
     */
    public function notifyReviewCompleted($proposal, $reviewer, bool $allComplete = false, Collection|array $recipients = []): void
    {
        $notification = new ReviewCompleted($proposal, $reviewer, $allComplete);

        if (! empty($recipients)) {
            $this->sendToMany($recipients, $notification);
        } else {
            // If no specific recipients, send to all proposal stakeholders
            $stakeholders = $this->getProposalStakeholders($proposal);
            $this->sendToMany($stakeholders, $notification);
        }
    }

    /**
     * Send Final Decision Made notification
     */
    public function notifyFinalDecision($proposal, string $decision, $kepalaLppm, Collection|array $recipients): void
    {
        $notification = new FinalDecisionMade($proposal, $decision, $kepalaLppm);
        $this->sendToMany($recipients, $notification);
    }

    /**
     * Send Proposal Revised notification (for re-review)
     */
    public function notifyProposalRevised($proposal, User $recipient, int $round, bool $isAdmin = false): void
    {
        $notification = new ProposalRevised($proposal, $round, $isAdmin);
        $this->send($recipient, $notification);
    }

    /**
     * Get all stakeholders of a proposal
     */
    private function getProposalStakeholders($proposal): Collection
    {
        $stakeholders = collect();

        // Add submitter
        $stakeholders->push($proposal->submitter);

        // Add all team members
        if ($proposal->teamMembers) {
            $stakeholders = $stakeholders->merge($proposal->teamMembers);
        }

        // Remove duplicates
        return $stakeholders->unique('id')->values();
    }

    /**
     * Get users by role
     */
    public function getUsersByRole(array|string $roles): Collection
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        return User::role($roles)->get();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(User $user, string $notificationId): void
    {
        $user->notifications()->where('id', $notificationId)->update([
            'read_at' => now(),
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(User $user): void
    {
        $user->unreadNotifications()->update([
            'read_at' => now(),
        ]);
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    /**
     * Send Team Invitation notification
     */
    public function notifyTeamInvitationSent($proposal, $inviter, $invitee): void
    {
        $notification = new TeamInvitationSent($proposal, $inviter, $invitee);
        $this->send($invitee, $notification);
    }

    /**
     * Send Team Invitation Accepted notification
     */
    public function notifyTeamInvitationAccepted($proposal, $member): void
    {
        $notification = new TeamInvitationAccepted($proposal, $member);
        $this->send($proposal->submitter, $notification);
    }

    /**
     * Send Team Invitation Rejected notification
     */
    public function notifyTeamInvitationRejected($proposal, $member): void
    {
        $notification = new TeamInvitationRejected($proposal, $member);
        $this->send($proposal->submitter, $notification);
    }

    /**
     * Send Review Reminder notification (3 days before deadline)
     */
    public function notifyReviewReminder($proposal, $reviewer, int $daysRemaining): void
    {
        $notification = new ReviewReminder($proposal, $reviewer, $daysRemaining);
        $this->send($reviewer, $notification);
    }

    /**
     * Send Review Overdue notification
     */
    public function notifyReviewOverdue($proposal, $reviewer, int $daysOverdue): void
    {
        $recipients = collect([
            $reviewer,
        ])->merge(
            $this->getUsersByRole(['admin lppm', 'kepala lppm'])
        )->unique('id')->values();

        $notification = new ReviewOverdue($proposal, $reviewer, $daysOverdue);
        $this->sendToMany($recipients, $notification);
    }

    /**
     * Send Daily Summary Report notification
     */
    public function notifyDailySummaryReport(string $role, array $data): void
    {
        $recipients = $this->getUsersByRole($role);
        $notification = new DailySummaryReport($role, $data);
        $this->sendToMany($recipients, $notification);
    }

    /**
     * Send Weekly Summary Report notification
     */
    public function notifyWeeklySummaryReport(string $role, array $data): void
    {
        $recipients = $this->getUsersByRole($role);
        $notification = new WeeklySummaryReport($role, $data);
        $this->sendToMany($recipients, $notification);
    }

    /**
     * Send Password Reset notification
     */
    public function notifyPasswordReset(User $user, string $resetToken): void
    {
        $notification = new PasswordReset($resetToken);
        $this->send($user, $notification);
    }

    /**
     * Send Email Verification notification
     */
    public function notifyEmailVerification(User $user, string $verificationUrl): void
    {
        $notification = new EmailVerification($verificationUrl);
        $this->send($user, $notification);
    }

    /**
     * Send Two Factor Authentication notification
     */
    public function notifyTwoFactorAuthentication(User $user, string $code, int $expiresIn = 5): void
    {
        $notification = new TwoFactorAuthentication($code, $expiresIn);
        $this->send($user, $notification);
    }

    /**
     * Send Role Assigned notification
     */
    public function notifyRoleAssigned(User $user, string $roleName, string $roleLabel): void
    {
        $notification = new RoleAssigned($roleName, $roleLabel);
        $this->send($user, $notification);
    }

    /**
     * Send Report Rejected notification to all proposal members
     */
    public function notifyReportRejected(ProgressReport $report, User $rejectedBy, string $notes): void
    {
        $proposal = $report->proposal;
        if (! $proposal) {
            return;
        }

        // Kirim ke ketua + semua anggota tim
        $recipients = collect([$proposal->submitter])
            ->merge($proposal->members ?? collect())
            ->filter()
            ->unique('id');

        $notification = new ReportRejected($report, $rejectedBy, $notes);
        $this->send($recipients, $notification);
    }
}
