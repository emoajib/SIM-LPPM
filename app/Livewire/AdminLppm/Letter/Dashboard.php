<?php

namespace App\Livewire\AdminLppm\Letter;

use App\Models\Letter;
use App\Models\LetterType;
use App\Services\LetterService;
use App\Services\LetterTypeService;
use Livewire\Component;
use Livewire\WithPagination;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager
class Dashboard extends Component
{
    use WithPagination;

    // Active main tab ('letters' or 'types')
    public string $activeTab = 'letters';

    // Letter Archive/List Properties
    public string $search = '';

    public string $statusFilter = 'pending_approval'; // Default to "Perlu Diproses" like kepala-lppm

    public string $typeFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    // Letter Type CRUD Properties
    public $showModal = false;

    public $editMode = false;

    public $letterTypeId;

    public $code = '';

    public $name = '';

    public $description = '';

    public $category = 'pelaksanaan';

    public $numberingFormat = '{NOMOR}/{CODE}/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}';

    public $templateView = '';

    public $isUploadable = false;

    public $isActive = true;

    public $showDeleteModal = false;

    public $deletingLetterType;

    protected $listeners = ['refreshList' => '$refresh'];

    public function updatedActiveTab(): void
    {
        $this->resetPage('lettersPage');
        $this->resetPage('typesPage');
    }

    public function updatedSearch(): void
    {
        $this->resetPage('lettersPage');
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage('lettersPage');
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage('lettersPage');
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage('lettersPage');
    }

    public function updatedDateTo(): void
    {
        $this->resetPage('lettersPage');
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function openEditModal($id): void
    {
        $type = LetterType::find($id);
        if (! $type) {
            return;
        }

        $this->letterTypeId = $type->id;
        $this->code = $type->code;
        $this->name = $type->name;
        $this->description = $type->description ?? '';
        $this->category = $type->category;
        $this->numberingFormat = $type->numbering_format ?? '{NOMOR}/{CODE}/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}';
        $this->templateView = $type->template_view ?? '';
        $this->isUploadable = $type->is_uploadable;
        $this->isActive = $type->is_active;
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save(LetterTypeService $service): void
    {
        $this->validate([
            'code' => 'required|string|max:10|unique:letter_types,code,'.$this->letterTypeId,
            'name' => 'required|string|max:255',
            'category' => 'required|in:persiapan,etik,pelaksanaan,pelaporan',
            'numberingFormat' => 'required|string',
        ]);

        $data = [
            'code' => strtoupper($this->code),
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'numbering_format' => $this->numberingFormat,
            'template_view' => $this->templateView,
            'is_uploadable' => $this->isUploadable,
            'is_active' => $this->isActive,
        ];

        try {
            if ($this->editMode) {
                $type = LetterType::find($this->letterTypeId);
                $service->update($type, $data);
                $this->dispatch('swal', title: 'Berhasil', text: 'Jenis surat berhasil diperbarui.', icon: 'success');
            } else {
                $service->create($data);
                $this->dispatch('swal', title: 'Berhasil', text: 'Jenis surat berhasil ditambahkan.', icon: 'success');
            }

            $this->showModal = false;
            $this->resetForm();
        } catch (\DomainException $e) {
            $this->dispatch('swal', title: 'Gagal', text: $e->getMessage(), icon: 'error');
        }
    }

    public function confirmDelete($id): void
    {
        $this->deletingLetterType = LetterType::find($id);
        $this->showDeleteModal = true;
    }

    public function delete(LetterTypeService $service): void
    {
        if (! $this->deletingLetterType) {
            return;
        }

        try {
            $service->delete($this->deletingLetterType);
            $this->showDeleteModal = false;
            $this->deletingLetterType = null;
            $this->dispatch('swal', title: 'Berhasil', text: 'Jenis surat berhasil dihapus.', icon: 'success');
        } catch (\DomainException $e) {
            $this->dispatch('swal', title: 'Gagal', text: $e->getMessage(), icon: 'error');
        }
    }

    private function resetForm(): void
    {
        $this->letterTypeId = null;
        $this->code = '';
        $this->name = '';
        $this->description = '';
        $this->category = 'pelaksanaan';
        $this->numberingFormat = '{NOMOR}/{CODE}/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}';
        $this->templateView = '';
        $this->isUploadable = false;
        $this->isActive = true;
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'pending_approval';
        $this->typeFilter = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage('lettersPage');
    }

    public function render()
    {
        $stats = (new LetterService)->getLetterStats();

        $letters = Letter::with(['letterType', 'user'])
            ->when($this->statusFilter, function ($query) {
                if ($this->statusFilter === 'all') {
                    return;
                }
                $query->where('status', $this->statusFilter);
            })
            ->when($this->typeFilter, fn ($q) => $q->where('letter_type_id', $this->typeFilter))
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('letter_number', 'like', '%'.$this->search.'%')
                        ->orWhereHas('user', function ($uq) {
                            $uq->where('name', 'like', '%'.$this->search.'%');
                        })
                        ->orWhereHas('letterType', function ($tq) {
                            $tq->where('name', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->latest()
            ->paginate(10, ['*'], 'lettersPage');

        $letterTypesList = LetterType::withCount('letters')
            ->orderBy('code')
            ->paginate(10, ['*'], 'typesPage');

        $letterTypes = LetterType::orderBy('code')->get();

        return view('livewire.admin-lppm.letter.dashboard', [
            'stats' => $stats,
            'letters' => $letters,
            'letterTypesList' => $letterTypesList,
            'letterTypes' => $letterTypes,
        ]);
    }
}
