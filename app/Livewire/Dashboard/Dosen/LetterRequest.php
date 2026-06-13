<?php

namespace App\Livewire\Dashboard\Dosen;

use App\Models\LetterType;
use App\Models\Proposal;
use App\Models\Setting;
use App\Services\LetterService;
use Livewire\Component;

class LetterRequest extends Component
{
    public Proposal $proposal;

    public $showModal = false;

    public $letterTypeId;

    public $availableTypes = [];

    // Form data
    public $activityType;

    public $dateString;

    public $timeString;

    public $location;

    public $destinationName; // For SP

    public $tembusan = '1. Arsip';

    protected $listeners = ['openLetterRequest' => 'open'];

    public function mount(Proposal $proposal): void
    {
        $this->proposal = $proposal;
        $this->availableTypes = LetterType::where('is_uploadable', false)->get();

        // Default data from proposal
        $this->location = $proposal->location ?? '';
        $this->activityType = str_contains($proposal->detailable_type, 'Research') ? 'Penelitian' : 'Pengabdian kepada Masyarakat';
    }

    public function open(): void
    {
        if (! Setting::get('module_persuratan_active', false)) {
            $this->dispatch('swal', title: 'Gagal', text: 'Modul persuratan sedang dinonaktifkan.', icon: 'error');

            return;
        }

        $this->showModal = true;
    }

    public function submit(): void
    {
        $this->validate([
            'letterTypeId' => 'required|exists:letter_types,id',
            'activityType' => 'required|string',
            'dateString' => 'required|string',
            'timeString' => 'required|string',
            'location' => 'required|string',
            'destinationName' => 'required_if:letterTypeId,2|nullable|string',
        ]);

        if (! Setting::get('module_persuratan_active', false)) {
            $this->dispatch('swal', title: 'Gagal', text: 'Modul persuratan sedang dinonaktifkan.', icon: 'error');

            return;
        }

        $service = app(LetterService::class);

        if ($service->hasDuplicateLetter($this->proposal, $this->letterTypeId)) {
            $this->dispatch('swal', title: 'Gagal', text: 'Surat jenis ini sudah diajukan untuk proposal ini.', icon: 'error');

            return;
        }

        $service->requestLetter($this->proposal, auth()->user(), [
            'letterTypeId' => $this->letterTypeId,
            'activityType' => $this->activityType,
            'dateString' => $this->dateString,
            'timeString' => $this->timeString,
            'location' => $this->location,
            'destinationName' => $this->destinationName,
            'tembusan' => $this->tembusan,
        ]);

        $this->showModal = false;
        $this->dispatch('swal', title: 'Berhasil', text: 'Permohonan surat berhasil dikirim ke Kepala LPPM.', icon: 'success');
        $this->dispatch('letterRequested');
    }

    public function render()
    {
        return view('livewire.dashboard.dosen.letter-request');
    }
}
