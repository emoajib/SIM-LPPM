<?php

namespace App\Http\Controllers;

use App\Models\LetterType;
use App\Services\LetterTypeService;
use Illuminate\Http\Request;

class LetterTypeController extends Controller
{
    public function uploadTemplate(Request $request, LetterType $letterType, LetterTypeService $service)
    {
        $request->validate([
            'template' => 'required|file|mimes:pdf|max:5120',
        ]);

        try {
            $service->uploadTemplate($letterType, $request->file('template'), auth()->id());

            return back()->with('success', 'Template berhasil diupload.');
        } catch (\DomainException $e) {
            return back()->withErrors(['template' => $e->getMessage()]);
        }
    }

    public function downloadTemplate(LetterType $letterType, LetterTypeService $service)
    {
        return $service->downloadTemplate($letterType);
    }

    public function deleteTemplate(LetterType $letterType, LetterTypeService $service)
    {
        try {
            $service->deleteTemplate($letterType);

            return back()->with('success', 'Template berhasil dihapus.');
        } catch (\DomainException $e) {
            return back()->withErrors(['template' => $e->getMessage()]);
        }
    }
}
