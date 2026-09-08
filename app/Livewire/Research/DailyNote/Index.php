<?php

namespace App\Livewire\Research\DailyNote;

use App\Enums\ProposalStatus;
use App\Livewire\Abstracts\ReportIndex;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager
class Index extends ReportIndex
{
    protected function getDetailableType(): string
    {
        return 'App\Models\Research';
    }

    protected function getStatusFilter(): array
    {
        // Only show approved or completed proposals for logbook
        return [
            ProposalStatus::COMPLETED,
        ];
    }

    protected function getViewName(): string
    {
        return 'livewire.research.daily-note.index';
    }

    protected function getRelations(): array
    {
        return [
            'submitter.identity',
            'researchScheme',
            'focusArea',
            'dailyNotes',
            'budgetItems',
            'media',
        ];
    }
}
