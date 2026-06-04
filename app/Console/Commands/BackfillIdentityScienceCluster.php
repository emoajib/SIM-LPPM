<?php

namespace App\Console\Commands;

use App\Models\Identity;
use App\Models\ScienceCluster;
use Illuminate\Console\Command;

class BackfillIdentityScienceCluster extends Command
{
    protected $signature = 'app:backfill-science-cluster
        {--dry-run : only show counts without making changes}
        {--cluster-id= : set all null identities to this science_cluster_id}';

    protected $description = 'Report/backfill identities with missing science_cluster_id';

    public function handle(): int
    {
        $nullCount = Identity::whereNull('science_cluster_id')->count();

        if ($nullCount === 0) {
            $this->info('All identities already have science_cluster_id set.');

            return 0;
        }

        $this->warn("Found {$nullCount} identities without science_cluster_id.");

        $identities = Identity::whereNull('science_cluster_id')
            ->select('study_program_id')
            ->with('studyProgram:id,name')
            ->get();

        $grouped = $identities
            ->groupBy(fn (Identity $i) => optional($i->studyProgram)->name ?? '(no study program)')
            ->map(fn ($group, $name) => ['study_program' => $name, 'count' => $group->count()])
            ->values()
            ->toArray();

        $this->table(['Study Program', 'Count'], $grouped);

        $clusterId = $this->option('cluster-id');

        if ($clusterId) {
            $cluster = ScienceCluster::find($clusterId);

            if (! $cluster) {
                $this->error("Science cluster with ID {$clusterId} not found.");

                return 1;
            }

            if ($this->option('dry-run')) {
                $this->info("[DRY-RUN] Would set {$nullCount} identities to science cluster: {$cluster->name} (ID: {$clusterId})");

                return 0;
            }

            if (! $this->confirm("Set all {$nullCount} identities to science cluster '{$cluster->name}'?")) {
                $this->info('Aborted.');

                return 0;
            }

            Identity::whereNull('science_cluster_id')->update(['science_cluster_id' => $clusterId]);
            $this->info("Updated {$nullCount} identities to science cluster '{$cluster->name}'.");
        } else {
            $this->info('Use --cluster-id={id} to set a default cluster for all null identities.');
            $this->info('Users can also set their science cluster via Profile settings.');
        }

        return 0;
    }
}
