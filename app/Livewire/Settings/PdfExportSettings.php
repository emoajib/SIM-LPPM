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
    protected array $pendingSaves = [];

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

    protected $rules = [
        'editingContentIntro' => 'nullable|string|max:50000',
        'editingContentOutro' => 'nullable|string|max:50000',
        'editingCoverTitle' => 'nullable|string|max:500',
        'editingCoverSubtitle' => 'nullable|string|max:500',
        'pdfCoverTitle' => 'nullable|string|max:500',
        'pdfCoverSubtitle' => 'nullable|string|max:500',
        'pdfApprovalCustomText' => 'nullable|string|max:50000',
    ];

    protected $validationAttributes = [
        'editingContentIntro' => 'Teks Pengantar',
        'editingContentOutro' => 'Teks Penutup',
        'editingCoverTitle' => 'Judul Cover',
        'editingCoverSubtitle' => 'Subjudul Cover',
        'pdfCoverTitle' => 'Judul Cover Global',
        'pdfCoverSubtitle' => 'Subjudul Cover Global',
        'pdfApprovalCustomText' => 'Teks Persetujuan',
    ];

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasRole('admin lppm') || Auth::user()?->hasRole('superadmin'), 403);

        // Family A
        $this->pdfFontFamily = Setting::get(PdfConstants::GLOBAL_FONT_FAMILY, 'Times New Roman, Times, serif');
        $this->pdfBodyFontSize = (int) Setting::get(PdfConstants::GLOBAL_BODY_FONT_SIZE, 11);
        $this->pdfLayoutCompact = (bool) Setting::get(PdfConstants::GLOBAL_LAYOUT_COMPACT, false);
        $this->pdfShowLogo = (bool) Setting::get(PdfConstants::GLOBAL_SHOW_LOGO, true);
        $this->pdfPageMargin = Setting::get(PdfConstants::GLOBAL_PAGE_MARGIN, 'normal');
        $this->pdfPaperSize = Setting::get(PdfConstants::GLOBAL_PAPER_SIZE, 'a4');

        // Extended layout
        $this->pdfLogoPosition = Setting::get(PdfConstants::GLOBAL_LOGO_POSITION, 'left');
        $this->pdfLogoSize = (int) Setting::get(PdfConstants::GLOBAL_LOGO_SIZE, 110);
        $this->pdfLineHeight = Setting::get(PdfConstants::GLOBAL_LINE_HEIGHT, '1.1');
        $this->pdfParagraphSpacing = (int) Setting::get(PdfConstants::GLOBAL_PARAGRAPH_SPACING, 6);
        $this->pdfParagraphIndent = (int) Setting::get(PdfConstants::GLOBAL_PARAGRAPH_INDENT, 0);

        // Custom margins
        $this->pdfMarginTop = Setting::get(PdfConstants::GLOBAL_MARGIN_TOP, '');
        $this->pdfMarginRight = Setting::get(PdfConstants::GLOBAL_MARGIN_RIGHT, '');
        $this->pdfMarginBottom = Setting::get(PdfConstants::GLOBAL_MARGIN_BOTTOM, '');
        $this->pdfMarginLeft = Setting::get(PdfConstants::GLOBAL_MARGIN_LEFT, '');

        // Cover & Approval
        $this->pdfCoverTitle = Setting::get(PdfConstants::GLOBAL_COVER_TITLE, '');
        $this->pdfCoverSubtitle = Setting::get(PdfConstants::GLOBAL_COVER_SUBTITLE, '');
        $this->pdfCoverShowTeam = (bool) Setting::get(PdfConstants::GLOBAL_COVER_SHOW_TEAM, true);
        $this->pdfApprovalCustomText = Setting::get(PdfConstants::GLOBAL_APPROVAL_CUSTOM_TEXT, '');

        $this->pdfReportFontFamily = Setting::get(PdfConstants::REPORT_FONT_FAMILY, 'Arial, Helvetica, sans-serif');
        $this->pdfReportFontSize = (int) Setting::get(PdfConstants::REPORT_FONT_SIZE, 9);
        $this->pdfReportLineHeight = Setting::get(PdfConstants::REPORT_LINE_HEIGHT, '1.1');

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
            'pdfFontFamily' => [PdfConstants::GLOBAL_FONT_FAMILY, 'string'],
            'pdfBodyFontSize' => [PdfConstants::GLOBAL_BODY_FONT_SIZE, 'integer'],
            'pdfLayoutCompact' => [PdfConstants::GLOBAL_LAYOUT_COMPACT, 'boolean'],
            'pdfShowLogo' => [PdfConstants::GLOBAL_SHOW_LOGO, 'boolean'],
            'pdfPageMargin' => [PdfConstants::GLOBAL_PAGE_MARGIN, 'string'],
            'pdfPaperSize' => [PdfConstants::GLOBAL_PAPER_SIZE, 'string'],
            'pdfLogoPosition' => [PdfConstants::GLOBAL_LOGO_POSITION, 'string'],
            'pdfLogoSize' => [PdfConstants::GLOBAL_LOGO_SIZE, 'integer'],
            'pdfLineHeight' => [PdfConstants::GLOBAL_LINE_HEIGHT, 'string'],
            'pdfParagraphSpacing' => [PdfConstants::GLOBAL_PARAGRAPH_SPACING, 'integer'],
            'pdfParagraphIndent' => [PdfConstants::GLOBAL_PARAGRAPH_INDENT, 'integer'],
            'pdfMarginTop' => [PdfConstants::GLOBAL_MARGIN_TOP, 'string'],
            'pdfMarginRight' => [PdfConstants::GLOBAL_MARGIN_RIGHT, 'string'],
            'pdfMarginBottom' => [PdfConstants::GLOBAL_MARGIN_BOTTOM, 'string'],
            'pdfMarginLeft' => [PdfConstants::GLOBAL_MARGIN_LEFT, 'string'],
            'pdfCoverTitle' => [PdfConstants::GLOBAL_COVER_TITLE, 'string'],
            'pdfCoverSubtitle' => [PdfConstants::GLOBAL_COVER_SUBTITLE, 'string'],
            'pdfCoverShowTeam' => [PdfConstants::GLOBAL_COVER_SHOW_TEAM, 'boolean'],
            'pdfApprovalCustomText' => [PdfConstants::GLOBAL_APPROVAL_CUSTOM_TEXT, 'string'],
            'pdfReportFontFamily' => [PdfConstants::REPORT_FONT_FAMILY, 'string'],
            'pdfReportFontSize' => [PdfConstants::REPORT_FONT_SIZE, 'integer'],
            'pdfReportLineHeight' => [PdfConstants::REPORT_LINE_HEIGHT, 'string'],
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

            $this->pendingSaves[$key] = $castValue;
        }

        $this->dispatch('settings-updated', message: 'Pengaturan PDF & Ekspor berhasil diperbarui.');
    }

    public function dehydrate(): void
    {
        if (! empty($this->pendingSaves)) {
            Setting::setMany($this->pendingSaves);
            $this->pendingSaves = [];

            clear_pdf_config_cache();
        }
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
        $this->editingModule = '';
        $this->editingModuleName = '';
        $this->editingContentIntro = '';
        $this->editingContentOutro = '';
        $this->editingCoverTitle = '';
        $this->editingCoverSubtitle = '';
        $this->editingCoverShowTeam = true;
    }

    public function saveContentEditor(): void
    {
        $this->validate([
            'editingContentIntro' => 'nullable|string|max:50000',
            'editingContentOutro' => 'nullable|string|max:50000',
            'editingCoverTitle' => 'nullable|string|max:500',
            'editingCoverSubtitle' => 'nullable|string|max:500',
        ]);

        $settingsToSave = [
            PdfConstants::contentKey($this->editingModule, PdfConstants::KEY_INTRO) => $this->editingContentIntro,
            PdfConstants::contentKey($this->editingModule, PdfConstants::KEY_OUTRO) => $this->editingContentOutro,
            PdfConstants::overrideKey($this->editingModule, PdfConstants::KEY_COVER_TITLE) => $this->editingCoverTitle,
            PdfConstants::overrideKey($this->editingModule, PdfConstants::KEY_COVER_SUBTITLE) => $this->editingCoverSubtitle,
            PdfConstants::overrideKey($this->editingModule, PdfConstants::KEY_COVER_SHOW_TEAM) => $this->editingCoverShowTeam ? '1' : '0',
        ];

        Setting::setMany($settingsToSave);
        clear_pdf_config_cache($this->editingModule);

        $this->contentModalOpen = false;
        $this->dispatch('settings-updated', message: "Konfigurasi kustom untuk {$this->editingModuleName} berhasil disimpan.");
    }

    public function resetContentEditor(): void
    {
        Setting::where('key', 'LIKE', PdfConstants::PREFIX_CONTENT."{$this->editingModule}_%")->delete();
        Setting::where('key', 'LIKE', PdfConstants::PREFIX_OVERRIDE."{$this->editingModule}_%")->delete();

        clear_pdf_config_cache($this->editingModule);

        $this->closeContentEditor();
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
