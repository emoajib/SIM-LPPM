<?php

namespace App\Livewire\Dashboard\Dosen;

use App\Models\Letter;
use App\Models\LetterType;
use App\Services\LetterService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Riwayat Surat', 'pageTitle' => 'Riwayat Surat Saya', 'pageSubtitle' => 'Lihat status dan riwayat pengajuan surat'])]
class LetterHistory extends Component
{
    use WithPagination;

    public $search = '';

    public $statusFilter = '';

    public $typeFilter = '';

    public $showResubmitModal = false;

    public $resubmitLetterId = null;

    public $resubmitData = [
        'title' => '',
        'activityType' => 'Penelitian',
        'dateString' => '',
        'timeString' => '',
        'location' => '',
        'destinationName' => '',
        'tembusan' => '1. Arsip',
    ];

    public function render()
    {
        $letters = Letter::with(['letterType'])
            ->where('user_id', auth()->id())
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter, fn ($q) => $q->where('letter_type_id', $this->typeFilter))
            ->when($this->search, function ($q) {
                $q->where('letter_number', 'like', '%'.$this->search.'%')
                    ->orWhereHas('letterType', fn ($tq) => $tq->where('name', 'like', '%'.$this->search.'%'));
            })
            ->latest()
            ->paginate(10);

        return view('livewire.dashboard.dosen.letter-history', [
            'letters' => $letters,
            'letterTypes' => LetterType::where('is_active', true)->orderBy('code')->get(),
        ]);
    }

    public function cancel($id, LetterService $service): void
    {
        $letter = Letter::find($id);

        if (! $letter || $letter->user_id !== auth()->id()) {
            $this->dispatch('swal', title: 'Gagal', text: 'Surat tidak ditemukan.', icon: 'error');

            return;
        }

        try {
            $service->cancelLetter($letter);

            $letter->logs()->create([
                'user_id' => auth()->id(),
                'action' => 'cancelled',
                'notes' => 'Surat dibatalkan oleh pengaju.',
                'created_at' => now(),
            ]);

            $this->dispatch('swal', title: 'Berhasil', text: 'Surat berhasil dibatalkan.', icon: 'success');
        } catch (\DomainException $e) {
            $this->dispatch('swal', title: 'Gagal', text: $e->getMessage(), icon: 'error');
        }
    }

    public function openResubmitModal($id): void
    {
        $letter = Letter::find($id);
        if (! $letter) {
            return;
        }

        $this->resubmitLetterId = $id;
        $this->resubmitData = [
            'title' => $letter->metadata['title'] ?? '',
            'activityType' => $letter->metadata['activity_type'] ?? 'Penelitian',
            'dateString' => $letter->metadata['date_string'] ?? '',
            'timeString' => $letter->metadata['time_string'] ?? '',
            'location' => $letter->metadata['location'] ?? '',
            'destinationName' => $letter->metadata['destination_name'] ?? '',
            'tembusan' => is_array($letter->metadata['tembusan'] ?? null) ? implode("\n", $letter->metadata['tembusan']) : ($letter->metadata['tembusan'] ?? '1. Arsip'),
        ];
        $this->showResubmitModal = true;
    }

    public function confirmResubmit(LetterService $service): void
    {
        $letter = Letter::find($this->resubmitLetterId);
        if (! $letter || $letter->user_id !== auth()->id()) {
            $this->dispatch('swal', title: 'Gagal', text: 'Surat tidak ditemukan.', icon: 'error');

            return;
        }

        try {
            $service->resubmitLetter($letter, [
                'title' => $this->resubmitData['title'],
                'activityType' => $this->resubmitData['activityType'],
                'dateString' => $this->resubmitData['dateString'],
                'timeString' => $this->resubmitData['timeString'],
                'location' => $this->resubmitData['location'],
                'destinationName' => $this->resubmitData['destinationName'],
                'tembusan' => $this->resubmitData['tembusan'],
            ]);

            $letter->logs()->create([
                'user_id' => auth()->id(),
                'action' => 'resubmitted',
                'notes' => 'Surat diajukan ulang setelah ditolak.',
                'created_at' => now(),
            ]);

            $this->showResubmitModal = false;
            $this->dispatch('swal', title: 'Berhasil', text: 'Surat berhasil diajukan ulang.', icon: 'success');
        } catch (\DomainException $e) {
            $this->dispatch('swal', title: 'Gagal', text: $e->getMessage(), icon: 'error');
        }
    }
}
