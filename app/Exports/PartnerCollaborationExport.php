<?php

namespace App\Exports;

use App\Actions\Reports\GetPartnerReportQuery;
use App\Enums\ProposalStatus;
use App\Models\Partner;
use App\Models\Proposal;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PartnerCollaborationExport implements FromView, ShouldAutoSize, WithColumnFormatting, WithStyles
{
    public function __construct(
        protected string $search = '',
        protected string $typeFilter = '',
        protected string $periodFilter = '',
        protected ?string $facultyId = null
    ) {}

    public function view(): View
    {
        $action = new GetPartnerReportQuery;
        $partners = $action->handle($this->search, $this->typeFilter, $this->periodFilter)
            ->when($this->facultyId, fn ($q) => $q->whereHas('proposals.submitter.identity', fn ($i) => $i->where('faculty_id', $this->facultyId)))
            ->get();

        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        $partners->each(function (Partner $partner) {
            $proposals = $partner->proposals()
                ->whereIn('status', [ProposalStatus::APPROVED->value, ProposalStatus::COMPLETED->value])
                ->when($this->periodFilter, fn ($q) => $q->where('start_year', $this->periodFilter))
                ->when($this->facultyId, fn ($q) => $q->whereHas('submitter.identity', fn ($i) => $i->where('faculty_id', $this->facultyId)))
                ->with(['budgetItems'])
                ->get();

            $partner->total_budget = $proposals->sum(function ($p) {
                /** @var Proposal $p */
                return ($p->sbk_value && $p->sbk_value > 0)
                    ? (float) $p->sbk_value
                    : $p->budgetItems->sum('total_price');
            });
        });

        return view('exports.partner-collaboration', [
            'partners' => $partners,
            'periodFilter' => $this->periodFilter,
            'typeFilter' => $this->typeFilter,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]], // Judul Laporan
            3 => ['font' => ['bold' => true]], // Header Tabel
        ];
    }

    public function columnFormats(): array
    {
        return [
            'H' => '#,##0', // Total Dana (Rp)
        ];
    }
}
