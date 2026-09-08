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
        $allowed = ['usulan', 'perbaikan_usulan', 'catatan_harian_keuangan', 'laporan_akhir'];
        if (! in_array($type, $allowed, true)) {
            return;
        }

        $this->activeProcessType = $type;
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
            'catatan_harian_keuangan', 'laporan_akhir' => $query->whereIn('status', [
                ProposalStatus::APPROVED->value,
                ProposalStatus::COMPLETED->value,
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
        ])
            ->withSum('budgetItems', 'total_price')
            ->withSum('dailyNotes', 'amount')
            ->withCount('dailyNotes')
            ->latest('updated_at');

        return $query->get();
    }
}
