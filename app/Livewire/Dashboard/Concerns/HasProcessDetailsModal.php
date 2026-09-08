<?php

namespace App\Livewire\Dashboard\Concerns;

use App\Enums\ProposalStatus;
use App\Enums\ProposalUserStatus;
use App\Models\Proposal;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * Trait untuk modal interaktif monitoring proses pada Dashboard.
 *
 * Vetted by AI - Manual Review Required by Senior Engineer/Manager
 */
trait HasProcessDetailsModal
{
    // Vetted by AI - Manual Review Required by Senior Engineer/Manager
    public ?string $activeProcessType = null;

    public string $processSearch = '';

    public string $processTypeFilter = 'all'; // 'all', 'research', 'community_service'

    public string $processStatusFilter = 'all';

    public function openProcessModal(string $type): void
    {
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        $canonicalType = match ($type) {
            'revision', 'perbaikan_usulan' => 'perbaikan_usulan',
            'financial', 'catatan_harian_keuangan' => 'catatan_harian_keuangan',
            'final_report', 'laporan_akhir' => 'laporan_akhir',
            'review' => 'review',
            'monev' => 'monev',
            'iku', 'luaran' => 'iku',
            default => $type,
        };

        $allowed = [
            'usulan',
            'perbaikan_usulan',
            'catatan_harian_keuangan',
            'laporan_akhir',
            'review',
            'monev',
            'iku',
        ];

        if (! in_array($canonicalType, $allowed, true)) {
            return;
        }

        $this->activeProcessType = $canonicalType;
        $this->processSearch = '';
        $this->processTypeFilter = 'all';
        $this->processStatusFilter = 'all';
    }

    public function closeProcessModal(): void
    {
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        $this->activeProcessType = null;
        $this->processSearch = '';
        $this->processTypeFilter = 'all';
        $this->processStatusFilter = 'all';
    }

    public function updatedProcessSearch(): void
    {
        // Real-time reactive search
    }

    public function updatedProcessTypeFilter(): void
    {
        // Real-time reactive type filter
    }

    public function updatedProcessStatusFilter(): void
    {
        // Real-time reactive status filter
    }

    #[Computed]
    public function processModalTitle(): string
    {
        return match ($this->activeProcessType) {
            'usulan' => 'Data Usulan Penelitian & Pengabdian',
            'perbaikan_usulan' => 'Data Perbaikan Usulan (Revisi)',
            'catatan_harian_keuangan' => 'Data Catatan Harian & Laporan Keuangan (LPJ)',
            'laporan_akhir' => 'Data Laporan Akhir Penelitian & Pengabdian',
            'review' => 'Data Progress Review Proposal',
            'monev' => 'Data Progress Monitoring & Evaluasi (Monev)',
            'iku' => 'Data Capaian Target Luaran (IKU)',
            default => 'Data Proses Usulan',
        };
    }

    #[Computed]
    public function processModalDescription(): string
    {
        $year = $this->selectedYear ?? date('Y');

        return match ($this->activeProcessType) {
            'usulan' => "Daftar seluruh usulan penelitian & PKM tahun {$year} yang masuk dan diproses di sistem.",
            'perbaikan_usulan' => "Daftar usulan tahun {$year} yang sedang dalam tahap perbaikan oleh dosen atau menunggu persetujuan perbaikan LPPM.",
            'catatan_harian_keuangan' => "Daftar usulan didanai tahun {$year} beserta rekapitulasi catatan harian belanja dan pengesahan LPJ.",
            'laporan_akhir' => "Daftar usulan didanai tahun {$year} beserta status pelaporan akhir (Draf, Diajukan, Disetujui Dekan/LPPM, Revisi).",
            'review' => "Daftar usulan tahun {$year} dalam tahapan penilaian reviewer beserta status penugasan dan rekomendasi review.",
            'monev' => "Daftar usulan didanai tahun {$year} beserta status monitoring kemajuan, reviewer monev, dan penilaian keterlaksanaan.",
            'iku' => "Daftar usulan didanai tahun {$year} beserta target luaran yang dijanjikan dan status realisasi bukti luaran.",
            default => '',
        };
    }

