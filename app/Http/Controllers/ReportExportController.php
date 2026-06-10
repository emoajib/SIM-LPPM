<?php

namespace App\Http\Controllers;

use App\Actions\Reports\GetPartnerReportQuery;
use App\Enums\ProposalStatus;
use App\Exports\ArchiveDataExport;
use App\Exports\ArchiveTemplateExport;
use App\Exports\CommunityServiceProposalExport;
use App\Exports\CommunityServiceReportExport;
use App\Exports\IkuReportExport;
use App\Exports\MonevRecapExport;
use App\Exports\OutputReportExport;
use App\Exports\PartnerCollaborationExport;
use App\Exports\ResearchProposalExport;
use App\Exports\ResearchReportExport;
use App\Exports\ReviewerReportExport;
use App\Models\CommunityService;
use App\Models\DocumentSignature;
use App\Models\Institution;
use App\Models\InstitutionalReport;
use App\Models\MonevReview;
use App\Models\Partner;
use App\Models\Proposal;
use App\Models\ProposalReviewer;
use App\Models\Research;
use App\Models\ReviewCriteria;
use App\Models\User;
use App\Services\DocumentSignatureService;
use App\Traits\HasIkuCalculations;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ReportExportController extends Controller
{
    use HasIkuCalculations;

    protected function institutionalVariant(?InstitutionalReport $report): ?string
    {
        if (! $report || ! $report->status) {
            return null;
        }

        return (string) $report->status->value === 'approved' ? 'approved' : ((string) $report->status->value === 'submitted' ? 'submitted' : null);
    }

    protected function institutionalPdfCachePath(InstitutionalReport $report, string $variant): string
    {
        return 'institutional-reports/'.$report->id.'/'.$variant.'.pdf';
    }

    protected function upsertInstitutionalSignatures(InstitutionalReport $report, string $variant, string $documentHash): void
    {
        $service = app(DocumentSignatureService::class);
        $kid = $service->currentKid();

        if ($report->submitted_at && $report->submitted_by) {
            $submitted = DocumentSignature::query()
                ->where('document_type', $report->getMorphClass())
                ->where('document_id', $report->id)
                ->where('variant', $variant)
                ->where('action', 'submitted')
                ->where('signed_role', 'kepala_lppm')
                ->first();

            $submittedNonce = (string) (($submitted?->payload['nonce'] ?? null) ?: Str::random(32));
            $submittedPayload = [
                'ver' => 1,
                'doc_type' => 'institutional_report',
                'doc_id' => (string) $report->id,
                'doc_year' => (int) $report->year,
                'doc_category' => (string) $report->type,
                'variant' => $variant,
                'action' => 'submitted',
                'signed_role' => 'kepala_lppm',
                'signed_by' => (string) $report->submitted_by,
                'signed_at' => $report->submitted_at->copy()->utc()->toIso8601ZuluString(),
                'pdf_hash_alg' => 'SHA-256',
                'pdf_hash' => $documentHash,
                'kid' => $kid,
                'nonce' => $submittedNonce,
            ];

            $submittedSig = $service->signPayload($submittedPayload, $kid);

            DocumentSignature::updateOrCreate(
                [
                    'document_type' => $report->getMorphClass(),
                    'document_id' => (string) $report->id,
                    'variant' => $variant,
                    'action' => 'submitted',
                    'signed_role' => 'kepala_lppm',
                ],
                [
                    'action' => 'submitted',
                    'signed_by' => (string) $report->submitted_by,
                    'signed_at' => $report->submitted_at,
                    'hash_alg' => 'sha256',
                    'document_hash' => $documentHash,
                    'kid' => $kid,
                    'signature' => $submittedSig,
                    'payload' => $submittedPayload,
                ]
            );
        }

        if ($variant === 'approved' && $report->approved_at && $report->approved_by) {
            $approved = DocumentSignature::query()
                ->where('document_type', $report->getMorphClass())
                ->where('document_id', $report->id)
                ->where('variant', $variant)
                ->where('action', 'approved')
                ->where('signed_role', 'rektor')
                ->first();

            $approvedNonce = (string) (($approved?->payload['nonce'] ?? null) ?: Str::random(32));
            $approvedPayload = [
                'ver' => 1,
                'doc_type' => 'institutional_report',
                'doc_id' => (string) $report->id,
                'doc_year' => (int) $report->year,
                'doc_category' => (string) $report->type,
                'variant' => $variant,
                'action' => 'approved',
                'signed_role' => 'rektor',
                'signed_by' => (string) $report->approved_by,
                'signed_at' => $report->approved_at->copy()->utc()->toIso8601ZuluString(),
                'pdf_hash_alg' => 'SHA-256',
                'pdf_hash' => $documentHash,
                'kid' => $kid,
                'nonce' => $approvedNonce,
            ];

            $approvedSig = $service->signPayload($approvedPayload, $kid);

            DocumentSignature::updateOrCreate(
                [
                    'document_type' => $report->getMorphClass(),
                    'document_id' => (string) $report->id,
                    'variant' => $variant,
                    'action' => 'approved',
                    'signed_role' => 'rektor',
                ],
                [
                    'action' => 'approved',
                    'signed_by' => (string) $report->approved_by,
                    'signed_at' => $report->approved_at,
                    'hash_alg' => 'sha256',
                    'document_hash' => $documentHash,
                    'kid' => $kid,
                    'signature' => $approvedSig,
                    'payload' => $approvedPayload,
                ]
            );
        }
    }

    protected function pdfDownloadResponse(string $pdfBinary, string $filename): Response
    {
        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    protected function pdfInlineResponse(string $pdfBinary, string $filename): Response
    {
        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function ikuPdf(Request $request)
    {

        try {
            $period = $request->query('period', date('Y'));
            $ikuMetrics = $this->getIkuMetrics($period);

            $institution = Institution::first();
            $rektor = User::role('rektor')->with('identity')->first();
            $lppmHead = User::role('kepala lppm')->with('identity')->first();

            $institutionalReport = InstitutionalReport::where('type', 'iku')
                ->where('year', $period)
                ->first();

            $isPreview = $request->boolean('preview');
            $pdf = Pdf::loadView('reports.iku-report-pdf', [
                'ikuMetrics' => $ikuMetrics,
                'period' => $period,
                'institution' => $institution,
                'rektor' => $rektor,
                'lppmHead' => $lppmHead,
                'institutionalReport' => $institutionalReport,
                'isPreview' => $isPreview,
            ])->setPaper('a4', 'portrait');

            $filename = ($isPreview ? 'PREVIEW-' : '').'laporan-rekap-iku-'.$period.'-'.now()->format('YmdHis').'.pdf';

            if ($isPreview) {
                return $this->pdfInlineResponse($pdf->output(), $filename);
            }

            $variant = $this->institutionalVariant($institutionalReport);
            if (! $institutionalReport || ! $variant) {
                return $this->pdfDownloadResponse($pdf->output(), $filename);
            }

            $cachePath = $this->institutionalPdfCachePath($institutionalReport, $variant);
            $pdfBinary = Storage::disk('local')->exists($cachePath)
                ? Storage::disk('local')->get($cachePath)
                : $pdf->output();

            if (! Storage::disk('local')->exists($cachePath)) {
                Storage::disk('local')->put($cachePath, $pdfBinary);
            }

            $hash = hash('sha256', $pdfBinary);
            $this->upsertInstitutionalSignatures($institutionalReport, $variant, $hash);

            return $this->pdfDownloadResponse($pdfBinary, $filename);
        } catch (\Exception $e) {

            Log::error('IKU PDF Export Error: '.$e->getMessage());

            return back()->with('error', 'Gagal mengunduh PDF: '.$e->getMessage());
        }
    }

    public function ikuExcel(Request $request)
    {

        try {
            $period = $request->query('period', date('Y'));

            $download = Excel::download(
                new IkuReportExport($period),
                'laporan-rekap-iku-'.$period.'-'.now()->format('YmdHis').'.xlsx'
            );

            return $download;
        } catch (\Exception $e) {

            Log::error('IKU Excel Export Error: '.$e->getMessage());

            return back()->with('error', 'Gagal mengunduh Excel: '.$e->getMessage());
        }
    }

    // ====== PENELITIAN ======
    public function researchPdf(Request $request)
    {

        try {
            $user = Auth::user();
            $period = $request->query('period', date('Y'));
            $search = $request->query('search');
            $scheme = $request->query('scheme');
            $faculty = $request->query('faculty');

            if ($user->hasRole('dekan')) {
                $faculty = (string) ($user->identity->faculty_id ?? $faculty);
            }
            $isPreview = $request->boolean('preview');

            $proposals = Proposal::query()
                ->where('detailable_type', 'App\Models\Research')
                ->where('start_year', $period)
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('title', 'like', "%{$search}%")
                            ->orWhereHas('submitter', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                    });
                })
                ->when($scheme && $scheme !== 'all', fn ($q) => $q->where('research_scheme_id', $scheme))
                ->when($faculty && $faculty !== 'all', function ($q) use ($faculty) {
                    $q->whereHas('submitter.identity', fn ($i) => $i->where('faculty_id', $faculty));
                })
                ->with(['submitter.identity.faculty', 'submitter.identity.studyProgram', 'researchScheme', 'budgetItems'])
                ->latest()
                ->get();

            $institutionalReport = InstitutionalReport::where('type', 'research')
                ->where('year', $period)
                ->first();

            $institution = Institution::first();
            $rektor = User::role('rektor')->with('identity')->first();
            $lppmHead = User::role('kepala lppm')->with('identity')->first();

            $pdf = Pdf::loadView('reports.research-pdf', [
                'proposals' => $proposals,
                'period' => $period,
                'institution' => $institution,
                'rektor' => $rektor,
                'lppmHead' => $lppmHead,
                'institutionalReport' => $institutionalReport,
                'isPreview' => $isPreview,
            ])->setPaper('a4', 'landscape');

            $filename = ($isPreview ? 'PREVIEW-' : '').'laporan-penelitian-'.$period.'-'.now()->format('YmdHis').'.pdf';

            if ($isPreview) {
                return $this->pdfInlineResponse($pdf->output(), $filename);
            }

            $variant = $this->institutionalVariant($institutionalReport);
            if (! $institutionalReport || ! $variant) {
                return $this->pdfDownloadResponse($pdf->output(), $filename);
            }

            $cachePath = $this->institutionalPdfCachePath($institutionalReport, $variant);
            $pdfBinary = Storage::disk('local')->exists($cachePath)
                ? Storage::disk('local')->get($cachePath)
                : $pdf->output();

            if (! Storage::disk('local')->exists($cachePath)) {
                Storage::disk('local')->put($cachePath, $pdfBinary);
            }

            $hash = hash('sha256', $pdfBinary);
            $this->upsertInstitutionalSignatures($institutionalReport, $variant, $hash);

            return $this->pdfDownloadResponse($pdfBinary, $filename);
        } catch (\Exception $e) {

            Log::error('Research PDF Export Error: '.$e->getMessage());

            return back()->with('error', 'Gagal mengunduh PDF: '.$e->getMessage());
        }
    }

    public function researchExcel(Request $request)
    {

        try {
            $user = Auth::user();
            $period = $request->query('period', date('Y'));
            $search = $request->query('search');
            $scheme = $request->query('scheme');
            $faculty = $request->query('faculty');

            if ($user->hasRole('dekan')) {
                $faculty = (string) ($user->identity->faculty_id ?? $faculty);
            }

            $download = Excel::download(
                new ResearchReportExport($period, $search, $scheme, $faculty),
                'laporan-penelitian-'.$period.'-'.now()->format('YmdHis').'.xlsx'
            );

            return $download;
        } catch (\Exception $e) {

            Log::error('Research Excel Export Error: '.$e->getMessage());

            return back()->with('error', 'Gagal mengunduh Excel: '.$e->getMessage());
        }
    }

    // ====== PENGABDIAN (PKM) ======
    public function pkmPdf(Request $request)
    {

        try {
            $user = Auth::user();
            $period = $request->query('period', date('Y'));
            $search = $request->query('search');
            $scheme = $request->query('scheme');
            $faculty = $request->query('faculty');

            if ($user->hasRole('dekan')) {
                $faculty = (string) ($user->identity->faculty_id ?? $faculty);
            }
            $isPreview = $request->boolean('preview');

            $proposals = Proposal::query()
                ->where('detailable_type', 'App\Models\CommunityService')
                ->where('start_year', $period)
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('title', 'like', "%{$search}%")
                            ->orWhereHas('submitter', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                    });
                })
                ->when($scheme && $scheme !== 'all', fn ($q) => $q->where('community_service_scheme_id', $scheme))
                ->when($faculty && $faculty !== 'all', function ($q) use ($faculty) {
                    $q->whereHas('submitter.identity', fn ($i) => $i->where('faculty_id', $faculty));
                })
                ->with(['submitter.identity.faculty', 'submitter.identity.studyProgram', 'communityServiceScheme', 'budgetItems'])
                ->latest()
                ->get();

            $institutionalReport = InstitutionalReport::where('type', 'pkm')
                ->where('year', $period)
                ->first();

            $institution = Institution::first();
            $rektor = User::role('rektor')->with('identity')->first();
            $lppmHead = User::role('kepala lppm')->with('identity')->first();

            $pdf = Pdf::loadView('reports.community-service-pdf', [
                'proposals' => $proposals,
                'period' => $period,
                'institution' => $institution,
                'rektor' => $rektor,
                'lppmHead' => $lppmHead,
                'institutionalReport' => $institutionalReport,
                'isPreview' => $isPreview,
            ])->setPaper('a4', 'landscape');

            $filename = ($isPreview ? 'PREVIEW-' : '').'laporan-pkm-'.$period.'-'.now()->format('YmdHis').'.pdf';

            if ($isPreview) {
                return $this->pdfInlineResponse($pdf->output(), $filename);
            }

            $variant = $this->institutionalVariant($institutionalReport);
            if (! $institutionalReport || ! $variant) {
                return $this->pdfDownloadResponse($pdf->output(), $filename);
            }

            $cachePath = $this->institutionalPdfCachePath($institutionalReport, $variant);
            $pdfBinary = Storage::disk('local')->exists($cachePath)
                ? Storage::disk('local')->get($cachePath)
                : $pdf->output();

            if (! Storage::disk('local')->exists($cachePath)) {
                Storage::disk('local')->put($cachePath, $pdfBinary);
            }

            $hash = hash('sha256', $pdfBinary);
            $this->upsertInstitutionalSignatures($institutionalReport, $variant, $hash);

            return $this->pdfDownloadResponse($pdfBinary, $filename);
        } catch (\Exception $e) {

            Log::error('PKM PDF Export Error: '.$e->getMessage());

            return back()->with('error', 'Gagal mengunduh PDF: '.$e->getMessage());
        }
    }

    public function pkmExcel(Request $request)
    {

        try {
            $user = Auth::user();
            $period = $request->query('period', date('Y'));
            $search = $request->query('search');
            $scheme = $request->query('scheme');
            $faculty = $request->query('faculty');

            if ($user->hasRole('dekan')) {
                $faculty = (string) ($user->identity->faculty_id ?? $faculty);
            }

            $download = Excel::download(
                new CommunityServiceReportExport($period, $search, $scheme, $faculty),
                'laporan-pkm-'.$period.'-'.now()->format('YmdHis').'.xlsx'
            );

            return $download;
        } catch (\Exception $e) {

            Log::error('PKM Excel Export Error: '.$e->getMessage());

            return back()->with('error', 'Gagal mengunduh Excel: '.$e->getMessage());
        }
    }

    // ====== LUARAN ======
    public function outputPdf(Request $request)
    {

        try {
            $user = Auth::user();
            $activeTab = $request->query('activeTab', 'research');
            $search = $request->query('search', '');
            $outputType = $request->query('outputType', 'all');
            $period = $request->query('period', date('Y'));
            $scheme = $request->query('scheme');
            $faculty = $request->query('faculty');

            if ($user->hasRole('dekan')) {
                $faculty = (string) ($user->identity->faculty_id ?? $faculty);
            }
            $isPreview = $request->boolean('preview');

            $proposals = $this->getOutputProposalsQuery($activeTab, $search, $outputType, $period, $scheme, $faculty)->get();

            $institutionalReport = InstitutionalReport::where('type', 'output')
                ->where('year', $period)
                ->first();

            $institution = Institution::first();
            $rektor = User::role('rektor')->with('identity')->first();
            $lppmHead = User::role('kepala lppm')->with('identity')->first();

            $pdf = Pdf::loadView('reports.output-reports-pdf', [
                'proposals' => $proposals,
                'activeTab' => $activeTab,
                'outputType' => $outputType,
                'period' => $period,
                'institution' => $institution,
                'rektor' => $rektor,
                'lppmHead' => $lppmHead,
                'institutionalReport' => $institutionalReport,
                'isPreview' => $isPreview,
            ])->setPaper('a4', 'landscape');

            $filename = ($isPreview ? 'PREVIEW-' : '').'laporan-luaran-'.$activeTab.'-'.now()->format('YmdHis').'.pdf';

            if ($isPreview) {
                return $this->pdfInlineResponse($pdf->output(), $filename);
            }

            $variant = $this->institutionalVariant($institutionalReport);
            if (! $institutionalReport || ! $variant) {
                return $this->pdfDownloadResponse($pdf->output(), $filename);
            }

            $cachePath = $this->institutionalPdfCachePath($institutionalReport, $variant);
            $pdfBinary = Storage::disk('local')->exists($cachePath)
                ? Storage::disk('local')->get($cachePath)
                : $pdf->output();

            if (! Storage::disk('local')->exists($cachePath)) {
                Storage::disk('local')->put($cachePath, $pdfBinary);
            }

            $hash = hash('sha256', $pdfBinary);
            $this->upsertInstitutionalSignatures($institutionalReport, $variant, $hash);

            return $this->pdfDownloadResponse($pdfBinary, $filename);
        } catch (\Exception $e) {

            Log::error('Output PDF Export Error: '.$e->getMessage());

            return back()->with('error', 'Gagal mengunduh PDF: '.$e->getMessage());
        }
    }

    public function outputExcel(Request $request)
    {

        try {
            $user = Auth::user();
            $activeTab = $request->query('activeTab', 'research');
            $search = $request->query('search', '');
            $outputType = $request->query('outputType', 'all');
            $period = $request->query('period', date('Y'));
            $scheme = $request->query('scheme');
            $faculty = $request->query('faculty');

            if ($user->hasRole('dekan')) {
                $faculty = (string) ($user->identity->faculty_id ?? $faculty);
            }

            $download = Excel::download(
                new OutputReportExport($activeTab, $search, $outputType, $period, $scheme, $faculty),
                'laporan-luaran-'.$activeTab.'-'.now()->format('YmdHis').'.xlsx'
            );

            return $download;
        } catch (\Exception $e) {

            Log::error('Output Excel Export Error: '.$e->getMessage());

            return back()->with('error', 'Gagal mengunduh Excel: '.$e->getMessage());
        }
    }

    protected function getOutputProposalsQuery($activeTab, $search, $outputType, $period = null, $scheme = null, $faculty = null)
    {
        $detailableType = $activeTab === 'research' ? 'App\\Models\\Research' : 'App\\Models\\CommunityService';

        $query = Proposal::query()
            ->with(['submitter.identity.faculty', 'submitter.identity.studyProgram', 'progressReports.mandatoryOutputs.proposalOutput', 'progressReports.additionalOutputs.proposalOutput'])
            ->where('detailable_type', $detailableType)
            ->when($period, fn ($q) => $q->where('start_year', $period))
            ->where(function (Builder $query) {
                $query->whereHas('progressReports.mandatoryOutputs')
                    ->orWhereHas('progressReports.additionalOutputs');
            });

        if ($search) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('submitter', function (Builder $u) use ($search) {
                        $u->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $schemeColumn = $activeTab === 'research' ? 'research_scheme_id' : 'community_service_scheme_id';

        if ($scheme && $scheme !== 'all') {
            $query->where($schemeColumn, $scheme);
        }

        if ($faculty && $faculty !== 'all') {
            $query->whereHas('submitter.identity', function ($q) use ($faculty) {
                $q->where('faculty_id', $faculty);
            });
        }

        if ($outputType === 'mandatory') {
            $query->whereHas('progressReports.mandatoryOutputs');
        } elseif ($outputType === 'additional') {
            $query->whereHas('progressReports.additionalOutputs');
        }

        return $query->latest();
    }

    // ====== MITRA ======
    public function partnerPdf(Request $request, GetPartnerReportQuery $action)
    {

        try {
            $user = Auth::user();
            $facultyId = $user->hasRole('dekan') ? $user->identity?->faculty_id : null;
            $search = $request->query('search', '');
            $typeFilter = $request->query('typeFilter', '');
            $periodFilter = $request->query('periodFilter', '');

            $partners = $action->handle($search, $typeFilter, $periodFilter, $facultyId !== null ? (string) $facultyId : null)->get();

            // Vetted by AI - Manual Review Required by Senior Engineer/Manager
            $partners->each(function (Partner $partner) use ($facultyId, $periodFilter) {
                $proposals = $partner->proposals()
                    ->whereIn('status', [ProposalStatus::APPROVED->value, ProposalStatus::COMPLETED->value])
                    ->when($periodFilter, fn ($q) => $q->where('start_year', $periodFilter))
                    ->when($facultyId, fn ($q) => $q->whereHas('submitter.identity', fn ($i) => $i->where('faculty_id', $facultyId)))
                    ->with(['budgetItems'])
                    ->get();

                $partner->total_budget = $proposals->sum(function ($p) {
                    /** @var Proposal $p */
                    return ($p->sbk_value && $p->sbk_value > 0)
                        ? (float) $p->sbk_value
                        : $p->budgetItems->sum('total_price');
                });
            });

            $institutionalReport = InstitutionalReport::where('type', 'partner')
                ->where('year', $periodFilter ?: date('Y'))
                ->first();

            $institution = Institution::first();
            $rektor = User::role('rektor')->with('identity')->first();
            $lppmHead = User::role('kepala lppm')->with('identity')->first();

            $isPreview = $request->boolean('preview');
            $pdf = Pdf::loadView('reports.partner-collaboration-pdf', [
                'partners' => $partners,
                'periodFilter' => $periodFilter,
                'typeFilter' => $typeFilter,
                'institution' => $institution,
                'rektor' => $rektor,
                'lppmHead' => $lppmHead,
                'institutionalReport' => $institutionalReport,
                'isPreview' => $isPreview,
            ])->setPaper('a4', 'landscape');

            $filename = ($isPreview ? 'PREVIEW-' : '').'laporan-mitra-'.now()->format('Y-m-d').'.pdf';

            if ($isPreview) {
                return $this->pdfInlineResponse($pdf->output(), $filename);
            }

            $variant = $this->institutionalVariant($institutionalReport);
            if (! $institutionalReport || ! $variant) {
                return $this->pdfDownloadResponse($pdf->output(), $filename);
            }

            $cachePath = $this->institutionalPdfCachePath($institutionalReport, $variant);
            $pdfBinary = Storage::disk('local')->exists($cachePath)
                ? Storage::disk('local')->get($cachePath)
                : $pdf->output();

            if (! Storage::disk('local')->exists($cachePath)) {
                Storage::disk('local')->put($cachePath, $pdfBinary);
            }

            $hash = hash('sha256', $pdfBinary);
            $this->upsertInstitutionalSignatures($institutionalReport, $variant, $hash);

            return $this->pdfDownloadResponse($pdfBinary, $filename);
        } catch (\Exception $e) {

            Log::error('Partner PDF Export Error: '.$e->getMessage());

            return back()->with('error', 'Gagal mengunduh PDF: '.$e->getMessage());
        }
    }

    public function partnerExcel(Request $request)
    {

        try {
            $user = Auth::user();
            $facultyId = $user->hasRole('dekan') ? $user->identity?->faculty_id : null;
            $search = $request->query('search', '');
            $typeFilter = $request->query('typeFilter', '');
            $periodFilter = $request->query('periodFilter', '');

            $download = Excel::download(
                new PartnerCollaborationExport($search, $typeFilter, $periodFilter, $facultyId !== null ? (string) $facultyId : null),
                'laporan-mitra-'.now()->format('Y-m-d').'.xlsx'
            );

            return $download;
        } catch (\Exception $e) {

            Log::error('Partner Excel Export Error: '.$e->getMessage());

            return back()->with('error', 'Gagal mengunduh Excel: '.$e->getMessage());
        }
    }

    // ====== ARSIP ======
    public function archiveExport(Request $request)
    {

        try {
            $search = $request->query('search', '');
            $yearFilter = $request->query('yearFilter', '');
            $filename = 'Export_Arsip_Kegiatan_'.date('Ymd_His').'.xlsx';

            $download = Excel::download(
                new ArchiveDataExport($search, $yearFilter),
                $filename
            );

            return $download;
        } catch (\Exception $e) {

            Log::error('Archive Export Error: '.$e->getMessage());

            return back()->with('error', 'Gagal mengunduh Export: '.$e->getMessage());
        }
    }

    public function archiveTemplate()
    {

        try {
            $download = Excel::download(
                new ArchiveTemplateExport,
                'template_import_arsip.xlsx'
            );

            return $download;
        } catch (\Exception $e) {

            Log::error('Archive Template Export Error: '.$e->getMessage());

            return back()->with('error', 'Gagal mengunduh Template: '.$e->getMessage());
        }
    }

    public function dashboardResearchExport(Request $request)
    {

        try {
            $period = $request->query('period', date('Y'));
            $scheme = $request->query('scheme');
            $filename = "research-proposals-{$period}.xlsx";

            $download = Excel::download(
                new ResearchProposalExport((int) $period, $scheme),
                $filename
            );

            return $download;
        } catch (\Exception $e) {

            Log::error('Dashboard Research Export Error: '.$e->getMessage());

            return back()->with('error', 'Gagal mengunduh Export: '.$e->getMessage());
        }
    }

    public function dashboardCommunityServiceExport(Request $request)
    {
        try {
            $period = $request->query('period', date('Y'));
            $scheme = $request->query('scheme');
            $filename = "community-service-proposals-{$period}.xlsx";

            $download = Excel::download(
                new CommunityServiceProposalExport((int) $period, $scheme),
                $filename
            );

            return $download;
        } catch (\Exception $e) {

            Log::error('Dashboard Community Service Export Error: '.$e->getMessage());

            return back()->with('error', 'Gagal mengunduh Export: '.$e->getMessage());
        }
    }

    /**
     * Export Monev Recap to Excel.
     */
    public function monevRecapExcel(Request $request)
    {
        $request->validate([
            'academic_year' => 'required|string',
            'semester' => 'nullable|in:ganjil,genap',
        ]);

        $academicYear = $request->academic_year;
        $semester = $request->semester;

        $fileName = "Monev_Recap_{$academicYear}_".($semester ?? 'all').'.xlsx';

        return Excel::download(new MonevRecapExport($academicYear, $semester), $fileName);
    }

    /**
     * Export Digital Monev Berita Acara (BA) to PDF.
     */
    public function monevBaPdf(Request $request, string $id)
    {
        try {
            $review = MonevReview::query()->with([
                'proposal.submitter.identity.faculty',
                'proposal.submitter.identity.studyProgram',
                'proposal.researchScheme',
                'proposal.communityServiceScheme',
                'proposal.focusArea',
                'proposal.progressReports.mandatoryOutputs.proposalOutput',
                'proposal.progressReports.additionalOutputs.proposalOutput',
                'proposal.progressReports.media',
                'reviewer.identity',
            ])->whereKey($id)->firstOrFail();

            // Determine active report based on period
            $activeReport = $review->proposal->progressReports
                ->filter(function ($report) use ($review) {
                    $period = strtolower((string) $review->semester) === 'ganjil' ? 'semester_1' : 'semester_2';

                    return (string) $report->reporting_year === (string) $review->academic_year
                        && (string) $report->reporting_period === $period;
                })
                ->first() ?? $review->proposal->progressReports->where('reporting_period', 'final')->first();

            // Access check: only Admin LPPM or the Assignee Reviewer can download
            $user = Auth::user();
            if (! $user instanceof User) {
                abort(403);
            }

            if (! $user->hasRole(['admin lppm', 'kepala lppm', 'superadmin']) && $user->id !== $review->reviewer_id) {
                abort(403);
            }

            $type = $review->proposal->detailable_type === Research::class
                ? 'monev_research'
                : 'monev_community_service';

            $criteria = ReviewCriteria::where('type', $type)
                ->where('is_active', true)
                ->orderBy('order')
                ->get();

            $isPreview = $request->boolean('preview');
            $variant = 'final';
            $cachePath = 'monev-ba/'.$review->id.'/'.$variant.'.pdf';

            $needsReviewer = (bool) $review->reviewed_at;
            $needsAdmin = (bool) $review->finalized_by_lppm_at;
            $needsKepala = (bool) $review->approved_by_kepala_at;

            $existing = DocumentSignature::query()
                ->where('document_type', $review->getMorphClass())
                ->where('document_id', (string) $review->id)
                ->where('variant', $variant)
                ->get()
                ->keyBy(fn (DocumentSignature $s) => $s->action.'|'.$s->signed_role);

            $hasReviewerSig = ! $needsReviewer || $existing->has('reviewed|reviewer');
            $hasAdminSig = ! $needsAdmin || $existing->has('finalized|admin_lppm');
            $hasKepalaSig = ! $needsKepala || $existing->has('approved|kepala_lppm');

            $shouldUseCache = ! $isPreview
                && Storage::disk('local')->exists($cachePath)
                && $hasReviewerSig
                && $hasAdminSig
                && $hasKepalaSig;

            $signatureService = app(DocumentSignatureService::class);
            $kid = $signatureService->currentKid();

            $reviewerSig = $needsReviewer
                ? ($existing->get('reviewed|reviewer') ?? DocumentSignature::create([
                    'id' => (string) Str::uuid(),
                    'document_type' => $review->getMorphClass(),
                    'document_id' => (string) $review->id,
                    'variant' => $variant,
                    'action' => 'reviewed',
                    'signed_role' => 'reviewer',
                    'signed_by' => (string) $review->reviewer_id,
                    'signed_at' => $review->reviewed_at,
                    'kid' => $kid,
                    'signature' => Str::random(64),
                    'payload' => ['ver' => 1, 'nonce' => Str::random(32)],
                ]))
                : null;

            $adminSig = $needsAdmin
                ? ($existing->get('finalized|admin_lppm') ?? DocumentSignature::create([
                    'id' => (string) Str::uuid(),
                    'document_type' => $review->getMorphClass(),
                    'document_id' => (string) $review->id,
                    'variant' => $variant,
                    'action' => 'finalized',
                    'signed_role' => 'admin_lppm',
                    'signed_by' => $review->finalized_by_lppm_by,
                    'signed_at' => $review->finalized_by_lppm_at,
                    'kid' => $kid,
                    'signature' => Str::random(64),
                    'payload' => ['ver' => 1, 'nonce' => Str::random(32)],
                ]))
                : null;

            $kepalaSig = $needsKepala
                ? ($existing->get('approved|kepala_lppm') ?? DocumentSignature::create([
                    'id' => (string) Str::uuid(),
                    'document_type' => $review->getMorphClass(),
                    'document_id' => (string) $review->id,
                    'variant' => $variant,
                    'action' => 'approved',
                    'signed_role' => 'kepala_lppm',
                    'signed_by' => $review->approved_by_kepala_by,
                    'signed_at' => $review->approved_by_kepala_at,
                    'kid' => $kid,
                    'signature' => Str::random(64),
                    'payload' => ['ver' => 1, 'nonce' => Str::random(32)],
                ]))
                : null;

            $qrReviewerUrl = $reviewerSig
                ? URL::signedRoute('signatures.verify', ['documentSignature' => $reviewerSig->id])
                : null;
            $qrAdminUrl = $adminSig
                ? URL::signedRoute('signatures.verify', ['documentSignature' => $adminSig->id])
                : null;
            $qrKepalaUrl = $kepalaSig
                ? URL::signedRoute('signatures.verify', ['documentSignature' => $kepalaSig->id])
                : null;

            $filename = 'Berita_Acara_Monev_'.str_replace(' ', '_', $review->proposal->title).'_'.now()->format('YmdHi').'.pdf';

            if ($shouldUseCache) {
                $pdfBinary = Storage::disk('local')->get($cachePath);

                return $this->pdfInlineResponse($pdfBinary, $filename);
            }

            $pdf = Pdf::loadView('reports.monev-ba-pdf', [
                'review' => $review,
                'criteria' => $criteria,
                'activeReport' => $activeReport,
                'institution' => Institution::first(),
                'isPreview' => $isPreview,
                'qrReviewerUrl' => $qrReviewerUrl,
                'qrAdminUrl' => $qrAdminUrl,
                'qrKepalaUrl' => $qrKepalaUrl,
                'generatedAt' => now(),
            ])->setPaper('a4', 'portrait');

            $pdfBinary = $pdf->output();

            if ($isPreview) {
                return $this->pdfInlineResponse($pdfBinary, $filename);
            }

            Storage::disk('local')->put($cachePath, $pdfBinary);

            $hash = hash('sha256', $pdfBinary);

            if ($reviewerSig && $review->reviewed_at) {
                $payload = [
                    'ver' => 1,
                    'doc_type' => 'monev_ba',
                    'doc_id' => (string) $review->id,
                    'variant' => $variant,
                    'action' => 'reviewed',
                    'signed_role' => 'reviewer',
                    'signed_by' => (string) $review->reviewer_id,
                    'signed_at' => $review->reviewed_at->copy()->utc()->toIso8601ZuluString(),
                    'pdf_hash_alg' => 'SHA-256',
                    'pdf_hash' => $hash,
                    'kid' => $kid,
                    'nonce' => (string) ($reviewerSig->payload['nonce'] ?? Str::random(32)),
                ];

                $reviewerSig->update([
                    'signed_by' => (string) $review->reviewer_id,
                    'signed_at' => $review->reviewed_at,
                    'hash_alg' => 'sha256',
                    'document_hash' => $hash,
                    'kid' => $kid,
                    'payload' => $payload,
                    'signature' => $signatureService->signPayload($payload, $kid),
                ]);
            }

            if ($adminSig && $review->finalized_by_lppm_at) {
                $payload = [
                    'ver' => 1,
                    'doc_type' => 'monev_ba',
                    'doc_id' => (string) $review->id,
                    'variant' => $variant,
                    'action' => 'finalized',
                    'signed_role' => 'admin_lppm',
                    'signed_by' => (string) ($review->finalized_by_lppm_by ?? ''),
                    'signed_at' => $review->finalized_by_lppm_at->copy()->utc()->toIso8601ZuluString(),
                    'pdf_hash_alg' => 'SHA-256',
                    'pdf_hash' => $hash,
                    'kid' => $kid,
                    'nonce' => (string) ($adminSig->payload['nonce'] ?? Str::random(32)),
                ];

                $adminSig->update([
                    'signed_by' => $review->finalized_by_lppm_by,
                    'signed_at' => $review->finalized_by_lppm_at,
                    'hash_alg' => 'sha256',
                    'document_hash' => $hash,
                    'kid' => $kid,
                    'payload' => $payload,
                    'signature' => $signatureService->signPayload($payload, $kid),
                ]);
            }

            if ($kepalaSig && $review->approved_by_kepala_at) {
                $payload = [
                    'ver' => 1,
                    'doc_type' => 'monev_ba',
                    'doc_id' => (string) $review->id,
                    'variant' => $variant,
                    'action' => 'approved',
                    'signed_role' => 'kepala_lppm',
                    'signed_by' => (string) ($review->approved_by_kepala_by ?? ''),
                    'signed_at' => $review->approved_by_kepala_at->copy()->utc()->toIso8601ZuluString(),
                    'pdf_hash_alg' => 'SHA-256',
                    'pdf_hash' => $hash,
                    'kid' => $kid,
                    'nonce' => (string) ($kepalaSig->payload['nonce'] ?? Str::random(32)),
                ];

                $kepalaSig->update([
                    'signed_by' => $review->approved_by_kepala_by,
                    'signed_at' => $review->approved_by_kepala_at,
                    'hash_alg' => 'sha256',
                    'document_hash' => $hash,
                    'kid' => $kid,
                    'payload' => $payload,
                    'signature' => $signatureService->signPayload($payload, $kid),
                ]);
            }

            return $this->pdfInlineResponse($pdfBinary, $filename);
        } catch (\Exception $e) {
            Log::error('Monev BA PDF Export Error: '.$e->getMessage());

            return back()->with('error', 'Gagal menghasilkan Berita Acara: '.$e->getMessage());
        }
    }

    /**
     * Export Collective Monev Report to PDF for Institutional Monitoring.
     */
    public function monevPdf(Request $request)
    {
        try {
            $period = $request->query('academic_year', date('Y'));
            $semester = $request->query('semester', 'all');
            $isPreview = $request->boolean('preview');

            $reviews = MonevReview::query()
                ->with(['proposal.submitter.identity', 'reviewer.identity'])
                ->where('academic_year', $period)
                ->when($semester !== 'all', function ($query) use ($semester) {
                    $query->where('semester', $semester);
                })
                ->latest()
                ->get();

            $institutionalReport = InstitutionalReport::where('type', 'monev')
                ->where('year', $period)
                ->when($semester !== 'all', function ($q) use ($semester) {
                    $q->where('metadata->semester', $semester);
                })
                ->first();

            $institution = Institution::first();
            $rektor = User::role('rektor')->with('identity')->first();
            $lppmHead = User::role('kepala lppm')->with('identity')->first();

            $pdf = Pdf::loadView('reports.monev-pdf', [
                'reviews' => $reviews,
                'period' => $period,
                'semester' => $semester,
                'institution' => $institution,
                'rektor' => $rektor,
                'lppmHead' => $lppmHead,
                'institutionalReport' => $institutionalReport,
                'isPreview' => $isPreview,
            ])->setPaper('a4', 'portrait');

            $filename = ($isPreview ? 'PREVIEW-' : '').'laporan-rekap-monev-'.$period.'-'.now()->format('YmdHis').'.pdf';

            if ($isPreview) {
                return $this->pdfInlineResponse($pdf->output(), $filename);
            }

            $variant = $this->institutionalVariant($institutionalReport);
            if (! $institutionalReport || ! $variant) {
                return $this->pdfDownloadResponse($pdf->output(), $filename);
            }

            $cachePath = $this->institutionalPdfCachePath($institutionalReport, $variant);
            $pdfBinary = Storage::disk('local')->exists($cachePath)
                ? Storage::disk('local')->get($cachePath)
                : $pdf->output();

            if (! Storage::disk('local')->exists($cachePath)) {
                Storage::disk('local')->put($cachePath, $pdfBinary);
            }

            $hash = hash('sha256', $pdfBinary);
            $this->upsertInstitutionalSignatures($institutionalReport, $variant, $hash);

            return $this->pdfDownloadResponse($pdfBinary, $filename);
        } catch (\Exception $e) {
            Log::error('Monev PDF Export Error: '.$e->getMessage());

            return back()->with('error', 'Gagal mengunduh PDF: '.$e->getMessage());
        }
    }

    // Vetted by AI - Manual Review Required by Senior Engineer/Manager
    public function reviewerPdf(Request $request)
    {
        try {
            $period = $request->query('period', date('Y'));
            $search = $request->query('search');
            $type = $request->query('type', 'all');
            $year = (int) $period;

            // Query proposals
            $proposalsQuery = Proposal::query()
                ->whereIn('status', [
                    ProposalStatus::WAITING_REVIEWER,
                    ProposalStatus::UNDER_REVIEW,
                    ProposalStatus::REVIEWED,
                    ProposalStatus::APPROVED,
                    ProposalStatus::COMPLETED,
                ])
                ->when($year, fn ($q) => $q->whereYear('created_at', $year))
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('title', 'like', "%{$search}%")
                            ->orWhereHas('submitter', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                    });
                })
                ->when($type && $type !== 'all', function ($q) use ($type) {
                    $detailableType = $type === 'research'
                        ? Research::class
                        : CommunityService::class;
                    $q->where('detailable_type', $detailableType);
                });

            $proposals = $proposalsQuery
                ->with([
                    'submitter.identity.faculty',
                    'submitter.identity.studyProgram',
                    'detailable',
                    'researchScheme',
                    'communityServiceScheme',
                    'reviewers.user',
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            // Query reviewers
            $reviewers = User::role('reviewer')
                ->withCount([
                    'reviews as total_assigned' => function ($query) use ($year) {
                        if ($year) {
                            $query->whereHas('proposal', function ($pq) use ($year) {
                                $pq->whereYear('created_at', $year);
                            });
                        }
                    },
                    'reviews as pending_count' => function ($query) use ($year) {
                        $query->where('status', 'pending');
                        if ($year) {
                            $query->whereHas('proposal', function ($pq) use ($year) {
                                $pq->whereYear('created_at', $year);
                            });
                        }
                    },
                    'reviews as completed_count' => function ($query) use ($year) {
                        $query->where('status', 'completed');
                        if ($year) {
                            $query->whereHas('proposal', function ($pq) use ($year) {
                                $pq->whereYear('created_at', $year);
                            });
                        }
                    },
                ])
                ->with(['identity.faculty'])
                ->get();

            // Summary stats
            $totalProposals = $proposals->count();
            $assigned = $proposals->filter(fn ($p) => $p->reviewers->count() > 0)->count();

            $totalReviews = ProposalReviewer::query()
                ->whereHas('proposal', function ($q) use ($year) {
                    $q->when($year, fn ($sub) => $sub->whereYear('created_at', $year));
                })
                ->count();

            $completedReviews = ProposalReviewer::query()
                ->whereHas('proposal', function ($q) use ($year) {
                    $q->when($year, fn ($sub) => $sub->whereYear('created_at', $year));
                })
                ->where('status', 'completed')
                ->count();

            $progressPercent = $totalReviews > 0 ? round(($completedReviews / $totalReviews) * 100) : 0;

            $avgScore = round(Proposal::query()
                ->whereIn('status', [
                    ProposalStatus::REVIEWED,
                    ProposalStatus::APPROVED,
                    ProposalStatus::COMPLETED,
                ])
                ->when($year, fn ($q) => $q->whereYear('created_at', $year))
                ->avg('score') ?? 0, 1);

            $summaryStats = [
                'total_proposals' => $totalProposals,
                'assigned' => $assigned,
                'progress_percent' => $progressPercent,
                'avg_score' => $avgScore,
            ];

            $institution = Institution::first();
            $rektor = User::role('rektor')->with('identity')->first();
            $lppmHead = User::role('kepala lppm')->with('identity')->first();

            $pdf = Pdf::loadView('reports.reviewer-report-pdf', [
                'proposals' => $proposals,
                'reviewers' => $reviewers,
                'period' => $period,
                'summaryStats' => $summaryStats,
                'institution' => $institution,
                'rektor' => $rektor,
                'lppmHead' => $lppmHead,
            ])->setPaper('a4', 'landscape');

            $filename = 'laporan-reviewer-'.$period.'-'.now()->format('YmdHis').'.pdf';

            return $this->pdfDownloadResponse($pdf->output(), $filename);
        } catch (\Exception $e) {
            Log::error('Reviewer PDF Export Error: '.$e->getMessage());

            return back()->with('error', 'Gagal mengunduh PDF: '.$e->getMessage());
        }
    }

    // Vetted by AI - Manual Review Required by Senior Engineer/Manager
    public function reviewerExcel(Request $request)
    {
        try {
            $period = $request->query('period', date('Y'));
            $search = $request->query('search');
            $type = $request->query('type', 'all');

            return Excel::download(
                new ReviewerReportExport($period, $type, $search),
                'laporan-reviewer-'.$period.'-'.now()->format('YmdHis').'.xlsx'
            );
        } catch (\Exception $e) {
            Log::error('Reviewer Excel Export Error: '.$e->getMessage());

            return back()->with('error', 'Gagal mengunduh Excel: '.$e->getMessage());
        }
    }
}
