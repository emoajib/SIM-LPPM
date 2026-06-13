<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use Illuminate\Contracts\View\View;

class LetterVerificationController extends Controller
{
    public function show(Letter $letter): View
    {
        $letter->load(['letterType', 'user']);

        // Abort for letters that are no longer valid
        if (in_array($letter->status, ['rejected', 'cancelled'])) {
            abort(410, 'Surat ini tidak lagi valid.');
        }

        return view('pdf.letters.verify', [
            'letter' => $letter,
            'statusLabel' => Letter::statusLabel($letter->status),
            'statusColor' => Letter::statusColor($letter->status),
        ]);
    }
}
