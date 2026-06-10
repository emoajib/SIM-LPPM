<?php

namespace App\Services;

use App\Enums\ProposalStatus;
use App\Models\CommunityService;
use App\Models\DailyNote;
use App\Models\DocumentSignature;
use App\Models\Faculty;
use App\Models\Identity;
use App\Models\Institution;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\ProposalStatusLog;
use App\Models\Research;
use App\Models\Setting;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProposalPdfService
{
    public function __construct(
        protected DocumentSignatureService $signatureService
    ) {}

    /**
     * Get a local PDF path for a media file.
     * Returns null if the file cannot be found.
     *
     * @param  array<int, string>  $tempFiles
     *
     * For local disks: resolves to the absolute filesystem path.
     */
    private function getLocalPdfPath(Media $media, array &$tempFiles): ?string
    {
        $diskName = config('media-library.disk_name', 'public');
        $path = $media->getPath();
        $fullPath = str_starts_with($path, '/') ? $path : Storage::disk($diskName)->path($path);

        return file_exists($fullPath) ? $fullPath : null;
    }

    /**
     * Export the proposal to a combined PDF.
     * Uses caching to avoid regenerating the same PDF multiple times.
     *
     * @return string Path to the combined PDF file
     */
    public function export(Proposal $proposal, bool $isPreview = false): string
    {
        // 0. Cache Check
        $cacheDir = storage_path('app/public/pdf_cache/proposals');
        if (! file_exists($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        // Calculate latest timestamp including media
        $latestTimestamp = $proposal->updated_at->timestamp;

        // Check detailable media collections
        $collections = ['substance_file', 'approval_file'];
        foreach ($collections as $col) {
            /** @var Model&HasMedia $detailable */
            $detailable = $proposal->detailable;
            if ($detailable) {
                $media = $detailable->getMedia($col)->first();
                if ($media) {
                    $latestTimestamp = max($latestTimestamp, $media->updated_at->timestamp);
                }
            }
        }

        // Check partner commitment letters
        foreach ($proposal->partners as $partner) {
            $commitment = $partner->getMedia('commitment_letter')
                ->where('custom_properties.proposal_id', $proposal->id)
                ->first();
            if ($commitment) {
                $latestTimestamp = max($latestTimestamp, $commitment->updated_at->timestamp);
            }
        }

        // Check identity updated_at (submitter + team members)
        $submitterIdentity = $proposal->submitter->identity;
        if ($submitterIdentity && $submitterIdentity->updated_at) {
            $latestTimestamp = max($latestTimestamp, $submitterIdentity->updated_at->timestamp);
        }
        foreach ($proposal->teamMembers as $member) {
            $memberIdentity = $member->identity;
            if ($memberIdentity && $memberIdentity->updated_at) {
                $latestTimestamp = max($latestTimestamp, $memberIdentity->updated_at->timestamp);
            }
            // Also check pivot table for team member changes (role, status, tasks)
            if ($member->relationLoaded('pivot') && $member->pivot && isset($member->pivot->updated_at)) {
                $latestTimestamp = max($latestTimestamp, $member->pivot->updated_at->timestamp);
            }
        }

        // Debug: Log timestamp calculation for cache invalidation
        Log::debug('PDF Cache Timestamp Calculation', [
            'proposal_id' => $proposal->id,
            'base_timestamp' => $proposal->updated_at->timestamp,
            'final_timestamp' => $latestTimestamp,
            'cache_file' => $isPreview ? 'preview' : 'export',
        ]);

        $cacheFileName = sprintf(
            '%sproposal_%s_%s.pdf',
            $isPreview ? 'preview_' : '',
            $proposal->id,
            $latestTimestamp
        );
        $cachePath = $cacheDir.DIRECTORY_SEPARATOR.$cacheFileName;

        if (file_exists($cachePath)) {
            return $cachePath;
        }

        // Cleanup old versions of this proposal's PDF
        $oldPdfs = glob($cacheDir.DIRECTORY_SEPARATOR."proposal_{$proposal->id}_*.pdf");
        foreach ($oldPdfs as $oldPdf) {
            @unlink($oldPdf);
        }

        /** @var Faculty|null $faculty */
        $faculty = $proposal->submitter->identity?->faculty?->load('deanUser.identity');
        $deanName = '..........................';
        $deanId = '..........................';

        if ($faculty?->deanUser) {
            // Dynamic: use linked user data
            /** @var Identity $identity */
            $identity = $faculty->deanUser->identity;
            $name = $faculty->deanUser->name;
            $prefix = $identity->title_prefix;
            $suffix = $identity->title_suffix;

            // Only prepend prefix if not already present
            if ($prefix && ! str_starts_with($name, $prefix) && ! str_contains($name, $prefix.' ')) {
                $name = $prefix.' '.$name;
            }

            // Only append suffix if not already present
            if ($suffix && ! str_ends_with($name, $suffix) && ! str_contains($name, ', '.$suffix)) {
                $name = $name.', '.$suffix;
            }

            $deanName = $name;
            $deanId = $identity->identity_id ?? '';
        } else {
            // Fallback: use static fields in faculty record
            $deanName = $faculty->dean_name ?: $deanName;
            $deanId = $faculty->dean_id ?: $deanId;
        }

        if ($deanName === '..........................') {
            $candidate = User::whereHas('roles', function ($q) {
                $q->where('name', 'dekan');
            })
                ->whereHas('identity', function ($q) use ($faculty) {
                    $q->where('faculty_id', $faculty->id);
                })
                ->with('identity')
                ->first();

            if ($candidate) {
                /** @var Identity $idn */
                $idn = $candidate->identity;
                $nm = $candidate->name;
                $px = $idn->title_prefix;
                $sx = $idn->title_suffix;
                if ($px && ! str_starts_with($nm, $px) && ! str_contains($nm, $px.' ')) {
                    $nm = $px.' '.$nm;
                }
                if ($sx && ! str_ends_with($nm, $sx) && ! str_contains($nm, ', '.$sx)) {
                    $nm = $nm.', '.$sx;
                }
                $deanName = $nm;
                $deanId = $idn->identity_id ?? '';
            }
        }

        // Fetch LPPM Head details based on institution
        /** @var Institution|null $institution */
        $institution = $proposal->submitter->identity?->institution?->load('lppmHeadUser.identity');
        $lppmHeadName = '..........................';
        $lppmHeadId = '..........................';

        if ($institution?->lppmHeadUser) {
            /** @var Identity $identity */
            $identity = $institution->lppmHeadUser->identity;
            $fullName = $institution->lppmHeadUser->name;
            $prefix = $identity->title_prefix;
            $suffix = $identity->title_suffix;

            if ($prefix && ! str_contains($fullName, $prefix)) {
                $fullName = $prefix.' '.$fullName;
            }
            if ($suffix && ! str_contains($fullName, $suffix)) {
                $fullName = $fullName.', '.$suffix;
            }

            $lppmHeadName = $fullName;
            $lppmHeadId = $identity->identity_id ?? '';
        } else {
            $lppmHeadName = $institution->lppm_head_name ?: (Setting::where('key', 'lppm_head_name')->first()->value ?? $lppmHeadName);
            $lppmHeadId = $institution->lppm_head_id ?: (Setting::where('key', 'lppm_head_id')->first()->value ?? $lppmHeadId);
        }

        if ($lppmHeadName === '..........................') {
            $candidate = User::whereHas('roles', function ($q) {
                $q->where('name', 'kepala lppm');
            })
                ->whereHas('identity', function ($q) use ($institution) {
                    $q->where('institution_id', $institution->id);
                })
                ->with('identity')
                ->first();

            if ($candidate) {
                /** @var Identity $idn */
                $idn = $candidate->identity;
                $nm = $candidate->name;
                $px = $idn->title_prefix;
                $sx = $idn->title_suffix;
                if ($px && ! str_contains($nm, $px)) {
                    $nm = $px.' '.$nm;
                }
                if ($sx && ! str_contains($nm, $sx)) {
                    $nm = $nm.', '.$sx;
                }
                $lppmHeadName = $nm;
                $deanId = $idn->identity_id ?? '';
            }
        } else {
            // Ultimate fallback
            $lppmHeadName = Setting::where('key', 'lppm_head_name')->first()->value ?? $lppmHeadName;
            $lppmHeadId = Setting::where('key', 'lppm_head_id')->first()->value ?? $lppmHeadId;
        }

        // Fetch approval logs for signatures
        $deanLog = ProposalStatusLog::where('proposal_id', $proposal->id)
            ->where('status_after', ProposalStatus::APPROVED)
            ->latest('at')
            ->first();

        $lppmLog = ProposalStatusLog::where('proposal_id', $proposal->id)
            ->whereIn('status_after', [ProposalStatus::UNDER_REVIEW, ProposalStatus::WAITING_REVIEWER])
            ->latest('at')
            ->first();

        $submissionLog = ProposalStatusLog::where('proposal_id', $proposal->id)
            ->where('status_after', ProposalStatus::SUBMITTED)
            ->latest('at')
            ->first();

        $lecturerSignedAt = $submissionLog->at ?? $proposal->created_at;

        // Clean up any existing signatures for draft/revision proposals (should not have signatures)
        if (in_array($proposal->status->value, [ProposalStatus::DRAFT->value, ProposalStatus::REVISION_NEEDED->value])) {
            DocumentSignature::where('document_type', get_class($proposal))
                ->where('document_id', $proposal->id)
                ->where('variant', 'final')
                ->delete();
        }

        // Pre-fetch approval mode once (reused for Blade view & FPDI merge)
        $approvalMode = Setting::where('key', 'proposal_approval_mode')->value('value') ?? 'digital';

        // Create signatures BEFORE generating PDF (so they exist when blade renders)
        // Use placeholder hash first, will be updated after PDF is generated
        Log::info('Creating proposal signatures', [
            'proposal_id' => $proposal->id,
            'status' => $proposal->status->value,
            'status_class' => get_class($proposal->status),
            'will_create_lecturer_sig' => in_array($proposal->status->value, [ProposalStatus::SUBMITTED->value, ProposalStatus::NEED_ASSIGNMENT->value, ProposalStatus::APPROVED->value, ProposalStatus::WAITING_REVIEWER->value, ProposalStatus::UNDER_REVIEW->value, ProposalStatus::REVIEWED->value, ProposalStatus::COMPLETED->value]),
        ]);
        $this->createProposalSignatures($proposal, 'placeholder-hash-for-initial-generation');

        // Debug: Check what was created
        $createdSigs = DocumentSignature::where('document_type', get_class($proposal))
            ->where('document_id', $proposal->id)
            ->where('variant', 'final')
            ->get();
        Log::info('Created signatures', [
            'proposal_id' => $proposal->id,
            'count' => $createdSigs->count(),
            'roles' => $createdSigs->pluck('signed_role')->toArray(),
        ]);

        // Force fresh load of signatures directly from database
        $proposal->unsetRelation('signatures');
        $proposal->setRelation('signatures', $createdSigs);

        // Load all relationships needed for the view
        $proposal->load([
            'submitter.identity.institution',
            'submitter.identity.studyProgram',
            'submitter.identity.faculty',
            'submitter.identity.scienceCluster',
            'teamMembers.identity.institution',
            'teamMembers.identity.studyProgram',
            'teamMembers.identity.faculty',
            'teamMembers.identity.scienceCluster',
            'researchScheme',
            'focusArea',
            'theme',
            'topic',
            'clusterLevel1',
            'keywords',
            'budgetItems.budgetGroup',
            'budgetItems.budgetComponent',
            'partners',
            'detailable.macroResearchGroup',
            'outputs',
            'sdgs',
            'signatures',
        ]);

        // 1. Generate the basic info PDF using DomPDF
        $infoPdfContent = Pdf::loadView('pdf.proposal-export', [
            'isPreview' => $isPreview,
            'proposal' => $proposal,
            'dean_name' => $deanName,
            'dean_id' => $deanId,
            'lppm_head_name' => $lppmHeadName,
            'lppm_head_id' => $lppmHeadId,
            'proposal_approval_mode' => $approvalMode, // reuse already-fetched variable
            'dean_signed_at' => $deanLog?->at,
            'lppm_signed_at' => $lppmLog?->at,
            'lecturer_signed_at' => $lecturerSignedAt,
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'times-roman',
            ]);

        // Add metadata
        $infoPdfContent->getDomPDF()->add_info('Title', 'PROPOSAL '.($proposal->detailable_type === 'App\Models\Research' ? 'PENELITIAN' : 'PENGABDIAN').' - '.$proposal->title);
        $infoPdfContent->getDomPDF()->add_info('Author', $proposal->submitter->name);
        $infoPdfContent->getDomPDF()->add_info('Subject', 'Dokumen Usulan Program PPM Internal ITSNU Pekalongan');
        $infoPdfContent->getDomPDF()->add_info('Keywords', 'SIM-LPPM, ITSNU, Pekalongan, '.($proposal->researchScheme->name ?? '').', '.($proposal->focusArea->name ?? ''));
        $infoPdfContent->getDomPDF()->add_info('Creator', 'SIM-LPPM ITSNU Pekalongan');

        $infoPdfContent = $infoPdfContent->output();

        $tempInfoPath = tempnam($cacheDir, 'proposal_info_');
        file_put_contents($tempInfoPath, $infoPdfContent);

        // 2. Prepare FPDI for merging
        $pdf = new Fpdi;

        // Track temp files for cleanup if needed
        $tempFiles = [];

        // Add pages from the generated info PDF
        $pageCount = $pdf->setSourceFile($tempInfoPath);
        for ($i = 1; $i <= $pageCount; $i++) {
            $templateId = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($templateId);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);
        }

        if ($approvalMode === 'upload' || $approvalMode === 'both') {
            $detailable = $proposal->detailable;
            /** @var ?Media $approvalFile */
            $approvalFile = null;
            if ($detailable instanceof Research) {
                $approvalFile = $detailable->getFirstMedia('approval_file');
            } elseif ($detailable instanceof CommunityService) {
                $approvalFile = $detailable->getFirstMedia('approval_file');
            }
            if ($approvalFile) {
                $approvalPath = $this->getLocalPdfPath($approvalFile, $tempFiles);
                $isPdf = str_contains($approvalFile->mime_type ?? '', 'pdf');

                if ($approvalPath !== null && $isPdf) {
                    Log::debug('Merging approval file to PDF', [
                        'proposal_id' => $proposal->id,
                        'file_path' => $approvalPath,
                        'mime_type' => $approvalFile->mime_type,
                    ]);
                    try {
                        $approvalPageCount = $pdf->setSourceFile($approvalPath);
                        for ($i = 1; $i <= $approvalPageCount; $i++) {
                            $templateId = $pdf->importPage($i);
                            $size = $pdf->getTemplateSize($templateId);
                            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                            $pdf->useTemplate($templateId);
                        }
                    } catch (\Throwable $e) {
                        \Log::warning('FPDI Merge Fail (Approval File) for '.$proposal->id.': '.$e->getMessage());
                    }
                } else {
                    Log::warning('Approval file skipped', [
                        'proposal_id' => $proposal->id,
                        'reason' => $approvalPath === null ? 'file_not_accessible' : 'not_pdf_mime',
                        'file_path' => $approvalPath,
                        'mime_type' => $approvalFile->mime_type,
                    ]);
                }
            }
        }

        // 3. Add pages from the substance file if it exists
        $detailableSubstance = $proposal->detailable;
        /** @var ?Media $substanceFile */
        $substanceFile = null;
        if ($detailableSubstance instanceof Research) {
            $substanceFile = $detailableSubstance->getFirstMedia('substance_file');
        } elseif ($detailableSubstance instanceof CommunityService) {
            $substanceFile = $detailableSubstance->getFirstMedia('substance_file');
        }
        if ($substanceFile) {
            $substancePath = $this->getLocalPdfPath($substanceFile, $tempFiles);
            $isPdf = str_contains($substanceFile->mime_type ?? '', 'pdf');

            if ($substancePath !== null && $isPdf) {
                Log::debug('Merging substance file to PDF', [
                    'proposal_id' => $proposal->id,
                    'file_path' => $substancePath,
                    'mime_type' => $substanceFile->mime_type,
                ]);
                try {
                    $substancePageCount = $pdf->setSourceFile($substancePath);
                    for ($i = 1; $i <= $substancePageCount; $i++) {
                        $templateId = $pdf->importPage($i);
                        $size = $pdf->getTemplateSize($templateId);
                        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                        $pdf->useTemplate($templateId);
                    }
                } catch (\Throwable $e) {
                    Log::warning('FPDI Merge Fail (Substance File) for '.$proposal->id.': '.$e->getMessage());
                }
            } else {
                Log::warning('Substance file skipped', [
                    'proposal_id' => $proposal->id,
                    'reason' => $substancePath === null ? 'file_not_accessible' : 'not_pdf_mime',
                    'file_path' => $substancePath,
                    'mime_type' => $substanceFile->mime_type,
                ]);
            }
        } else {
            Log::debug('No substance file found for proposal', ['proposal_id' => $proposal->id]);
        }

        // 4. Add pages from partner commitment letters
        foreach ($proposal->partners as $partner) {
            /** @var ?Media $commitmentLetter */
            $commitmentLetter = $partner->getMedia('commitment_letter')
                ->where('custom_properties.proposal_id', $proposal->id)
                ->first();

            if ($commitmentLetter) {
                $filePath = $this->getLocalPdfPath($commitmentLetter, $tempFiles);
                $isPdf = str_contains($commitmentLetter->mime_type ?? '', 'pdf');

                Log::debug('Checking commitment letter for PDF merge', [
                    'partner_id' => $partner->id,
                    'partner_name' => $partner->name,
                    'proposal_id' => $proposal->id,
                    'file_path' => $filePath,
                    'file_accessible' => $filePath !== null,
                    'mime_type' => $commitmentLetter->mime_type,
                    'is_pdf' => $isPdf,
                    'custom_properties' => $commitmentLetter->custom_properties,
                ]);

                if ($filePath !== null && $isPdf) {
                    try {
                        $commitmentPageCount = $pdf->setSourceFile($filePath);
                        for ($i = 1; $i <= $commitmentPageCount; $i++) {
                            $templateId = $pdf->importPage($i);
                            $size = $pdf->getTemplateSize($templateId);
                            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                            $pdf->useTemplate($templateId);
                        }
                        Log::info('Successfully merged commitment letter', [
                            'partner_id' => $partner->id,
                            'proposal_id' => $proposal->id,
                            'pages_merged' => $commitmentPageCount,
                        ]);
                    } catch (\Throwable $e) {
                        Log::warning('FPDI Merge Fail (Partner Commitment Letter) for partner '.$partner->id.' in proposal '.$proposal->id.': '.$e->getMessage(), [
                            'exception' => $e,
                            'file_path' => $filePath,
                        ]);
                    }
                } else {
                    Log::warning('Commitment letter skipped', [
                        'partner_id' => $partner->id,
                        'proposal_id' => $proposal->id,
                        'reason' => $filePath === null ? 'file_not_accessible' : 'not_pdf_mime',
                        'file_path' => $filePath,
                        'mime_type' => $commitmentLetter->mime_type,
                    ]);
                }
            } else {
                Log::debug('No commitment letter found for partner in this proposal', [
                    'partner_id' => $partner->id,
                    'partner_name' => $partner->name,
                    'proposal_id' => $proposal->id,
                ]);
            }
        }

        $pdf->Output('F', $cachePath);

        // Update signature hashes with actual PDF hash
        $pdfBinary = file_get_contents($cachePath);
        $actualHash = hash('sha256', $pdfBinary);
        $this->updateProposalSignatureHashes($proposal, $actualHash);

        // Cleanup temporary info PDF
        @unlink($tempInfoPath);

        // Cleanup temp files
        foreach ($tempFiles as $tempFile) {
            @unlink($tempFile);
        }

        return $cachePath;
    }

    /**
     * Create proposal signatures before PDF generation.
     */
    protected function createProposalSignatures(Proposal $proposal, string $placeholderHash): void
    {
        $kid = config('document-signatures.current_kid', 'v1');

        $signatories = [
            'lecturer' => ['submitted', in_array($proposal->status->value, [ProposalStatus::SUBMITTED->value, ProposalStatus::NEED_ASSIGNMENT->value, ProposalStatus::APPROVED->value, ProposalStatus::WAITING_REVIEWER->value, ProposalStatus::UNDER_REVIEW->value, ProposalStatus::REVIEWED->value, ProposalStatus::COMPLETED->value])],
            'dekan' => ['approved', in_array($proposal->status->value, [ProposalStatus::APPROVED->value, ProposalStatus::WAITING_REVIEWER->value, ProposalStatus::UNDER_REVIEW->value, ProposalStatus::REVIEWED->value, ProposalStatus::COMPLETED->value])],
            'kepala_lppm' => ['finalized', in_array($proposal->status->value, [ProposalStatus::WAITING_REVIEWER->value, ProposalStatus::UNDER_REVIEW->value, ProposalStatus::REVIEWED->value, ProposalStatus::COMPLETED->value])],
        ];

        foreach ($signatories as $role => $config) {
            [$action, $condition] = $config;

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

            $signedAt = [
                'lecturer' => ProposalStatusLog::where('proposal_id', $proposal->id)->where('status_after', ProposalStatus::SUBMITTED)->latest('at')->value('at') ?? $proposal->created_at ?? now(),
                'dekan' => ProposalStatusLog::where('proposal_id', $proposal->id)->where('status_after', ProposalStatus::APPROVED)->latest('at')->value('at') ?? now(),
                'kepala_lppm' => ProposalStatusLog::where('proposal_id', $proposal->id)->whereIn('status_after', [ProposalStatus::WAITING_REVIEWER, ProposalStatus::UNDER_REVIEW])->latest('at')->value('at') ?? now(),
            ][$role];

            $nonce = Str::random(32);

            $payload = [
                'ver' => 1,
                'doc_type' => 'proposal',
                'doc_id' => (string) $proposal->id,
                'variant' => 'final',
                'action' => $action,
                'signed_role' => $role,
                'signed_by' => (string) $user->id,
                'signed_at' => Carbon::parse($signedAt)->copy()->utc()->toIso8601ZuluString(),
                'pdf_hash_alg' => 'SHA-256',
                'pdf_hash' => $placeholderHash,
                'kid' => $kid,
                'nonce' => $nonce,
            ];

            DocumentSignature::updateOrCreate(
                [
                    'document_type' => get_class($proposal),
                    'document_id' => (string) $proposal->id,
                    'signed_role' => $role,
                    'action' => $action,
                    'variant' => 'final',
                ],
                [
                    'action' => $action,
                    'signed_by' => $user->id,
                    'signed_at' => $signedAt,
                    'hash_alg' => 'sha256',
                    'document_hash' => $placeholderHash,
                    'kid' => $kid,
                    'payload' => $payload,
                    'signature' => $this->signatureService->signPayload($payload, $kid),
                ]
            );
        }
    }

    /**
     * Update signature hashes with actual PDF hash.
     */
    protected function updateProposalSignatureHashes(Proposal $proposal, string $actualHash): void
    {
        $kid = config('document-signatures.current_kid', 'v1');

        $proposal->signatures()
            ->where('document_type', get_class($proposal))
            ->where('document_id', $proposal->id)
            ->where('variant', 'final')
            ->update([
                'document_hash' => $actualHash,
            ]);
    }

    /**
     * Export a report to PDF.
     */
    public function exportReport(Proposal $proposal, ProgressReport $report, bool $isPreview = false): string
    {
        // 0. Cache Check
        $cacheDir = storage_path('app/public/pdf_cache/reports');
        if (! file_exists($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        // Calculate latest timestamp including media
        $latestTimestamp = $report->updated_at->timestamp;

        // Check report media
        $collections = ['substance_file', 'realization_file', 'presentation_file', 'signature_page'];
        foreach ($collections as $col) {
            $media = $report->getFirstMedia($col);
            if ($media) {
                $latestTimestamp = max($latestTimestamp, $media->updated_at->timestamp);
            }
        }

        // Check output media
        $outputModels = $report->mandatoryOutputs->concat($report->additionalOutputs);
        foreach ($outputModels as $output) {
            $outputCols = ['journal_article', 'book_document', 'publication_certificate', 'output_file'];
            foreach ($outputCols as $col) {
                $media = $output->getFirstMedia($col);
                if ($media) {
                    $latestTimestamp = max($latestTimestamp, $media->updated_at->timestamp);
                }
            }
        }

        // Check identity updated_at (submitter + team members)
        $submitterIdentity = $proposal->submitter->identity;
        if ($submitterIdentity && $submitterIdentity->updated_at) {
            $latestTimestamp = max($latestTimestamp, $submitterIdentity->updated_at->timestamp);
        }
        foreach ($proposal->teamMembers as $member) {
            $memberIdentity = $member->identity;
            if ($memberIdentity && $memberIdentity->updated_at) {
                $latestTimestamp = max($latestTimestamp, $memberIdentity->updated_at->timestamp);
            }
        }

        $cacheFileName = sprintf(
            '%sreport_%s_%s.pdf',
            $isPreview ? 'preview_' : '',
            $report->id,
            $latestTimestamp
        );
        $cachePath = $cacheDir.DIRECTORY_SEPARATOR.$cacheFileName;

        if (file_exists($cachePath)) {
            return $cachePath;
        }

        // Cleanup old versions of this report's PDF
        $oldPdfs = glob($cacheDir.DIRECTORY_SEPARATOR."report_{$report->id}_*.pdf");
        foreach ($oldPdfs as $oldPdf) {
            @unlink($oldPdf);
        }

        // Signatures setup (Same as export)
        /** @var Faculty|null $faculty */
        $faculty = $proposal->submitter->identity?->faculty?->load('deanUser.identity');
        $deanName = '..........................';
        $deanId = '..........................';
        if ($faculty?->deanUser) {
            /** @var Identity $identity */
            $identity = $faculty->deanUser->identity;
            $deanName = format_name($identity->title_prefix, $faculty->deanUser->name, $identity->title_suffix);
            $deanId = $identity->identity_id ?? '';
        } else {
            $deanName = $faculty->dean_name ?: $deanName;
            $deanId = $faculty->dean_id ?: $deanId;
        }

        /** @var Institution|null $institution */
        $institution = $proposal->submitter->identity?->institution?->load('lppmHeadUser.identity');
        $lppmHeadName = '..........................';
        $lppmHeadId = '..........................';
        if ($institution?->lppmHeadUser) {
            /** @var Identity $identity */
            $identity = $institution->lppmHeadUser->identity;
            $lppmHeadName = format_name($identity->title_prefix, $institution->lppmHeadUser->name, $identity->title_suffix);
            $lppmHeadId = $identity->identity_id ?? '';
        } elseif ($institution) {
            $lppmHeadName = $institution->lppm_head_name ?: (Setting::where('key', 'lppm_head_name')->first()->value ?? $lppmHeadName);
            $lppmHeadId = $institution->lppm_head_id ?: (Setting::where('key', 'lppm_head_id')->first()->value ?? $lppmHeadId);
        } else {
            $lppmHeadName = Setting::where('key', 'lppm_head_name')->first()->value ?? $lppmHeadName;
            $lppmHeadId = Setting::where('key', 'lppm_head_id')->first()->value ?? $lppmHeadId;
        }

        if ($institution && $lppmHeadName === '..........................') {
            $candidate = User::whereHas('roles', function ($q) {
                $q->where('name', 'kepala lppm');
            })
                ->whereHas('identity', function ($q) use ($institution) {
                    $q->where('institution_id', $institution->id);
                })
                ->with('identity')
                ->first();

            if ($candidate) {
                /** @var Identity $idn */
                $idn = $candidate->identity;
                $lppmHeadName = format_name($idn->title_prefix, $candidate->name, $idn->title_suffix);
                $lppmHeadId = $idn->identity_id ?? '';
            }
        }

        // Determine signature presence for reports
        // Strict logic: Dean signature appears ONLY IF report is approved_by_dekan OR approved
        // LPPM signature appears ONLY IF report is approved
        $deanSignedAt = null;
        $lppmSignedAt = null;

        $reportStatusVal = $report->status instanceof \BackedEnum ? $report->status->value : $report->status;

        if (in_array($reportStatusVal, ['approved_by_dekan', 'approved', 'accepted'])) {
            $deanSignedAt = $report->updated_at;
        }

        if (in_array($reportStatusVal, ['approved', 'accepted'])) {
            $lppmSignedAt = $report->updated_at;
        }

        $lecturerSignedAt = $report->submitted_at ?? ($report->created_at ?? now());

        // Fetch digital signatures for QR codes
        /** @var Collection<string, DocumentSignature> $reportSigs */
        $reportSigs = $report->signatures()
            ->get()
            ->keyBy(function (Model $s) {
                /** @var DocumentSignature $s */
                return "{$s->action}|{$s->signed_role}";
            });

        $qrLecturerUrl = isset($reportSigs['submitted|lecturer'])
            ? URL::signedRoute('signatures.verify', ['documentSignature' => $reportSigs['submitted|lecturer']->id])
            : URL::signedRoute('signatures.verify', ['documentSignature' => Str::uuid()]); // Fallback for legacy

        $qrDeanUrl = isset($reportSigs['approved|dekan'])
            ? URL::signedRoute('signatures.verify', ['documentSignature' => $reportSigs['approved|dekan']->id])
            : null;

        $qrLppmUrl = isset($reportSigs['finalized|kepala_lppm'])
            ? URL::signedRoute('signatures.verify', ['documentSignature' => $reportSigs['finalized|kepala_lppm']->id])
            : null;

        // Generate report content PDF
        $infoPdfContent = Pdf::loadView('pdf.report-export', [
            'proposal' => $proposal->load([
                'submitter.identity.institution',
                'submitter.identity.studyProgram',
                'submitter.identity.faculty',
                'submitter.identity.scienceCluster',
                'teamMembers.identity.institution',
                'teamMembers.identity.studyProgram',
                'teamMembers.identity.faculty',
                'teamMembers.identity.scienceCluster',
                'clusterLevel1',
                'researchScheme',
                'focusArea',
                'theme',
                'topic',
                'keywords',
                'budgetItems.budgetGroup',
                'budgetItems.budgetComponent',
                'partners',
                'outputs',
                'sdgs',
                'signatures',
            ]),
            'report' => $report->load([
                'mandatoryOutputs.proposalOutput',
                'additionalOutputs.proposalOutput',
                'signatures',
            ]),
            'isPreview' => $isPreview,
            'dean_name' => $deanName,
            'dean_id' => $deanId,
            'lppm_head_name' => $lppmHeadName,
            'lppm_head_id' => $lppmHeadId,
            'report_approval_mode' => Setting::where('key', 'report_approval_mode')->value('value') ?? 'digital',
            'dean_signed_at' => $deanSignedAt,
            'lppm_signed_at' => $lppmSignedAt,
            'lecturer_signed_at' => $lecturerSignedAt,
            'qrLecturerUrl' => $qrLecturerUrl,
            'qrDeanUrl' => $qrDeanUrl,
            'qrLppmUrl' => $qrLppmUrl,
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'times-roman',
            ]);

        // Add metadata
        $periodLabel = $report->reporting_period === 'final' ? 'AKHIR' : 'KEMAJUAN';
        $infoPdfContent->getDomPDF()->add_info('Title', 'LAPORAN '.$periodLabel.' - '.$proposal->title);
        $infoPdfContent->getDomPDF()->add_info('Author', $proposal->submitter->name);
        $infoPdfContent->getDomPDF()->add_info('Subject', 'Laporan Program PPM Internal ITSNU Pekalongan');
        $infoPdfContent->getDomPDF()->add_info('Keywords', 'SIM-LPPM, ITSNU, Pekalongan, Laporan, '.$periodLabel);
        $infoPdfContent->getDomPDF()->add_info('Creator', 'SIM-LPPM ITSNU Pekalongan');

        $infoPdfContent = $infoPdfContent->output();

        $tempInfoPath = tempnam($cacheDir, 'report_info_');
        file_put_contents($tempInfoPath, $infoPdfContent);

        $pdf = new Fpdi;

        // Track temp files for cleanup if needed
        $tempFiles = [];

        $pageCount = $pdf->setSourceFile($tempInfoPath);
        for ($i = 1; $i <= $pageCount; $i++) {
            $templateId = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($templateId);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);
        }

        // 1.5. Add pages from the report's uploaded signature page (if exists)
        /** @var ?Media $signaturePage */
        $signaturePage = $report->getFirstMedia('signature_page');
        if ($signaturePage) {
            $signaturePath = $this->getLocalPdfPath($signaturePage, $tempFiles);
            if ($signaturePath !== null && str_contains($signaturePage->mime_type ?? '', 'pdf')) {
                try {
                    $sigPageCount = $pdf->setSourceFile($signaturePath);
                    for ($i = 1; $i <= $sigPageCount; $i++) {
                        $templateId = $pdf->importPage($i);
                        $size = $pdf->getTemplateSize($templateId);
                        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                        $pdf->useTemplate($templateId);
                    }
                } catch (\Throwable $e) {
                    Log::warning('FPDI Merge Fail (Report Signature Page) for '.$report->id.': '.$e->getMessage());
                }
            }
        }

        // 2. Add pages from the report's substance file
        /** @var ?Media $substanceFile */
        $substanceFile = $report->getFirstMedia('substance_file');
        if ($substanceFile) {
            $reportSubstancePath = $this->getLocalPdfPath($substanceFile, $tempFiles);
            if ($reportSubstancePath !== null && str_contains($substanceFile->mime_type ?? '', 'pdf')) {
                try {
                    $substancePageCount = $pdf->setSourceFile($reportSubstancePath);
                    for ($i = 1; $i <= $substancePageCount; $i++) {
                        $templateId = $pdf->importPage($i);
                        $size = $pdf->getTemplateSize($templateId);
                        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                        $pdf->useTemplate($templateId);
                    }
                } catch (\Throwable $e) {
                    Log::warning('FPDI Merge Fail (Report Substance) for '.$report->id.': '.$e->getMessage());
                }
            }
        }

        // 3. Add pages from Realisasi Keterlibatan file
        /** @var ?Media $realizationFile */
        $realizationFile = $report->getFirstMedia('realization_file');
        if ($realizationFile) {
            $realizationPath = $this->getLocalPdfPath($realizationFile, $tempFiles);
            if ($realizationPath !== null && str_contains($realizationFile->mime_type ?? '', 'pdf')) {
                try {
                    $realizationPageCount = $pdf->setSourceFile($realizationPath);
                    for ($i = 1; $i <= $realizationPageCount; $i++) {
                        $templateId = $pdf->importPage($i);
                        $size = $pdf->getTemplateSize($templateId);
                        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                        $pdf->useTemplate($templateId);
                    }
                } catch (\Throwable $e) {
                    Log::warning('FPDI Merge Fail (Realization File) for '.$report->id.': '.$e->getMessage());
                }
            }
        }

        // 4. Add pages from Presentation file (Community Service only)
        if ($proposal->detailable_type === 'App\Models\CommunityService') {
            /** @var ?Media $presentationFile */
            $presentationFile = $report->getFirstMedia('presentation_file');
            if ($presentationFile) {
                $presentationPath = $this->getLocalPdfPath($presentationFile, $tempFiles);
                if ($presentationPath !== null && str_contains($presentationFile->mime_type ?? '', 'pdf')) {
                    try {
                        $presentationPageCount = $pdf->setSourceFile($presentationPath);
                        for ($i = 1; $i <= $presentationPageCount; $i++) {
                            $templateId = $pdf->importPage($i);
                            $size = $pdf->getTemplateSize($templateId);
                            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                            $pdf->useTemplate($templateId);
                        }
                    } catch (\Throwable $e) {
                        Log::warning('FPDI Merge Fail (Presentation File) for '.$report->id.': '.$e->getMessage());
                    }
                }
            }
        }

        // Add pages from output files (if they are PDFs)
        $outputModels = $report->mandatoryOutputs->concat($report->additionalOutputs);
        foreach ($outputModels as $outputRecord) {
            // Check all possible PDF-containing collections for outputs
            $collections = ['journal_article', 'book_document', 'publication_certificate', 'output_file'];

            foreach ($collections as $collection) {
                /** @var ?Media $outputMedia */
                $outputMedia = $outputRecord->getFirstMedia($collection);
                if ($outputMedia) {
                    $outputPath = $this->getLocalPdfPath($outputMedia, $tempFiles);
                    if ($outputPath !== null && str_contains($outputMedia->mime_type ?? '', 'pdf')) {
                        try {
                            $outputPageCount = $pdf->setSourceFile($outputPath);
                            for ($i = 1; $i <= $outputPageCount; $i++) {
                                $templateId = $pdf->importPage($i);
                                $size = $pdf->getTemplateSize($templateId);
                                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                                $pdf->useTemplate($templateId);
                            }
                        } catch (\Throwable $e) {
                            Log::warning("FPDI Merge Fail (Output File - {$collection}) for report ".$report->id.': '.$e->getMessage());
                        }
                    }
                }
            }
        }

        // Add pages from Daily Notes
        $dailyNotes = DailyNote::where('proposal_id', $proposal->id)
            ->with(['budgetGroup', 'media'])
            ->orderBy('activity_date', 'asc')
            ->get();

        if ($dailyNotes->isNotEmpty()) {
            // Prepare submitter information for daily notes view
            $submitterIdentity = $proposal->submitter->identity;
            $submitterFullName = format_name($submitterIdentity?->title_prefix, $proposal->submitter->name, $submitterIdentity?->title_suffix);
            $facultyName = $submitterIdentity?->faculty->name ?? '-';
            $prodiName = $submitterIdentity?->studyProgram->name ?? '-';
            $institutionName = $submitterIdentity?->institution->name ?? 'ITSNU Pekalongan';
            $academicYear = $proposal->start_year.'/'.($proposal->start_year + 1);

            // Get QR URLs for daily notes signatures
            $logbookSigs = $proposal->signatures()
                ->where('variant', 'logbook')
                ->get()
                ->keyBy(function (Model $s) {
                    /** @var DocumentSignature $s */
                    return "{$s->action}|{$s->signed_role}";
                });

            $qrUrlSubmitter = isset($logbookSigs['submitted|lecturer'])
                ? URL::signedRoute('signatures.verify', ['documentSignature' => $logbookSigs['submitted|lecturer']->id])
                : null;

            $qrUrlLppm = isset($logbookSigs['approved|kepala_lppm'])
                ? URL::signedRoute('signatures.verify', ['documentSignature' => $logbookSigs['approved|kepala_lppm']->id])
                : null;

            $notesPdfContent = Pdf::loadView('pdf.daily-notes', [
                'proposal' => $proposal,
                'notes' => $dailyNotes,
                'isSigned' => $proposal->logbook_signed_at !== null,
                'isApproved' => $proposal->logbook_approved_at !== null,
                'logbookApprovalMode' => Setting::where('key', 'logbook_approval_mode')->value('value') ?? 'digital',
                'submitterFullName' => $submitterFullName,
                'facultyName' => $facultyName,
                'prodiName' => $prodiName,
                'institutionName' => $institutionName,
                'academicYear' => $academicYear,
                'docTitle' => 'CATATAN HARIAN '.($proposal->detailable_type === 'App\Models\Research' ? 'PENELITIAN' : 'PENGABDIAN').' INTERNAL',
                'qrUrlSubmitter' => $qrUrlSubmitter,
                'qrUrlLppm' => $qrUrlLppm,
            ])->setPaper('a4', 'portrait')->output();

            $tempNotesPath = tempnam(storage_path('app'), 'report_notes_');
            file_put_contents($tempNotesPath, $notesPdfContent);

            try {
                $notesPageCount = $pdf->setSourceFile($tempNotesPath);
                for ($i = 1; $i <= $notesPageCount; $i++) {
                    $templateId = $pdf->importPage($i);
                    $size = $pdf->getTemplateSize($templateId);
                    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $pdf->useTemplate($templateId);
                }
            } catch (\Throwable $e) {
                Log::warning('FPDI Merge Fail (Daily Notes) for '.$report->id.': '.$e->getMessage());
            }
            @unlink($tempNotesPath);
        }

        $pdf->Output('F', $cachePath);
        @unlink($tempInfoPath);

        // Cleanup temp files
        foreach ($tempFiles as $tempFile) {
            @unlink($tempFile);
        }

        return $cachePath;
    }
}
