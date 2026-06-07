<?php

namespace App\Livewire\Research\Proposal;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

use App\Livewire\Abstracts\ProposalCreate;
use App\Models\Proposal;

class Edit extends ProposalCreate
{
    public string $proposalApprovalMode = 'new';

    public string $componentId = '';

    public function mount(?string $proposalId = null, ?Proposal $proposal = null): void
    {
        if ($proposalId === null && $proposal === null) {
            abort(404);
        }

        parent::mount($proposalId, $proposal);
        $this->componentId = $proposal ? $proposal->id : $proposalId;
    }

    protected function getProposalType(): string
    {
        return 'research';
    }

    protected function getIndexRoute(): string
    {
        return 'research.proposal.index';
    }

    protected function getShowRoute(string $proposalId): string
    {
        return route('research.proposal.show', $proposalId);
    }

    protected function getStep2Rules(): array
    {
        $rules = array_merge($this->getOutputValidationRules(), [
            'form.author_tasks' => 'required|string',
            'form.macro_research_group_id' => 'required|exists:macro_research_groups,id',
            'form.substance_file' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $rules['form.tkt_type'] = 'nullable|string';
        $rules['form.tkt_results'] = 'nullable|array';
        $rules['form.roadmap_data'] = 'nullable|array';

        return $rules;
    }
}
