<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\HasToast;
use App\Models\ManualBook;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ManualBookSetting extends Component
{
    use HasToast, WithFileUploads, WithPagination;

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasRole('admin lppm') || Auth::user()?->hasRole('superadmin'), 403);
    }

    public $title = '';

    public $description = '';

    public $version_number = '1.0';

    public $status = 'active';

    public $assignedRoles = [];

    public $file;

    public $existingFile = null;

    public $editingId = null;

    public $modalTitle = 'Tambah Manual Book';

    public $deleteItemId = null;

    public $deleteItemName = '';

    public function render()
    {
        return view('livewire.settings.manual-book-setting', [
            'manualBooks' => ManualBook::with(['creator', 'media'])
                ->latest()
                ->paginate(10),
            'allRoles' => [
                'superadmin',
                'admin lppm',
                'kepala lppm',
                'dekan',
                'kaprodi',
                'dosen',
                'reviewer',
                'rektor',
            ],
        ]);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->modalTitle = 'Tambah Manual Book';
        $this->dispatch('open-modal', modalId: 'modal-manual-book');
    }

    public function edit(ManualBook $manualBook): void
    {
        $this->editingId = $manualBook->id;
        $this->title = $manualBook->title;
        $this->description = $manualBook->description;
        $this->version_number = $manualBook->version_number;
        $this->status = $manualBook->status;
        $this->assignedRoles = $manualBook->assigned_roles ?? [];
        $this->modalTitle = 'Edit Manual Book';

        $media = $manualBook->getFirstMedia('manual_book_file');
        if ($media) {
            $this->existingFile = [
                'name' => $media->file_name,
                'size' => $media->size,
                'url' => route('media.download', $media),
                'mime_type' => $media->mime_type,
            ];
        } else {
            $this->existingFile = null;
        }

        $this->dispatch('open-modal', modalId: 'modal-manual-book');
    }

    public function save(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'version_number' => 'required|string|max:20',
            'status' => 'required|in:active,inactive',
            'assignedRoles' => 'required|array|min:1',
            'assignedRoles.*' => 'string',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,gif|max:10240',
        ], [
            'file.mimes' => 'File harus berupa PDF atau gambar (JPG, PNG, WebP, GIF).',
            'file.max' => 'Ukuran file maksimum 10 MB.',
            'assignedRoles.required' => 'Pilih minimal satu role.',
        ]);

        $data = [
            'title' => $this->title,
            'description' => $this->description,
            'version_number' => $this->version_number,
            'status' => $this->status,
            'assigned_roles' => $this->assignedRoles,
        ];

        if ($this->editingId) {
            $manualBook = ManualBook::findOrFail($this->editingId);
            $manualBook->update($data);
        } else {
            $data['id'] = Str::uuid();
            $data['created_by'] = Auth::id();
            $manualBook = ManualBook::create($data);
        }

        if ($this->file) {
            $manualBook->clearMediaCollection('manual_book_file');
            $manualBook
                ->addMedia($this->file->getRealPath())
                ->usingName($this->file->getClientOriginalName())
                ->usingFileName($this->file->hashName())
                ->toMediaCollection('manual_book_file');
        }

        $message = $this->editingId
            ? 'Manual book berhasil diperbarui.'
            : 'Manual book berhasil dibuat.';

        $this->dispatch('close-modal', modalId: 'modal-manual-book');
        $this->resetForm();
        $this->toastSuccess($message);
    }

    public function toggleStatus(ManualBook $manualBook): void
    {
        $manualBook->update([
            'status' => $manualBook->status === 'active' ? 'inactive' : 'active',
        ]);
        $this->toastSuccess('Status manual book berhasil diubah.');
    }

    public function removeFile(): void
    {
        if ($this->editingId) {
            $manualBook = ManualBook::find($this->editingId);
            if ($manualBook) {
                $manualBook->clearMediaCollection('manual_book_file');
            }
        }
        $this->file = null;
        $this->existingFile = null;
        $this->toastSuccess('File berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset([
            'title', 'description', 'version_number', 'status',
            'assignedRoles', 'file', 'existingFile', 'editingId',
        ]);
        $this->version_number = '1.0';
        $this->status = 'active';
    }

    public function confirmDelete(string $id): void
    {
        $book = ManualBook::find($id);
        $this->deleteItemId = $id;
        $this->deleteItemName = $book !== null ? $book->title : '';
        $this->dispatch('open-modal', modalId: 'modal-confirm-delete-manual-book');
    }

    public function handleConfirmDeleteAction(): void
    {
        if ($this->deleteItemId) {
            ManualBook::findOrFail($this->deleteItemId)->delete();
            $this->resetConfirmDelete();
            $this->toastSuccess('Manual book berhasil dihapus.');
        }
    }

    public function resetConfirmDelete(): void
    {
        $this->reset(['deleteItemId', 'deleteItemName']);
    }
}
