<?php

namespace App\Http\Controllers;

use App\Enums\LetterStatus;
use App\Models\Letter;
use Illuminate\Contracts\View\View;

class LetterVerificationController extends Controller
{
    public function show(Letter $letter): View
    {
        $letter->load(['letterType', 'user']);

        // Abort for letters that are no longer valid
        if (in_array($letter->status, [LetterStatus::REJECTED, LetterStatus::CANCELLED])) {
            abort(410, 'Surat ini tidak lagi valid.');
        }

        return view('pdf.letters.verify', [
            'letter' => $letter,
            'statusLabel' => Letter::statusLabel($letter->status->value),
            'statusColor' => Letter::statusColor($letter->status->value),
        ]);
    }
}
