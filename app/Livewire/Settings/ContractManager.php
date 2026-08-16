<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Enums\ProposalStatus;
use App\Livewire\Concerns\HasToast;
use App\Models\Proposal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Vetted by AI - Manual Review Required by Senior Engineer/Manager
 * Contract Management Livewire Component for Admin LPPM
 */
class ContractManager extends Component
{
    use HasToast;
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    // Filters
    public string $search = '';

    public string $type = 'all'; // all, research, community-service

    public string $year = '';

    public string $statusFilter = 'all'; // all, missing_contract, has_contract

    public int $perPage = 15;

    // Selection for Batch Actions
    public array $selectedProposals = [];

    public bool $selectAll = false;

    // Single Edit Modal State
    public bool $showEditModal = false;

    public ?string $editingProposalId = null;

    public string $editingProposalTitle = '';

    public string $editingContractNumber = '';

    public ?string $editingContractDate = null;

    // Batch Generator Modal State
    public bool $showBatchModal = false;

    public string $batchTarget = 'selected'; // 'selected' or 'all_filtered'

    public string $batchScopeType = 'all'; // 'research', 'community-service', 'all'

    public int $batchStartNumber = 1;

    public int $batchNumberDigits = 3;

    public string $batchPattern = '{num}/ITSNU/LPPM/KTR-{type}/{month}/{year}';

    public ?string $batchContractDate = null;

