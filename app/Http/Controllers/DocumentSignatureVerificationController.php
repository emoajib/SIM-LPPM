<?php

namespace App\Http\Controllers;

use App\Models\DocumentSignature;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\User;
use App\Services\DocumentSignatureService;
use Illuminate\Contracts\View\View;

class DocumentSignatureVerificationController extends Controller
{
    /**
     * Display the document signature verification page.
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    public function show(DocumentSignature $documentSignature, DocumentSignatureService $signatureService): View
    {
        $isValid = $signatureService->verify($documentSignature);

        // 1. Resolve Signer Details
        $signer = $documentSignature->signed_by
            ? User::with(['identity.institution'])->find($documentSignature->signed_by)
            : null;
        $signerIdentity = $signer?->identity;

        $signerName = $documentSignature->payload['signer_name']
            ?? ($signer ? format_name($signerIdentity?->title_prefix, $signer->name, $signerIdentity?->title_suffix) : '-');

        $signerNidn = $documentSignature->payload['signer_nidn']
            ?? ($signerIdentity->identity_id ?? '-');

        $roleSlug = $documentSignature->signed_role;
        $roleLabel = $documentSignature->payload['role_label']
            ?? match (strtolower((string) $roleSlug)) {
                'kepala_lppm', 'kepala lppm' => 'Kepala LPPM',
                'dekan' => 'Dekan Fakultas',
                'lecturer', 'dosen', 'ketua' => 'Ketua Peneliti / Pelaksana',
                'reviewer' => 'Reviewer',
                'rektor' => 'Rektor',
                'admin_lppm', 'admin lppm' => 'Admin LPPM',
                default => ucwords(str_replace('_', ' ', (string) $roleSlug)),
            };

        $institutionName = $documentSignature->payload['institution']
            ?? ($signerIdentity->institution->name ?? (get_institution_config('name') ?? 'ITSNU Pekalongan'));

        // 2. Resolve Document Details
        $doc = $documentSignature->document;
        $docInfo = $this->resolveDocumentInfo($doc, $documentSignature);

        return view('signatures.verify', [
            'signature' => $documentSignature,
            'isValid' => $isValid,
            'signerName' => $signerName,
            'signerNidn' => $signerNidn,
            'roleLabel' => $roleLabel,
            'institutionName' => $institutionName,
            'docInfo' => $docInfo,
        ]);
    }

    /**
     * Resolve human-readable document metadata.
     */
    private function resolveDocumentInfo(?object $doc, DocumentSignature $signature): array
    {
        if ($doc instanceof Proposal) {
            $isResearch = str_contains((string) $doc->detailable_type, 'Research');
            $scheme = $doc->researchScheme->name ?? $doc->communityServiceScheme->name ?? null;

            return [
                'type_label' => $isResearch ? 'Proposal Penelitian' : 'Proposal Pengabdian Masyarakat',
                'title' => clean_proposal_title($doc->title),
                'contract_number' => $doc->contract_number ?? '-',
                'submitter_name' => format_name($doc->submitter->identity?->title_prefix, $doc->submitter->name, $doc->submitter->identity?->title_suffix),
                'scheme' => $scheme,
                'year' => $doc->start_year,
            ];
        }

        if ($doc instanceof ProgressReport) {
            $proposal = $doc->proposal;
            $isResearch = str_contains((string) $proposal->detailable_type, 'Research');
            $periodLabel = $doc->isFinalReport() ? 'Laporan Akhir' : 'Laporan Kemajuan';
            $categoryLabel = $isResearch ? 'Penelitian' : 'Pengabdian Masyarakat';
            $scheme = $proposal->researchScheme->name ?? $proposal->communityServiceScheme->name ?? null;

            return [
                'type_label' => "{$periodLabel} {$categoryLabel}",
                'title' => clean_proposal_title($proposal->title),
                'contract_number' => $proposal->contract_number ?? '-',
                'submitter_name' => format_name($proposal->submitter->identity?->title_prefix, $proposal->submitter->name, $proposal->submitter->identity?->title_suffix),
                'scheme' => $scheme,
                'year' => $proposal->start_year,
            ];
        }

        return [
            'type_label' => $signature->payload['doc_type_label'] ?? ucwords(str_replace(['App\\Models\\', '_'], ['', ' '], (string) $signature->document_type)),
            'title' => $signature->payload['doc_title'] ?? "Dokumen ID: {$signature->document_id}",
            'contract_number' => $signature->payload['contract_number'] ?? '-',
            'submitter_name' => $signature->payload['submitter_name'] ?? '-',
            'scheme' => null,
            'year' => null,
        ];
    }
}
