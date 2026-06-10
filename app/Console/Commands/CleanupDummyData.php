<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

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
            // add other relevant tables as needed
        ];

        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }

        // re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('Dummy data removed. Only superadmin and admin lppm remain.');

        return 0;
    }
}
