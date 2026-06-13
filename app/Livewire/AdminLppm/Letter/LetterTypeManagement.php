<?php

namespace App\Livewire\AdminLppm\Letter;

use App\Models\LetterType;
use App\Services\LetterTypeService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Kelola Jenis Surat', 'pageTitle' => 'Kelola Jenis Surat', 'pageSubtitle' => 'CRUD jenis surat dan template'])]
class LetterTypeManagement extends Component
{
    use WithPagination;

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

    public function render()
    {
        return view('livewire.admin-lppm.letter.letter-type-management', [
            'letterTypes' => LetterType::withCount('letters')->orderBy('code')->paginate(10),
        ]);
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
}
