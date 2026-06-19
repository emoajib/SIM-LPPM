<?php

namespace App\Livewire\Forms;

use App\Enums\ProposalUserStatus;
use App\Models\Identity;
use App\Models\Institution;
use App\Models\StudyProgram;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TeamMembersForm extends Component
{
    public array $members = [];

    public string $memberLabel = 'Anggota Peneliti';

    public string $modalTitle = 'Tambah Anggota Peneliti';

    public string $member_nidn = '';

    public string $member_tugas = '';

    public bool $memberFound = false;

    public ?array $foundMember = null;

    public bool $isManual = false;

    public ?int $editingIndex = null;

    public string $editingTugas = '';

    public string $member_name = '';

    public string $member_email = '';

    public string $member_type = 'dosen'; // dosen, mahasiswa

    public string $member_institution = '';

    public string $member_study_program = '';

    /**
     * Mount the component with optional configuration
     */
    public function mount(?array $members = null, ?string $modalTitle = null, ?string $memberLabel = null): void
    {
        if ($members !== null) {
            $this->members = $members;
        }

        if ($modalTitle !== null) {
            $this->modalTitle = $modalTitle;
        }

        if ($memberLabel !== null) {
            $this->memberLabel = $memberLabel;
        }
    }

    public function openAddModal(): void
    {
        $this->resetMemberForm();
    }

    public function openDeleteModal(int $index): void
    {
        $this->dispatch('open-modal', modalId: 'modal-confirm-delete-'.$index);
    }

    /**
     * Check if member identity exists in system
     */
    public function checkMember(): void
    {
        $this->validate([
            'member_nidn' => 'required|string|max:255',
        ]);

        // Search for user by identity_id (NIDN/NIP)
        $identity = Identity::where('identity_id', $this->member_nidn)
            ->with('user', 'institution', 'studyProgram')
            ->first();

        // if it self show error
        if ($identity && request()->user() && $identity->user_id === request()->user()->getKey()) {
            $this->memberFound = false;
            $this->foundMember = null;
            $this->addError('member_nidn', 'Anda tidak dapat menambahkan diri sendiri sebagai anggota');

            return;
        }

        if ($identity) {
            $this->memberFound = true;
            $this->foundMember = [
                'name' => $identity->user->name,
                'email' => $identity->user->email,
                'nidn' => $identity->identity_id,
                'institution' => $identity->institution?->name,
                'study_program' => $identity->studyProgram?->name,
                'identity_type' => $identity->type,
                'sinta_id' => $identity->sinta_id,
            ];
        } else {
            $this->memberFound = false;
            $this->foundMember = null;
            $this->addError('member_nidn', 'NIDN/NIP tidak ditemukan dalam sistem');
        }
    }

    /**
     * Add member to the list
     */
    public function addMember(): void
    {
        $validationRules = [
            'member_nidn' => 'required|string|max:255',
            'member_tugas' => 'required|string|max:500',
        ];

        if ($this->isManual) {
            $validationRules['member_name'] = 'required|string|max:255';
            $validationRules['member_email'] = 'required|email|max:255';
            $validationRules['member_type'] = 'required|in:dosen,mahasiswa';
            $validationRules['member_institution'] = 'required|string|max:255';
            $validationRules['member_study_program'] = 'required|string|max:255';
        }

        $this->validate($validationRules);

        if (! $this->memberFound || (! $this->isManual && ! $this->foundMember)) {
            $this->addError('member_nidn', 'Silakan cek anggota terlebih dahulu');

            return;
        }

        // Check if member already added
        $alreadyAdded = collect($this->members)->contains(function ($member) {
            return $member['nidn'] === $this->member_nidn;
        });

        if ($alreadyAdded) {
            $this->addError('member_nidn', 'Anggota ini sudah ditambahkan');

            return;
        }

        if ($this->isManual) {
            $this->members[] = [
                'name' => $this->member_name,
                'nidn' => $this->member_nidn,
                'tugas' => $this->member_tugas,
                'status' => ProposalUserStatus::ACCEPTED->value, // Manual entry is implicitly accepted
                'role' => $this->member_type,
                'email' => $this->member_email,
                'institution' => $this->member_institution,
                'study_program' => $this->member_study_program,
                'is_manual' => true,
            ];
        } else {
            $this->members[] = [
                'name' => $this->foundMember['name'],
                'nidn' => $this->member_nidn,
                'tugas' => $this->member_tugas,
                'status' => 'pending',
                'sinta_id' => $this->foundMember['sinta_id'] ?? null,
                'role' => $this->foundMember['identity_type'] ?? 'dosen',
                'study_program' => $this->foundMember['study_program'] ?? '',
                'institution' => $this->foundMember['institution'] ?? '',
            ];
        }

        $this->resetMemberForm();

        $this->dispatch('members-updated', members: $this->members);

        // Dispatch event to close modal
        $this->dispatch('close-modal', modalId: 'modal-add-member');
    }

    /**
     * Remove member from the list
     */
    public function removeMember(int $index): void
    {
        unset($this->members[$index]);
        $this->members = array_values($this->members);

        // Dispatch to parent component
        $this->dispatch('members-updated', members: $this->members);
    }

    public function startEditingMember(int $index): void
    {
        $this->editingIndex = $index;
        $this->editingTugas = $this->members[$index]['tugas'] ?? '';
    }

    public function saveEditingMember(): void
    {
        $this->validate([
            'editingTugas' => 'required|string|max:500',
        ]);

        if ($this->editingIndex !== null && isset($this->members[$this->editingIndex])) {
            $this->members[$this->editingIndex]['tugas'] = $this->editingTugas;
            $this->dispatch('members-updated', members: $this->members);
        }

        $this->cancelEditingMember();
    }

    public function cancelEditingMember(): void
    {
        $this->editingIndex = null;
        $this->editingTugas = '';
        $this->resetErrorBag('editingTugas');
    }

    /**
     * Reset member form
     */
    public function resetMemberForm(): void
    {
        $this->member_nidn = '';
        $this->member_tugas = '';
        $this->member_name = '';
        $this->member_email = '';
        $this->member_type = 'dosen';
        $institution = Institution::where('name', 'like', '%Pekalongan%')->first();
        $this->member_institution = $institution !== null ? $institution->name : 'ITSNU Pekalongan';
        $this->member_study_program = '';
        $this->memberFound = false;
        $this->isManual = false;
        $this->foundMember = null;
        $this->resetErrorBag();
    }

    public function toggleManual(): void
    {
        $this->isManual = ! $this->isManual;
        if ($this->isManual) {
            $this->member_type = 'mahasiswa';
            $this->memberFound = true; // Bypass check
        } else {
            $this->member_type = 'dosen';
            $this->memberFound = false;
        }
    }

    #[Computed]
    public function studyPrograms()
    {
        return StudyProgram::orderBy('name')->get();
    }

    #[Computed]
    public function membersList(): array
    {
        return $this->members;
    }

    public function render(): View
    {
        return view('livewire.forms.team-members-form');
    }
}
