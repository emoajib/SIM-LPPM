<?php

namespace App\Livewire\Settings\Tabs;

use App\Livewire\Concerns\HasToast;
use App\Models\BudgetCap;
use App\Models\CommunityServiceScheme;
use App\Models\ResearchScheme;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * BudgetCapManager component for managing year-based budget caps.
 *
 * Only accessible by Admin LPPM users. Allows setting maximum budget
 * limits for research and community service proposals per year.
 */
class BudgetCapManager extends Component
{
    use HasToast, WithPagination;

    #[Validate('required|integer|min:2000|max:2100')]
    public string $year = '';

    #[Validate('required|string|in:ganjil,genap')]
    public string $semester = 'ganjil';

    #[Validate('nullable|integer|min:0')]
    public ?string $research_budget_cap = null;

    #[Validate('nullable|integer|min:0')]
    public ?string $community_service_budget_cap = null;

    #[Validate('boolean')]
    public bool $enforce_percentage = true;

    #[Validate('nullable|numeric|min:0|max:100')]
    public ?string $amendment_tolerance_percent = '10';

    public array $research_scheme_caps = [];

    public array $community_service_scheme_caps = [];

    public ?int $editingId = null;

    public string $modalTitle = 'Pengaturan Anggaran';

    public ?int $deleteItemId = null;

    public string $deleteItemYear = '';

    /**
     * Authorization check - only Admin LPPM can access this component
     */
    public function mount(): void
    {
        if (! Auth::user()->activeHasRole('admin lppm')) {
            abort(403, 'Hanya Admin LPPM yang dapat mengakses pengaturan anggaran.');
        }
    }

    public function render()
    {
        return view('livewire.settings.tabs.budget-cap-manager', [
            'budgetCaps' => BudgetCap::orderByDesc('year')->orderBy('semester')->paginate(10),
            'researchSchemes' => ResearchScheme::all(),
            'communityServiceSchemes' => CommunityServiceScheme::all(),
        ]);
    }

    public function copyFromPrevious(): void
    {
        $previous = BudgetCap::orderByDesc('year')->orderByDesc('semester')->first();
        if (! $previous) {
            $this->toastError('Belum ada periode sebelumnya untuk disalin.');

            return;
        }
        $this->year = (string) $previous->year;
        $this->semester = $previous->semester === 'ganjil' ? 'genap' : 'ganjil';
        $this->research_budget_cap = $previous->research_budget_cap ? (string) (int) $previous->research_budget_cap : null;
        $this->community_service_budget_cap = $previous->community_service_budget_cap ? (string) (int) $previous->community_service_budget_cap : null;
        $this->enforce_percentage = $previous->enforce_percentage;
        $this->amendment_tolerance_percent = $previous->amendment_tolerance_percent !== null ? (string) (float) $previous->amendment_tolerance_percent : '10';

        $caps = is_array($previous->scheme_caps) ? $previous->scheme_caps : [];
        $this->research_scheme_caps = [];
        $this->community_service_scheme_caps = [];
        if (isset($caps['research']) && is_array($caps['research'])) {
            foreach ($caps['research'] as $k => $v) {
                $this->research_scheme_caps[$k] = (string) $v;
            }
        }
        if (isset($caps['community_service']) && is_array($caps['community_service'])) {
            foreach ($caps['community_service'] as $k => $v) {
                $this->community_service_scheme_caps[$k] = (string) $v;
            }
        }
        $this->toastSuccess('Data periode sebelumnya disalin. Sesuaikan tahun/semester lalu simpan.');
    }

    public function create(): void
    {
        $this->reset(['year', 'semester', 'research_budget_cap', 'community_service_budget_cap', 'enforce_percentage', 'amendment_tolerance_percent', 'research_scheme_caps', 'community_service_scheme_caps', 'editingId']);
        $this->enforce_percentage = true;
        $this->amendment_tolerance_percent = '10';
        $this->modalTitle = 'Tambah Aturan Anggaran';
    }

