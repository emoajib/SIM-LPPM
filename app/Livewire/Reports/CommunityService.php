<?php

namespace App\Livewire\Reports;

use Livewire\Attributes\Layout;

#[Layout('components.layouts.app', ['title' => 'Laporan Pengabdian (PKM)', 'pageTitle' => 'Laporan Pengabdian (PKM)'])]
class CommunityService extends AbstractInstitutionalReport
{
    protected function displayName(): string
    {
        return 'PKM';
    }

    protected function detailableType(): string
    {
        return 'App\Models\CommunityService';
    }

    protected function schemeColumn(): string
    {
        return 'community_service_scheme_id';
    }

    protected function schemeRelation(): string
    {
        return 'communityServiceScheme';
    }

    protected function reportType(): string
    {
        return 'pkm';
    }

    protected function viewName(): string
    {
        return 'livewire.reports.community-service';
    }

    protected function pdfRoute(): string
    {
        return 'reports.pkm.pdf';
    }

    protected function excelRoute(): string
    {
        return 'reports.pkm.excel';
    }

    protected function detailRoute(): string
    {
        return 'community-service.proposal.show';
    }
}
