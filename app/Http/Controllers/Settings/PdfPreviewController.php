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

        $module = $request->get('module', 'dummy');

        // default generic preview
        if ($module === 'dummy' || empty($module)) {
            $pdf = Pdf::loadView('pdf.settings-preview');
            $pdf->setPaper($paperSizeArray);
            return $pdf->stream('pratinjau-pengaturan.pdf', ['Attachment' => false]);
        }

        try {
            $data = [];
            
            if (in_array($module, ['pdf.letters.surat-tugas', 'pdf.letters.surat-keterangan', 'pdf.letters.surat-permohonan-izin'])) {
                $typeCode = match($module) {
                    'pdf.letters.surat-tugas' => 'surat tugas',
                    'pdf.letters.surat-keterangan' => 'surat keterangan',
                    'pdf.letters.surat-permohonan-izin' => 'surat permohonan izin',
                };
                
                $letter = \App\Models\Letter::whereHas('letterType', function($q) use ($typeCode) {
                    $q->where('name', 'LIKE', "%{$typeCode}%");
                })->latest()->first() ?? new \App\Models\Letter([
                    'letter_number' => '001/DUMMY/2026',
                    'metadata' => [
                        'title' => 'Judul Dummy',
                        'activity_type' => 'Kegiatan',
                        'date_string' => '10 Juni 2026',
                        'time_string' => '08:00 WIB',
                        'location' => 'Gedung A',
                    ]
                ]);

                $data = [
                    'letter' => $letter,
                    'metadata' => array_merge([
                        'signer_name' => Setting::get('lppm_head_name', 'Nama LPPM'),
                        'signer_position' => Setting::get('lppm_head_position', 'Kepala LPPM'),
                    ], is_array($letter->metadata) ? $letter->metadata : []),
                    'team' => is_array($letter->team_snapshot) && count($letter->team_snapshot) > 0 ? $letter->team_snapshot : [['name' => 'Dr. Dummy', 'role' => 'Ketua', 'identifier' => '123']],
                    'qrDataUri' => '',
                    'pdfConfig' => get_pdf_config('letter'),
                ];

            } elseif ($module === 'pdf.proposal-export') {
                $proposal = \App\Models\Proposal::latest()->first();
                if (!$proposal) throw new \Exception("Data Proposal kosong di sistem.");
                $data = [
                    'proposal' => $proposal,
                    'pdfConfig' => get_pdf_config('proposal'),
                    'isDraft' => false,
                    'signatureLppmDataUri' => '',
                    'signatureDekanDataUri' => '',
                    'signatureLecturerDataUri' => '',
                ];

            } elseif ($module === 'pdf.report-export') {
                $report = \App\Models\ProgressReport::with('proposal')->latest()->first();
                if (!$report) throw new \Exception("Data Laporan Kemajuan/Akhir kosong.");
                $data = [
                    'report' => $report,
                    'proposal' => $report->proposal,
                    'pdfConfig' => get_pdf_config('report'),
                    'signatureLppmDataUri' => '',
                    'signatureLecturerDataUri' => '',
                ];

            } elseif ($module === 'pdf.daily-notes') {
                $proposal = \App\Models\Proposal::has('dailyNotes')->with('dailyNotes')->latest()->first();
                if (!$proposal) throw new \Exception("Data Logbook Harian kosong.");
                $data = [
                    'proposal' => $proposal,
                    'notes' => $proposal->dailyNotes,
                    'pdfConfig' => get_pdf_config('report'),
                    'dateRange' => 'Semua Waktu',
                ];

            } elseif ($module === 'pdf.review-evaluation') {
                $review = \App\Models\ProposalReview::with(['proposal', 'reviewer'])->latest()->first();
                if (!$review) throw new \Exception("Data Review kosong.");
                $data = [
                    'review' => $review,
                    'proposal' => $review->proposal,
                    'reviewer' => $review->reviewer,
                    'pdfConfig' => get_pdf_config('letter'),
                    'signatureReviewerDataUri' => '',
                    'signatureLppmDataUri' => '',
                ];

            } elseif ($module === 'reports.iku-report-pdf') {
                $data = [
                    'year' => date('Y'),
                    'iku1' => collect(), 'iku2' => collect(), 'iku3' => collect(), 
                    'iku4' => collect(), 'iku5' => collect(),
                    'pdfConfig' => get_pdf_config('report'),
                ];

            } elseif ($module === 'reports.research-pdf' || $module === 'reports.community-service-pdf') {
                $proposals = \App\Models\Proposal::take(3)->get();
                if ($proposals->isEmpty()) throw new \Exception("Data Proposal kosong.");
                $data = [
                    'year' => date('Y'),
                    'proposals' => $proposals,
                    'totalBudget' => $proposals->sum('approved_funds'),
                    'pdfConfig' => get_pdf_config('report'),
                ];

            } elseif ($module === 'reports.output-reports-pdf') {
                $outputs = \App\Models\MandatoryOutput::take(3)->get();
                if ($outputs->isEmpty()) throw new \Exception("Data Laporan Output kosong.");
                $data = [
                    'year' => date('Y'),
                    'outputs' => $outputs,
                    'pdfConfig' => get_pdf_config('report'),
                ];

            } elseif ($module === 'reports.partner-collaboration-pdf') {
                $proposals = \App\Models\Proposal::take(3)->get();
                if ($proposals->isEmpty()) throw new \Exception("Data Kerjasama Mitra kosong.");
                $data = [
                    'year' => date('Y'),
                    'proposals' => $proposals,
                    'pdfConfig' => get_pdf_config('report'),
                ];

            } elseif ($module === 'reports.monev-ba-pdf' || $module === 'reports.monev-pdf') {
                $monev = \App\Models\Monev::with('proposal')->latest()->first();
                if (!$monev) throw new \Exception("Data Monev kosong.");
                $data = [
                    'monev' => $monev,
                    'proposal' => $monev->proposal,
                    'pdfConfig' => get_pdf_config('report'),
                    'qrDataUri' => '',
                    'signatureReviewer1DataUri' => '',
                    'signatureReviewer2DataUri' => '',
                ];

            } elseif ($module === 'reports.reviewer-report-pdf') {
                $reviews = \App\Models\ProposalReview::with(['proposal', 'reviewer'])->take(3)->get();
                if ($reviews->isEmpty()) throw new \Exception("Data Reviewer kosong.");
                $data = [
                    'year' => date('Y'),
                    'reviews' => $reviews,
                    'pdfConfig' => get_pdf_config('report'),
                ];
            } else {
                throw new \Exception("Modul '{$module}' tidak dikenali.");
            }

            $pdf = Pdf::loadView($module, $data);
            $pdf->setPaper($paperSizeArray);

            return $pdf->stream('pratinjau.pdf', [
                'Attachment' => false
            ]);

        } catch (\Exception $e) {
            $errorHtml = '<div style="font-family: sans-serif; text-align: center; margin-top: 50px;">
                            <h2 style="color: #dc3545;">Pratinjau Tidak Tersedia</h2>
                            <p>' . htmlspecialchars($e->getMessage()) . '</p>
                            <p style="color: #6c757d; font-size: 12px;">Data riil diperlukan untuk mempratinjau modul ini.</p>
                          </div>';
            $pdf = Pdf::loadHTML($errorHtml);
            $pdf->setPaper($paperSizeArray);
            return $pdf->stream();
        }
    }
}
