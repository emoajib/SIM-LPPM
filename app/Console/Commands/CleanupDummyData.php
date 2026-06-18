<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanupDummyData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-dummy {--force : proceed without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all non-superadmin/admin-lppm users and related test data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->option('force')) {
            if (! $this->confirm('This will remove ALL users except superadmin and admin lppm. Continue?')) {
                $this->info('Aborted.');

                return 0;
            }
        }

        // disable foreign key checks to allow truncation
        Schema::disableForeignKeyConstraints();

        $this->info('Deleting users...');
        User::whereDoesntHave('roles', function ($q) {
            $q->whereIn('name', ['superadmin', 'admin lppm']);
        })->each(function (User $user) {
            $user->delete();
        });

        // optionally truncate other tables used by dummy data
        $tables = [
            'proposals',
            'research',
            'community_services',
            'progress_reports',
            'proposal_outputs',
            'additional_outputs',
            'activity_schedules',
            'activity_logs',
            'daily_notes',
            'budget_items',
            'partners',
            'proposal_user',
            'proposal_keyword',
            'proposal_partner',
            'proposal_reviewer',
            'proposal_monev',
            'proposal_activity',
            'proposal_status_logs',
            'review_logs',
            'review_scores',
            'progress_report_keyword',
            'mandatory_outputs',
            'monev_reviews',
            'document_signatures',
            'notifications',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $this->info("Truncated table: {$table}");
            }
        }

        // re-enable foreign key checks
        Schema::enableForeignKeyConstraints();

        // Clear all caches
        Artisan::call('optimize:clear');
        Cache::flush();

        $this->info('Dummy data removed. Only superadmin and admin lppm remain.');
        $this->info('All caches have been cleared.');

        return 0;
    }
}
