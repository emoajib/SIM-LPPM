<?php

namespace App\Livewire\Settings\Tabs;

use App\Livewire\Concerns\HasToast;
use App\Models\ResearchScheme;
use App\Models\Strata;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

class ResearchSchemeManager extends Component
{
    use HasToast, WithPagination;

    #[Validate('required|min:3|max:255')]
    public string $name = '';

    #[Validate('required|min:3|max:255')]
    public string $strata = '';

    #[Validate('nullable|integer|min:1|max:9')]
    public ?int $min_tkt = null;

    #[Validate('nullable|integer|min:1|max:9|gte:min_tkt')]
    public ?int $max_tkt = null;

    public ?int $editingId = null;

    public string $modalTitle = 'Skema Penelitian';

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
        return view('livewire.settings.tabs.research-scheme-manager', [
            'researchSchemes' => ResearchScheme::latest()->paginate(10),
            'strataOptions' => Strata::all(),
            'functionalPositionOptions' => $this->getFunctionalPositionOptions(),
        ]);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->modalTitle = 'Tambah Skema Penelitian';
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'strata' => $this->strata,
            'min_tkt' => $this->min_tkt,
            'max_tkt' => $this->max_tkt,
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
            ResearchScheme::findOrFail($this->editingId)->update($data);
        } else {
            ResearchScheme::create($data);
        }

        $message = $this->editingId ? 'Skema Penelitian berhasil diubah' : 'Skema Penelitian berhasil ditambahkan';

        // close modal
        $this->dispatch('close-modal', modalId: 'modal-research-scheme');
        $this->resetForm();

        session()->flash('success', $message);
        $this->toastSuccess($message);
    }

    public function edit(ResearchScheme $researchScheme): void
    {
        $this->editingId = $researchScheme->id;
        $this->name = $researchScheme->name;
        $this->strata = $researchScheme->strata;
        $this->min_tkt = $researchScheme->min_tkt;
        $this->max_tkt = $researchScheme->max_tkt;
        $rules = $researchScheme->eligibility_rules ?? [];

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

        $this->modalTitle = 'Edit Skema Penelitian';
        $this->dispatch('open-modal', modalId: 'modal-research-scheme');
    }

    public function delete(ResearchScheme $researchScheme): void
    {
        $researchScheme->delete();

        $this->resetForm();
        $message = 'Skema Penelitian berhasil dihapus';
        session()->flash('success', $message);
        $this->toastSuccess($message);
    }

    public function resetForm(): void
    {
        $this->reset([
            'name',
            'strata',
            'min_tkt',
            'max_tkt',
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
        ]);
        $this->pending_report_block_role = 'none';
        $this->resetValidation();
    }

    public function handleConfirmDeleteAction(): void
    {
        if ($this->deleteItemId) {
            ResearchScheme::findOrFail($this->deleteItemId)->delete();

            $message = 'Skema Penelitian berhasil dihapus';
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
        $this->deleteItemName = ResearchScheme::find($id)->name ?? '';
        $this->dispatch('open-modal', modalId: 'modal-confirm-delete-research-scheme');
    }
}