    protected function rules(): array
    {
        return [
            'editingContractNumber' => 'nullable|string|max:100',
            'editingContractDate' => 'nullable|date',
            'batchTarget' => 'required|in:selected,all_filtered',
            'batchScopeType' => 'required|in:research,community-service,all',
            'batchStartNumber' => 'required|integer|min:1',
            'batchNumberDigits' => 'required|integer|min:1|max:6',
            'batchPattern' => 'required|string|max:150',
            'batchContractDate' => 'required|date',
        ];
    }

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user || ! $user->activeHasAnyRole(['admin lppm', 'admin lppm saintek', 'admin lppm dekabita', 'kepala lppm', 'superadmin'])) {
            abort(403, 'Akses ditolak. Halaman ini hanya untuk Admin LPPM dan Pimpinan.');
        }

        $this->batchContractDate = Carbon::now()->format('Y-m-d');
        $this->year = (string) Carbon::now()->year;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function updatingYear(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $this->selectedProposals = $this->proposalsQuery()->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selectedProposals = [];
        }
    }

    /**
     * Base query for funded/completed proposals
     */
    private function proposalsQuery(): Builder
    {
        return Proposal::query()
            ->with([
                'submitter.identity.studyProgram',
                'submitter.identity.faculty',
                'researchScheme',
                'communityServiceScheme',
                'budgetItems',
            ])
            ->whereIn('status', [
                ProposalStatus::APPROVED,
                ProposalStatus::REVISION_SUBMITTED,
                ProposalStatus::COMPLETED,
            ])
            ->when($this->search !== '', function (Builder $q) {
                $term = '%'.$this->search.'%';
                $q->where(function (Builder $sub) use ($term) {
                    $sub->where('title', 'like', $term)
                        ->orWhere('contract_number', 'like', $term)
                        ->orWhereHas('submitter', function (Builder $sq) use ($term) {
                            $sq->where('name', 'like', $term)
                                ->orWhereHas('identity', function (Builder $iq) use ($term) {
                                    $iq->where('identity_id', 'like', $term);
                                });
                        });
                });
            })
            ->when($this->type !== 'all', function (Builder $q) {
                if ($this->type === 'research') {
                    $q->where('detailable_type', 'App\Models\Research');
                } elseif ($this->type === 'community-service') {
                    $q->where('detailable_type', 'App\Models\CommunityService');
                }
            })
            ->when($this->year !== '', function (Builder $q) {
                $q->where(function (Builder $sub) {
                    $sub->where('start_year', (int) $this->year)
                        ->orWhereYear('created_at', (int) $this->year);
                });
            })
            ->when($this->statusFilter !== 'all', function (Builder $q) {
                if ($this->statusFilter === 'missing_contract') {
                    $q->where(function (Builder $sub) {
                        $sub->whereNull('contract_number')->orWhere('contract_number', '');
                    });
                } elseif ($this->statusFilter === 'has_contract') {
                    $q->whereNotNull('contract_number')->where('contract_number', '!=', '');
                }
            });
    }

    /**
     * Statistics counters
     */
    #[Computed]
    public function stats(): array
    {
        $base = Proposal::query()
            ->whereIn('status', [
                ProposalStatus::APPROVED,
                ProposalStatus::REVISION_SUBMITTED,
                ProposalStatus::COMPLETED,
            ])
            ->when($this->year !== '', function (Builder $q) {
                $q->where(function (Builder $sub) {
                    $sub->where('start_year', (int) $this->year)
                        ->orWhereYear('created_at', (int) $this->year);
                });
            });

        $total = (clone $base)->count();
        $hasContract = (clone $base)->whereNotNull('contract_number')->where('contract_number', '!=', '')->count();
        $missingContract = $total - $hasContract;

        return [
            'total' => $total,
            'has_contract' => $hasContract,
            'missing_contract' => $missingContract,
        ];
    }

    /**
     * List of years for filter
     */
    #[Computed]
    public function availableYears(): array
    {
        $currentYear = (int) Carbon::now()->year;
        $years = [];
        for ($y = $currentYear + 1; $y >= $currentYear - 4; $y--) {
            $years[] = (string) $y;
        }

        return $years;
    }

    /**
     * Open single edit modal
     */
    public function openEdit(string $proposalId): void
    {
        $proposal = Proposal::find($proposalId);
        if (! $proposal) {
            $this->toastError('Proposal tidak ditemukan.');

            return;
        }

        $this->editingProposalId = $proposal->id;
        $this->editingProposalTitle = $proposal->title;
        $this->editingContractNumber = $proposal->contract_number ?? '';
        $this->editingContractDate = $proposal->contract_date ? Carbon::parse($proposal->contract_date)->format('Y-m-d') : Carbon::now()->format('Y-m-d');

        $this->showEditModal = true;
    }

    /**
     * Save single edit
     */
    public function saveSingle(): void
    {
        $this->validate([
            'editingContractNumber' => 'nullable|string|max:100',
            'editingContractDate' => 'nullable|date',
        ]);

        if (! $this->editingProposalId) {
            return;
        }

        $proposal = Proposal::find($this->editingProposalId);
        if ($proposal) {
            $proposal->update([
                'contract_number' => $this->editingContractNumber ?: null,
                'contract_date' => $this->editingContractDate ?: null,
            ]);

            $this->toastSuccess('Nomor kontrak berhasil disimpan.');
        }

        $this->showEditModal = false;
        $this->reset(['editingProposalId', 'editingProposalTitle', 'editingContractNumber', 'editingContractDate']);
    }

    /**
     * Set batch preset scope and pattern
     */
    public function setBatchScopeType(string $scope): void
    {
        $this->batchScopeType = $scope;
        if ($scope === 'research') {
            $this->batchPattern = '{num}/ITSNU/LPPM/KTR-L/{month}/{year}';
        } elseif ($scope === 'community-service') {
            $this->batchPattern = '{num}/ITSNU/LPPM/KTR-PKM/{month}/{year}';
        } else {
            $this->batchPattern = '{num}/ITSNU/LPPM/KTR-{type}/{month}/{year}';
        }
    }

    /**
     * Open batch modal with optional preset
     */
    public function openBatchModal(?string $scope = null): void
    {
        if ($scope) {
            $this->setBatchScopeType($scope);
        }

        if (! empty($this->selectedProposals)) {
            $this->batchTarget = 'selected';
        } else {
            $this->batchTarget = 'all_filtered';
        }

        $this->showBatchModal = true;
    }

    /**
     * Convert month number to Roman numeral
     */
    private function getRomanMonth(int $month): string
    {
        $map = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
            5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
            9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];

        return $map[$month] ?? 'I';
    }

    /**
     * Execute Batch Generation
     */
    public function generateBatch(): void
    {
        $this->validate([
            'batchTarget' => 'required|in:selected,all_filtered',
            'batchScopeType' => 'required|in:research,community-service,all',
            'batchStartNumber' => 'required|integer|min:1',
            'batchNumberDigits' => 'required|integer|min:1|max:6',
            'batchPattern' => 'required|string|max:150',
            'batchContractDate' => 'required|date',
        ]);

        if ($this->batchTarget === 'selected') {
            if (empty($this->selectedProposals)) {
                $this->toastWarning('Silakan pilih minimal satu proposal atau gunakan opsi semua usulan filter.');

                return;
            }
            $proposals = Proposal::whereIn('id', $this->selectedProposals)->orderBy('created_at', 'asc')->get();
        } else {
            $proposals = $this->proposalsQuery()->orderBy('created_at', 'asc')->get();
        }

        if ($this->batchScopeType === 'research') {
            $proposals = $proposals->filter(fn (Proposal $p) => $p->detailable_type === 'App\Models\Research');
        } elseif ($this->batchScopeType === 'community-service') {
            $proposals = $proposals->filter(fn (Proposal $p) => $p->detailable_type === 'App\Models\CommunityService');
        }

        if ($proposals->isEmpty()) {
            $this->toastWarning('Tidak ada usulan yang ditemukan untuk digenerate.');

            return;
        }

        $cDate = Carbon::parse($this->batchContractDate);
        $monthRoman = $this->getRomanMonth((int) $cDate->month);
        $year = (string) $cDate->year;

        $currentNumber = $this->batchStartNumber;

        DB::transaction(function () use ($proposals, &$currentNumber, $monthRoman, $year, $cDate) {
            foreach ($proposals as $proposal) {
                /** @var Proposal $proposal */
                $isResearch = $proposal->detailable_type === 'App\Models\Research';
                $typeCode = $isResearch ? 'L' : 'PKM';

                $formattedNum = str_pad((string) $currentNumber, $this->batchNumberDigits, '0', STR_PAD_LEFT);

                $contractNumber = str_replace(
                    ['{num}', '{type}', '{month}', '{year}'],
                    [$formattedNum, $typeCode, $monthRoman, $year],
                    $this->batchPattern
                );

                $proposal->update([
                    'contract_number' => $contractNumber,
                    'contract_date' => $cDate->format('Y-m-d'),
                ]);

                $currentNumber++;
            }
        });

        $this->toastSuccess(count($proposals).' nomor kontrak berhasil digenerate.');
        $this->showBatchModal = false;
        $this->selectedProposals = [];
        $this->selectAll = false;
    }

    /**
     * Render the Livewire component
     */
    public function render()
    {
        /** @var LengthAwarePaginator $proposals */
        $proposals = $this->proposalsQuery()
            ->orderBy('start_year', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        return view('livewire.settings.contract-manager', [
            'proposals' => $proposals,
        ])->layout('components.layouts.app', [
            'title' => 'Manajemen Nomor Kontrak Usulan',
            'pageTitle' => 'Manajemen Nomor Kontrak',
            'pageSubtitle' => 'Kelola nomor dan tanggal kontrak perjanjian penugasan penelitian & pengabdian',
        ]);
    }
}
