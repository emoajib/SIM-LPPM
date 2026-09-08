<?php

namespace App\Livewire\CommunityService\DailyNote;

use App\Enums\ProposalStatus;
use App\Livewire\Abstracts\ReportIndex;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager
class Index extends ReportIndex
{
    protected function getDetailableType(): string
    {
        return 'App\Models\CommunityService';
    }

    protected function getStatusFilter(): array
    {
        return [
            ProposalStatus::COMPLETED,
        ];
    }

    protected function getViewName(): string
    {
        return 'livewire.community-service.daily-note.index';
    }

    protected function getRelations(): array
    {
        return [
            'submitter.identity',
            'communityServiceScheme',
            'focusArea',
            'dailyNotes',
            'budgetItems',
            'media',
        ];
    }
}
