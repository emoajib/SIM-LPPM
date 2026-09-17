<?php

namespace App\Exports;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager
class ResearchReportExport implements FromView, ShouldAutoSize, WithColumnFormatting, WithStyles
{
    public function __construct(
        protected string $period,
        protected ?string $search = null,
        protected ?string $scheme = null,
        protected ?string $faculty = null,
        protected ?string $semester = null,
        protected ?string $reportStatus = null
    ) {}

    public function view(): View
    {
        $proposals = Proposal::query()
            ->where('detailable_type', 'App\Models\Research')
            ->where(function ($q) {
                $q->where('status', ProposalStatus::COMPLETED)
                    ->orWhereHas('progressReports');
            })
            ->when($this->period, fn ($q) => $q->where('start_year', $this->period))
            ->when($this->semester && $this->semester !== 'all', fn ($q) => $q->where('semester', $this->semester))
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('title', 'like', "%{$this->search}%")
                        ->orWhereHas('submitter', fn ($u) => $u->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->scheme && $this->scheme !== 'all', fn ($q) => $q->where('research_scheme_id', $this->scheme))
            ->when($this->faculty && $this->faculty !== 'all', function ($q) {
                $q->whereHas('submitter.identity', fn ($i) => $i->where('faculty_id', $this->faculty));
            })
            ->when($this->reportStatus && $this->reportStatus !== 'all', function ($q) {
                if ($this->reportStatus === 'belum_laporan') {
                    $q->whereDoesntHave('progressReports', fn ($rq) => $rq->where('reporting_period', 'final'));
                } else {
                    $q->whereHas('progressReports', fn ($rq) => $rq->where('reporting_period', 'final')->where('status', $this->reportStatus));
                }
            })
            ->with(['submitter.identity.faculty', 'submitter.identity.studyProgram', 'researchScheme', 'budgetItems', 'latestFinalReport', 'progressReports' => fn ($q) => $q->where('reporting_period', 'final')->latest()])
            ->latest()
            ->get();

        return view('exports.research', [
            'proposals' => $proposals,
            'period' => $this->period,
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

    public function columnFormats(): array
    {
        return [
            'G' => '#,##0', // Kolom Dana Disetujui
        ];
    }
}
