<?php

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PdfPreviewController extends Controller
{
    /**
     * Tampilkan pratinjau PDF riil yang dihasilkan oleh DomPDF
     * menggunakan pengaturan aktual dari database.
     */
    public function preview(Request $request)
    {
        // Hanya Admin LPPM atau Superadmin yang boleh melihat preview ini
        abort_unless(Auth::user()?->hasRole('admin lppm') || Auth::user()?->hasRole('superadmin'), 403, 'Akses ditolak.');

        $paperSize = Setting::get('pdf_paper_size', 'a4');
        if ($paperSize === 'folio') {
            $paperSizeArray = [0, 0, 612.00, 935.43]; // Ukuran F4
        } else {
            $paperSizeArray = $paperSize;
        }

        $pdf = Pdf::loadView('pdf.settings-preview');
        $pdf->setPaper($paperSizeArray);

        // Render PDF ke memory dan tampilkan ke browser
        return $pdf->stream('pratinjau-pengaturan.pdf', [
            'Attachment' => false
        ]);
    }
}
