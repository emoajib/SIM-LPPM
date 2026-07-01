<?php

namespace App\Console\Commands;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use App\Models\ProposalReviewer;
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
            'new_proposals' => Proposal::whereBetween('created_at', [$weekStart, $weekEnd])
                ->where('status', '!=', ProposalStatus::DRAFT->value)
                ->count(),
            'approved' => Proposal::whereBetween('updated_at', [$weekStart, $weekEnd])
                ->where('status', ProposalStatus::APPROVED->value)
                ->count(),
            'rejected' => Proposal::whereBetween('updated_at', [$weekStart, $weekEnd])
                ->where('status', ProposalStatus::REJECTED->value)
                ->count(),
            'pending' => Proposal::where('status', ProposalStatus::SUBMITTED->value)->count(),
        ]);
    }

    private function getKepalaData(array $data): array
    {
        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();

        return array_merge($data, [
            'total_active' => Proposal::whereIn('status', [
                ProposalStatus::SUBMITTED->value,
                ProposalStatus::UNDER_REVIEW->value,
                ProposalStatus::REVISION_NEEDED->value,
                ProposalStatus::REVISION_SUBMITTED->value,
            ])->count(),
            'under_review' => Proposal::where('status', ProposalStatus::UNDER_REVIEW->value)->count(),
            'reviewed' => Proposal::where('status', ProposalStatus::REVISION_NEEDED->value)->count(),
            'completed' => Proposal::whereIn('status', [
                ProposalStatus::COMPLETED->value,
                ProposalStatus::APPROVED->value,
            ])->count(),
        ]);
    }

    private function getRektorData(array $data): array
    {
        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();

        return array_merge($data, [
            'total_proposals' => Proposal::count(),
            'completed_this_week' => Proposal::whereBetween('updated_at', [$weekStart, $weekEnd])
                ->where('status', ProposalStatus::COMPLETED->value)
                ->count(),
            'avg_process_time' => round(
                ProposalReviewer::whereBetween('updated_at', [$weekStart, $weekEnd])
                    ->where('status', 'completed')
                    ->selectRaw('AVG('.(DB::getDriverName() === 'mysql' ? 'DATEDIFF(updated_at, created_at)' : 'EXTRACT(EPOCH FROM (updated_at - created_at)) / 86400').') as avg_days')
                    ->value('avg_days') ?? 0
            ),
            'approval_rate' => round((Proposal::where('status', ProposalStatus::COMPLETED->value)->count() / max(Proposal::count(), 1)) * 100),
        ]);
    }
}
