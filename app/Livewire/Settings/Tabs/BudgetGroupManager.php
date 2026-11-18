<?php

namespace App\Livewire\Settings\Tabs;

use App\Livewire\Concerns\HasToast;
use App\Models\BudgetGroup;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

class BudgetGroupManager extends Component
{
    use HasToast, WithPagination;

    #[Validate('required|min:2|max:10')]
    public string $code = '';

    #[Validate('required|min:3|max:255')]
    public string $name = '';

    public ?string $description = null;

    #[Validate('nullable|integer|min:0|max:100')]
    public ?string $percentage = null;

    #[Validate('nullable|in:research,community_service')]
    public ?string $proposal_type = null;

    #[Validate('nullable|in:min,max')]
    public ?string $percentage_type = null;

    #[Validate('boolean')]
    public bool $is_active = true;

    public ?int $editingId = null;

    public string $modalTitle = 'Kelompok Anggaran';

    public ?int $deleteItemId = null;

    public string $deleteItemName = '';

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
        return view('livewire.settings.tabs.budget-group-manager', [
            'budgetGroups' => BudgetGroup::with(['components'])->latest()->paginate(10),
        ]);
    }

    public function create(): void
    {
        $this->reset(['code', 'name', 'description', 'percentage', 'proposal_type', 'percentage_type', 'is_active', 'editingId']);
        $this->is_active = true;
        $this->modalTitle = 'Tambah Kelompok Anggaran';
    }

    public function save(): void
    {
        $this->validate();

        // Check if total percentages exceed 100%
        $totalPercentage = $this->calculateTotalPercentage();

        if ($totalPercentage > 100) {
            $this->addError('percentage', 'Total persentase semua kelompok anggaran tidak boleh melebihi 100%. Saat ini: '.number_format($totalPercentage, 2).'%');

            return;
        }

        $data = [
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'percentage' => $this->percentage ? (int) $this->percentage : null,
            'proposal_type' => $this->proposal_type ?: null,
            'percentage_type' => $this->percentage_type ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            BudgetGroup::findOrFail($this->editingId)->update($data);
        } else {
            BudgetGroup::create($data);
        }

        $message = $this->editingId ? 'Kelompok Anggaran berhasil diubah' : 'Kelompok Anggaran berhasil ditambahkan';

        // close modal
        $this->dispatch('close-modal', modalId: 'modal-budget-group');
        $this->reset(['code', 'name', 'description', 'percentage', 'proposal_type', 'percentage_type', 'is_active', 'editingId']);

        session()->flash('success', $message);
        $this->toastSuccess($message);
    }

    public function edit(BudgetGroup $budgetGroup): void
    {
        $this->editingId = $budgetGroup->id;
        $this->code = $budgetGroup->code;
        $this->name = $budgetGroup->name;
        $this->description = $budgetGroup->description;
        $this->percentage = $budgetGroup->percentage ? (string) (int) $budgetGroup->percentage : null;
        $this->proposal_type = $budgetGroup->proposal_type;
        $this->percentage_type = $budgetGroup->percentage_type;
        $this->is_active = $budgetGroup->is_active;
        $this->modalTitle = 'Edit Kelompok Anggaran';
        $this->dispatch('open-modal', modalId: 'modal-budget-group');
    }

    public function delete(BudgetGroup $budgetGroup): void
    {
        $budgetGroup->delete();

        $this->resetForm();
        $message = 'Kelompok Anggaran berhasil dihapus';
        session()->flash('success', $message);
        $this->toastSuccess($message);
    }

    public function resetForm(): void
    {
        $this->reset(['code', 'name', 'description', 'percentage', 'proposal_type', 'percentage_type', 'is_active', 'editingId']);
    }

    public function handleConfirmDeleteAction(): void
    {
        if ($this->deleteItemId) {
            BudgetGroup::findOrFail($this->deleteItemId)->delete();

            $message = 'Kelompok Anggaran berhasil dihapus';
            session()->flash('success', $message);
            $this->toastSuccess($message);
            $this->resetConfirmDelete();
        }
    }

    public function resetConfirmDelete(): void
    {
        $this->reset(['deleteItemId', 'deleteItemName']);
    }

    /**
     * Calculate total percentage across all budget groups.
     * Excludes the currently editing group to allow percentage updates.
     */
    private function calculateTotalPercentage(): float
    {
        $total = BudgetGroup::when($this->editingId, function ($query) {
            $query->where('id', '!=', $this->editingId);
        })
            ->whereNotNull('percentage')
            ->sum('percentage');

        // Add current percentage being set
        if ($this->percentage) {
            $total += (float) $this->percentage;
        }

        return (float) $total;
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteItemId = $id;
        $this->deleteItemName = BudgetGroup::find($id)->name ?? '';
        $this->dispatch('open-modal', modalId: 'modal-confirm-delete-budget-group');
    }
}
