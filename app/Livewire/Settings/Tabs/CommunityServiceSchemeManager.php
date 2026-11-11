<?php

namespace App\Livewire\Settings\Tabs;

use App\Livewire\Concerns\HasToast;
use App\Models\CommunityServiceScheme;
use App\Models\Strata;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

class CommunityServiceSchemeManager extends Component
{
    use HasToast, WithPagination;

    #[Validate('required|min:3|max:255')]
    public string $name = '';

    #[Validate('required|min:3|max:255')]
    public string $strata = '';

    public ?int $editingId = null;

    public string $modalTitle = 'Skema Pengabdian';

    public ?int $deleteItemId = null;

    public string $deleteItemName = '';

    // Eligibility Properties
    public array $allowed_functional_positions = [];

    public ?float $min_sinta_score = null;

    public ?float $min_scopus_score = null;

    public ?int $min_students_involved = null;

    public ?int $max_proposals_as_head = null;

    public ?int $max_proposals_as_member = null;

    public ?int $max_total_proposals_as_head = null;

    public ?int $max_total_proposals_as_member = null;

    public ?int $min_members = null;

    public ?int $max_members = null;

    public bool $require_cross_prodi = false;

    public bool $is_active = true;

    public ?int $min_cross_prodi_members = null;

    public string $pending_report_block_role = 'none';

    public function getFunctionalPositionOptions(): array
    {
        return [
            'Tenaga Pengajar',
            'Asisten Ahli',
            'Lektor',
            'Lektor Kepala',
            'Guru Besar',
        ];
    }

    public function render()
    {
        return view('livewire.settings.tabs.community-service-scheme-manager', [
            'communityServiceSchemes' => CommunityServiceScheme::latest()->paginate(10),
            'strataOptions' => Strata::all(),
            'functionalPositionOptions' => $this->getFunctionalPositionOptions(),
        ]);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->modalTitle = 'Tambah Skema Pengabdian';
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'is_active' => $this->is_active,
            'strata' => $this->strata,
            'eligibility_rules' => [
                'allowed_functional_positions' => $this->allowed_functional_positions,
                'min_sinta_score' => $this->min_sinta_score,
                'min_scopus_score' => $this->min_scopus_score,
                'min_students_involved' => $this->min_students_involved,
                'max_proposals_as_head' => $this->max_proposals_as_head,
                'max_proposals_as_member' => $this->max_proposals_as_member,
                'max_total_proposals_as_head' => $this->max_total_proposals_as_head,
                'max_total_proposals_as_member' => $this->max_total_proposals_as_member,
                'min_members' => $this->min_members,
                'max_members' => $this->max_members,
                'require_cross_prodi' => $this->require_cross_prodi,
                'min_cross_prodi_members' => $this->require_cross_prodi ? $this->min_cross_prodi_members : null,
                'pending_report_block_role' => $this->pending_report_block_role,
            ],
        ];

        if ($this->editingId) {
            CommunityServiceScheme::findOrFail($this->editingId)->update($data);
        } else {
            CommunityServiceScheme::create($data);
        }

        $message = $this->editingId ? 'Skema Pengabdian berhasil diubah' : 'Skema Pengabdian berhasil ditambahkan';

        // close modal
        $this->dispatch('close-modal', modalId: 'modal-community-service-scheme');
        $this->resetForm();

        session()->flash('success', $message);
        $this->toastSuccess($message);
    }

    public function edit(CommunityServiceScheme $communityServiceScheme): void
    {
        $this->editingId = $communityServiceScheme->id;
        $this->name = $communityServiceScheme->name;
        $this->is_active = (bool) $communityServiceScheme->is_active;
        $this->strata = $communityServiceScheme->strata;
        /** @var array<string, mixed> $rules */
        $rules = is_array($communityServiceScheme->eligibility_rules)
            ? $communityServiceScheme->eligibility_rules
            : [];

        $this->allowed_functional_positions = $rules['allowed_functional_positions'] ?? [];
        $this->min_sinta_score = $rules['min_sinta_score'] ?? null;
        $this->min_scopus_score = $rules['min_scopus_score'] ?? null;
        $this->min_students_involved = $rules['min_students_involved'] ?? null;
        $this->max_proposals_as_head = $rules['max_proposals_as_head'] ?? null;
        $this->max_proposals_as_member = $rules['max_proposals_as_member'] ?? null;
        $this->max_total_proposals_as_head = $rules['max_total_proposals_as_head'] ?? null;
        $this->max_total_proposals_as_member = $rules['max_total_proposals_as_member'] ?? null;
        $this->min_members = $rules['min_members'] ?? null;
        $this->max_members = $rules['max_members'] ?? null;
        $this->require_cross_prodi = $rules['require_cross_prodi'] ?? false;
        $this->min_cross_prodi_members = $rules['min_cross_prodi_members'] ?? null;
        $this->pending_report_block_role = $rules['pending_report_block_role'] ?? 'none';

        $this->modalTitle = 'Edit Skema Pengabdian';
        $this->dispatch('open-modal', modalId: 'modal-community-service-scheme');
    }

    public function delete(CommunityServiceScheme $communityServiceScheme): void
    {
        $communityServiceScheme->delete();

        $this->resetForm();
        $message = 'Skema Pengabdian berhasil dihapus';
        session()->flash('success', $message);
        $this->toastSuccess($message);
    }

    public function resetForm(): void
    {
        $this->reset([
            'name',
            'strata',
            'editingId',
            'allowed_functional_positions',
            'min_sinta_score',
            'min_scopus_score',
            'min_students_involved',
            'max_proposals_as_head',
            'max_proposals_as_member',
            'max_total_proposals_as_head',
            'max_total_proposals_as_member',
            'min_members',
            'max_members',
            'require_cross_prodi',
            'min_cross_prodi_members',
            'is_active',
        ]);
        $this->pending_report_block_role = 'none';
        $this->resetValidation();
    }

    public function handleConfirmDeleteAction(): void
    {
        if ($this->deleteItemId) {
            CommunityServiceScheme::findOrFail($this->deleteItemId)->delete();

            $message = 'Skema Pengabdian berhasil dihapus';
            session()->flash('success', $message);
            $this->toastSuccess($message);
            $this->resetConfirmDelete();
        }
    }

    public function resetConfirmDelete(): void
    {
        $this->reset(['deleteItemId', 'deleteItemName']);
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteItemId = $id;
        $this->deleteItemName = CommunityServiceScheme::find($id)->name ?? '';
        $this->dispatch('open-modal', modalId: 'modal-confirm-delete-community-service-scheme');
    }
}
