<?php

namespace App\Traits;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use Illuminate\Support\Facades\DB;

trait HandlesProposalStateTransitions
{
    /**
     * @return array{success: bool, message?: string}
     */
    protected function transitionProposal(
        Proposal $proposal,
        ProposalStatus $expectedCurrent,
        ProposalStatus $newStatus,
        ?callable $onSuccess = null
    ): array {
        $affected = DB::table('proposals')
            ->where('id', $proposal->id)
            ->where('status', $expectedCurrent->value)
            ->where('version', $proposal->version)
            ->update([
                'status' => $newStatus->value,
                'version' => $proposal->version + 1,
                'updated_at' => now(),
            ]);

        if (! $affected) {
            return [
                'success' => false,
                'message' => 'Proposal sudah berubah oleh pengguna lain. Silakan refresh.',
            ];
        }

        $proposal->refresh();
        if ($onSuccess) {
            $onSuccess($proposal);
        }

        return ['success' => true];
    }
}
