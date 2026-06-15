<?php

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

namespace App\Livewire\Settings;

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

    // Override Tipografi & Tata Letak
    public string $editingPaperSize = '';

    public string $editingFontFamily = '';

    public string $editingFontSize = '';

    public string $editingMarginTop = '';

    public string $editingMarginRight = '';

    public string $editingMarginBottom = '';

    public string $editingMarginLeft = '';

    // --- UI State ---
    public string $activePdfTab = 'layout';

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

        // Family B
        $this->pdfReportFontFamily = Setting::get('pdf_report_font_family', 'Arial, Helvetica, sans-serif');
        $this->pdfReportFontSize = (int) Setting::get('pdf_report_font_size', 9);
        $this->pdfReportLineHeight = Setting::get('pdf_report_line_height', '1.1');
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
            'pdfReportFontFamily' => ['pdf_report_font_family', 'string'],
            'pdfReportFontSize' => ['pdf_report_font_size', 'integer'],
            'pdfReportLineHeight' => ['pdf_report_line_height', 'string'],
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
            $fullDir = storage_path("app/public/{$dir}");
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
            'proposals' => storage_path('app/public/pdf_cache/proposals'),
            'reports' => storage_path('app/public/pdf_cache/reports'),
            'reviewer' => storage_path('app/public/pdf_cache/reviewer_reports'),
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

        $this->editingContentIntro = Setting::get("pdf_content_{$moduleKey}_intro", '');
        $this->editingContentOutro = Setting::get("pdf_content_{$moduleKey}_outro", '');

        $this->editingPaperSize = Setting::get("pdf_override_{$moduleKey}_paper_size", '');
        $this->editingFontFamily = Setting::get("pdf_override_{$moduleKey}_font_family", '');
        $this->editingFontSize = Setting::get("pdf_override_{$moduleKey}_font_size", '');
        $this->editingMarginTop = Setting::get("pdf_override_{$moduleKey}_margin_top", '');
        $this->editingMarginRight = Setting::get("pdf_override_{$moduleKey}_margin_right", '');
        $this->editingMarginBottom = Setting::get("pdf_override_{$moduleKey}_margin_bottom", '');
        $this->editingMarginLeft = Setting::get("pdf_override_{$moduleKey}_margin_left", '');

        $this->contentModalOpen = true;
    }

    public function closeContentEditor(): void
    {
        $this->contentModalOpen = false;
    }

    public function saveContentEditor(): void
    {
        Setting::set("pdf_content_{$this->editingModule}_intro", $this->editingContentIntro, 'string');
        Setting::set("pdf_content_{$this->editingModule}_outro", $this->editingContentOutro, 'string');

        Setting::set("pdf_override_{$this->editingModule}_paper_size", $this->editingPaperSize, 'string');
        Setting::set("pdf_override_{$this->editingModule}_font_family", $this->editingFontFamily, 'string');
        Setting::set("pdf_override_{$this->editingModule}_font_size", $this->editingFontSize, 'string');
        Setting::set("pdf_override_{$this->editingModule}_margin_top", $this->editingMarginTop, 'string');
        Setting::set("pdf_override_{$this->editingModule}_margin_right", $this->editingMarginRight, 'string');
        Setting::set("pdf_override_{$this->editingModule}_margin_bottom", $this->editingMarginBottom, 'string');
        Setting::set("pdf_override_{$this->editingModule}_margin_left", $this->editingMarginLeft, 'string');

        $this->contentModalOpen = false;
        $this->dispatch('settings-updated', message: "Konfigurasi kustom untuk {$this->editingModuleName} berhasil disimpan.");
    }

    public function resetContentEditor(): void
    {
        Setting::where('key', 'LIKE', "pdf_content_{$this->editingModule}_%")->delete();
        Setting::where('key', 'LIKE', "pdf_override_{$this->editingModule}_%")->delete();

        $this->contentModalOpen = false;
        $this->dispatch('settings-updated', message: "Konfigurasi kustom untuk {$this->editingModuleName} berhasil di-reset ke pengaturan global bawaan.");
    }

    public function render(): View
    {
        return view('livewire.settings.pdf-export-settings', [
            'cacheStats' => $this->getCacheStats(),
            'hasLogo' => file_exists(public_path('logo.png')),
            'logoUrl' => asset('logo.png'),
        ]);
    }
}
