<?php

namespace App\Livewire\Proposals;

use App\Models\Proposal;
use Livewire\Component;

class ProposalActivityTimeline extends Component
{
    public Proposal $proposal;

    public function mount(Proposal $proposal): void
    {
        $this->proposal = $proposal;
    }

    public function render()
    {
        $activities = $this->proposal->activities()->with('user')->get();

        return view('livewire.proposals.proposal-activity-timeline', [
            'activities' => $activities,
        ]);
    }
}
