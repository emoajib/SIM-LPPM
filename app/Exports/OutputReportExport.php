<?php

namespace App\Exports;

use App\Models\Proposal;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OutputReportExport implements FromView, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected string $activeTab = 'research',
        protected string $search = '',
        protected string $outputType = 'all',
        protected ?string $period = null,
        protected ?string $scheme = null,
        protected ?string $faculty = null,
        protected ?string $semester = null
    ) {}

    public function view(): View
    {
        $detailableType = $this->activeTab === 'research' ? 'App\\Models\\Research' : 'App\\Models\\CommunityService';

        $query = Proposal::query()
            ->with(['submitter.identity.faculty', 'submitter.identity.studyProgram', 'progressReports.mandatoryOutputs.proposalOutput', 'progressReports.additionalOutputs.proposalOutput'])
            ->where('detailable_type', $detailableType)
            ->when($this->period, fn ($q) => $q->where('start_year', $this->period))
            ->when($this->semester && $this->semester !== 'all', fn ($q) => $q->where('semester', $this->semester))
            ->where(function (Builder $query) {
                $query->whereHas('progressReports.mandatoryOutputs')
                    ->orWhereHas('progressReports.additionalOutputs');
            });

        if ($this->search) {
            $query->where(function (Builder $q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhereHas('submitter', function (Builder $u) {
                        $u->where('name', 'like', "%{$this->search}%");
                    });
            });
        }

        if ($this->scheme && $this->scheme !== 'all') {
            $schemeColumn = $this->activeTab === 'research' ? 'research_scheme_id' : 'community_service_scheme_id';
            $query->where($schemeColumn, $this->scheme);
        }

        if ($this->faculty && $this->faculty !== 'all') {
            $query->whereHas('submitter.identity', function ($q) {
                $q->where('faculty_id', $this->faculty);
            });
        }

        if ($this->outputType === 'mandatory') {
            $query->whereHas('progressReports.mandatoryOutputs');
        } elseif ($this->outputType === 'additional') {
            $query->whereHas('progressReports.additionalOutputs');
        }

        $proposals = $query->latest()->get();

        return view('exports.output-reports', [
            'proposals' => $proposals,
            'activeTab' => $this->activeTab,
            'outputType' => $this->outputType,
            'semester' => $this->semester,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            3 => ['font' => ['bold' => true]],
        ];
    }
}
