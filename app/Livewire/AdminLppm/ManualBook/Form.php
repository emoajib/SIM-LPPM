<?php

namespace App\Livewire\AdminLppm\ManualBook;

use App\Livewire\Concerns\HasToast;
use App\Models\ManualBook;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use AuthorizesRequests, HasToast, WithFileUploads;

    public ?ManualBook $manualBook = null;

    public $title = '';

    public $description = '';

    public $version_number = '1.0';

    public $status = 'active';

    public $assignedRoles = [];

    public $file;

    public $existingFile = null;

    public $isEdit = false;

    public function mount(?ManualBook $manualBook = null): void
    {
        $this->manualBook = $manualBook;
        $this->isEdit = $manualBook !== null;

        if ($this->isEdit) {
            $this->title = $manualBook->title;
            $this->description = $manualBook->description;
            $this->version_number = $manualBook->version_number;
            $this->status = $manualBook->status;
            $this->assignedRoles = $manualBook->assigned_roles ?? [];

            $media = $manualBook->getFirstMedia('manual_book_file');
            if ($media) {
                $this->existingFile = [
                    'name' => $media->file_name,
                    'size' => $media->size,
                    'url' => route('media.download', $media),
                    'mime_type' => $media->mime_type,
                ];
            }
        }
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

        if ($this->isEdit) {
            $this->manualBook->update($data);
            $manualBook = $this->manualBook;
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

        $this->toastSuccess(
            $this->isEdit
                ? 'Manual book berhasil diperbarui.'
                : 'Manual book berhasil dibuat.'
        );

        $this->redirect(route('admin-lppm.manual-books.admin.index'), navigate: true);
    }

    public function removeFile(): void
    {
        if ($this->manualBook) {
            $this->manualBook->clearMediaCollection('manual_book_file');
        }
        $this->file = null;
        $this->existingFile = null;
        $this->toastSuccess('File berhasil dihapus.');
    }

    public function render()
    {
        return view('livewire.admin-lppm.manual-book.form', [
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
}
