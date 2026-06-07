<?php

namespace App\Livewire\Research\Proposal;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

use App\Constants\ProposalConstants;
use App\Livewire\Abstracts\ProposalCreate;
use App\Models\ResearchScheme;
use Spatie\MediaLibrary\HasMedia;

class Create extends ProposalCreate
{
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
        // Check if file already exists (edit mode)
        $detailable = $this->form->proposal?->detailable;
        $hasFile = $detailable instanceof HasMedia &&
            $detailable->hasMedia('substance_file');

        return array_merge($this->getOutputValidationRules(), [
            'form.macro_research_group_id' => 'required|exists:macro_research_groups,id',
            'form.substance_file' => $hasFile ? 'nullable|file|mimes:pdf|max:10240' : 'required|file|mimes:pdf|max:10240',
        ]);
    }

    public function updatedFormResearchSchemeId(): void
    {
        if (! $this->form->research_scheme_id || ! empty($this->form->outputs)) {
            return;
        }

        $scheme = ResearchScheme::find($this->form->research_scheme_id);
        if (! $scheme) {
            return;
        }

        $output = $this->getOutputForScheme($scheme);

        if (! empty($output)) {
            $this->form->outputs[] = $output;
        }
    }

    private function getOutputForScheme(ResearchScheme $scheme): array
    {
        $schemeName = strtolower($scheme->name);
        $schemeStrata = strtolower($scheme->strata);

        if (str_contains($schemeName, 'pemula') || str_contains($schemeName, 'internal')) {
            return [
                'year' => 1,
                'category' => ProposalConstants::OUTPUT_CATEGORIES[0],
                'group' => 'jurnal',
                'type' => ProposalConstants::RESEARCH_OUTPUT_TYPES['jurnal'][3],
                'status' => 'Published',
                'description' => 'Target publikasi jurnal nasional',
            ];
        }

        if (str_contains($schemeStrata, 'terapan') || str_contains($schemeName, 'terapan')) {
            return [
                'year' => 1,
                'category' => ProposalConstants::OUTPUT_CATEGORIES[0],
                'group' => 'lainnya',
                'type' => 'Purwarupa/Prototipe TRL 4-6',
                'status' => 'Draft',
                'description' => 'Target prototipe produk',
            ];
        }

        return [
            'year' => 1,
            'category' => ProposalConstants::OUTPUT_CATEGORIES[0],
            'group' => 'jurnal',
            'type' => ProposalConstants::RESEARCH_OUTPUT_TYPES['jurnal'][0],
            'status' => 'Submitted',
            'description' => 'Target publikasi jurnal internasional bereputasi',
        ];
    }
}
