<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProposalStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PublicResearchResource;
use App\Models\Research;
use Illuminate\Http\Request;

class PublicResearchController extends Controller
{
    /**
     * Display a listing of completed research.
     */
    public function index(Request $request)
    {
        // Cap the public-facing page size to prevent resource exhaustion.
        $limit = max(1, min((int) $request->query('limit', 10), 100));

        $research = Research::whereHas('proposal', function ($query) {
            $query->where('status', ProposalStatus::COMPLETED);
        })
            ->with(['proposal.submitter.identity.faculty', 'proposal.researchScheme', 'proposal.focusArea', 'proposal.theme', 'proposal.topic', 'proposal.keywords'])
            ->latest()
            ->paginate($limit);

        return PublicResearchResource::collection($research);
    }

    /**
     * Display the specified research.
     */
    public function show(string $id)
    {
        $research = Research::where('id', $id)
            ->whereHas('proposal', function ($query) {
                $query->where('status', ProposalStatus::COMPLETED);
            })
            ->with(['proposal.submitter.identity.faculty', 'proposal.researchScheme', 'proposal.focusArea', 'proposal.theme', 'proposal.topic', 'proposal.keywords'])
            ->firstOrFail();

        return new PublicResearchResource($research);
    }
}
