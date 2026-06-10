<?php

namespace App\Http\Controllers\AdminLppm;

use App\Http\Controllers\Controller;
use App\Models\ManualBook;
use Illuminate\Http\Request;

class ManualBookUploadController extends Controller
{
    public function upload(Request $request, ManualBook $manualBook)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240',
        ], [
            'file.required' => 'Silakan pilih file sebelum mengunggah.',
            'file.mimes' => 'Format file harus PDF.',
            'file.max' => 'Ukuran file maksimum adalah 10 MB.',
        ]);

        $manualBook->clearMediaCollection('manual_book_file');

        $manualBook
            ->addMedia($request->file('file')->getRealPath())
            ->usingName($request->file('file')->getClientOriginalName())
            ->usingFileName($request->file('file')->getClientOriginalName())
            ->toMediaCollection('manual_book_file');

        return redirect()
            ->route('admin-lppm.manual-books.edit', $manualBook)
            ->with('success', 'File berhasil diunggah: '.$request->file('file')->getClientOriginalName());
    }
}
