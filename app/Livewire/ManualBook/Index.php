<?php

namespace App\Livewire\ManualBook;

use App\Models\ManualBook;
use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        $role = active_role();

        $manualBooks = ManualBook::query()
            ->visibleForRole($role)
            ->with('media')
            ->orderBy('title')
            ->get()
            ->map(function ($book) {
                $media = $book->getFirstMedia('manual_book_file');

                return [
                    'id' => $book->id,
                    'title' => $book->title,
                    'description' => $book->description,
                    'version_number' => $book->version_number,
                    'downloadUrl' => $media ? route('media.download', $media) : null,
                    'hasFile' => $media !== null,
                    'fileSize' => $media?->size,
                    'mimeType' => $media?->mime_type,
                ];
            });

        return view('livewire.manual-book.index', [
            'manualBooks' => $manualBooks,
            'role' => $role,
        ]);
    }
}
