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

class SintaCommunityServiceExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
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
            ->where('detailable_type', 'App\Models\CommunityService')
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

        $dana = $proposal->approved_budget_total;

        // Exclude ketua from members list
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
            1,
            'Pemberdayaan Masyarakat',
            $proposal->researchScheme->name ?? 'Pengabdian Kepada Masyarakat Internal',
            'didanai',
            $dana,
            '',
            $this->institutionName,
            '-',
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
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0ca678']],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }
}
