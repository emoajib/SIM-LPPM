<?php

declare(strict_types=1);

namespace App\Livewire\KepalaLppm;

use App\Enums\BudgetAmendmentStatus;
use App\Enums\ProposalStatus;
use App\Livewire\Concerns\HasToast;
use App\Models\ActivityLog;
use App\Models\BudgetAmendment;
use App\Models\Faculty;
use App\Models\Proposal;
use App\Services\BudgetAmendmentService;
use App\Services\NotificationService;
use App\Support\AcademicPeriod;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager

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

    #[Url]
    public string $yearFilter = '';

    #[Url(except: 'all')]
    public string $facultyFilter = 'all';

    public ?string $returningProposalId = null;

    public string $returnNotes = '';

    public string $decisionNotes = '';

    public ?string $rejectingAmendmentId = null;

    public string $rejectionNotes = '';

    public function mount(): void
    {
        abort_unless(Auth::user()?->activeHasAnyRole(['kepala lppm', 'admin lppm', 'superadmin', 'rektor']), 403);
    }

    // Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager
    public function updated(string $property): void
    {
        if (str_ends_with($property, 'Filter') || $property === 'search') {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->typeFilter = 'all';
        $this->statusFilter = 'all';
        $this->yearFilter = '';
        $this->facultyFilter = 'all';
        $this->resetPage();
    }

    #[Computed]
    public function faculties()
    {
        return Faculty::orderBy('name')->get();
    }

    #[Computed]
    public function availableYears(): array
    {
        $periods = Proposal::where('status', ProposalStatus::COMPLETED)
            ->whereNotNull('start_year')
            ->select('start_year', 'semester')
            ->distinct()
            ->get();

        return AcademicPeriod::optionsFromPeriods($periods);
    }

    // Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager
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
        $pendingAmendment = $proposal->budgetAmendments()->where('status', BudgetAmendmentStatus::PENDING->value)->first();
        if ($pendingAmendment) {
            $this->toastError('LPJ belum bisa disahkan: ada amandemen RAB v'.$pendingAmendment->version.' yang menunggu persetujuan. Putuskan amandemen terlebih dahulu (lihat kolom Amandemen).');

            return;
        }

        DB::transaction(function () use ($proposal) {
            $proposal->update([
                'logbook_approved_at' => now(),
                'logbook_rejection_notes' => null,
                'logbook_rejected_by' => null,
                'logbook_rejected_at' => null,
            ]);
            app(BudgetAmendmentService::class)->snapshotApprovedBudget($proposal->fresh());
        });
        $this->clearFinancialPdfCache((string) $proposal->id);

        unset($this->proposals);
        unset($this->stats);

        $message = 'Laporan Keuangan untuk usulan "'.$proposal->title.'" berhasil disahkan.';
        session()->flash('success', $message);
        $this->toastSuccess($message);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'activity' => 'approve_lpj',
            'description' => 'Mengesahkan LPJ: '.$proposal->title,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ]);
    }

    // Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager
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

        ActivityLog::create([
            'user_id' => Auth::id(),
            'activity' => 'unapprove_lpj',
            'description' => 'Membatalkan pengesahan LPJ: '.$proposal->title,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ]);
    }

    public function approveAmendment(string $amendmentId): void
    {
        abort_unless(Auth::user()?->activeHasAnyRole(['kepala lppm', 'admin lppm', 'superadmin']), 403);

        try {
            $amendment = BudgetAmendment::findOrFail($amendmentId);
            app(BudgetAmendmentService::class)->approve($amendment, Auth::user(), $this->decisionNotes ?: null);
            $this->reset('decisionNotes');
            $this->toastSuccess('Amandemen RAB disetujui dan RAB aktif diperbarui.');

            ActivityLog::create([
                'user_id' => Auth::id(),
                'activity' => 'approve_amendment',
                'description' => 'Menyetujui amandemen RAB #'.$amendment->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
            ]);
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->implode(' ');
            $this->toastError($msg ?: 'Gagal menyetujui amandemen.');
        } catch (\Throwable $e) {
            $this->toastError('Gagal menyetujui amandemen: '.$e->getMessage());
        }

        unset($this->proposals);
        unset($this->stats);
        unset($this->amendmentViolations);
    }

    public function rejectAmendment(?string $amendmentId = null): void
    {
        abort_unless(Auth::user()?->activeHasAnyRole(['kepala lppm', 'admin lppm', 'superadmin']), 403);

        $amendmentId = $amendmentId ?: $this->rejectingAmendmentId;

        if (! $amendmentId) {
            return;
        }

        // Catatan penolakan wajib dan divalidasi inline di modal, agar Kepala
        // LPPM tidak menekan tombol lalu "tidak terjadi apa-apa" (BC-08).
        $this->validate([
            'rejectionNotes' => 'required|string|min:5|max:2000',
        ], [
            'rejectionNotes.required' => 'Catatan penolakan wajib diisi agar dosen tahu alasan dan cara memperbaikinya.',
            'rejectionNotes.min' => 'Catatan penolakan minimal 5 karakter.',
            'rejectionNotes.max' => 'Catatan penolakan maksimal 2000 karakter.',
        ]);

        try {
            $amendment = BudgetAmendment::findOrFail($amendmentId);
            app(BudgetAmendmentService::class)->reject($amendment, Auth::user(), $this->rejectionNotes);
            $this->reset('decisionNotes');
            $this->closeRejectModal();
            $this->toastSuccess('Amandemen RAB ditolak. LPJ dapat disahkan dengan RAB awal.');

            ActivityLog::create([
                'user_id' => Auth::id(),
                'activity' => 'reject_amendment',
                'description' => 'Menolak amandemen RAB #'.$amendment->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
            ]);
        } catch (ValidationException $e) {
            $this->toastError(collect($e->errors())->flatten()->first() ?? 'Gagal menolak amandemen.');
        } catch (\Throwable $e) {
            $this->toastError('Gagal menolak amandemen: '.$e->getMessage());
        }

        unset($this->proposals);
        unset($this->stats);
        unset($this->amendmentViolations);
    }

    /**
     * Buka modal penolakan amandemen dengan catatan yang sudah terisi.
     *
     * Catatan sebelumnya berada di satu textarea di atas tabel sehingga
     * tombol "Tolak Amandemen" di baris tabel terasa tidak berfungsi.
     */
    public function openRejectModal(string $amendmentId): void
    {
        abort_unless(Auth::user()?->activeHasAnyRole(['kepala lppm', 'admin lppm', 'superadmin']), 403);

        $amendment = BudgetAmendment::with('items')->findOrFail($amendmentId);
        $violations = app(BudgetAmendmentService::class)
            ->allocationViolations($amendment->proposal, $amendment);

        $this->rejectingAmendmentId = $amendment->id;
        $this->rejectionNotes = $this->buildRejectionDraft($violations);
        $this->resetValidation('rejectionNotes');

        $this->dispatch('open-modal', modalId: 'modalRejectAmendment');
    }

    public function closeRejectModal(): void
    {
        $this->rejectingAmendmentId = null;
        $this->rejectionNotes = '';
        $this->resetValidation('rejectionNotes');
    }

    /**
     * @param  array<int, array{group: string, used: float, allocation: float}>  $violations
     */
    protected function buildRejectionDraft(array $violations): string
    {
        if ($violations === []) {
            return 'Amandemen RAB ditolak. Mohon sesuaikan kembali rencana anggaran dan ajukan ulang bila diperlukan.';
        }

        $lines = ['Alokasi baru di bawah realisasi yang sudah tercatat:'];
        foreach ($violations as $violation) {
            $lines[] = '- '.$violation['group'].': realisasi Rp '.number_format($violation['used'], 0, ',', '.').
                ' > alokasi baru Rp '.number_format($violation['allocation'], 0, ',', '.');
        }
        $lines[] = 'Mohon sesuaikan alokasi agar tidak lebih kecil dari realisasi, lalu ajukan amandemen ulang.';

        return implode("\n", $lines);
    }

    /**
     * Pelanggaran realisasi vs alokasi baru per proposal, untuk proposal di
     * halaman aktif. Nilainya dipakai UI agar tombol "Setujui" dinonaktifkan
     * sebelum diklik, bukan gagal setelahnya.
     *
     * @return array<string, array<int, array{group_id: int, group: string, used: float, allocation: float}>>
     */
    #[Computed]
    public function amendmentViolations(): array
    {
        $service = app(BudgetAmendmentService::class);
        $violations = [];

        foreach ($this->proposals as $proposal) {
            $pending = $proposal->budgetAmendments
                ->firstWhere('status', BudgetAmendmentStatus::PENDING->value);

            if (! $pending) {
                continue;
            }

            $found = $service->allocationViolations($proposal, $pending);

            if ($found !== []) {
                $violations[$proposal->id] = $found;
            }
        }

        return $violations;
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

        ActivityLog::create([
            'user_id' => Auth::id(),
            'activity' => 'return_lpj',
            'description' => 'Mengembalikan LPJ: '.$proposal->title,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ]);
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

        $blockedAmendment = (clone $baseQuery)
            ->whereNull('logbook_approved_at')
            ->whereHas('budgetAmendments', fn ($q) => $q->where('status', BudgetAmendmentStatus::PENDING->value))
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
            'blocked_amendment' => $blockedAmendment,
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
                'budgetAmendments',
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

        if ($this->facultyFilter !== 'all') {
            $query->whereHas('submitter.identity', fn ($iq) => $iq->where('faculty_id', $this->facultyFilter));
        }

        if ($this->yearFilter !== '') {
            $query->forAcademicYear($this->yearFilter);
        }

        $paginator = $query
            ->orderByRaw('CASE WHEN logbook_approved_at IS NULL THEN 0 ELSE 1 END')
            ->latest('updated_at')
            ->paginate(15);

        // Self-healing pagination clamp
        if ($paginator->currentPage() > $paginator->lastPage() && $paginator->total() > 0) {
            $this->setPage(1);

            return $query
                ->orderByRaw('CASE WHEN logbook_approved_at IS NULL THEN 0 ELSE 1 END')
                ->latest('updated_at')
                ->paginate(15);
        }

        return $paginator;
    }

    public function render(): View
    {
        return view('livewire.kepala-lppm.financial-approval');
    }
}
