<?php

namespace App\Livewire\Dashboard\KepalaLppm;

use App\Models\Letter;
use App\Services\LetterService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Persetujuan Surat', 'pageTitle' => 'Persetujuan Surat', 'pageSubtitle' => 'Penandatanganan dokumen legal LPPM'])]
class LetterApproval extends Component
{
    use WithPagination;

    public $search = '';

    public $selectedLetter;

    public $showPreviewModal = false;

    public function render()
    {
        $letters = Letter::with(['letterType', 'user'])
            ->whereIn('status', ['pending_approval', 'published', 'ready_to_print'])
            ->when($this->search, function ($query) {
                $query->where('letter_number', 'like', '%'.$this->search.'%')
                    ->orWhereHas('user', function ($q) {
                        $q->where('name', 'like', '%'.$this->search.'%');
                    });
            })
            ->latest()
            ->paginate(10);

        return view('livewire.dashboard.kepala-lppm.letter-approval', [
            'letters' => $letters,
        ]);
    }

    public function preview($id): void
    {
        $this->selectedLetter = Letter::with(['letterType', 'user'])->find($id);
        $this->showPreviewModal = true;
    }

    public function approve($id, LetterService $service): void
    {
        $letter = Letter::find($id);

        if ($letter->status !== 'pending_approval') {
            $this->dispatch('swal', title: 'Gagal', text: 'Surat ini sudah diproses.', icon: 'error');

            return;
        }

        // 1. Generate Letter Number
        /** @var \App\Models\LetterType $letterType */
        $letterType = $letter->letterType;
        $letter->letter_number = $service->generateNextNumber($letterType);

        // 2. Set Publication Info
        $letter->published_at = now();

        // 3. Set Status based on mode
        if ($letter->signature_mode === 'tte') {
            $letter->status = 'published';
        } else {
            $letter->status = 'ready_to_print';
        }

        $letter->save();

        // 4. Generate PDF
        $service->generatePdf($letter);

        // 5. Log activity
        $letter->logs()->create([
            'user_id' => auth()->id(),
            'action' => 'approved_and_signed',
            'notes' => 'Surat disetujui dan ditandatangani oleh Kepala LPPM.',
            'created_at' => now(),
        ]);

        $this->showPreviewModal = false;
        $this->dispatch('swal', title: 'Berhasil', text: 'Surat berhasil ditandatangani dan diterbitkan.', icon: 'success');
    }

    public function reject($id, $notes): void
    {
        $letter = Letter::find($id);
        $letter->update(['status' => 'rejected']);

        $letter->logs()->create([
            'user_id' => auth()->id(),
            'action' => 'rejected',
            'notes' => $notes,
            'created_at' => now(),
        ]);

        $this->showPreviewModal = false;
        $this->dispatch('swal', title: 'Berhasil', text: 'Surat telah ditolak.', icon: 'info');
    }
}
