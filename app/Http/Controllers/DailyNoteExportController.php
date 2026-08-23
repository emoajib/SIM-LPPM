<?php

namespace App\Http\Controllers;

use App\Models\DocumentSignature;
use App\Models\Proposal;
use App\Models\Setting;
use App\Models\User;
use App\Services\DocumentSignatureService;
use App\Services\ProposalPdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

class DailyNoteExportController extends Controller
{
    protected $signatureService;

    protected $pdfService;

    public function __construct(DocumentSignatureService $signatureService, ProposalPdfService $pdfService)
    {
        $this->signatureService = $signatureService;
        $this->pdfService = $pdfService;
    }

    /**
     * Download the Financial Report (LPJ) PDF.
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    public function financialReport(Proposal $proposal, Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $isMember = $proposal->teamMembers()->where('users.id', $user->id)->exists();
        $isSubmitter = $proposal->submitter_id === $user->id;
        $isLppm = $user->activeHasAnyRole(['admin lppm', 'kepala lppm', 'superadmin', 'rektor', 'dekan']);

        if (! $isSubmitter && ! $isMember && ! $isLppm) {
            abort(403, 'Anda tidak memiliki akses untuk mengekspor laporan keuangan ini.');
        }

        try {
            $pdfPath = $this->pdfService->exportFinancialReport($proposal, $request->has('preview'));

            $title = preg_replace('/[^A-Za-z0-9_\-]/', '_', substr($proposal->title, 0, 50));
            $filename = 'Laporan_Keuangan_'.$title.'.pdf';

            if ($request->query('download') === 'true') {
                return response()->download($pdfPath, $filename);
            }

            return response()->file($pdfPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]);
        } catch (\Exception $e) {
            \Log::error('Financial Report PDF Export Error: '.$e->getMessage());

            return back()->with('error', 'Gagal mengunduh laporan keuangan: '.$e->getMessage());
        }
    }

    /**
     * Download the daily notes PDF.
     */
    public function download(Proposal $proposal, Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $isMember = $proposal->teamMembers()->where('users.id', $user->id)->exists();
        $isSubmitter = $proposal->submitter_id === $user->id;
        $isLppm = $user->activeHasAnyRole(['admin lppm', 'kepala lppm', 'superadmin', 'rektor', 'dekan']);

        if (! $isSubmitter && ! $isMember && ! $isLppm) {
            abort(403, 'Anda tidak memiliki akses untuk mengekspor catatan harian ini.');
        }

        $proposal->load([
            'dailyNotes' => fn ($q) => $q->with(['media.model', 'budgetGroup'])->latest('activity_date'),
            'submitter.identity.faculty',
            'submitter.identity.studyProgram',
            'submitter.identity.institution',
            'teamMembers.identity',
            'researchScheme',
            'communityServiceScheme',
        ]);

        $submitterIdentity = $proposal->submitter->identity;
        $submitterFullName = format_name($submitterIdentity?->title_prefix, $proposal->submitter->name, $submitterIdentity?->title_suffix);
        $submitterNidn = $submitterIdentity->identity_id ?? '-';
        $facultyName = $submitterIdentity?->faculty->name ?? '.......................';
        $prodiName = $submitterIdentity?->studyProgram->name ?? '.......................';
        $institutionName = $submitterIdentity?->institution->name ?? 'ITSNU Pekalongan';
        $academicYear = $proposal->start_year.'/'.($proposal->start_year + 1);

        $logbookApprovalMode = Setting::where('key', 'logbook_approval_mode')->value('value') ?? 'digital';
        $pdfConfig = get_pdf_config('letter', 'logbook');

        // 1. Render for hash
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        $pdf = Pdf::loadView('pdf.daily-notes', [
            'isPreview' => $request->has('preview'),
            'proposal' => $proposal,
            'notes' => $proposal->dailyNotes,
            'isSigned' => $proposal->logbook_signed_at !== null || $request->query('signed') === 'true',
            'isApproved' => $proposal->logbook_approved_at !== null,
            'logbookApprovalMode' => $logbookApprovalMode,
            'qrUrlSubmitter' => null,
            'qrUrlLppm' => null,
            'submitterFullName' => $submitterFullName,
            'submitterNidn' => $submitterNidn,
            'facultyName' => $facultyName,
            'prodiName' => $prodiName,
            'institutionName' => $institutionName,
            'academicYear' => $academicYear,
            'docTitle' => 'CATATAN HARIAN '.($proposal->detailable_type === 'App\Models\Research' ? 'PENELITIAN' : 'PENGABDIAN').' INTERNAL',
            'pdfConfig' => $pdfConfig,
        ])->setPaper(normalize_paper_size($pdfConfig['paper_size'] ?? 'a4'), $pdfConfig['orientation'] ?? 'portrait');

        if ($request->has('preview')) {
            return $pdf->stream('preview.pdf');
        }

        $pdfBinary = $pdf->output();
        $hash = hash('sha256', $pdfBinary);
        $kid = $this->signatureService->currentKid();

        // 2. Sign for Submitter
        $submitterSig = null;
        if ($proposal->logbook_signed_at || $request->query('signed') === 'true') {
            $signedAt = $proposal->logbook_signed_at ?? now();
            $submitterSig = $this->upsertLogbookSignature($proposal, 'lecturer', 'submitted', $signedAt, $hash, $kid);
        }

        // 3. Sign for LPPM (Approver)
        $lppmSig = null;
        if ($proposal->logbook_approved_at) {
            $lppmSig = $this->upsertLogbookSignature($proposal, 'kepala_lppm', 'approved', $proposal->logbook_approved_at, $hash, $kid);
        }

        // 4. Re-render with QR codes
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        $pdf = Pdf::loadView('pdf.daily-notes', [
            'isPreview' => false,
            'proposal' => $proposal,
            'notes' => $proposal->dailyNotes,
            'isSigned' => $submitterSig !== null,
            'isApproved' => $lppmSig !== null,
            'logbookApprovalMode' => $logbookApprovalMode,
            'qrUrlSubmitter' => $submitterSig ? URL::signedRoute('signatures.verify', ['documentSignature' => $submitterSig->id]) : null,
            'qrUrlLppm' => $lppmSig ? URL::signedRoute('signatures.verify', ['documentSignature' => $lppmSig->id]) : null,
            'submitterFullName' => $submitterFullName,
            'submitterNidn' => $submitterNidn,
            'facultyName' => $facultyName,
            'prodiName' => $prodiName,
            'institutionName' => $institutionName,
            'academicYear' => $academicYear,
            'docTitle' => 'CATATAN HARIAN '.($proposal->detailable_type === 'App\Models\Research' ? 'PENELITIAN' : 'PENGABDIAN').' INTERNAL',
            'pdfConfig' => $pdfConfig,
        ])->setPaper(normalize_paper_size($pdfConfig['paper_size'] ?? 'a4'), $pdfConfig['orientation'] ?? 'portrait');

        $title = preg_replace('/[^A-Za-z0-9_\-]/', '_', substr($proposal->title, 0, 50));
        $filename = 'Catatan_Harian_'.$title.'.pdf';
        $outputPdfBinary = $pdf->output();

        if ($proposal->hasMedia('logbook_approval_file')) {
            try {
                $tempPath = tempnam(sys_get_temp_dir(), 'dn_info_').'.pdf';
                file_put_contents($tempPath, $outputPdfBinary);

                $fpdi = new Fpdi;
                $pageCount = $fpdi->setSourceFile($tempPath);

                // 1. Add Cover (Page 1)
                if ($pageCount >= 1) {
                    $templateId = $fpdi->importPage(1);
                    $size = $fpdi->getTemplateSize($templateId);
                    $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $fpdi->useTemplate($templateId);
                }

                // 2. Add uploaded signed scan page(s) (replacing the generated unsigned Page 2)
                $scanMedia = $proposal->getFirstMedia('logbook_approval_file');
                $scanPath = $scanMedia ? $scanMedia->getPath() : null;
                if ($scanPath && file_exists($scanPath) && strtolower(pathinfo($scanPath, PATHINFO_EXTENSION)) === 'pdf') {
                    $scanPageCount = $fpdi->setSourceFile($scanPath);
                    for ($p = 1; $p <= $scanPageCount; $p++) {
                        $templateId = $fpdi->importPage($p);
                        $size = $fpdi->getTemplateSize($templateId);
                        $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                        $fpdi->useTemplate($templateId);
                    }
                } elseif ($pageCount >= 2) {
                    $fpdi->setSourceFile($tempPath);
                    $templateId = $fpdi->importPage(2);
                    $size = $fpdi->getTemplateSize($templateId);
                    $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $fpdi->useTemplate($templateId);
                }

                // 3. Add remaining pages (Page 3 onwards)
                if ($pageCount >= 3) {
                    $fpdi->setSourceFile($tempPath);
                    for ($pageNo = 3; $pageNo <= $pageCount; $pageNo++) {
                        $templateId = $fpdi->importPage($pageNo);
                        $size = $fpdi->getTemplateSize($templateId);
                        $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                        $fpdi->useTemplate($templateId);
                    }
                }

                $mergedPath = tempnam(sys_get_temp_dir(), 'dn_merged_').'.pdf';
                $fpdi->Output('F', $mergedPath);
                $outputPdfBinary = file_get_contents($mergedPath);
                @unlink($tempPath);
                @unlink($mergedPath);
            } catch (\Throwable $e) {
                Log::warning('Failed to merge logbook approval scan: '.$e->getMessage());
            }
        }

        if ($request->query('download') === 'true') {
            return response($outputPdfBinary, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        }

        return response($outputPdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * Helper to upsert signatures for logbook.
     */
    protected function upsertLogbookSignature(Proposal $proposal, string $role, string $action, $signedAt, string $hash, string $kid): DocumentSignature
    {
        $user = match ($role) {
            'lecturer' => $proposal->submitter,
            'kepala_lppm' => User::role('kepala lppm')->first(),
            default => null,
        };

        /** @var DocumentSignature|null $signatureRecord */
        $signatureRecord = $proposal->signatures()
            ->where('signed_role', $role)
            ->where('action', $action)
            ->where('variant', 'logbook')
            ->first();

        $nonce = $signatureRecord?->payload['nonce'] ?? Str::random(32);

        $payload = [
            'ver' => 1,
            'doc_type' => 'logbook',
            'doc_id' => (string) $proposal->id,
            'variant' => 'logbook',
            'action' => $action,
            'signed_role' => $role,
            'signed_by' => (string) ($user->id ?? ''),
            'signed_at' => Carbon::parse($signedAt)->copy()->utc()->toIso8601ZuluString(),
            'pdf_hash_alg' => 'SHA-256',
            'pdf_hash' => $hash,
            'kid' => $kid,
            'nonce' => $nonce,
        ];

        /** @var DocumentSignature $signature */
        $signature = $proposal->signatures()->updateOrCreate(
            [
                'signed_role' => $role,
                'action' => $action,
                'variant' => 'logbook',
            ],
            [
                'signed_by' => $user->id ?? null,
                'signed_at' => $signedAt,
                'hash_alg' => 'sha256',
                'document_hash' => $hash,
                'kid' => $kid,
                'payload' => $payload,
                'signature' => $this->signatureService->signPayload($payload, $kid),
            ]
        );

        return $signature;
    }
}
