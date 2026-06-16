<?php

namespace App\Livewire\Concerns;

use App\Models\LetterType;
use App\Models\Proposal;
use App\Services\TeamSnapshotBuilder;

trait HasLetterForm
{
    public $letterTypeId;

    public $title = '';

    public $activityType = 'Penelitian';

    public $date = '';

    public $timeStart = '';

    public $timeEnd = '';

    public $location = '';

    public $destinationName = '';

    public $tembusan = '1. Arsip';

    public $letterTypes = [];

    public $selectedLetterType;

    public $proposals = [];

    public $selectedProposalId = '';

    public $referenceType = null;

    public $referenceId = null;

    public function mount(): void
    {
        $this->letterTypes = LetterType::where('is_active', true)->orderBy('code')->get();

        $this->proposals = Proposal::where('submitter_id', auth()->id())
            ->whereIn('status', ['approved', 'completed'])
            ->with('detailable')
            ->latest()
            ->get();
    }

    public function updatedLetterTypeId(): void
    {
        $this->selectedLetterType = LetterType::find($this->letterTypeId);
    }

    public function updatedSelectedProposalId(): void
    {
        if ($this->selectedProposalId) {
            $proposal = Proposal::with(['detailable', 'teamMembers', 'submitter.identity'])
                ->find($this->selectedProposalId);

            if ($proposal) {
                $this->title = $proposal->title;
                $this->activityType = str_contains($proposal->detailable_type, 'Research') ? 'Penelitian' : 'PKM';
                $this->location = $proposal->location ?? '';
                $this->team = TeamSnapshotBuilder::forProposal($proposal);
                $this->referenceType = get_class($proposal);
                $this->referenceId = $proposal->id;
            }
        } else {
            $this->title = '';
            $this->activityType = 'Penelitian';
            $this->location = '';
            $this->team = [];
            $this->referenceType = null;
            $this->referenceId = null;
        }
    }

    protected function resetFormFields(): void
    {
        $this->letterTypeId = null;
        $this->title = '';
        $this->activityType = 'Penelitian';
        $this->date = '';
        $this->timeStart = '';
        $this->timeEnd = '';
        $this->location = '';
        $this->destinationName = '';
        $this->tembusan = '1. Arsip';
        $this->team = [];
        $this->selectedLetterType = null;
        $this->selectedProposalId = '';
        $this->referenceType = null;
        $this->referenceId = null;
    }
}
