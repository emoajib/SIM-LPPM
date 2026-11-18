<?php

namespace App\Livewire\Settings\Tabs;

use App\Livewire\Concerns\HasToast;
use App\Models\BudgetComponent;
use App\Models\BudgetGroup;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

class BudgetComponentManager extends Component
{
    use HasToast, WithPagination;

    #[Validate('required|exists:budget_groups,id')]
    public ?int $budgetGroupId = null;

    #[Validate('required|min:2|max:10')]
    public string $code = '';

    #[Validate('required|min:3|max:255')]
    public string $name = '';

    #[Validate('required|min:2|max:20')]
    public string $unit = '';

    public ?string $description = null;

    public ?int $editingId = null;

    public string $modalTitle = 'Komponen Anggaran';

    public ?int $deleteItemId = null;

    public string $deleteItemName = '';

    public string $search = '';

    public ?int $filterGroupId = null;

    public ?string $filterProposalType = null;

    /**
     * Authorization check - budget master data is Admin LPPM only.
     *
     * Parity with BudgetCapManager::mount(); superadmin inherits via
     * User::activeHasRole(). See BC-02 (active role) and BC-08 (workflow dead end).
     */
    public function mount(): void
    {
        if (! Auth::user()?->activeHasRole('admin lppm')) {
            abort(403, 'Hanya Admin LPPM yang dapat mengakses pengaturan anggaran.');
        }
    }

    public function render()
    {
        $query = BudgetComponent::with(['budgetGroup']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%")
                    ->orWhere('unit', 'like', "%{$this->search}%");
            });
        }

        if ($this->filterGroupId) {
            $query->where('budget_group_id', $this->filterGroupId);
        }

        if ($this->filterProposalType) {
            $query->whereHas('budgetGroup', function ($q) {
                $q->where('proposal_type', $this->filterProposalType);
            });
        }

        $proposalTypes = BudgetGroup::whereNotNull('proposal_type')
            ->distinct()
            ->pluck('proposal_type')
            ->toArray();

        return view('livewire.settings.tabs.budget-component-manager', [
            'budgetComponents' => $query->latest()->paginate(10),
            'budgetGroups' => BudgetGroup::all(),
            'proposalTypes' => $proposalTypes,
        ]);
    }

    public function create(): void
    {
        $this->reset(['budgetGroupId', 'code', 'name', 'unit', 'description', 'editingId']);
        $this->modalTitle = 'Tambah Komponen Anggaran';
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'budget_group_id' => $this->budgetGroupId,
            'code' => $this->code,
            'name' => $this->name,
            'unit' => $this->unit,
            'description' => $this->description,
        ];

        if ($this->editingId) {
            BudgetComponent::findOrFail($this->editingId)->update($data);
        } else {
            BudgetComponent::create($data);
        }

        $message = $this->editingId ? 'Komponen Anggaran berhasil diubah' : 'Komponen Anggaran berhasil ditambahkan';

        // close modal
        $this->dispatch('close-modal', modalId: 'modal-budget-component');
        $this->reset(['budgetGroupId', 'code', 'name', 'unit', 'description', 'editingId']);

        session()->flash('success', $message);
        $this->toastSuccess($message);
    }

    public function edit(BudgetComponent $budgetComponent): void
    {
        $this->editingId = $budgetComponent->id;
        $this->budgetGroupId = $budgetComponent->budget_group_id;
        $this->code = $budgetComponent->code;
        $this->name = $budgetComponent->name;
        $this->unit = $budgetComponent->unit;
        $this->description = $budgetComponent->description;
        $this->modalTitle = 'Edit Komponen Anggaran';
        $this->dispatch('open-modal', modalId: 'modal-budget-component');
    }

    public function delete(BudgetComponent $budgetComponent): void
    {
        $budgetComponent->delete();

        $this->resetForm();
        $message = 'Komponen Anggaran berhasil dihapus';
        session()->flash('success', $message);
        $this->toastSuccess($message);
    }

    public function resetForm(): void
    {
        $this->reset(['budgetGroupId', 'code', 'name', 'unit', 'description', 'editingId']);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterGroupId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterProposalType(): void
    {
        $this->resetPage();
    }

    public function updatedBudgetGroupId($value): void
    {
        if (! $value || $this->editingId) {
            return;
        }

        $group = BudgetGroup::find($value);
        if (! $group) {
            return;
        }

        $prefix = $group->code;
        $prefixLen = strlen($prefix);

        $lastCode = BudgetComponent::where('budget_group_id', $value)
            ->where('code', 'like', $prefix.'%')
            ->orderByRaw('LENGTH(code) DESC')
            ->orderByRaw('code DESC')
            ->value('code');

        if ($lastCode) {
            $number = (int) substr($lastCode, $prefixLen);
            $this->code = $prefix.($number + 1);
        } else {
            $this->code = $prefix.'1';
        }
    }

    public function handleConfirmDeleteAction(): void
    {
        if ($this->deleteItemId) {
            BudgetComponent::findOrFail($this->deleteItemId)->delete();

            $message = 'Komponen Anggaran berhasil dihapus';
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
        $this->deleteItemName = BudgetComponent::find($id)->name ?? '';
        $this->dispatch('open-modal', modalId: 'modal-confirm-delete-budget-component');
    }
}
