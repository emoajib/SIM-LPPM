<?php

namespace App\Exports;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * @implements WithMapping<Proposal>
 */
class ArchiveDataExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    protected string $search;

    protected string $yearFilter;

    public function __construct(string $search = '', string $yearFilter = '')
    {
        $this->search = $search;
        $this->yearFilter = $yearFilter;
    }

    /**
     * @return Builder<Proposal>
     */
    public function query(): Builder
    {
        return Proposal::query()
            ->with(['submitter.identity', 'budgetItems', 'detailable'])
            ->where('status', ProposalStatus::COMPLETED)
            ->when($this->search, function (Builder $q) {
                $q->where('title', 'like', '%'.$this->search.'%')
                    ->orWhereHas('submitter', fn ($u) => $u->where('name', 'like', '%'.$this->search.'%'));
            })
            ->when($this->yearFilter, fn (Builder $q) => $q->where('start_year', $this->yearFilter))
            ->orderByDesc('start_year');
    }

    public function headings(): array
    {
        return [
            'No',
            'Tahun Pelaksanaan',
            'Jenis Kegiatan',
            'Judul Kegiatan',
            'Ketua Pengusul',
            'NIDN / NIP',
            'Dana Disetujui (Rp)',
            'Ringkasan',
        ];
    }

    /**
     * @param  Proposal  $proposal
     * @return array<int, mixed>
     */
    public function map($proposal): array
    {
        static $no = 1;

        $type = 'Tidak Diketahui';
        if ($proposal->detailable_type) {
            $type = str_contains($proposal->detailable_type, 'Research') ? 'Penelitian' : 'Pengabdian';
        }

        $dana = $proposal->sbk_value > 0
            ? $proposal->sbk_value
            : $proposal->budgetItems->sum('total_price');

        return [
            $no++,
            $proposal->start_year,
            $type,
            $proposal->title,
            $proposal->submitter->name ?? '-',
            $proposal->submitter->identity->identity_id ?? '-',
            $dana,
            $proposal->summary,
        ];
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4C6EF5']]],
        ];
    }
}
