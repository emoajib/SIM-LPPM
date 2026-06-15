<?php

namespace App\Http\Controllers;

use App\Models\DocumentSignature;
use App\Models\ProposalReviewer;
use App\Models\User;
use App\Services\DocumentSignatureService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ReviewExportController extends Controller
{
    protected function pdfDownloadResponse(string $pdfBinary, string $filename): Response
    {
        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    // Vetted by AI - Manual Review Required by Senior Engineer/Manager
    protected function pdfInlineResponse(string $pdfBinary, string $filename): Response
    {
        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Download the review result as PDF.
     */
    public function download(Request $request, ProposalReviewer $proposalReviewer)
    {
        /** @var User $user */
        $user = Auth::user();
        $proposal = $proposalReviewer->proposal;

        // Authorization check
        $isReviewer = $proposalReviewer->user_id === $user->id;
        $isLppm = $user->hasRole(['admin lppm', 'kepala lppm', 'superadmin', 'rektor']);

        if (! $isReviewer && ! $isLppm) {
            abort(403, 'Anda tidak memiliki akses untuk mengekspor penilaian ini.');
        }

        if (! $proposalReviewer->isCompleted()) {
            abort(400, 'Penilaian belum diselesaikan.');
        }

        $isPreview = $request->has('preview');
        $pdfConfig = get_pdf_config('letter', 'evaluasi-reviewer');

        $proposal->load([
            'submitter.identity.faculty',
            'submitter.identity.studyProgram',
            'teamMembers',
            'budgetItems',
        ]);

        $proposalReviewer->load('user.identity');

        $scores = $proposalReviewer->scores()
            ->where('round', $proposalReviewer->round)
            ->with('criteria')
            ->get();

        $totalScore = $scores->sum('value');

        $type = $proposal->detailable_type === 'App\Models\Research' ? 'research' : 'community_service';

        $title = preg_replace('/[^A-Za-z0-9_\-]/', '_', substr($proposal->title, 0, 30));
        $filename = ($isPreview ? 'PREVIEW-' : '').'Penilaian_Reviewer_'.$title.'.pdf';

        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        $signedAt = $proposalReviewer->completed_at ?? $proposalReviewer->updated_at ?? now();
        $variant = 'round-'.((int) ($proposalReviewer->round ?? 1)).'-'.$signedAt->format('YmdHis');
        $cachePath = 'review-evaluations/'.$proposalReviewer->id.'/'.$variant.'.pdf';

        $signatureService = app(DocumentSignatureService::class);
        $kid = $signatureService->currentKid();

        $signature = DocumentSignature::query()
            ->where('document_type', $proposalReviewer->getMorphClass())
            ->where('document_id', (string) $proposalReviewer->id)
            ->where('variant', $variant)
            ->where('action', 'reviewed')
            ->where('signed_role', 'reviewer')
            ->first();

        if (! $signature) {
            $signature = DocumentSignature::create([
                'id' => (string) Str::uuid(),
                'document_type' => $proposalReviewer->getMorphClass(),
                'document_id' => (string) $proposalReviewer->id,
                'variant' => $variant,
                'action' => 'reviewed',
                'signed_role' => 'reviewer',
                'signed_by' => (string) $proposalReviewer->user_id,
                'signed_at' => $signedAt,
                'kid' => $kid,
                'signature' => Str::random(64),
                'payload' => ['ver' => 1, 'nonce' => Str::random(32)],
            ]);
        }

        $qrUrl = URL::signedRoute('signatures.verify', ['documentSignature' => $signature->id]);

        if ($isPreview) {
            // Vetted by AI - Manual Review Required by Senior Engineer/Manager
            $pdf = Pdf::loadView('pdf.review-evaluation', [
                'isPreview' => true,
                'assignment' => $proposalReviewer,
                'proposal' => $proposal,
                'scores' => $scores,
                'totalScore' => $totalScore,
                'type' => $type,
                'qrUrl' => $qrUrl,
                'pdfConfig' => $pdfConfig,
            ])
                ->setPaper(normalize_paper_size($pdfConfig['paper_size'] ?? 'a4'), $pdfConfig['orientation'] ?? 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'times-roman',
                ]);

            return $this->pdfInlineResponse($pdf->output(), $filename);
        }

        if (Storage::disk('local')->exists($cachePath) && $signature->document_hash) {
            return $this->pdfDownloadResponse(Storage::disk('local')->get($cachePath), $filename);
        }

        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        $pdf = Pdf::loadView('pdf.review-evaluation', [
            'isPreview' => false,
            'assignment' => $proposalReviewer,
            'proposal' => $proposal,
            'scores' => $scores,
            'totalScore' => $totalScore,
            'type' => $type,
            'qrUrl' => $qrUrl,
            'pdfConfig' => $pdfConfig,
        ])
            ->setPaper(normalize_paper_size($pdfConfig['paper_size'] ?? 'a4'), $pdfConfig['orientation'] ?? 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'times-roman',
            ]);

        $pdfBinary = $pdf->output();
        Storage::disk('local')->put($cachePath, $pdfBinary);

        $hash = hash('sha256', $pdfBinary);

        $payload = [
            'ver' => 1,
            'doc_type' => 'review_evaluation',
            'doc_id' => (string) $proposalReviewer->id,
            'proposal_id' => (string) $proposalReviewer->proposal_id,
            'variant' => $variant,
            'action' => 'reviewed',
            'signed_role' => 'reviewer',
            'signed_by' => (string) $proposalReviewer->user_id,
            'signed_at' => $signedAt->copy()->utc()->toIso8601ZuluString(),
            'pdf_hash_alg' => 'SHA-256',
            'pdf_hash' => $hash,
            'kid' => $kid,
            'nonce' => (string) ($signature->payload['nonce'] ?? Str::random(32)),
        ];

        $signature->update([
            'signed_by' => (string) $proposalReviewer->user_id,
            'signed_at' => $signedAt,
            'hash_alg' => 'sha256',
            'document_hash' => $hash,
            'kid' => $kid,
            'payload' => $payload,
            'signature' => $signatureService->signPayload($payload, $kid),
        ]);

        return $this->pdfDownloadResponse($pdfBinary, $filename);
    }
}
