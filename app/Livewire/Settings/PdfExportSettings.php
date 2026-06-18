<?php

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

namespace App\Livewire\Settings;

use App\Constants\PdfConstants;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class PdfExportSettings extends Component
{
    // --- Family A (Surat / Proposal) ---
    public string $pdfFontFamily = 'Times New Roman, Times, serif';

    public int $pdfBodyFontSize = 11;

    public bool $pdfLayoutCompact = false;

    public bool $pdfShowLogo = true;

    public string $pdfPageMargin = 'normal';

    public string $pdfPaperSize = 'a4';

    // --- Logo & Layout Extended ---
    public string $pdfLogoPosition = 'left';

    public int $pdfLogoSize = 110;

    public string $pdfLineHeight = '1.1';

    public int $pdfParagraphSpacing = 6;

    public int $pdfParagraphIndent = 0;

    // --- Custom Margins (cm, empty = use preset) ---
    public string $pdfMarginTop = '';

    public string $pdfMarginRight = '';

    public string $pdfMarginBottom = '';

    public string $pdfMarginLeft = '';

    // --- Cover & Approval Editor ---
    public string $pdfCoverTitle = '';

    public string $pdfCoverSubtitle = '';

    public bool $pdfCoverShowTeam = true;

    public string $pdfApprovalCustomText = '';

    // --- Family B (Laporan Modul) ---
    public string $pdfReportFontFamily = 'Arial, Helvetica, sans-serif';

    public int $pdfReportFontSize = 9;

    public string $pdfReportLineHeight = '1.1';

    // --- Editor Konten & Override Modul ---
    public bool $contentModalOpen = false;

    public string $editingModule = '';

    public string $editingModuleName = '';

    // Konten
    public string $editingContentIntro = '';

    public string $editingContentOutro = '';

    public string $editingCoverTitle = '';

    public string $editingCoverSubtitle = '';

    public bool $editingCoverShowTeam = true;

    // --- Family C (Dokumen Usulan & Hasil) ---
    public string $pdfFamilyCFontFamily = '';

    public int $pdfFamilyCFontSize = 11;

    public string $pdfFamilyCLineHeight = '1.5';

    // --- Family C (Visibility: Proposal) ---
    public bool $pdfProposalShowCover = true;

    public bool $pdfProposalShowSubstance = true;

    public bool $pdfProposalShowApproval = true;

    public bool $pdfProposalShowDocs = true;

    public bool $pdfProposalShowOtherDocs = true;

    public bool $pdfProposalShowOutro = true;

    // --- Family C (Visibility: Report) ---
    public bool $pdfReportShowCover = true;

    public bool $pdfReportShowBasicInfo = true;

    public bool $pdfReportShowApproval = true;

    public bool $pdfReportShowRealization = true;

    public bool $pdfReportShowLogbook = true;

    public bool $pdfReportShowDocs = true;

    public bool $pdfReportShowOtherDocs = true;

    public bool $pdfReportShowOutro = true;

    // --- Modal Editor State ---
    public string $activePdfTab = 'layout';

    public string $viewMode = 'card'; // 'card' or 'table'

    protected function getListeners(): array
    {
        return [
            'open-content-editor' => 'handleOpenContentEditor',
            'module-override-updated' => 'handleModuleOverrideUpdated',
        ];
    }

    public function handleOpenContentEditor(string $moduleKey, string $moduleName): void
    {
        $this->openContentEditor($moduleKey, $moduleName);
    }

    public function handleModuleOverrideUpdated(string $moduleKey, bool $hasOverrides): void
    {
        // Child card override status changed; parent may refresh if needed
    }

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasRole('admin lppm') || Auth::user()?->hasRole('superadmin'), 403);

        // Family A
        $this->pdfFontFamily = Setting::get('pdf_font_family', 'Times New Roman, Times, serif');
        $this->pdfBodyFontSize = (int) Setting::get('pdf_body_font_size', 11);
        $this->pdfLayoutCompact = (bool) Setting::get('pdf_layout_compact', false);
        $this->pdfShowLogo = (bool) Setting::get('pdf_show_logo', true);
        $this->pdfPageMargin = Setting::get('pdf_page_margin', 'normal');
        $this->pdfPaperSize = Setting::get('pdf_paper_size', 'a4');

        // Extended layout
        $this->pdfLogoPosition = Setting::get('pdf_logo_position', 'left');
        $this->pdfLogoSize = (int) Setting::get('pdf_logo_size', 110);
        $this->pdfLineHeight = Setting::get('pdf_line_height', '1.1');
        $this->pdfParagraphSpacing = (int) Setting::get('pdf_paragraph_spacing', 6);
        $this->pdfParagraphIndent = (int) Setting::get('pdf_paragraph_indent', 0);

        // Custom margins
        $this->pdfMarginTop = Setting::get('pdf_margin_top', '');
        $this->pdfMarginRight = Setting::get('pdf_margin_right', '');
        $this->pdfMarginBottom = Setting::get('pdf_margin_bottom', '');
        $this->pdfMarginLeft = Setting::get('pdf_margin_left', '');

        // Cover & Approval
        $this->pdfCoverTitle = Setting::get('pdf_cover_title', '');
        $this->pdfCoverSubtitle = Setting::get('pdf_cover_subtitle', '');
        $this->pdfCoverShowTeam = (bool) Setting::get('pdf_cover_show_team', true);
        $this->pdfApprovalCustomText = Setting::get('pdf_approval_custom_text', '');

        $this->pdfReportFontFamily = Setting::get('pdf_report_font_family', 'Arial, Helvetica, sans-serif');
        $this->pdfReportFontSize = (int) Setting::get('pdf_report_font_size', 9);
        $this->pdfReportLineHeight = Setting::get('pdf_report_line_height', '1.1');

        // Family C
        $this->pdfFamilyCFontFamily = Setting::get(PdfConstants::PROPOSAL_FONT_FAMILY, config('pdf-modules.families.C.default_font', 'Times New Roman, Times, serif'));
        $this->pdfFamilyCFontSize = (int) Setting::get(PdfConstants::PROPOSAL_FONT_SIZE, config('pdf-modules.families.C.default_size', 11));
        $this->pdfFamilyCLineHeight = Setting::get(PdfConstants::PROPOSAL_LINE_HEIGHT, '1.5');

        $this->pdfProposalShowCover = (bool) Setting::get(PdfConstants::PROPOSAL_SHOW_COVER, true);
        $this->pdfProposalShowSubstance = (bool) Setting::get(PdfConstants::PROPOSAL_SHOW_SUBSTANCE, true);
        $this->pdfProposalShowApproval = (bool) Setting::get(PdfConstants::PROPOSAL_SHOW_APPROVAL, true);
        $this->pdfProposalShowDocs = (bool) Setting::get(PdfConstants::PROPOSAL_SHOW_DOCS, true);
        $this->pdfProposalShowOtherDocs = (bool) Setting::get(PdfConstants::PROPOSAL_SHOW_OTHER_DOCS, true);
        $this->pdfProposalShowOutro = (bool) Setting::get(PdfConstants::PROPOSAL_SHOW_OUTRO, true);

        $this->pdfReportShowCover = (bool) Setting::get(PdfConstants::REPORT_SHOW_COVER, true);
        $this->pdfReportShowBasicInfo = (bool) Setting::get(PdfConstants::REPORT_SHOW_BASIC_INFO, true);
        $this->pdfReportShowApproval = (bool) Setting::get(PdfConstants::REPORT_SHOW_APPROVAL, true);
        $this->pdfReportShowRealization = (bool) Setting::get(PdfConstants::REPORT_SHOW_REALIZATION, true);
        $this->pdfReportShowLogbook = (bool) Setting::get(PdfConstants::REPORT_SHOW_LOGBOOK, true);
        $this->pdfReportShowDocs = (bool) Setting::get(PdfConstants::REPORT_SHOW_DOCS, true);
        $this->pdfReportShowOtherDocs = (bool) Setting::get(PdfConstants::REPORT_SHOW_OTHER_DOCS, true);
        $this->pdfReportShowOutro = (bool) Setting::get(PdfConstants::REPORT_SHOW_OUTRO, true);
    }

    public function updated(string $property, mixed $value): void
    {
        $map = [
            'pdfFontFamily' => ['pdf_font_family', 'string'],
            'pdfBodyFontSize' => ['pdf_body_font_size', 'integer'],
            'pdfLayoutCompact' => ['pdf_layout_compact', 'boolean'],
            'pdfShowLogo' => ['pdf_show_logo', 'boolean'],
            'pdfPageMargin' => ['pdf_page_margin', 'string'],
            'pdfPaperSize' => ['pdf_paper_size', 'string'],
            'pdfLogoPosition' => ['pdf_logo_position', 'string'],
            'pdfLogoSize' => ['pdf_logo_size', 'integer'],
            'pdfLineHeight' => ['pdf_line_height', 'string'],
            'pdfParagraphSpacing' => ['pdf_paragraph_spacing', 'integer'],
            'pdfParagraphIndent' => ['pdf_paragraph_indent', 'integer'],
            'pdfMarginTop' => ['pdf_margin_top', 'string'],
            'pdfMarginRight' => ['pdf_margin_right', 'string'],
            'pdfMarginBottom' => ['pdf_margin_bottom', 'string'],
            'pdfMarginLeft' => ['pdf_margin_left', 'string'],
            'pdfCoverTitle' => ['pdf_cover_title', 'string'],
            'pdfCoverSubtitle' => ['pdf_cover_subtitle', 'string'],
            'pdfCoverShowTeam' => ['pdf_cover_show_team', 'boolean'],
            'pdfApprovalCustomText' => ['pdf_approval_custom_text', 'string'],
            'pdfReportFontFamily' => ['pdf_report_font_family', 'string'],
            'pdfReportFontSize' => ['pdf_report_font_size', 'integer'],
            'pdfReportLineHeight' => ['pdf_report_line_height', 'string'],
            'pdfFamilyCFontFamily' => [PdfConstants::PROPOSAL_FONT_FAMILY, 'string'],
            'pdfFamilyCFontSize' => [PdfConstants::PROPOSAL_FONT_SIZE, 'integer'],
            'pdfFamilyCLineHeight' => [PdfConstants::PROPOSAL_LINE_HEIGHT, 'string'],
            'pdfProposalShowCover' => [PdfConstants::PROPOSAL_SHOW_COVER, 'boolean'],
            'pdfProposalShowSubstance' => [PdfConstants::PROPOSAL_SHOW_SUBSTANCE, 'boolean'],
            'pdfProposalShowApproval' => [PdfConstants::PROPOSAL_SHOW_APPROVAL, 'boolean'],
            'pdfProposalShowDocs' => [PdfConstants::PROPOSAL_SHOW_DOCS, 'boolean'],
            'pdfProposalShowOtherDocs' => [PdfConstants::PROPOSAL_SHOW_OTHER_DOCS, 'boolean'],
            'pdfProposalShowOutro' => [PdfConstants::PROPOSAL_SHOW_OUTRO, 'boolean'],
            'pdfReportShowCover' => [PdfConstants::REPORT_SHOW_COVER, 'boolean'],
            'pdfReportShowBasicInfo' => [PdfConstants::REPORT_SHOW_BASIC_INFO, 'boolean'],
            'pdfReportShowApproval' => [PdfConstants::REPORT_SHOW_APPROVAL, 'boolean'],
            'pdfReportShowRealization' => [PdfConstants::REPORT_SHOW_REALIZATION, 'boolean'],
            'pdfReportShowLogbook' => [PdfConstants::REPORT_SHOW_LOGBOOK, 'boolean'],
            'pdfReportShowDocs' => [PdfConstants::REPORT_SHOW_DOCS, 'boolean'],
            'pdfReportShowOtherDocs' => [PdfConstants::REPORT_SHOW_OTHER_DOCS, 'boolean'],
            'pdfReportShowOutro' => [PdfConstants::REPORT_SHOW_OUTRO, 'boolean'],
        ];

        if (isset($map[$property])) {
            [$key, $type] = $map[$property];
            $castValue = match ($type) {
                'integer' => (int) $value,
                'boolean' => (bool) $value,
                default => (string) $value,
            };
            Setting::set($key, $castValue, $type);
        }

        $this->dispatch('settings-updated', message: 'Pengaturan PDF & Ekspor berhasil diperbarui.');
    }

    /**
     * Hapus cache PDF berdasarkan tipe.
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    public function clearPdfCache(string $type = 'all'): void
    {
        abort_unless(Auth::user()?->hasRole('admin lppm') || Auth::user()?->hasRole('superadmin'), 403);

        $dirMap = [
            'proposals' => ['pdf_cache/proposals'],
            'reports' => ['pdf_cache/reports'],
            'reviewer' => ['pdf_cache/reviewer_reports'],
            'all' => ['pdf_cache/proposals', 'pdf_cache/reports', 'pdf_cache/reviewer_reports'],
        ];

        $dirs = $dirMap[$type] ?? $dirMap['all'];
        $deleted = 0;

        foreach ($dirs as $dir) {
            $fullDir = storage_path("app/{$dir}");
            if (is_dir($fullDir)) {
                $files = glob($fullDir.DIRECTORY_SEPARATOR.'*.pdf') ?: [];
                foreach ($files as $file) {
                    if (is_file($file) && @unlink($file)) {
                        $deleted++;
                    }
                }
            }
        }

        $label = match ($type) {
            'proposals' => 'Proposal',
            'reports' => 'Laporan',
            'reviewer' => 'Reviewer',
            default => 'Semua',
        };

        $this->dispatch('settings-updated', message: "Cache PDF {$label} berhasil dibersihkan ({$deleted} file dihapus).");
    }

    /**
     * Hitung statistik file cache PDF.
     *
     * @return array<string, array{count: int, size: string, bytes: int}>
     */
    public function getCacheStats(): array
    {
        $dirs = [
            'proposals' => storage_path('app/pdf_cache/proposals'),
            'reports' => storage_path('app/pdf_cache/reports'),
            'reviewer' => storage_path('app/pdf_cache/reviewer_reports'),
        ];

        $stats = [];
        foreach ($dirs as $key => $dir) {
            $files = is_dir($dir) ? (glob($dir.DIRECTORY_SEPARATOR.'*.pdf') ?: []) : [];
            $bytes = array_sum(array_map('filesize', array_filter($files, 'is_file')));
            $stats[$key] = [
                'count' => count($files),
                'bytes' => $bytes,
                'size' => $this->formatBytes($bytes),
            ];
        }

        return $stats;
    }

    public function formatBytes(int $bytes): string
    {
        if ($bytes >= 1_048_576) {
            return round($bytes / 1_048_576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    public function openContentEditor(string $moduleKey, string $moduleName): void
    {
        $this->editingModule = $moduleKey;
        $this->editingModuleName = $moduleName;

        $this->editingContentIntro = Setting::get(PdfConstants::contentKey($moduleKey, PdfConstants::KEY_INTRO), '');
        $this->editingContentOutro = Setting::get(PdfConstants::contentKey($moduleKey, PdfConstants::KEY_OUTRO), '');

        $this->editingCoverTitle = Setting::get(PdfConstants::overrideKey($moduleKey, PdfConstants::KEY_COVER_TITLE), '');
        $this->editingCoverSubtitle = Setting::get(PdfConstants::overrideKey($moduleKey, PdfConstants::KEY_COVER_SUBTITLE), '');
        $this->editingCoverShowTeam = (bool) Setting::get(PdfConstants::overrideKey($moduleKey, PdfConstants::KEY_COVER_SHOW_TEAM), true);

        $this->contentModalOpen = true;
    }

    public function closeContentEditor(): void
    {
        $this->contentModalOpen = false;
    }

    public function saveContentEditor(): void
    {
        $settingsToSave = [
            PdfConstants::contentKey($this->editingModule, PdfConstants::KEY_INTRO) => $this->editingContentIntro,
            PdfConstants::contentKey($this->editingModule, PdfConstants::KEY_OUTRO) => $this->editingContentOutro,
            PdfConstants::overrideKey($this->editingModule, PdfConstants::KEY_COVER_TITLE) => $this->editingCoverTitle,
            PdfConstants::overrideKey($this->editingModule, PdfConstants::KEY_COVER_SUBTITLE) => $this->editingCoverSubtitle,
            PdfConstants::overrideKey($this->editingModule, PdfConstants::KEY_COVER_SHOW_TEAM) => $this->editingCoverShowTeam ? '1' : '0',
        ];

        Setting::setMany($settingsToSave);

        $this->contentModalOpen = false;
        $this->dispatch('settings-updated', message: "Konfigurasi kustom untuk {$this->editingModuleName} berhasil disimpan.");
    }

    public function resetContentEditor(): void
    {
        Setting::where('key', 'LIKE', PdfConstants::PREFIX_CONTENT."{$this->editingModule}_%")->delete();
        Setting::where('key', 'LIKE', PdfConstants::PREFIX_OVERRIDE."{$this->editingModule}_%")->delete();

        $this->contentModalOpen = false;
        $this->dispatch('settings-updated', message: "Konfigurasi kustom untuk {$this->editingModuleName} berhasil di-reset ke pengaturan global bawaan.");
    }

    public function render(): View
    {
        // Pre-fetch all pdf overrides for children to eliminate N+1 queries
        $prefetchedOverrides = Setting::where('key', 'like', 'pdf_%')->pluck('value', 'key')->toArray();

        return view('livewire.settings.pdf-export-settings', [
            'cacheStats' => $this->getCacheStats(),
            'hasLogo' => file_exists(public_path('logo.png')),
            'logoUrl' => asset('logo.png'),
            'prefetchedOverrides' => $prefetchedOverrides,
        ]);
    }
}
