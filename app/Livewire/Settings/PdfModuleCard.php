<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class PdfModuleCard extends Component
{
    public string $moduleKey;

    public string $moduleName;

    public string $family;

    public string $viewType;

    // --- Override fields (empty = use global) ---
    public string $fontFamily = '';

    public string $fontSize = '';

    public string $paperSize = '';

    public string $orientation = '';

    public string $marginTop = '';

    public string $marginRight = '';

    public string $marginBottom = '';

    public string $marginLeft = '';

    public string $introText = '';

    public string $outroText = '';

    // --- Additional per-view_type overrides ---
    public string $showLogo = '';

    public string $coverTitle = '';

    public string $coverSubtitle = '';

    public string $coverShowTeam = '';

    // --- UI state ---
    public bool $showInlineEditor = false;

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasRole('admin lppm') || Auth::user()?->hasRole('superadmin'), 403);
        $this->loadOverrides();
    }

    private function loadOverrides(): void
    {
        $this->fontFamily = Setting::get("pdf_override_{$this->moduleKey}_font_family", '');
        $this->fontSize = Setting::get("pdf_override_{$this->moduleKey}_font_size", '');
        $this->paperSize = Setting::get("pdf_override_{$this->moduleKey}_paper_size", '');
        $this->orientation = Setting::get("pdf_override_{$this->moduleKey}_orientation", '');
        $this->marginTop = Setting::get("pdf_override_{$this->moduleKey}_margin_top", '');
        $this->marginRight = Setting::get("pdf_override_{$this->moduleKey}_margin_right", '');
        $this->marginBottom = Setting::get("pdf_override_{$this->moduleKey}_margin_bottom", '');
        $this->marginLeft = Setting::get("pdf_override_{$this->moduleKey}_margin_left", '');
        $this->introText = Setting::get("pdf_content_{$this->moduleKey}_intro", '');
        $this->outroText = Setting::get("pdf_content_{$this->moduleKey}_outro", '');
        $this->showLogo = Setting::get("pdf_override_{$this->moduleKey}_show_logo", '');
        $this->coverTitle = Setting::get("pdf_override_{$this->moduleKey}_cover_title", '');
        $this->coverSubtitle = Setting::get("pdf_override_{$this->moduleKey}_cover_subtitle", '');
        $this->coverShowTeam = Setting::get("pdf_override_{$this->moduleKey}_cover_show_team", '');
    }

    public function hasOverrides(): bool
    {
        return $this->fontFamily !== ''
            || $this->fontSize !== ''
            || $this->paperSize !== ''
            || $this->orientation !== ''
            || $this->marginTop !== ''
            || $this->marginRight !== ''
            || $this->marginBottom !== ''
            || $this->marginLeft !== ''
            || $this->introText !== ''
            || $this->outroText !== ''
            || $this->showLogo !== ''
            || $this->coverTitle !== ''
            || $this->coverSubtitle !== ''
            || $this->coverShowTeam !== '';
    }

    public function updated(string $property): void
    {
        if ($property === 'showInlineEditor') {
            return;
        }

        $map = [
            'fontFamily' => "pdf_override_{$this->moduleKey}_font_family",
            'fontSize' => "pdf_override_{$this->moduleKey}_font_size",
            'paperSize' => "pdf_override_{$this->moduleKey}_paper_size",
            'orientation' => "pdf_override_{$this->moduleKey}_orientation",
            'marginTop' => "pdf_override_{$this->moduleKey}_margin_top",
            'marginRight' => "pdf_override_{$this->moduleKey}_margin_right",
            'marginBottom' => "pdf_override_{$this->moduleKey}_margin_bottom",
            'marginLeft' => "pdf_override_{$this->moduleKey}_margin_left",
            'introText' => "pdf_content_{$this->moduleKey}_intro",
            'outroText' => "pdf_content_{$this->moduleKey}_outro",
            'showLogo' => "pdf_override_{$this->moduleKey}_show_logo",
            'coverTitle' => "pdf_override_{$this->moduleKey}_cover_title",
            'coverSubtitle' => "pdf_override_{$this->moduleKey}_cover_subtitle",
            'coverShowTeam' => "pdf_override_{$this->moduleKey}_cover_show_team",
        ];

        if (isset($map[$property])) {
            Setting::set($map[$property], $this->$property, 'string');
            $this->dispatch('module-override-updated', moduleKey: $this->moduleKey, hasOverrides: $this->hasOverrides());
        }
    }

    public function resetOverrides(): void
    {
        Setting::where('key', 'LIKE', "pdf_content_{$this->moduleKey}_%")->delete();
        Setting::where('key', 'LIKE', "pdf_override_{$this->moduleKey}_%")->delete();
        $this->loadOverrides();
        $this->showInlineEditor = false;
        $this->dispatch('module-override-updated', moduleKey: $this->moduleKey, hasOverrides: false);
        $this->dispatch('settings-updated', message: "Override {$this->moduleName} telah di-reset ke global.");
    }

    public function openModalEditor(): void
    {
        $this->dispatch('open-content-editor', moduleKey: $this->moduleKey, moduleName: $this->moduleName);
    }

    public function render(): View
    {
        $familyLabel = config("pdf-modules.families.{$this->family}.label", 'Unknown');
        $defaultFont = config("pdf-modules.families.{$this->family}.default_font", '');
        $defaultSize = config("pdf-modules.families.{$this->family}.default_size", '');

        $effectivePaper = $this->paperSize ?: Setting::get('pdf_paper_size', 'a4');
        $effectiveOrientation = $this->orientation ?: Setting::get('pdf_orientation', 'portrait');

        return view('livewire.settings.pdf-module-card', [
            'familyLabel' => $familyLabel,
            'defaultFont' => $defaultFont,
            'defaultSize' => $defaultSize,
            'effectiveFont' => $this->fontFamily ?: $defaultFont,
            'effectiveSize' => $this->fontSize ?: $defaultSize,
            'effectivePaper' => $effectivePaper,
            'effectiveOrientation' => $effectiveOrientation,
        ]);
    }
}
