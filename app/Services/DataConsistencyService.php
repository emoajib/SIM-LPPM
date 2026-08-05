<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Proposal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DataConsistencyService
{
    /**
     * Safe fields that can be updated without triggering re-approval workflow.
     */
    public const SAFE_FIELDS = ['title', 'summary', 'scheme_id', 'focus_area_id'];

    /**
     * Update safe (non-risky) proposal fields inline from the report view.
     *
     * These fields are: Judul, Ringkasan, Skema, Fokus Area (TKT).
     * They do NOT affect: semester, outputs, partners, budget, or reporting period.
     */
    public function updateSafeFields(Proposal $proposal, array $data): bool
    {
        $updates = [];

        foreach (self::SAFE_FIELDS as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $updates[$field] = $data[$field];
        }

        if (empty($updates)) {
            return false;
        }

        try {
            DB::transaction(function () use ($proposal, $updates) {
                $proposal->update($updates);
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Bulk update of safe proposal fields failed', [
                'proposal_id' => $proposal->id,
                'fields' => array_keys($updates),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