    public function save(): void
    {
        $this->validate();

        // Check for duplicate year/semester (except when editing)
        $exists = BudgetCap::where('year', $this->year)
            ->where('semester', $this->semester)
            ->when($this->editingId, function ($query) {
                $query->where('id', '!=', $this->editingId);
            })
            ->exists();

        if ($exists) {
            $semesterLabel = ucfirst($this->semester);
            $this->addError('year', "Pengaturan anggaran untuk tahun $this->year semester $semesterLabel sudah ada.");

            return;
        }

        // Map array string inputs to structured associative array JSON
        $schemeCaps = [
            'research' => [],
            'community_service' => [],
        ];
        foreach ($this->research_scheme_caps as $id => $val) {
            if ($val !== '' && $val !== null) {
                // Remove non-numeric characters potentially bypassed
                $schemeCaps['research'][$id] = (int) preg_replace('/\D/', '', $val);
            }
        }
        foreach ($this->community_service_scheme_caps as $id => $val) {
            if ($val !== '' && $val !== null) {
                $schemeCaps['community_service'][$id] = (int) preg_replace('/\D/', '', $val);
            }
        }

        $data = [
            'year' => (int) $this->year,
            'semester' => $this->semester,
            'research_budget_cap' => $this->research_budget_cap ? (int) $this->research_budget_cap : null,
            'community_service_budget_cap' => $this->community_service_budget_cap ? (int) $this->community_service_budget_cap : null,
            'scheme_caps' => $schemeCaps,
            'enforce_percentage' => $this->enforce_percentage,
            'amendment_tolerance_percent' => $this->amendment_tolerance_percent !== null && $this->amendment_tolerance_percent !== '' ? (float) $this->amendment_tolerance_percent : 10,
        ];

        if ($this->editingId) {
            BudgetCap::findOrFail($this->editingId)->update($data);
        } else {
            BudgetCap::create($data);
        }

        $message = $this->editingId ? 'Pengaturan Anggaran berhasil diubah' : 'Pengaturan Anggaran berhasil ditambahkan';

        // close modal
        $this->dispatch('close-modal', modalId: 'modal-budget-cap');
        $this->reset(['year', 'semester', 'research_budget_cap', 'community_service_budget_cap', 'enforce_percentage', 'amendment_tolerance_percent', 'research_scheme_caps', 'community_service_scheme_caps', 'editingId']);

        session()->flash('success', $message);
        $this->toastSuccess($message);
    }

    public function edit(BudgetCap $budgetCap): void
    {
        $this->editingId = $budgetCap->id;
        $this->year = (string) $budgetCap->year;
        $this->semester = (string) $budgetCap->semester;
        $this->research_budget_cap = $budgetCap->research_budget_cap ? (string) (int) $budgetCap->research_budget_cap : null;
        $this->community_service_budget_cap = $budgetCap->community_service_budget_cap ? (string) (int) $budgetCap->community_service_budget_cap : null;
        $this->enforce_percentage = $budgetCap->enforce_percentage;
        $this->amendment_tolerance_percent = $budgetCap->amendment_tolerance_percent !== null ? (string) (float) $budgetCap->amendment_tolerance_percent : '10';

        /** @var array<string, array<int, mixed>> $caps */
        $caps = is_array($budgetCap->scheme_caps) ? $budgetCap->scheme_caps : [];
        $this->research_scheme_caps = [];
        $this->community_service_scheme_caps = [];

        if (is_array($caps) && isset($caps['research']) && is_array($caps['research'])) {
            foreach ($caps['research'] as $k => $v) {
                $this->research_scheme_caps[$k] = (string) $v;
            }
        }

        if (is_array($caps) && isset($caps['community_service']) && is_array($caps['community_service'])) {
            foreach ($caps['community_service'] as $k => $v) {
                $this->community_service_scheme_caps[$k] = (string) $v;
            }
        }

        $this->modalTitle = 'Edit Pengaturan Anggaran';
        $this->dispatch('open-modal', modalId: 'modal-budget-cap');
    }

    public function delete(BudgetCap $budgetCap): void
    {
        $budgetCap->delete();

        $this->resetForm();
        $message = 'Pengaturan Anggaran berhasil dihapus';
        session()->flash('success', $message);
        $this->toastSuccess($message);
    }

    public function resetForm(): void
    {
        $this->reset(['year', 'semester', 'research_budget_cap', 'community_service_budget_cap', 'enforce_percentage', 'amendment_tolerance_percent', 'research_scheme_caps', 'community_service_scheme_caps', 'editingId']);
    }

    public function handleConfirmDeleteAction(): void
    {
        if ($this->deleteItemId) {
            BudgetCap::findOrFail($this->deleteItemId)->delete();

            $message = 'Pengaturan Anggaran berhasil dihapus';
            session()->flash('success', $message);
            $this->toastSuccess($message);
            $this->resetConfirmDelete();
        }
    }

    public function resetConfirmDelete(): void
    {
        $this->reset(['deleteItemId', 'deleteItemYear']);
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteItemId = $id;
        $item = BudgetCap::find($id);
        if ($item) {
            $semesterLabel = ucfirst($item->semester);
            $this->deleteItemYear = "Tahun $item->year (Semester $semesterLabel)";
        }
        $this->dispatch('open-modal', modalId: 'modal-confirm-delete-budget-cap');
    }
}
