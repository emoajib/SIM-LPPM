<?php

namespace App\Livewire\Dashboard\Dosen;

use App\Livewire\Concerns\HasTeamSearch;
use App\Models\LetterType;
use App\Models\Proposal;
use App\Services\LetterService;
use App\Services\TeamSnapshotBuilder;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Buat Surat dari Proposal', 'pageTitle' => 'Buat Surat dari Proposal', 'pageSubtitle' => 'Data proposal akan diisi otomatis'])]
class LetterProposalLinkedRequest extends Component
{
    use HasTeamSearch;

    public Proposal $proposal;

    public $letterTypeId;

    public $title = '';

    public $activityType = 'Penelitian';

    public $dateString = '';

    public $timeString = '';

    public $location = '';

    public $destinationName = '';

    public $tembusan = '1. Arsip';

    public $selectedLetterType;

    public $letterTypes;

    public function mount(Proposal $proposal): void
    {
        $this->proposal = $proposal;
        $this->letterTypes = LetterType::where('is_active', true)->orderBy('code')->get();

        // Pre-fill from proposal
        $this->title = $proposal->title;
        $this->location = $proposal->location ?? '';
        $this->activityType = str_contains($proposal->detailable_type, 'Research')
            ? 'Penelitian'
            : 'PKM';

        // Pre-fill team from proposal (editable)
        $this->team = TeamSnapshotBuilder::forProposal($proposal);
    }

    public function updatedLetterTypeId(): void
    {
        $this->selectedLetterType = LetterType::find($this->letterTypeId);
    }

    public function submit(): void
    {
        $this->validate([
            'letterTypeId' => 'required|exists:letter_types,id',
            'title' => 'required|string|min:3',
            'activityType' => 'required|string',
            'dateString' => 'required|string',
            'timeString' => 'required|string',
            'location' => 'required|string',
            'destinationName' => 'required_if:selectedLetterType.code,SP|nullable|string',
            'tembusan' => 'nullable|string',
        ]);

        $service = app(LetterService::class);

        if ($service->hasDuplicateLetter($this->proposal, $this->letterTypeId, 'manual')) {
            $this->dispatch('swal', title: 'Gagal', text: 'Anda sudah mengajukan surat jenis ini untuk proposal tersebut.', icon: 'error');

            return;
        }

        $teamData = $this->buildTeamData();
        $requester = auth()->user();
        $teamData = TeamSnapshotBuilder::forProposalLinked($this->proposal, $teamData, $requester);

        $service->requestManualLetter($requester, [
            'letterTypeId' => $this->letterTypeId,
            'title' => $this->title,
            'activityType' => $this->activityType,
            'dateString' => $this->dateString,
            'timeString' => $this->timeString,
            'location' => $this->location,
            'destinationName' => $this->destinationName,
            'tembusan' => $this->tembusan,
            'team' => $teamData,
            'reference_type' => get_class($this->proposal),
            'reference_id' => $this->proposal->id,
        ]);

        session()->flash('success', 'Surat berhasil diajukan ke Kepala LPPM. Data proposal dilampirkan sebagai referensi.');
        $this->redirect(route('dashboard.dosen.surat.dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.dashboard.dosen.letter-proposal-linked-request');
    }
}
