<?php

namespace App\Livewire\Dashboard\Dosen;

use App\Models\Letter;
use App\Models\LetterType;
use App\Models\Proposal;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
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
            'destinationName' => 'required_if:letterTypeId,2|nullable|string', // Assuming ID 2 is SP
        ]);

        $type = LetterType::find($this->letterTypeId);

        $letter = Letter::create([
            'letter_type_id' => $this->letterTypeId,
            'user_id' => Auth::id(),
            'reference_type' => get_class($this->proposal),
            'reference_id' => $this->proposal->id,
            'signature_mode' => Setting::get('surat_signature_mode', 'tte'),
            'status' => 'pending_approval', // 2-Step Workflow: Dosen -> Kepala
            'metadata' => [
                'activity_type' => $this->activityType,
                'title' => $this->proposal->title,
                'date_string' => $this->dateString,
                'time_string' => $this->timeString,
                'location' => $this->location,
                'destination_name' => $this->destinationName,
                'tembusan' => array_map('trim', explode("\n", $this->tembusan)),
                'signer_name' => Setting::get('lppm_head_name', 'Aria Mulyapradana, S.Psi., M.A.'),
                'signer_position' => Setting::get('lppm_head_position', 'Kepala LPPM ITSNU Pekalongan'),
                'signer_nidn' => Setting::get('lppm_head_nidn', '0612118401'),
            ],
            'team_snapshot' => $this->getProposalTeam(),
        ]);

        $this->showModal = false;
        $this->dispatch('swal', title: 'Berhasil', text: 'Permohonan surat berhasil dikirim ke Kepala LPPM.', icon: 'success');
        $this->dispatch('letterRequested');
    }

    private function getProposalTeam(): array
    {
        $team = [];

        // Add Chair
        $team[] = [
            'name' => $this->proposal->submitter->name,
            'role' => 'Ketua',
            'identifier' => $this->proposal->submitter->identity->identity_id ?? '-',
        ];

        // Add Members
        foreach ($this->proposal->teamMembers as $member) {
            /** @var \App\Models\User $member */
            $pivot = $member->pivot;
            if ($pivot && $pivot->getAttribute('status') === 'accepted') {
                $team[] = [
                    'name' => $member->name,
                    'role' => 'Anggota',
                    'identifier' => $member->identity->identity_id ?? '-',
                ];
            }
        }

        // Add Students
        if ($this->proposal->student_members) {
            foreach ($this->proposal->student_members as $student) {
                $team[] = [
                    'name' => $student['name'],
                    'role' => 'Mahasiswa',
                    'identifier' => $student['nim'] ?? '-',
                ];
            }
        }

        return $team;
    }

    public function render()
    {
        return view('livewire.dashboard.dosen.letter-request');
    }
}
