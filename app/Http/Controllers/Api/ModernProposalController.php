<?php

namespace App\Http\Controllers\Api;

use App\Models\Proposal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ModernProposalController
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->get('search', '');
        $status = $request->get('status', 'all');
        $year = $request->get('year', '');
        $type = $request->get('type', 'research');

        $query = Proposal::with(['submitter.identity', 'focusArea', 'researchScheme'])
            ->whereHas('detailable', fn ($q) => $q->where('detailable_type', $type === 'research' ? 'App\\Models\\Research' : 'App\\Models\\CommunityService'));

        if ($search) {
            $query->where('title', 'like', "%{$search}%");
        }
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        if ($year) {
            $query->whereYear('created_at', $year);
        }

        // Scope to user unless admin/leader
        if (! $user->activeHasAnyRole(['admin lppm', 'kepala lppm', 'rektor', 'dekan'])) {
            $query->where(function ($q) use ($user) {
                $q->where('submitter_id', $user->id)
                    ->orWhereHas('teamMembers', fn ($t) => $t->where('user_id', $user->id));
            });
        }

        $proposals = $query->latest()->paginate(15);

        return response()->json([
            'data' => $proposals->map(fn ($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'focus_area' => $p->focusArea?->name,
                'submitter' => $p->submitter->name,
                'identity_id' => $p->submitter->identity?->identity_id,
                'status' => $p->status->value,
                'status_label' => $p->status->label(),
                'status_color' => $p->status->color(),
                'created_at' => $p->created_at?->format('d M Y'),
                'show_url' => route('research.proposal.show', $p),
            ]),
            'meta' => [
                'current_page' => $proposals->currentPage(),
                'last_page' => $proposals->lastPage(),
                'total' => $proposals->total(),
            ],
        ]);
    }
}
