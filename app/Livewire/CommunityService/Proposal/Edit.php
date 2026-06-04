<?php

namespace App\Livewire\CommunityService\Proposal;

use App\Constants\ProposalConstants;
use App\Livewire\Abstracts\ProposalCreate;
use App\Models\Setting;
use Illuminate\Validation\Rule;
use Spatie\MediaLibrary\HasMedia;

class Edit extends ProposalCreate
{
    public string $proposalApprovalMode = 'new';

    public string $componentId = '';

    protected function getProposalType(): string
    {
        return 'community-service';
    }

    protected function getIndexRoute(): string
    {
        return 'community-service.proposal.index';
    }

    protected function getShowRoute(string $proposalId): string
    {
        return route('community-service.proposal.show', $proposalId);
    }

    protected function getStepValidationRules(int $step): array
    {
        if ($step === 1) {
            $rules = parent::getStepValidationRules(1);
            $rules['form.partner_issue_summary'] = 'required|string|min:50';
            $rules['form.solution_offered'] = 'required|string|min:50';

            return $rules;
        }

        if ($step === 4) {
            $rule = Setting::get('feature_community_partner_required', true) ? 'required|array|min:1' : 'nullable|array';

            return [
                'form.partner_ids' => $rule,
                'form.partner_ids.*' => 'exists:partners,id',
            ];
        }

        return parent::getStepValidationRules($step);
    }

    protected function getStep2Rules(): array
    {
        // Check if file already exists (edit mode)
        $detailable = $this->form->proposal?->detailable;
        $hasFile = $detailable instanceof HasMedia &&
            $detailable->hasMedia('substance_file');

        return [
            'form.macro_research_group_id' => 'required|exists:macro_research_groups,id',
            'form.substance_file' => $hasFile ? 'nullable|file|mimes:pdf,doc,docx|max:10240' : 'required|file|mimes:pdf,doc,docx|max:10240',
            'form.outputs' => [
                'required',
                'array',
                'min:1',
                function ($attribute, $value, $fail) {
                    $wajibCount = collect($value)->where('category', 'Wajib')->count();
                    if ($wajibCount < 1) {
                        $fail('Minimal harus ada 1 luaran wajib untuk proposal pengabdian masyarakat.');
                    }
                },
            ],
            'form.outputs.*.year' => 'required|integer|min:1|max:10',
            'form.outputs.*.category' => ['required', Rule::in(ProposalConstants::OUTPUT_CATEGORIES)],
            'form.outputs.*.group' => ['required', Rule::in(ProposalConstants::PKM_OUTPUT_GROUPS)],
            'form.outputs.*.type' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    // Validate type matches group
                    $index = (int) explode('.', $attribute)[2];
                    $group = $this->form->outputs[$index]['group'] ?? null;
                    if ($group && isset(ProposalConstants::PKM_OUTPUT_TYPES[$group])) {
                        if (! in_array($value, ProposalConstants::PKM_OUTPUT_TYPES[$group])) {
                            $fail('Luaran baris '.($index + 1).' tidak valid untuk kategori yang dipilih.');
                        }
                    }
                },
            ],
            'form.outputs.*.status' => ['required', Rule::in(ProposalConstants::OUTPUT_STATUSES)],
            'form.outputs.*.description' => 'required|string|max:2000',
        ];
    }
}
