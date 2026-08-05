<?php

namespace App\Livewire\Reports;

use Livewire\Attributes\Layout;

#[Layout('components.layouts.app', ['title' => 'Laporan Penelitian', 'pageTitle' => 'Laporan Penelitian'])]
class Research extends AbstractInstitutionalReport
{
    protected function displayName(): string
    {
        return 'Penelitian';
    }

    protected function detailableType(): string
    {
        return 'App\Models\Research';
    }

    protected function schemeColumn(): string
    {
        return 'research_scheme_id';
    }

    protected function schemeRelation(): string
    {
        return 'researchScheme';
    }

    protected function reportType(): string
    {
        return 'research';
    }

    protected function viewName(): string
    {
        return 'livewire.reports.research';
    }

    protected function pdfRoute(): string
    {
        return 'reports.research.pdf';
    }

    protected function excelRoute(): string
    {
        return 'reports.research.excel';
    }

    protected function detailRoute(): string
    {
        return 'research.proposal.show';
    }
}
