<?php

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Letter;
use App\Models\MandatoryOutput;
use App\Models\Partner;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\ProposalMonev;
use App\Models\ProposalReviewer;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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

        $modules = config('pdf-modules.list', []);
        $moduleLabels = collect($modules)->pluck('name', 'template')->toArray();
        $moduleKeys = collect($modules)->pluck('key', 'template')->toArray();

        $shortKey = $moduleKeys[$module] ?? null;

        try {
            $data = $this->resolveModuleData($module, $shortKey);

            $pdf = Pdf::loadView($module, $data);

            $modulePaperSize = $data['pdfConfig']['paper_size'] ?? $paperSize;
            $pdf->setPaper(normalize_paper_size($modulePaperSize));

            return $pdf->stream('pratinjau.pdf');

        } catch (\Exception $e) {
            Log::error('PDF Preview gagal untuk modul: '.$module.' - '.$e->getMessage(), [
                'module' => $module,
                'trace' => $e->getTraceAsString(),
            ]);
            $moduleLabel = $moduleLabels[$module] ?? $module;
            $errorHtml = '<div style="font-family: sans-serif; text-align: center; margin-top: 50px;">
                            <h2 style="color: #dc3545;">Pratinjau Tidak Tersedia</h2>
                            <p>'.htmlspecialchars($e->getMessage()).'</p>
                            <p style="color: #6c757d; font-size: 12px;">Data riil diperlukan untuk mempratinjau modul ini.</p>
                            <hr style="width: 50%; margin: 20px auto; border: 0.5px solid #ddd;">
                            <p style="color: #6c757d; font-size: 11px;">Menampilkan pratinjau generik dengan pengaturan saat ini...</p>
                          </div>';

            if (config('app.env') === 'local') {
                $pdf = Pdf::loadView('pdf.settings-preview', [
                    'moduleLabel' => $moduleLabel,
                ]);
            } else {
                $pdf = Pdf::loadHTML($errorHtml);
            }
            $pdf->setPaper($paperSizeArray);

            return $pdf->stream();
        }
    }

    /**
     * Resolve module-specific data for PDF preview.
     */
    private function resolveModuleData(string $module, ?string $shortKey): array
    {
        if (in_array($module, ['pdf.letters.surat-tugas', 'pdf.letters.surat-keterangan', 'pdf.letters.surat-permohonan-izin'])) {
            $typeCode = 'surat permohonan izin';
            if ($module === 'pdf.letters.surat-tugas') {
                $typeCode = 'surat tugas';
            } elseif ($module === 'pdf.letters.surat-keterangan') {
                $typeCode = 'surat keterangan';
            }

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

            return [
                'letter' => $letter,
                'metadata' => array_merge([
                    'signer_name' => get_institution_config('lppm_head_name'),
                    'signer_position' => get_institution_config('lppm_head_position'),
                ], is_array($letter->metadata) ? $letter->metadata : []),
                'team' => is_array($letter->team_snapshot) && count($letter->team_snapshot) > 0 ? $letter->team_snapshot : [['name' => 'Dr. Dummy', 'role' => 'Ketua', 'identifier' => '123']],
                'qrDataUri' => '',
                'pdfConfig' => get_pdf_config('letter', $shortKey),
            ];
        }

        if ($module === 'pdf.proposal-export') {
            $proposal = Proposal::with([
                'submitter.identity.institution',
                'submitter.identity.studyProgram',
                'submitter.identity.scienceCluster',
                'submitter.identity.faculty',
                'teamMembers' => fn ($q) => $q->withPivot(['tasks', 'role']),
                'teamMembers.identity',
                'signatures',
                'partners',
                'outputs',
                'keywords',
                'budgetItems',
                'sdgs',
                'researchScheme',
                'focusArea',
                'theme',
                'topic',
                'detailable',
            ])->latest()->first();
            if (! $proposal) {
                throw new \Exception('Data Proposal kosong di sistem.');
            }

            return [
                'proposal' => $proposal,
                'pdfConfig' => get_pdf_config('letter', $shortKey),
                'isDraft' => false,
                'signatureLppmDataUri' => '',
                'signatureDekanDataUri' => '',
                'signatureLecturerDataUri' => '',
            ];
        }

        if ($module === 'pdf.report-export') {
            $report = ProgressReport::with([
                'proposal.submitter.identity.faculty',
                'proposal.submitter.identity.studyProgram',
                'proposal.researchScheme',
                'proposal.focusArea',
                'proposal.signatures',
                'proposal.detailable',
            ])->where('reporting_period', 'final')->latest()->first();
            if (! $report) {
                throw new \Exception('Data Laporan Akhir kosong.');
            }

            return [
                'report' => $report,
                'proposal' => $report->proposal,
                'pdfConfig' => get_pdf_config('report', $shortKey),
                'signatureLppmDataUri' => '',
                'signatureLecturerDataUri' => '',
            ];
        }

        if ($module === 'pdf.daily-notes') {
            $proposal = Proposal::with([
                'dailyNotes',
                'submitter.identity',
                'teamMembers' => fn ($q) => $q->withPivot('role'),
            ])->has('dailyNotes')->latest()->first();
            if (! $proposal) {
                throw new \Exception('Data Logbook Harian kosong.');
            }

            return [
                'proposal' => $proposal,
                'notes' => $proposal->dailyNotes,
                'pdfConfig' => get_pdf_config('report', $shortKey),
                'dateRange' => 'Semua Waktu',
            ];
        }

        if ($module === 'pdf.review-evaluation') {
            $assignment = ProposalReviewer::with([
                'proposal.submitter.identity',
                'proposal.researchScheme',
                'user.identity',
            ])->latest()->first();
            if (! $assignment) {
                throw new \Exception('Data Review kosong.');
            }

            return [
                'assignment' => $assignment,
                'proposal' => $assignment->proposal,
                'pdfConfig' => get_pdf_config('letter', $shortKey),
                'signatureReviewerDataUri' => '',
                'signatureLppmDataUri' => '',
            ];
        }

        if ($module === 'reports.iku-report-pdf') {
            return [
                'period' => date('Y'),
                'ikuMetrics' => collect(),
                'institutionalReport' => null,
                'rektor' => null,
                'lppmHead' => null,
                'pdfConfig' => get_pdf_config('report', $shortKey),
            ];
        }

        if ($module === 'reports.research-pdf' || $module === 'reports.community-service-pdf') {
            $proposals = Proposal::with([
                'submitter.identity.faculty',
                'submitter.identity.studyProgram',
                'researchScheme',
                'focusArea',
            ])->take(3)->get();
            if ($proposals->isEmpty()) {
                throw new \Exception('Data Proposal kosong.');
            }

            return [
                'period' => date('Y'),
                'semester' => 'all',
                'proposals' => $proposals,
                'totalBudget' => $proposals->sum('approved_funds'),
                'institutionalReport' => null,
                'rektor' => null,
                'lppmHead' => null,
                'pdfConfig' => get_pdf_config('report', $shortKey),
            ];
        }

        if ($module === 'reports.output-reports-pdf') {
            $outputs = MandatoryOutput::take(3)->get();
            if ($outputs->isEmpty()) {
                throw new \Exception('Data Laporan Output kosong.');
            }

            return [
                'period' => date('Y'),
                'proposals' => Proposal::take(3)->get(),
                'activeTab' => 'research',
                'outputType' => 'all',
                'institutionalReport' => null,
                'rektor' => null,
                'lppmHead' => null,
                'pdfConfig' => get_pdf_config('report', $shortKey),
            ];
        }

        if ($module === 'reports.partner-collaboration-pdf') {
            $partners = Partner::take(3)->get();
            if ($partners->isEmpty()) {
                throw new \Exception('Data Kerjasama Mitra kosong.');
            }

            return [
                'periodFilter' => date('Y'),
                'typeFilter' => null,
                'partners' => $partners,
                'institutionalReport' => null,
                'rektor' => null,
                'lppmHead' => null,
                'pdfConfig' => get_pdf_config('report', $shortKey),
            ];
        }

        if ($module === 'reports.monev-ba-pdf' || $module === 'reports.monev-pdf') {
            $monev = ProposalMonev::with('proposal')->latest()->first();
            if (! $monev) {
                throw new \Exception('Data Monev kosong.');
            }

            $review = $monev;

            if ($module === 'reports.monev-ba-pdf') {
                return [
                    'review' => $review,
                    'proposal' => $review->proposal,
                    'criteria' => collect(),
                    'activeReport' => null,
                    'period' => date('Y'),
                    'semester' => 'all',
                    'qrReviewerUrl' => null,
                    'qrAdminUrl' => null,
                    'qrKepalaUrl' => null,
                    'generatedAt' => now(),
                    'pdfConfig' => get_pdf_config('report', $shortKey),
                ];
            }

            return [
                'reviews' => collect([$review]),
                'period' => date('Y'),
                'semester' => 'all',
                'institutionalReport' => null,
                'rektor' => null,
                'lppmHead' => null,
                'pdfConfig' => get_pdf_config('report', $shortKey),
            ];
        }

        if ($module === 'reports.reviewer-report-pdf') {
            $proposals = ProposalReviewer::with(['proposal', 'user.identity'])->take(3)->get();
            if ($proposals->isEmpty()) {
                throw new \Exception('Data Reviewer kosong.');
            }

            return [
                'period' => date('Y'),
                'semester' => 'all',
                'proposals' => $proposals,
                'reviewers' => collect(),
                'summaryStats' => [
                    'total_proposals' => 0,
                    'assigned' => 0,
                    'progress_percent' => 0,
                    'avg_score' => '-',
                ],
                'institutionalReport' => null,
                'rektor' => null,
                'lppmHead' => null,
                'pdfConfig' => get_pdf_config('report', $shortKey),
            ];
        }

        throw new \Exception("Modul '{$module}' tidak dikenali.");
    }
}
