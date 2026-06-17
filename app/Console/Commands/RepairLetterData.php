<?php

namespace App\Console\Commands;

use App\Models\Letter;
use App\Models\Setting;
use App\Models\User;
use App\Services\LetterService;
use Illuminate\Console\Command;

class RepairLetterData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:repair-letter-data {id? : Specific letter ID to repair}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Repair missing metadata and team identifiers in existing letters';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $id = $this->argument('id');

        $query = Letter::query();
        if ($id) {
            $query->where('id', $id);
        }

        $letters = $query->get();
        $service = new LetterService;

        foreach ($letters as $letter) {
            $this->info("Processing Letter: {$letter->id}");

            // 1. Repair Metadata (Signer Info)
            $metadata = $letter->metadata ?? [];
            $metadata['signer_name'] = get_institution_config('lppm_head_name');
            $metadata['signer_position'] = get_institution_config('lppm_head_position');
            $metadata['signer_nidn'] = get_institution_config('lppm_head_nidn');
            $metadata['signer_address'] = get_institution_config('lppm_head_address');

            // 2. Repair Team Snapshot (Missing Identifiers)
            $team = $letter->team_snapshot ?? [];
            $repairedTeam = [];
            foreach ($team as $member) {
                if (empty($member['identifier'])) {
                    $user = User::where('name', $member['name'])->first();
                    if ($user && $user->identity) {
                        $member['identifier'] = $user->identity->identity_id;
                        $this->line("  ✓ Restored identifier for {$member['name']}: {$member['identifier']}");
                    }
                }
                $repairedTeam[] = $member;
            }

            $letter->update([
                'metadata' => $metadata,
                'team_snapshot' => $repairedTeam,
            ]);

            // 3. Regenerate PDF
            try {
                $service->generatePdf($letter);
                $this->info("  ✓ PDF Regenerated: {$letter->file_path}");
            } catch (\Exception $e) {
                $this->error('  ✗ PDF Regeneration failed: '.$e->getMessage());
            }
        }

        $this->info('Repair process completed.');
    }
}