    #[Computed]
    public function processModalIcon(): string
    {
        return match ($this->activeProcessType) {
            'usulan' => 'ti ti-file-text',
            'perbaikan_usulan' => 'ti ti-refresh',
            'catatan_harian_keuangan' => 'ti ti-receipt-2',
            'laporan_akhir' => 'ti ti-file-certificate',
            'review' => 'ti ti-clipboard-check',
            'monev' => 'ti ti-chart-dots',
            'iku' => 'ti ti-award',
            default => 'ti ti-list-details',
        };
    }

    #[Computed]
    public function processModalColor(): string
    {
        return match ($this->activeProcessType) {
            'usulan' => 'primary',
            'perbaikan_usulan' => 'warning',
            'catatan_harian_keuangan' => 'indigo',
            'laporan_akhir' => 'success',
            'review' => 'warning',
            'monev' => 'info',
            'iku' => 'primary',
            default => 'secondary',
        };
    }

    #[Computed]
    public function processModalData(): Collection
    {
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        if (! $this->activeProcessType) {
            return collect();
        }

        $year = $this->selectedYear ?? date('Y');
        $query = Proposal::query()->where('start_year', $year);

        // Scope by role
        if (method_exists($this, 'applyCommonFilters')) {
            $this->applyCommonFilters($query);
        } elseif (isset($this->user->id)) {
            $query->where(function ($q) {
                $q->where('submitter_id', $this->user->id)
                    ->orWhereHas('teamMembers', fn ($q2) => $q2
                        ->where('user_id', $this->user->id)
                        ->where('status', ProposalUserStatus::ACCEPTED->value)
                    );
            });
        }

        // Scope by process stage
        match ($this->activeProcessType) {
            'usulan' => null, // Tampilkan seluruh usulan di tahun tersebut
            'perbaikan_usulan' => $query->whereIn('status', [
                ProposalStatus::REVISION_NEEDED->value,
                ProposalStatus::REVISION_SUBMITTED->value,
            ]),
            'catatan_harian_keuangan', 'laporan_akhir', 'monev', 'iku' => $query->whereIn('status', [
                ProposalStatus::APPROVED->value,
                ProposalStatus::COMPLETED->value,
            ]),
            'review' => $query->whereIn('status', [
                ProposalStatus::APPROVED->value,
                ProposalStatus::WAITING_REVIEWER->value,
                ProposalStatus::UNDER_REVIEW->value,
                ProposalStatus::REVIEWED->value,
                ProposalStatus::REVISION_NEEDED->value,
                ProposalStatus::REVISION_SUBMITTED->value,
                ProposalStatus::COMPLETED->value,
                ProposalStatus::REJECTED->value,
            ]),
            default => null,
        };

        // Filter: Research vs Community Service
        if ($this->processTypeFilter === 'research') {
            $query->where('detailable_type', 'App\Models\Research');
        } elseif ($this->processTypeFilter === 'community_service') {
            $query->where('detailable_type', 'App\Models\CommunityService');
        }

        // Search Filter
        if (trim($this->processSearch) !== '') {
            $term = '%'.trim($this->processSearch).'%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhereHas('submitter', fn ($sq) => $sq->where('name', 'like', $term));
            });
        }

        // Eager load necessary relationships
        $query->with([
            'submitter.identity.faculty',
            'submitter.identity.studyProgram',
            'researchScheme',
            'communityServiceScheme',
            'latestFinalReport',
            'dailyNotes',
            'budgetItems',
            'reviewers.user.identity',
            'reviewLogs',
            'monevReviews.reviewer.identity',
            'monevs',
            'outputs',
            'progressReports.mandatoryOutputs.media',
            'progressReports.additionalOutputs.media',
        ])
            ->withSum('budgetItems', 'total_price')
            ->withSum('dailyNotes', 'amount')
            ->withCount('dailyNotes')
            ->withCount('outputs')
            ->latest('updated_at');

        return $query->get();
    }
}
