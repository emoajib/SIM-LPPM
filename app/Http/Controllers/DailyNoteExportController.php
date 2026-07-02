<?php

namespace App\Http\Controllers;

use App\Models\DocumentSignature;
use App\Models\Proposal;
use App\Models\Setting;
use App\Models\User;
use App\Services\DocumentSignatureService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class DailyNoteExportController extends Controller
{
    protected $signatureService;

    public function __construct(DocumentSignatureService $signatureService)
    {
        $this->signatureService = $signatureService;
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

        if ($request->query('download') === 'true') {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
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
