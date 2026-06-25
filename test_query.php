<?php

namespace App\Livewire\AdminLppm;

use App\Enums\ReviewStatus;
use App\Models\CommunityService;
use App\Models\Proposal;
use App\Models\Research;

$search = 'a';
$reviewerSearch = 'a';
$typeFilter = 'research';
$schemeFilter = 1;
$progressFilter = 'in_progress';
$facultyFilter = 1;
$prodiFilter = 1;
$requiredCount = 2;

try {
    $q = Proposal::query()
        ->whereIn('status', ['under_review', 'reviewed'])
        ->with(['submitter', 'detailable', 'reviewers.user'])
        ->when($search, function ($query) use ($search) {
            $query->where('title', 'like', "%{$search}%");
        })
        ->when($reviewerSearch, function ($query) use ($reviewerSearch) {
            $query->whereHas('reviewers.user', function ($q) use ($reviewerSearch) {
                $q->where('name', 'like', "%{$reviewerSearch}%");
            });
        })
        ->when($typeFilter !== 'all', function ($query) use ($typeFilter, $schemeFilter) {
            $detailableType = $typeFilter === 'research'
                ? Research::class
                : CommunityService::class;
            $query->where('detailable_type', $detailableType);

            if ($schemeFilter !== 'all') {
                $query->whereHasMorph('detailable', [$detailableType], function ($q) use ($detailableType, $schemeFilter) {
                    if ($detailableType === Research::class) {
                        $q->where('research_scheme_id', $schemeFilter);
                    } else {
                        $q->where('community_service_scheme_id', $schemeFilter);
                    }
                });
            }
        })
        ->when($facultyFilter !== 'all', function ($query) use ($facultyFilter) {
            $query->whereHas('submitter.identity', function ($q) use ($facultyFilter) {
                $q->where('faculty_id', $facultyFilter);
            });
        })
        ->when($prodiFilter !== 'all', function ($query) use ($prodiFilter) {
            $query->whereHas('submitter.identity', function ($q) use ($prodiFilter) {
                $q->where('study_program_id', $prodiFilter);
            });
        })
        ->when($progressFilter !== 'all', function ($query) use ($requiredCount, $progressFilter) {
            if ($progressFilter === 'unassigned') {
                $query->whereDoesntHave('reviewers', function ($q) {
                    // Just need total count
                }, '>=', $requiredCount);
            } elseif ($progressFilter === 'completed') {
                $query->has('reviewers', '>=', $requiredCount)
                    ->whereDoesntHave('reviewers', function ($q) {
                        $q->where('status', '!=', ReviewStatus::COMPLETED->value);
                    });
            } elseif ($progressFilter === 'in_progress') {
                $query->has('reviewers', '>', 0)
                    ->whereHas('reviewers', function ($q) {
                        $q->where('status', '!=', ReviewStatus::COMPLETED->value);
                    });
            }
        });

    $count = $q->count();
    echo "Query executed successfully! Found: $count records\n";
} catch (\Exception $e) {
    echo 'ERROR: '.$e->getMessage()."\n";
}
