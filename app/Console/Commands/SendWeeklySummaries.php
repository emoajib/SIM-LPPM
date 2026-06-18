<?php

namespace App\Console\Commands;

use App\Models\CommunityService;
use App\Models\Proposal;
use App\Models\ProposalReviewer;
use App\Models\Research;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendWeeklySummaries extends Command
{
    protected $signature = 'reports:send-weekly-summary';

    protected $description = 'Send weekly summary reports to role-specific users';

    public function handle(NotificationService $notificationService): int
    {
        $week = now()->startOfWeek()->format('Y-m-d').' to '.now()->endOfWeek()->format('Y-m-d');
        $data = [
            'week' => $week,
            'timestamp' => now()->toDateTimeString(),
        ];

        // Dekan
        $dekanData = $this->getDekanData($data);
        $notificationService->notifyWeeklySummaryReport('dekan', $dekanData);
        $this->info('Weekly summary sent to Dekan');

        // Kepala LPPM
        $kepalaData = $this->getKepalaData($data);
        $notificationService->notifyWeeklySummaryReport('kepala lppm', $kepalaData);
        $this->info('Weekly summary sent to Kepala LPPM');

        // Rektor
        $rektorData = $this->getRektorData($data);
        $notificationService->notifyWeeklySummaryReport('rektor', $rektorData);
        $this->info('Weekly summary sent to Rektor');

        return 0;
    }

    private function getDekanData(array $data): array
    {
        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();

        return array_merge($data, [
            'new_submissions' => Proposal::whereBetween('created_at', [$weekStart, $weekEnd])
                ->where('status', '!=', 'draft')
                ->count(),
            'approved_count' => Proposal::whereBetween('updated_at', [$weekStart, $weekEnd])
                ->where('status', 'approved')
                ->count(),
            'rejected_count' => Proposal::whereBetween('updated_at', [$weekStart, $weekEnd])
                ->where('status', 'rejected')
                ->count(),
            'total_pending' => Proposal::where('status', 'submitted')->count(),
        ]);
    }

    private function getKepalaData(array $data): array
    {
        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();

        return array_merge($data, [
            'proposals_assigned' => Proposal::whereBetween('updated_at', [$weekStart, $weekEnd])
                ->where('status', 'under_review')
                ->count(),
            'reviews_completed' => ProposalReviewer::whereBetween('updated_at', [$weekStart, $weekEnd])
                ->where('status', 'completed')
                ->count(),
            'final_decisions' => Proposal::whereBetween('updated_at', [$weekStart, $weekEnd])
                ->whereIn('status', ['completed', 'rejected'])
                ->count(),
            'under_review' => Proposal::where('status', 'under_review')->count(),
        ]);
    }

    private function getRektorData(array $data): array
    {
        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();

        return array_merge($data, [
            'total_proposals' => Proposal::count(),
            'completed_this_week' => Proposal::whereBetween('updated_at', [$weekStart, $weekEnd])
                ->where('status', 'completed')
                ->count(),
            'total_research' => Proposal::where('detailable_type', Research::class)->count(),
            'total_community_service' => Proposal::where('detailable_type', CommunityService::class)->count(),
            'avg_review_time' => round(
                ProposalReviewer::whereBetween('updated_at', [$weekStart, $weekEnd])
                    ->where('status', 'completed')
                    ->selectRaw('AVG('.(DB::getDriverName() === 'mysql' ? 'DATEDIFF(updated_at, created_at)' : 'EXTRACT(EPOCH FROM (updated_at - created_at)) / 86400').') as avg_days')
                    ->value('avg_days') ?? 0
            ),
        ]);
    }
}
