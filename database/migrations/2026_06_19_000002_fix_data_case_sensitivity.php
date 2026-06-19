<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fix proposals.status: lowercase any remaining uppercase values
        $proposalStatuses = ['draft', 'submitted', 'need_assignment', 'approved', 'waiting_reviewer', 'under_review', 'reviewed', 'revision_needed', 'revision_submitted', 'completed', 'rejected'];

        if (DB::getSchemaBuilder()->hasTable('proposals')) {
            foreach ($proposalStatuses as $status) {
                DB::table('proposals')
                    ->where('status', strtoupper($status))
                    ->update(['status' => $status]);
            }
        }

        // Fix proposal_status_logs.status_before and status_after
        if (DB::getSchemaBuilder()->hasTable('proposal_status_logs')) {
            foreach ($proposalStatuses as $status) {
                DB::table('proposal_status_logs')
                    ->where('status_before', strtoupper($status))
                    ->update(['status_before' => $status]);

                DB::table('proposal_status_logs')
                    ->where('status_after', strtoupper($status))
                    ->update(['status_after' => $status]);
            }
        }

        // Fix research_schemes.strata: normalize to Title Case
        if (DB::getSchemaBuilder()->hasTable('research_schemes')) {
            $strataMap = [
                'dasar' => 'Dasar',
                'DASAR' => 'Dasar',
                'terapan' => 'Terapan',
                'TERAPAN' => 'Terapan',
                'pengembangan' => 'Pengembangan',
                'PENGEMBANGAN' => 'Pengembangan',
                'pkm' => 'PKM',
            ];

            foreach ($strataMap as $old => $new) {
                DB::table('research_schemes')
                    ->where('strata', $old)
                    ->update(['strata' => $new]);
            }
        }

        // Fix additional_outputs.status: map legacy values
        if (DB::getSchemaBuilder()->hasTable('additional_outputs')) {
            DB::table('additional_outputs')
                ->where('status', 'review')
                ->update(['status' => 'under_review']);

            DB::table('additional_outputs')
                ->where('status', 'editing')
                ->update(['status' => 'draft']);
        }
    }

    public function down(): void
    {
        // Revert additional_outputs.status
        if (DB::getSchemaBuilder()->hasTable('additional_outputs')) {
            DB::table('additional_outputs')
                ->where('status', 'under_review')
                ->update(['status' => 'review']);

            DB::table('additional_outputs')
                ->where('status', 'draft')
                ->where('id', 'in', DB::table('additional_outputs')->where('status', 'editing')->pluck('id'))
                ->update(['status' => 'editing']);
        }

        // Note: proposals.status, proposal_status_logs, research_schemes.strata
        // are destructive one-way fixes. Reverting them is not practical
        // as we'd lose the true status values.
    }
};
