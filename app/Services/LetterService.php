<?php

namespace App\Services;

use App\Models\Letter;
use App\Models\LetterType;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LetterService
{
    /**
     * Generate the next letter number for a given type.
     */
    public function generateNextNumber(LetterType $type): string
    {
        $year = date('Y');
        $month = (int) date('n');
        $romanMonth = $this->getRomanMonth($month);

        // Find the last sequence number for this year
        $lastLetter = Letter::where('letter_type_id', $type->id)
            ->whereYear('created_at', $year)
            ->whereNotNull('letter_number')
            ->orderByRaw('CAST(SUBSTRING_INDEX(letter_number, "/", 1) AS UNSIGNED) DESC')
            ->first();

        $nextSequence = 1;
        if ($lastLetter) {
            $parts = explode('/', (string) $lastLetter->letter_number);
            $nextSequence = (int) $parts[0] + 1;
        }

        $formattedNumber = str_pad((string) $nextSequence, 3, '0', STR_PAD_LEFT);

        // Replace placeholders in format
        $format = $type->numbering_format ?? '{NOMOR}/{CODE}/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}';

        return str_replace(
            ['{NOMOR}', '{CODE}', '{BULAN-ROMAWI}', '{TAHUN}'],
            [$formattedNumber, (string) $type->code, $romanMonth, $year],
            $format
        );
    }

    /**
     * Get Roman numeral for a month number.
     */
    public function getRomanMonth(int $month): string
    {
        $map = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

        return $map[$month] ?? (string) $month;
    }

    /**
     * Generate PDF for a letter.
     */
    public function generatePdf(Letter $letter): string
    {
        $letter->load(['letterType', 'user']);

        /** @var LetterType $letterType */
        $letterType = $letter->letterType;

        $data = [
            'letter' => $letter,
            'metadata' => $letter->metadata ?? [],
            'team' => $letter->team_snapshot ?? [],
        ];

        $pdf = Pdf::loadView((string) $letterType->template_view, $data);

        $filename = 'letters/' . $letterType->code . '-' . Str::slug($letter->letter_number ?? (string) $letter->id) . '.pdf';
        Storage::disk('public')->put($filename, $pdf->output());

        $letter->update(['file_path' => $filename]);

        return $filename;
    }

    /**
     * Check if the lettering module is active.
     */
    public function isActive(): bool
    {
        return (bool) Setting::get('module_persuratan_active', false);
    }
}
