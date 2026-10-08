<?php

declare(strict_types=1);

namespace App\Livewire\KepalaLppm;

use App\Enums\ProposalStatus;
use App\Livewire\Concerns\HasToast;
use App\Models\Proposal;
use App\Services\BudgetAmendmentService;
use App\Services\NotificationService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

/**
 * @property-read LengthAwarePaginator $proposals
 * @property-read array $stats
 */
class FinancialApproval extends Component
{
    use HasToast;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $typeFilter = 'all';

    #[Url]
    public string $statusFilter = 'all';

    public ?string $returningProposalId = null;

    public string $returnNotes = '';

    public function mount(): void
    {
        abort_unless(Auth::user()?->activeHasAnyRole(['kepala lppm', 'admin lppm', 'superadmin', 'rektor']), 403);
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->typeFilter = 'all';
        $this->statusFilter = 'all';
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    // Vetted by AI - Manual Review Required by Senior Engineer/Manager
    public function approveLpj(string $proposalId): void
    {
        abort_unless(Auth::user()?->activeHasAnyRole(['kepala lppm', 'admin lppm', 'superadmin']), 403);

        $proposal = Proposal::findOrFail($proposalId);

        // Jangan sahkan LPJ yang belum ada isinya sama sekali.
        if (! $proposal->hasMedia('logbook_approval_file') && ! $proposal->dailyNotes()->exists()) {
            $this->toastError('LPJ belum bisa disahkan: belum ada berkas scan maupun catatan harian. Kembalikan ke dosen bila perlu dilengkapi.');

            return;
        }

        // Jangan sahkan LPJ yang RAB-nya sedang dalam amandemen pending.
        if ($proposal->hasPendingBudgetAmendment()) {
            $this->toastError('LPJ belum bisa disahkan: ada amandemen RAB yang menunggu persetujuan. Putuskan amandemen terlebih dahulu.');

            return;
        }

        $proposal->update([
            'logbook_approved_at' => now(),
            'logbook_rejection_notes' => null,
            'logbook_rejected_by' => null,
            'logbook_rejected_at' => null,
        ]);
        app(BudgetAmendmentService::class)->snapshotApprovedBudget($proposal->fresh());
        $this->clearFinancialPdfCache((string) $proposal->id);

        unset($this->proposals);
        unset($this->stats);

        $message = 'Laporan Keuangan untuk usulan "'.$proposal->title.'" berhasil disahkan.';
        session()->flash('success', $message);
        $this->toastSuccess($message);
    }

    // Vetted by AI - Manual Review Required by Senior Engineer/Manager
    public function unapproveLpj(string $proposalId): void
    {
        abort_unless(Auth::user()?->activeHasAnyRole(['kepala lppm', 'admin lppm', 'superadmin']), 403);

        $proposal = Proposal::findOrFail($proposalId);
        $proposal->update(['logbook_approved_at' => null]);
        $this->clearFinancialPdfCache((string) $proposal->id);

        unset($this->proposals);
        unset($this->stats);

        $message = 'Pengesahan Laporan Keuangan usulan "'.$proposal->title.'" dibatalkan.';
        session()->flash('info', $message);
        $this->toastInfo($message);
    }

    protected function clearFinancialPdfCache(string $proposalId): void
    {
        $financialFiles = glob(storage_path('app/pdf_cache/financial/financial_'.$proposalId.'*.pdf'));
        if (is_array($financialFiles)) {
            foreach ($financialFiles as $file) {
                @unlink($file);
            }
        }
    }

    public function openReturnModal(string $proposalId): void
    {
        abort_unless(Auth::user()?->activeHasAnyRole(['kepala lppm', 'admin lppm', 'superadmin']), 403);

        $this->returningProposalId = $proposalId;
        $this->returnNotes = '';
    }

    public function closeReturnModal(): void
    {
        $this->returningProposalId = null;
        $this->returnNotes = '';
    }

    /**
     * Kembalikan LPJ ke dosen beserta catatan perbaikan.
     * Dapat dilakukan Kepala LPPM maupun Admin LPPM.
     */
    public function returnToDosen(): void
    {
        abort_unless(Auth::user()?->activeHasAnyRole(['kepala lppm', 'admin lppm', 'superadmin']), 403);

        $this->validate([
            'returnNotes' => 'required|string|min:10|max:2000',
        ], [
            'returnNotes.required' => 'Catatan pengembalian wajib diisi agar dosen tahu yang perlu diperbaiki.',
            'returnNotes.min' => 'Catatan minimal 10 karakter agar dosen memahami yang perlu diperbaiki.',
            'returnNotes.max' => 'Catatan maksimal 2000 karakter.',
        ]);

        if (! $this->returningProposalId) {
            return;
        }

        $proposal = Proposal::findOrFail($this->returningProposalId);
        $returner = Auth::user();
        $notes = $this->returnNotes;

        $proposal->update([
            'logbook_approved_at' => null,
            'logbook_rejection_notes' => $notes,
            'logbook_rejected_by' => $returner->id,
            'logbook_rejected_at' => now(),
        ]);
        $this->clearFinancialPdfCache((string) $proposal->id);

        // Beri tahu ketua + anggota tim agar catatan terbaca dosen.
        try {
            $roleTitle = match (active_role()) {
                'admin lppm' => 'Admin LPPM',
                default => 'Kepala LPPM',
            };
            app(NotificationService::class)->notifyLpjReturned(
                $proposal->loadMissing(['submitter', 'teamMembers']),
                $returner,
                $notes,
                $roleTitle
            );
        } catch (\Throwable $e) {
            \Log::warning('Gagal kirim notifikasi pengembalian LPJ: '.$e->getMessage(), [
                'proposal_id' => $proposal->id,
            ]);
        }

        $this->closeReturnModal();
        unset($this->proposals);
        unset($this->stats);

        $message = 'LPJ dikembalikan ke dosen beserta catatan perbaikan. Dosen akan menerima notifikasi.';
        session()->flash('success', $message);
        $this->toastSuccess($message);
        $this->dispatch('close-modal', modalId: 'modalReturnLpj');
    }

    #[Computed]
    public function stats(): array
    {
        $baseQuery = Proposal::query()->where('status', ProposalStatus::COMPLETED);

        $total = (clone $baseQuery)->count();
        $approved = (clone $baseQuery)->whereNotNull('logbook_approved_at')->count();

        $pending = (clone $baseQuery)
            ->whereNull('logbook_approved_at')
            ->where(function ($q) {
                $q->whereHas('media', fn ($m) => $m->where('collection_name', 'logbook_approval_file'))
                    ->orWhereHas('dailyNotes');
            })
            ->count();

        $empty = (clone $baseQuery)
            ->whereNull('logbook_approved_at')
            ->whereDoesntHave('media', fn ($m) => $m->where('collection_name', 'logbook_approval_file'))
            ->whereDoesntHave('dailyNotes')
            ->count();

        $progress = $total > 0 ? round(($approved / $total) * 100, 1) : 0;

        return [
            'total' => $total,
            'approved' => $approved,
            'pending' => $pending,
            'empty' => $empty,
            'progress' => $progress,
        ];
    }

    #[Computed]
    public function proposals(): LengthAwarePaginator
    {
        $query = Proposal::query()
            ->where('status', ProposalStatus::COMPLETED)
            ->with([
                'submitter.identity.studyProgram',
                'submitter.identity.faculty',
                'detailable',
                'researchScheme',
                'communityServiceScheme',
                'dailyNotes',
                'budgetItems',
                'media',
            ]);

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhereHas('submitter', function ($sq) {
                        $sq->where('name', 'like', "%{$this->search}%");
                    });
            });
        }

        if ($this->typeFilter !== 'all') {
            $detailableType = $this->typeFilter === 'research'
                ? 'App\Models\Research'
                : 'App\Models\CommunityService';
            $query->where('detailable_type', $detailableType);
        }

        if ($this->statusFilter === 'approved') {
            $query->whereNotNull('logbook_approved_at');
        } elseif ($this->statusFilter === 'pending') {
            $query->whereNull('logbook_approved_at')
                ->where(function ($q) {
                    $q->whereHas('media', fn ($m) => $m->where('collection_name', 'logbook_approval_file'))
                        ->orWhereHas('dailyNotes');
                });
        } elseif ($this->statusFilter === 'empty') {
            $query->whereNull('logbook_approved_at')
                ->whereDoesntHave('media', fn ($m) => $m->where('collection_name', 'logbook_approval_file'))
                ->whereDoesntHave('dailyNotes');
        }

        return $query
            ->orderByRaw('CASE WHEN logbook_approved_at IS NULL THEN 0 ELSE 1 END')
            ->latest('updated_at')
            ->paginate(15);
    }

    public function render(): View
    {
        return view('livewire.kepala-lppm.financial-approval');
    }
}
