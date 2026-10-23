<?php

namespace App\Exports;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MonevRecapExport implements FromView, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected string $academicYear,
        protected ?string $semester = null
    ) {}

    public function view(): View
    {
        $query = Proposal::query()
            ->where('status', ProposalStatus::COMPLETED)
            ->forAcademicYear($this->academicYear)
            ->with([
                'submitter.identity.faculty',
                'submitter.identity.studyProgram',
                'monevReviews' => function ($q) {
                    $q->whereNotNull('finalized_by_lppm_at')->latest();
                },
                'monevReviews.reviewer',
                'progressReports' => function ($q) {
                    $q->where('reporting_period', 'final')->latest();
                },
            ]);

        if ($this->semester) {
            $query->where('semester', $this->semester);
        }

        $proposals = $query->latest()->get();

        return view('exports.monev-recap', [
            'proposals' => $proposals,
            'academicYear' => $this->academicYear,
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
