<?php

namespace App\Exports;

use App\Models\CommunityService;
use App\Models\Proposal;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * @implements WithMapping<Proposal>
 */
class CommunityServiceProposalExport implements FromQuery, WithHeadings, WithMapping
{
    protected int $year;

    protected ?string $scheme = null;

    public function __construct(int $year, ?string $scheme = null)
    {
        $this->year = $year;
        $this->scheme = $scheme;
    }

    /**
     * @return Builder<Proposal>
     */
    public function query(): Builder
    {
        return Proposal::where('detailable_type', CommunityService::class)
            ->where('start_year', $this->year)
            ->when($this->scheme && $this->scheme !== 'all', fn ($q) => $q->where('community_service_scheme_id', $this->scheme))
            ->with([
                'submitter',
                'submitter.identity',
                'communityServiceScheme',
                'focusArea',
                'detailable',
                'teamMembers',
                'teamMembers.identity',
            ])
            ->latest();
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

    /**
     * @param  Proposal  $proposal
     * @return array<int, mixed>
     */
    public function map($proposal): array
    {
        $no = 1;
        $submitter = $proposal->submitter;
        $submitterIdentity = $submitter->identity;
        $communityService = $proposal->detailable;

        $teamMembers = $proposal->teamMembers()->limit(5)->get();

        $memberData = [];
        for ($i = 0; $i < 5; $i++) {
            if ($i < $teamMembers->count()) {
                $member = $teamMembers[$i];
                $memberIdentity = $member->identity;
                $memberData[] = $memberIdentity->sinta_id ?? '';
                $memberData[] = $memberIdentity->identity_id ?? '';
                $memberData[] = $member->name;
            } else {
                $memberData[] = '';
                $memberData[] = '';
                $memberData[] = '';
            }
        }

        return [
            $no,
            $submitterIdentity->sinta_id ?? '',
            $submitter->name,
            $submitterIdentity->identity_id ?? '',
            $submitterIdentity->institution_name ?? '',
            '',
            $proposal->title ?? '',
            $proposal->communityServiceScheme->name ?? '',
            $proposal->start_year ?? '',
            $proposal->start_year ?? '',
            $proposal->start_year ?? '',
            $proposal->duration_in_years ?? '',
            $proposal->focusArea->name ?? '',
            $proposal->communityServiceScheme->name ?? '',
            $proposal->status->label(),
            $proposal->budgetItems->sum('total_price'),
            $submitterIdentity->sinta_id ?? '',
            $submitterIdentity->institution_name ?? '',
            '',
            '',
            'ID',
            'Pribadi',
            ...$memberData,
        ];
    }
}
