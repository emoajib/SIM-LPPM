<?php

namespace App\Livewire\Dashboard\Dosen;

use App\Models\LetterType;
use App\Models\Proposal;
use App\Models\User;
use App\Services\LetterService;
use App\Services\TeamSnapshotBuilder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Buat Surat', 'pageTitle' => 'Buat Surat Baru', 'pageSubtitle' => 'Ajukan surat tanpa proposal'])]
class LetterManualRequest extends Component
{
    public $letterTypeId;

    public $title = '';

    public $activityType = 'Penelitian';

    public $date = '';

    public $timeStart = '';

    public $timeEnd = '';

    public $location = '';

    public $destinationName = '';

    public $tembusan = '1. Arsip';

    public $team = [];

    public $searchQuery = '';

    public $searchResults = [];

    public $letterTypes;

    public $selectedLetterType;

    // Proposal-linked fields
    public $proposals = [];

    public $selectedProposalId = '';

    public $referenceType = null;

    public $referenceId = null;

    public function mount(): void
    {
        $this->letterTypes = LetterType::where('is_active', true)->orderBy('code')->get();

        $this->proposals = Proposal::where('submitter_id', auth()->id())
            ->whereIn('status', ['approved', 'completed'])
            ->with('detailable')
            ->latest()
            ->get();
    }

    public function updatedLetterTypeId(): void
    {
        $this->selectedLetterType = LetterType::find($this->letterTypeId);
    }

    public function updatedSearchQuery(): void
    {
        try {
            $query = (string) $this->searchQuery;
            if (strlen($query) < 2) {
                $this->searchResults = [];

                return;
            }

            $service = new LetterService;
            $this->searchResults = $service->searchDosen($query)->toArray();
        } catch (\Exception $e) {
            Log::error('Search dosen failed in manual request', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            $this->searchResults = [];
        }
    }

    public function updatedSelectedProposalId(): void
    {
        if ($this->selectedProposalId) {
            $proposal = Proposal::with(['detailable', 'teamMembers', 'submitter.identity'])
                ->find($this->selectedProposalId);

            if ($proposal) {
                $this->title = $proposal->title;
                $this->activityType = str_contains($proposal->detailable_type, 'Research') ? 'Penelitian' : 'PKM';
                $this->location = $proposal->location ?? '';
                $this->team = TeamSnapshotBuilder::forProposal($proposal);
                $this->referenceType = get_class($proposal);
                $this->referenceId = $proposal->id;
            }
        } else {
            $this->title = '';
            $this->activityType = 'Penelitian';
            $this->location = '';
            $this->team = [];
            $this->referenceType = null;
            $this->referenceId = null;
        }
    }

    public function addTeamMember(string $dosenId): void
    {
        $dosen = User::whereHas('roles', fn ($q) => $q->where('name', 'dosen'))
            ->where('id', $dosenId)
            ->with('identity')
            ->first();

        if (! $dosen) {
            $this->dispatch('swal', title: 'Gagal', text: 'Dosen tidak ditemukan.', icon: 'error');

            return;
        }

        foreach ($this->team as $member) {
            if ($member['id'] === $dosenId) {
                return;
            }
        }

        $this->team[] = [
            'id' => $dosen->id,
            'name' => $dosen->name,
            'role' => 'Anggota',
            'identifier' => $dosen->identity->identity_id ?? '-',
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
            'date' => 'required|date',
            'timeStart' => 'required',
            'timeEnd' => 'required',
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

            // Format date and time for PDF
            $dateObj = Carbon::parse($this->date);
            $dateString = $dateObj->translatedFormat('l, d F Y');
            $timeString = $this->timeStart.' - '.$this->timeEnd.' WIB';

            $service->requestManualLetter(auth()->user(), [
                'letterTypeId' => $this->letterTypeId,
                'title' => $this->title,
                'activityType' => $this->activityType,
                'dateString' => $dateString,
                'timeString' => $timeString,
                'location' => $this->location,
                'destinationName' => $this->destinationName,
                'tembusan' => $this->tembusan,
                'team' => $teamData,
                'reference_type' => $this->referenceType,
                'reference_id' => $this->referenceId,
            ]);

            session()->flash('success', 'Surat berhasil diajukan ke Kepala LPPM.');

            $this->redirect(route('dashboard.dosen.surat.dashboard'), navigate: true);
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
