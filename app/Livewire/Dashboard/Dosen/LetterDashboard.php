<?php

namespace App\Livewire\Dashboard\Dosen;

use App\Models\Letter;
use App\Models\LetterType;
use App\Models\Proposal;
use App\Models\User;
use App\Services\LetterService;
use App\Services\TeamSnapshotBuilder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager
#[Layout('components.layouts.app', ['title' => 'Dashboard Persuratan'])]
class LetterDashboard extends Component
{
    use WithPagination;

    // Tab & Search
    public $statusFilter = 'pending_approval';

    public $search = '';

    // Form visibility
    public $showForm = false;

    public function setFilter(string $status): void
    {
        $this->statusFilter = $status;
        $this->resetPage();
    }

    public function resetFilter(): void
    {
        $this->statusFilter = '';
        $this->search = '';
        $this->resetPage();
    }

    public function closeResubmitModal(): void
    {
        $this->showResubmitModal = false;
    }

    // Form fields
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

    public $letterTypes = [];

    public $selectedLetterType;

    // Proposal-linked fields
    public $proposals = [];

    public $selectedProposalId = '';

    public $referenceType = null;

    public $referenceId = null;

    // Resubmit modal
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
            Log::error('Search dosen failed in dashboard', [
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

    public function showSectionForm(): void
    {
        $this->showForm = true;
    }

    public function showSectionTable(): void
    {
        $this->showForm = false;
        $this->resetForm();
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

    public function submitLetter(LetterService $service): void
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
            $userId = auth()->id();
            $userName = auth()->user()->name;
            $userIdentifier = auth()->user()->identity->identity_id ?? '-';

            $teamData = array_values(array_filter($this->team, fn ($m) => ($m['name'] ?? '') !== $userName));

            array_unshift($teamData, [
                'name' => $userName,
                'role' => 'Ketua',
                'identifier' => $userIdentifier,
            ]);

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

            $this->resetForm();
            $this->dispatch('swal', title: 'Berhasil', text: 'Surat berhasil diajukan ke Kepala LPPM.', icon: 'success');
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

    public function render()
    {
        $service = new LetterService;
        $stats = $service->getLetterStatsForUser(auth()->id());

        $letters = Letter::with(['letterType', 'user.identity'])
            ->where('user_id', auth()->id())
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('letter_number', 'like', '%'.$this->search.'%')
                        ->orWhereHas('letterType', fn ($tq) => $tq->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->latest()
            ->paginate(10);

        return view('livewire.dashboard.dosen.letter-dashboard', [
            'stats' => $stats,
            'letters' => $letters,
        ]);
    }

    private function resetForm(): void
    {
        $this->showForm = false;
        $this->letterTypeId = null;
        $this->title = '';
        $this->activityType = 'Penelitian';
        $this->date = '';
        $this->timeStart = '';
        $this->timeEnd = '';
        $this->location = '';
        $this->destinationName = '';
        $this->tembusan = '1. Arsip';
        $this->team = [];
        $this->selectedLetterType = null;
        $this->selectedProposalId = '';
        $this->referenceType = null;
        $this->referenceId = null;
    }
}
