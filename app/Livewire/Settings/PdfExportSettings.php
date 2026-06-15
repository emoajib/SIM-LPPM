<?php

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

namespace App\Livewire\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class PdfExportSettings extends Component
{
    public string $pdfFontFamily = 'Times New Roman, Times, serif';

    public int $pdfBodyFontSize = 11;

    public bool $pdfLayoutCompact = false;

    public bool $pdfShowLogo = true;

    public string $pdfPageMargin = 'normal';

    public string $pdfReportFontFamily = 'Arial, Helvetica, sans-serif';

    public int $pdfReportFontSize = 9;

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasRole('admin lppm') || Auth::user()?->hasRole('superadmin'), 403);

        $this->pdfFontFamily = Setting::get('pdf_font_family', 'Times New Roman, Times, serif');
        $this->pdfBodyFontSize = (int) Setting::get('pdf_body_font_size', 11);
        $this->pdfLayoutCompact = (bool) Setting::get('pdf_layout_compact', false);
        $this->pdfShowLogo = (bool) Setting::get('pdf_show_logo', true);
        $this->pdfPageMargin = Setting::get('pdf_page_margin', 'normal');

        $this->pdfReportFontFamily = Setting::get('pdf_report_font_family', 'Arial, Helvetica, sans-serif');
        $this->pdfReportFontSize = (int) Setting::get('pdf_report_font_size', 9);
    }

    public function updated(string $property, mixed $value): void
    {
        if ($property === 'pdfFontFamily') {
            Setting::set('pdf_font_family', $value, 'string');
        }

        if ($property === 'pdfBodyFontSize') {
            Setting::set('pdf_body_font_size', (int) $value, 'integer');
        }

        if ($property === 'pdfLayoutCompact') {
            Setting::set('pdf_layout_compact', (bool) $value, 'boolean');
        }

        if ($property === 'pdfShowLogo') {
            Setting::set('pdf_show_logo', (bool) $value, 'boolean');
        }

        if ($property === 'pdfPageMargin') {
            Setting::set('pdf_page_margin', $value, 'string');
        }

        if ($property === 'pdfReportFontFamily') {
            Setting::set('pdf_report_font_family', $value, 'string');
        }

        if ($property === 'pdfReportFontSize') {
            Setting::set('pdf_report_font_size', (int) $value, 'integer');
        }

        $this->dispatch('settings-updated', message: 'Pengaturan PDF & Ekspor berhasil diperbarui.');
    }

    public function render(): View
    {
        return view('livewire.settings.pdf-export-settings');
    }
}
