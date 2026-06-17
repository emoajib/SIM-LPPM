<?php

namespace App\Http\Controllers;

use App\Models\DocumentSignature;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\User;
use App\Services\DocumentSignatureService;
use App\Services\ProposalPdfService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProposalExportController extends Controller
{
    public function __construct(
        protected ProposalPdfService $pdfService,
        protected DocumentSignatureService $signatureService
    ) {}

    /**
     * Download the combined proposal PDF.
     */
    public function download(Request $request, Proposal $proposal)
    {
        /** @var User $user */
        $user = Auth::user();

        $isMember = $proposal->teamMembers()->where('users.id', $user->id)->exists();
        $isSubmitter = $proposal->submitter_id === $user->id;
        $isLppm = $user->hasRole(['admin lppm', 'kepala lppm', 'superadmin', 'rektor', 'dekan']);
        $isAssignedReviewer = $proposal->reviewers()->where('user_id', $user->id)->exists();

        if (! $isSubmitter && ! $isMember && ! $isLppm && ! $isAssignedReviewer) {
            abort(403, 'Anda tidak memiliki akses untuk mengekspor proposal ini.');
        }

        try {
            $pdfPath = $this->pdfService->export($proposal, $request->has('preview'));

            $title = preg_replace('/[^A-Za-z0-9_\-]/', '_', substr($proposal->title, 0, 50));
            $filename = 'Proposal_'.$title.'.pdf';

            // Since we use caching, we do NOT delete the file after sending
            return response()->download($pdfPath, $filename);
        } catch (\Exception $e) {
            \Log::error('Proposal PDF Download Error: '.$e->getMessage());

            return back()->with('error', 'Gagal mengunduh proposal PDF: '.$e->getMessage());
        }
    }

    /**
     * Preview the combined proposal PDF in browser.
     */
    public function preview(Request $request, Proposal $proposal)
    {
        /** @var User $user */
        $user = Auth::user();

        $isMember = $proposal->teamMembers()->where('users.id', $user->id)->exists();
        $isSubmitter = $proposal->submitter_id === $user->id;
        $isLppm = $user->hasRole(['admin lppm', 'kepala lppm', 'superadmin', 'rektor', 'dekan']);
        $isAssignedReviewer = $proposal->reviewers()->where('user_id', $user->id)->exists();

        if (! $isSubmitter && ! $isMember && ! $isLppm && ! $isAssignedReviewer) {
            abort(403, 'Anda tidak memiliki akses untuk melihat proposal ini.');
        }

        try {
            $pdfPath = $this->pdfService->export($proposal, true);

            // Return the cached file directly without deleting it
            return response()->file($pdfPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        } catch (\Exception $e) {
            \Log::error('Proposal PDF Preview Error: '.$e->getMessage());

            return back()->with('error', 'Gagal mempratinjau proposal PDF: '.$e->getMessage());
        }
    }

    /**
     * Download the report (Progress/Final) PDF.
     */
    public function downloadReport(Request $request, Proposal $proposal)
    {
        /** @var User $user */
        $user = Auth::user();

        $isMember = $proposal->teamMembers()->where('users.id', $user->id)->exists();
        $isSubmitter = $proposal->submitter_id === $user->id;
        $isLppm = $user->hasRole(['admin lppm', 'kepala lppm', 'superadmin', 'rektor', 'dekan']);
        $isAssignedReviewer = $proposal->reviewers()->where('user_id', $user->id)->exists();

        if (! $isSubmitter && ! $isMember && ! $isLppm && ! $isAssignedReviewer) {
            abort(403, 'Anda tidak memiliki akses untuk mengekspor laporan ini.');
        }

        $type = $request->query('type', 'final');

        // Find the report based on type
        if ($type === 'final') {
            $report = $proposal->progressReports()
                ->where('reporting_period', 'final')
                ->latest()
                ->first();
        } else {
            // For progress, we take the latest non-final report
            // or specific version if needed (but UI currently only passes 'progress')
            $report = $proposal->progressReports()
                ->where('reporting_period', '!=', 'final')
                ->latest()
                ->first();
        }

        if (! $report) {
            $message = 'Data Laporan '.($type === 'final' ? 'Akhir' : 'Kemajuan').' belum tersedia. Harap simpan draft laporan terlebih dahulu.';

            return back()->with('error', $message);
        }

        try {
            /** @var ProgressReport $report */
            $pdfPath = $this->pdfService->exportReport($proposal, $report, $request->has('preview'));
            $pdfBinary = file_get_contents($pdfPath);

            $this->upsertReportSignatures($proposal, $report, $pdfBinary);

            $title = preg_replace('/[^A-Za-z0-9_\-]/', '_', substr($proposal->title, 0, 50));
            $filename = ucfirst($type).'_Report_'.$title.'.pdf';

            if ($request->query('download') === 'true') {
                return response()->download($pdfPath, $filename);
            }

            return response()->file($pdfPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]);
        } catch (\Exception $e) {
            \Log::error('Report PDF Export Error: '.$e->getMessage());

            return back()->with('error', 'Gagal memulihkan dan mengunduh laporan PDF: '.$e->getMessage());
        }
    }

    /**
     * Upsert digital signatures for the report.
     */
    protected function upsertReportSignatures(Proposal $proposal, ProgressReport $report, string $pdfBinary): void
    {
        $hash = hash('sha256', $pdfBinary);
        $kid = config('document-signatures.current_kid', 'v1');
        $variant = $report->reporting_period === 'final' ? 'final' : 'progress';

        $reportStatusVal = $report->status instanceof \BackedEnum ? $report->status->value : $report->status;

        // Signatories mapping: role => [action, condition, signed_at]
        $signatories = [
            'lecturer' => ['submitted', true, $report->submitted_at ?? $report->created_at],
            'dekan' => [
                'approved',
                in_array($reportStatusVal, ['approved_by_dekan', 'approved', 'accepted']),
                $report->updated_at,
            ],
            'kepala_lppm' => [
                'finalized',
                in_array($reportStatusVal, ['approved', 'accepted']),
                $report->updated_at,
            ],
        ];

        /** @var Collection<string, DocumentSignature> $reportSigs */
        $reportSigs = $report->signatures()
            ->get()
            ->keyBy(function (Model $s) {
                /** @var DocumentSignature $s */
                return (string) "{$s->action}|{$s->signed_role}";
            });

        foreach ($signatories as $role => $config) {
            [$action, $condition, $signedAt] = $config;

            if (! $condition) {
                continue;
            }

            $user = [
                'lecturer' => $proposal->submitter,
                'dekan' => $proposal->submitter->identity?->faculty?->deanUser,
                'kepala_lppm' => User::role('kepala lppm')->first(),
            ][$role] ?? null;

            if (! $user) {
                continue;
            }

            $signatureRecord = $reportSigs->get("{$action}|{$role}");

            $nonce = $signatureRecord?->payload['nonce'] ?? Str::random(32);

            $payload = [
                'ver' => 1,
                'doc_type' => 'report',
                'doc_id' => (string) $report->id,
                'variant' => $variant,
                'action' => $action,
                'signed_role' => $role,
                'signed_by' => (string) $user->id,
                'signed_at' => Carbon::parse($signedAt)->copy()->utc()->toIso8601ZuluString(),
                'pdf_hash_alg' => 'SHA-256',
                'pdf_hash' => $hash,
                'kid' => $kid,
                'nonce' => $nonce,
            ];

            $report->signatures()->updateOrCreate(
                [
                    'signed_role' => $role,
                    'action' => $action,
                    'variant' => $variant,
                ],
                [
                    'signed_by' => $user->id,
                    'signed_at' => $signedAt,
                    'hash_alg' => 'sha256',
                    'document_hash' => $hash,
                    'kid' => $kid,
                    'payload' => $payload,
                    'signature' => $this->signatureService->signPayload($payload, $kid),
                ]
            );
        }
    }
}
