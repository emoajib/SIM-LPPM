<?php

namespace App\Services;

use App\Models\AdditionalOutput;
use App\Models\CommunityService;
use App\Models\DailyNote;
use App\Models\MandatoryOutput;
use App\Models\ManualBook;
use App\Models\Partner;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\Setting;
use App\Models\User;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaDownloadService
{
    /**
     * Determine if a user can access a specific media file.
     * Logic based on ABAC (Attribute Based Access Control).
     */
    public function canUserAccessMedia(User $user, Media $media): bool
    {
        // 1. Roles with global access
        if ($user->hasAnyRole(['admin lppm', 'kepala lppm', 'superadmin', 'rektor', 'dekan'])) {
            return true;
        }

        $model = $media->model;

        // 2. Global resources (Settings)
        if ($model instanceof Setting) {
            return true;
        }

        $proposalId = null;

        // 3. Resolve proposal relationship based on model type
        if ($model instanceof ProgressReport) {
            $proposalId = $model->proposal_id;
        } elseif ($model instanceof DailyNote) {
            $proposalId = $model->proposal_id;
        } elseif ($model instanceof MandatoryOutput) {
            $proposalId = optional($model->progressReport)->proposal_id;
        } elseif ($model instanceof AdditionalOutput) {
            $proposalId = optional($model->progressReport)->proposal_id;
        } elseif ($model instanceof Partner) {
            // MOU/PKS is readable by authenticated users (as per current logic)
            if ($media->collection_name === 'mou_pks') {
                return true;
            }
            $proposalId = $media->getCustomProperty('proposal_id');
        } elseif ($model instanceof Research || $model instanceof CommunityService) {
            // Explicit handling for Research/CommunityService - query proposal directly
            $proposal = Proposal::where('detailable_id', $model->id)
                ->where('detailable_type', get_class($model))
                ->first();
            $proposalId = $proposal?->id;
        } elseif (method_exists($model, 'proposal')) {
            try {
                /** @phpstan-ignore-next-line */
                $proposalId = optional($model->proposal)->id;
            } catch (\Throwable $e) {
                $proposalId = null;
            }
        } elseif ($model instanceof Proposal) {
            $proposalId = $model->id;
        }

        // 3b. ManualBook - check assigned roles
        if ($model instanceof ManualBook) {
            $role = active_role();
            if ($user->hasAnyRole(['admin lppm', 'superadmin', 'kepala lppm', 'rektor'])) {
                return true;
            }

            return in_array($role, $model->assigned_roles ?? []);
        }

        if (! $proposalId) {
            return false;
        }

        $proposal = Proposal::find($proposalId);
        if (! $proposal) {
            return false;
        }

        // 4. Ownership check
        if ($proposal->submitter_id === $user->id) {
            return true;
        }

        // 5. Team membership check
        return $proposal->teamMembers()
            ->where('users.id', $user->id)
            ->exists();
    }
}
