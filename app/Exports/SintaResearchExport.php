<?php

namespace App\Exports;

use App\Enums\ProposalStatus;
use App\Models\Institution;
use App\Models\Proposal;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SintaResearchExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected ?string $institutionName;

    protected ?string $institutionCode;

    public function __construct(
        protected ?string $year = null
    ) {
        $institution = Institution::first();
        $this->institutionName = $institution->name ?? 'Institut Teknologi dan Sains Nahdlatul Ulama Pekalongan';
        $this->institutionCode = $institution->code ?? '062004';
    }

    public function collection()
    {
        return Proposal::query()
            ->where('detailable_type', 'App\Models\Research')
            ->whereIn('status', [ProposalStatus::APPROVED->value, ProposalStatus::COMPLETED->value])
            ->when($this->year, fn ($q) => $q->where('start_year', $this->year))
            ->with([
                'submitter.identity',
                'teamMembers.identity',
                'budgetItems',
                'detailable',
                'researchScheme',
            ])
            ->latest()
            ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'sinta_id_ketua',
            'nama_ketua',
            'nidn_ketua',
            'afiliasi_ketua',
            'kd_pt_ketua',
            'judul',
            'nama_singkat_skema',
            'thn_pertama_usulan',
            'thn_usulan_kegiatan',
            'thn_pelaksanaan_kegiatan',
            'lama_kegiatan(tahun)',
            'bidang_fokus',
            'nama_skema',
            'status_usulan (hanya didanai)',
            'dana_disetujui',
            'afiliasi_sinta_id',
            'nama_institusi_penerima_dana',
            'target_tkt',
            'nama_program_hibah',
            'kategori_sumber_dana',
            'negara_sumber_dana',
            'sumber_dana',
            'sinta_id_member1',
            'nidn_member1',
            'nama_member1',
            'sinta_id_member2',
            'nidn_member_sinta2',
            'nama_member_sinta2',
            'sinta_id_member3',
            'nidn_member_sinta3',
            'nama_member_sinta3',
            'sinta_id_member4',
            'nidn_member_sinta4',
            'nama_member_sinta4',
            'sinta_id_member5',
            'nidn_member_sinta5',
            'nama_member_sinta5',
        ];
    }

    public function map($proposal): array
    {
        static $no = 0;
        $no++;

        $ketua = $proposal->submitter;
        $identity = $ketua->identity;

        // Budget: sbk_value or sum of active budget_items
        $dana = $proposal->approved_budget_total;

        // TKT level from detailable
        $tkt = $proposal->detailable->tkt_type ?? '-';

        // Team members (up to 5)
        $members = $proposal->teamMembers
            ->filter(fn ($m) => $m->identity && $m->identity->identity_id !== $identity?->identity_id)
            ->values()
            ->take(5);

        $row = [
            $no,
            $identity->sinta_id ?? '',
            $ketua->name,
            $identity->identity_id ?? '',
            $this->institutionName,
            $this->institutionCode,
            $proposal->title,
            $proposal->researchScheme->code ?? $proposal->researchScheme->name ?? '',
            $proposal->start_year ?? date('Y'),
            $proposal->start_year ?? date('Y'),
            $proposal->start_year ?? date('Y'),
            1, // lama_kegiatan default 1 tahun
            $proposal->detailable->macro_research_group_id ? 'Ilmu Komputer & Informatika' : 'Multidisiplin',
            $proposal->researchScheme->name ?? 'Penelitian Internal',
            'didanai',
            $dana,
            '',  // afiliasi_sinta_id institusi (optional)
            $this->institutionName,
            $tkt,
            'Hibah Internal LPPM ITSNU Pekalongan',
            'mandiri',
            'ID',
            'Dana Internal Perguruan Tinggi',
        ];

        // Add up to 5 team members
        for ($i = 0; $i < 5; $i++) {
            $member = $members->get($i);
            $row[] = $member->identity->sinta_id ?? '';
            $row[] = $member->identity->identity_id ?? '';
            $row[] = $member->name ?? '';
        }

        return $row;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '206bc4']],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }
}
