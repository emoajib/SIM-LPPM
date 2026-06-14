<?php

namespace App\Livewire\Dashboard\KepalaLppm;

use App\Models\Letter;
use App\Services\LetterService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager
#[Layout('components.layouts.app', ['title' => 'Persetujuan Surat', 'pageTitle' => 'Persetujuan Surat', 'pageSubtitle' => 'Penandatanganan dokumen legal LPPM'])]
class LetterApproval extends Component
{
    use WithPagination;

    public $search = '';

    public $statusFilter = 'pending_approval';

    public $selectedLetter;

    public $showPreviewModal = false;

    public $selectedIds = [];

    public $rejectReason = '';

    public $showRejectModal = false;

    public $rejectingLetterId = null;

    protected $listeners = ['batchApprove' => 'batchApprove', 'batchReject' => 'batchReject'];

    public function render()
    {
        $letters = Letter::with(['letterType', 'user'])
            ->when($this->statusFilter, function ($query) {
                if ($this->statusFilter === 'all') {
                    return;
                }
                $query->where('status', $this->statusFilter);
            })
            ->when($this->search, function ($query) {
                $query->where(function ($sub) {
                    $sub->where('letter_number', 'like', '%'.$this->search.'%')
                        ->orWhereHas('user', function ($q) {
                            $q->where('name', 'like', '%'.$this->search.'%');
                        })
                        ->orWhereHas('letterType', function ($q) {
                            $q->where('name', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->latest()
            ->paginate(10);

        $stats = (new LetterService)->getLetterStats();

        return view('livewire.dashboard.kepala-lppm.letter-approval', [
            'letters' => $letters,
            'stats' => $stats,
        ]);
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
        $this->selectedIds = [];
    }

    public function preview($id): void
    {
        $this->selectedLetter = Letter::with(['letterType', 'user', 'logs.user'])->find($id);
        $this->showPreviewModal = true;
    }

    public function approve($id, LetterService $service): void
    {
        $letter = Letter::find($id);

        if (! $letter) {
            $this->dispatch('swal', title: 'Gagal', text: 'Surat tidak ditemukan.', icon: 'error');

            return;
        }

        $this->authorize('approve', $letter);

        if ($letter->status !== 'pending_approval') {
            $this->dispatch('swal', title: 'Gagal', text: 'Surat ini sudah diproses.', icon: 'error');

            return;
        }

        try {
            $service->approveLetter($letter);
        } catch (\Exception $e) {
            Log::error('Letter approval failed', [
                'letter_id' => $letter->id, 'error' => $e->getMessage(),
            ]);

            $this->dispatch('swal', title: 'Gagal', text: 'Gagal menerbitkan surat. Surat dikembalikan ke status menunggu.', icon: 'error');

            return;
        }

        $letter->logs()->create([
            'user_id' => auth()->id(),
            'action' => 'approved_and_signed',
            'notes' => 'Surat disetujui dan ditandatangani oleh Kepala LPPM.',
            'created_at' => now(),
        ]);

        $this->showPreviewModal = false;
        $this->dispatch('swal', title: 'Berhasil', text: 'Surat berhasil ditandatangani dan diterbitkan.', icon: 'success');
    }

    public function openRejectModal($id): void
    {
        $this->rejectingLetterId = $id;
        $this->rejectReason = '';
        $this->showRejectModal = true;
    }

    public function confirmReject(LetterService $service): void
    {
        $letter = Letter::find($this->rejectingLetterId);

        if (! $letter) {
            $this->dispatch('swal', title: 'Gagal', text: 'Surat tidak ditemukan.', icon: 'error');

            return;
        }

        $this->authorize('reject', $letter);

        if ($letter->status !== 'pending_approval') {
            $this->dispatch('swal', title: 'Gagal', text: 'Surat ini sudah diproses.', icon: 'error');

            return;
        }

        $service->rejectLetter($letter, $this->rejectReason);

        $letter->logs()->create([
            'user_id' => auth()->id(),
            'action' => 'rejected',
            'notes' => $this->rejectReason,
            'created_at' => now(),
        ]);

        $this->showRejectModal = false;
        $this->showPreviewModal = false;
        $this->rejectingLetterId = null;
        $this->rejectReason = '';
        $this->dispatch('swal', title: 'Berhasil', text: 'Surat telah ditolak.', icon: 'info');
    }

    public function toggleSelect($id): void
    {
        if (in_array($id, $this->selectedIds)) {
            $this->selectedIds = array_filter($this->selectedIds, fn ($i) => $i !== $id);
        } else {
            $this->selectedIds[] = $id;
        }
    }

    public function toggleSelectAll(): void
    {
        $pendingIds = Letter::where('status', 'pending_approval')->pluck('id')->toArray();

        if (count($this->selectedIds) === count($pendingIds)) {
            $this->selectedIds = [];
        } else {
            $this->selectedIds = $pendingIds;
        }
    }

    public function batchApprove(LetterService $service): void
    {
        if (empty($this->selectedIds)) {
            $this->dispatch('swal', title: 'Gagal', text: 'Pilih surat terlebih dahulu.', icon: 'warning');

            return;
        }

        $letters = Letter::whereIn('id', $this->selectedIds)->where('status', 'pending_approval')->get();

        $results = $service->batchApprove($letters);

        foreach ($results['succeeded'] as $letterId) {
            Letter::find($letterId)?->logs()->create([
                'user_id' => auth()->id(),
                'action' => 'approved_and_signed',
                'notes' => 'Surat disetujui secara batch oleh Kepala LPPM.',
                'created_at' => now(),
            ]);
        }

        $this->selectedIds = [];

        $successCount = count($results['succeeded']);
        $failCount = count($results['failed']);

        $this->dispatch('swal', title: 'Selesai', text: "{$successCount} surat berhasil disetujui. {$failCount} gagal.", icon: $failCount > 0 ? 'warning' : 'success');
    }

    public function batchReject(LetterService $service): void
    {
        if (empty($this->selectedIds)) {
            $this->dispatch('swal', title: 'Gagal', text: 'Pilih surat terlebih dahulu.', icon: 'warning');

            return;
        }

        $letters = Letter::whereIn('id', $this->selectedIds)->where('status', 'pending_approval')->get();

        $results = $service->batchReject($letters, 'Ditolak secara batch oleh Kepala LPPM.');

        foreach ($results['succeeded'] as $letterId) {
            Letter::find($letterId)?->logs()->create([
                'user_id' => auth()->id(),
                'action' => 'rejected',
                'notes' => 'Ditolak secara batch oleh Kepala LPPM.',
                'created_at' => now(),
            ]);
        }

        $this->selectedIds = [];

        $successCount = count($results['succeeded']);
        $failCount = count($results['failed']);

        $this->dispatch('swal', title: 'Selesai', text: "{$successCount} surat ditolak. {$failCount} gagal.", icon: $failCount > 0 ? 'warning' : 'info');
    }
}
