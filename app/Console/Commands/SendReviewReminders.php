<?php

namespace App\Console\Commands;

use App\Enums\ReviewStatus;
use App\Models\ProposalReviewer;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Send review reminders to reviewers 3 days before deadline
 */
class SendReviewReminders extends Command
{
    protected $signature = 'reviews:send-reminders';

    protected $description = 'Send review reminders to reviewers 3 days before deadline';

    public function handle(NotificationService $notificationService): int
    {
        // Find all reviews that are due in 3 days
        $threeDay = Carbon::now()->addDays(3)->startOfDay();
        $threeDayEnd = $threeDay->copy()->endOfDay();

        $reviewers = ProposalReviewer::query()
            ->where('status', ReviewStatus::PENDING->value)
            ->whereBetween('deadline_at', [$threeDay, $threeDayEnd])
            ->with(['proposal', 'user'])
            ->get();

        /** @var ProposalReviewer $review */
        foreach ($reviewers as $review) {
            $daysRemaining = $review->deadline_at ? (int) $review->deadline_at->diffInDays(now()) : 0;
            $notificationService->notifyReviewReminder(
                $review->proposal,
                $review->user,
                $daysRemaining
            );

            $this->info("Reminder sent to {$review->user->name} for proposal: {$review->proposal->title}");
        }

        $this->info("Sent {$reviewers->count()} review reminders");

        return 0;
    }
}
