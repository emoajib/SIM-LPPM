<?php

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Letter;
use App\Models\MandatoryOutput;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\ProposalMonev;
use App\Models\ProposalReviewer;
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
        $paperSizeArray = normalize_paper_size($paperSize);

        $module = $request->get('module', 'dummy');

        // default generic preview
        if ($module === 'dummy' || empty($module)) {
            $pdf = Pdf::loadView('pdf.settings-preview');
            $pdf->setPaper($paperSizeArray);

            return $pdf->stream('pratinjau-pengaturan.pdf');
        }

        try {
            $data = [];

            $moduleKeys = [
                'pdf.letters.surat-tugas' => 'surat-tugas',
                'pdf.letters.surat-keterangan' => 'surat-keterangan',
                'pdf.letters.surat-permohonan-izin' => 'surat-izin',
                'pdf.proposal-export' => 'proposal-export',
                'pdf.report-export' => 'laporan-kemajuan',
                'pdf.daily-notes' => 'logbook',
                'pdf.review-evaluation' => 'evaluasi-reviewer',
                'reports.iku-report-pdf' => 'iku',
                'reports.research-pdf' => 'penelitian',
                'reports.community-service-pdf' => 'pengabdian',
                'reports.output-reports-pdf' => 'output',
                'reports.partner-collaboration-pdf' => 'mitra',
                'reports.monev-ba-pdf' => 'monev-ba',
                'reports.monev-pdf' => 'monev',
                'reports.reviewer-report-pdf' => 'reviewer',
            ];

            $shortKey = $moduleKeys[$module] ?? null;

            if (in_array($module, ['pdf.letters.surat-tugas', 'pdf.letters.surat-keterangan', 'pdf.letters.surat-permohonan-izin'])) {
                $typeCode = match ($module) {
                    'pdf.letters.surat-tugas' => 'surat tugas',
                    'pdf.letters.surat-keterangan' => 'surat keterangan',
                    'pdf.letters.surat-permohonan-izin' => 'surat permohonan izin',
                    default => 'surat tugas',
                };

                $letter = Letter::whereHas('letterType', function ($q) use ($typeCode) {
                    $q->where('name', 'LIKE', "%{$typeCode}%");
                })->latest()->first() ?? new Letter([
                    'letter_number' => '001/DUMMY/2026',
                    'metadata' => [
                        'title' => 'Judul Dummy',
                        'activity_type' => 'Kegiatan',
                        'date_string' => '10 Juni 2026',
                        'time_string' => '08:00 WIB',
                        'location' => 'Gedung A',
                    ],
                ]);

                $data = [
                    'letter' => $letter,
                    'metadata' => array_merge([
                        'signer_name' => Setting::get('lppm_head_name', 'Nama LPPM'),
                        'signer_position' => Setting::get('lppm_head_position', 'Kepala LPPM'),
                    ], is_array($letter->metadata) ? $letter->metadata : []),
                    'team' => is_array($letter->team_snapshot) && count($letter->team_snapshot) > 0 ? $letter->team_snapshot : [['name' => 'Dr. Dummy', 'role' => 'Ketua', 'identifier' => '123']],
                    'qrDataUri' => '',
                    'pdfConfig' => get_pdf_config('letter', $shortKey),
                ];

            } elseif ($module === 'pdf.proposal-export') {
                $proposal = Proposal::latest()->first();
                if (! $proposal) {
                    throw new \Exception('Data Proposal kosong di sistem.');
                }
                $data = [
                    'proposal' => $proposal,
                    'pdfConfig' => get_pdf_config('letter', $shortKey),
                    'isDraft' => false,
                    'signatureLppmDataUri' => '',
                    'signatureDekanDataUri' => '',
                    'signatureLecturerDataUri' => '',
                ];

            } elseif ($module === 'pdf.report-export') {
                $report = ProgressReport::with('proposal')->latest()->first();
                if (! $report) {
                    throw new \Exception('Data Laporan Kemajuan/Akhir kosong.');
                }
                $data = [
                    'report' => $report,
                    'proposal' => $report->proposal,
                    'pdfConfig' => get_pdf_config('report', $shortKey),
                    'signatureLppmDataUri' => '',
                    'signatureLecturerDataUri' => '',
                ];

            } elseif ($module === 'pdf.daily-notes') {
                $proposal = Proposal::has('dailyNotes')->with('dailyNotes')->latest()->first();
                if (! $proposal) {
                    throw new \Exception('Data Logbook Harian kosong.');
                }
                $data = [
                    'proposal' => $proposal,
                    'notes' => $proposal->dailyNotes,
                    'pdfConfig' => get_pdf_config('report', $shortKey),
                    'dateRange' => 'Semua Waktu',
                ];

            } elseif ($module === 'pdf.review-evaluation') {
                $assignment = ProposalReviewer::with(['proposal', 'user.identity'])->latest()->first();
                if (! $assignment) {
                    throw new \Exception('Data Review kosong.');
                }
                $data = [
                    'assignment' => $assignment,
                    'proposal' => $assignment->proposal,
                    'pdfConfig' => get_pdf_config('letter', $shortKey),
                    'signatureReviewerDataUri' => '',
                    'signatureLppmDataUri' => '',
                ];

            } elseif ($module === 'reports.iku-report-pdf') {
                $data = [
                    'year' => date('Y'),
                    'iku1' => collect(), 'iku2' => collect(), 'iku3' => collect(),
                    'iku4' => collect(), 'iku5' => collect(),
                    'pdfConfig' => get_pdf_config('report', $shortKey),
                ];

            } elseif ($module === 'reports.research-pdf' || $module === 'reports.community-service-pdf') {
                $proposals = Proposal::take(3)->get();
                if ($proposals->isEmpty()) {
                    throw new \Exception('Data Proposal kosong.');
                }
                $data = [
                    'year' => date('Y'),
                    'proposals' => $proposals,
                    'totalBudget' => $proposals->sum('approved_funds'),
                    'pdfConfig' => get_pdf_config('report', $shortKey),
                ];

            } elseif ($module === 'reports.output-reports-pdf') {
                $outputs = MandatoryOutput::take(3)->get();
                if ($outputs->isEmpty()) {
                    throw new \Exception('Data Laporan Output kosong.');
                }
                $data = [
                    'year' => date('Y'),
                    'outputs' => $outputs,
                    'pdfConfig' => get_pdf_config('report', $shortKey),
                ];

            } elseif ($module === 'reports.partner-collaboration-pdf') {
                $proposals = Proposal::take(3)->get();
                if ($proposals->isEmpty()) {
                    throw new \Exception('Data Kerjasama Mitra kosong.');
                }
                $data = [
                    'year' => date('Y'),
                    'proposals' => $proposals,
                    'pdfConfig' => get_pdf_config('report', $shortKey),
                ];

            } elseif ($module === 'reports.monev-ba-pdf' || $module === 'reports.monev-pdf') {
                $monev = ProposalMonev::with('proposal')->latest()->first();
                if (! $monev) {
                    throw new \Exception('Data Monev kosong.');
                }
                $data = [
                    'monev' => $monev,
                    'proposal' => $monev->proposal,
                    'pdfConfig' => get_pdf_config('report', $shortKey),
                    'qrDataUri' => '',
                    'signatureReviewer1DataUri' => '',
                    'signatureReviewer2DataUri' => '',
                ];

            } elseif ($module === 'reports.reviewer-report-pdf') {
                $reviews = ProposalReviewer::with(['proposal', 'user'])->take(3)->get();
                if ($reviews->isEmpty()) {
                    throw new \Exception('Data Reviewer kosong.');
                }
                $data = [
                    'year' => date('Y'),
                    'reviews' => $reviews,
                    'pdfConfig' => get_pdf_config('report', $shortKey),
                ];
            } else {
                throw new \Exception("Modul '{$module}' tidak dikenali.");
            }

            $pdf = Pdf::loadView($module, $data);

            $modulePaperSize = $data['pdfConfig']['paper_size'] ?? $paperSize;
            $pdf->setPaper(normalize_paper_size($modulePaperSize));

            return $pdf->stream('pratinjau.pdf');

        } catch (\Exception $e) {
            $errorHtml = '<div style="font-family: sans-serif; text-align: center; margin-top: 50px;">
                            <h2 style="color: #dc3545;">Pratinjau Tidak Tersedia</h2>
                            <p>'.htmlspecialchars($e->getMessage()).'</p>
                            <p style="color: #6c757d; font-size: 12px;">Data riil diperlukan untuk mempratinjau modul ini.</p>
                          </div>';
            $pdf = Pdf::loadHTML($errorHtml);
            $pdf->setPaper($paperSizeArray);

            return $pdf->stream();
        }
    }
}
