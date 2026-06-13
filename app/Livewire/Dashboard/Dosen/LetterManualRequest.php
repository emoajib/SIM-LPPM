<?php

namespace App\Livewire\Dashboard\Dosen;

use App\Models\LetterType;
use App\Services\LetterService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Buat Surat', 'pageTitle' => 'Buat Surat Baru', 'pageSubtitle' => 'Ajukan surat tanpa proposal'])]
class LetterManualRequest extends Component
{
    public $letterTypeId;

    public $title = '';

    public $activityType = 'Penelitian';

    public $dateString = '';

    public $timeString = '';

    public $location = '';

    public $destinationName = '';

    public $tembusan = '1. Arsip';

    public $team = [];

    public $searchQuery = '';

    public $searchResults = [];

    public $letterTypes;

    public $selectedLetterType;

    public function mount(): void
    {
        $this->letterTypes = LetterType::where('is_active', true)->orderBy('code')->get();
    }

    public function updatedLetterTypeId(): void
    {
        $this->selectedLetterType = LetterType::find($this->letterTypeId);
    }

    public function searchDosen(): void
    {
        if (strlen($this->searchQuery) < 2) {
            $this->searchResults = [];

            return;
        }

        $service = new LetterService;
        $this->searchResults = $service->searchDosen($this->searchQuery)->toArray();
    }

    public function addTeamMember(array $dosen): void
    {
        // Check if already added
        foreach ($this->team as $member) {
            if ($member['id'] === $dosen['id']) {
                return;
            }
        }

        $this->team[] = [
            'id' => $dosen['id'],
            'name' => $dosen['name'],
            'role' => 'Anggota',
            'identifier' => '',
        ];

        $this->searchQuery = '';
        $this->searchResults = [];
    }

    public function removeTeamMember(int $index): void
    {
        unset($this->team[$index]);
        $this->team = array_values($this->team);
    }

    public function submit(LetterService $service): void
    {
        $this->validate([
            'letterTypeId' => 'required|exists:letter_types,id',
            'title' => 'required|string|min:3',
            'activityType' => 'required|in:Penelitian,PKM',
            'dateString' => 'required|string',
            'timeString' => 'required|string',
            'location' => 'required|string',
            'destinationName' => 'required_if:selectedLetterType.code,SP|nullable|string',
            'tembusan' => 'nullable|string',
        ]);

        try {
            $teamData = array_map(fn ($m) => [
                'name' => $m['name'],
                'role' => $m['role'],
                'identifier' => $m['identifier'] ?? '-',
            ], $this->team);

            // Add the requester as ketua
            array_unshift($teamData, [
                'name' => auth()->user()->name,
                'role' => 'Ketua',
                'identifier' => auth()->user()->identity->identity_id ?? '-',
            ]);

            $service->requestManualLetter(auth()->user(), [
                'letterTypeId' => $this->letterTypeId,
                'title' => $this->title,
                'activityType' => $this->activityType,
                'dateString' => $this->dateString,
                'timeString' => $this->timeString,
                'location' => $this->location,
                'destinationName' => $this->destinationName,
                'tembusan' => $this->tembusan,
                'team' => $teamData,
            ]);

            session()->flash('success', 'Surat berhasil diajukan ke Kepala LPPM.');

            $this->redirect(route('letters.history'), navigate: true);
        } catch (\DomainException $e) {
            $this->dispatch('swal', title: 'Gagal', text: $e->getMessage(), icon: 'error');
        } catch (\Exception $e) {
            Log::error('Manual letter request failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('swal', title: 'Gagal', text: 'Terjadi kesalahan. Silakan coba lagi.', icon: 'error');
        }
    }

    public function render()
    {
        return view('livewire.dashboard.dosen.letter-manual-request');
    }
}
