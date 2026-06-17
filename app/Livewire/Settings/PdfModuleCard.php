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

    protected $rules = [
        'fontFamily' => 'nullable|string',
        'fontSize' => 'nullable|numeric|between:6,20',
        'paperSize' => 'nullable|string|in:a4,folio,letter,legal',
        'orientation' => 'nullable|string|in:portrait,landscape',
        'marginTop' => 'nullable|numeric|min:0|max:10',
        'marginRight' => 'nullable|numeric|min:0|max:10',
        'marginBottom' => 'nullable|numeric|min:0|max:10',
        'marginLeft' => 'nullable|numeric|min:0|max:10',
        'introText' => 'nullable|string',
        'outroText' => 'nullable|string',
        'showLogo' => 'nullable|string|in:0,1',
        'coverTitle' => 'nullable|string',
        'coverSubtitle' => 'nullable|string',
        'coverShowTeam' => 'nullable|string|in:0,1',
    ];

    protected $validationAttributes = [
        'fontFamily' => 'Font Family',
        'fontSize' => 'Ukuran Font',
        'paperSize' => 'Ukuran Kertas',
        'orientation' => 'Orientasi',
        'marginTop' => 'Margin Atas',
        'marginRight' => 'Margin Kanan',
        'marginBottom' => 'Margin Bawah',
        'marginLeft' => 'Margin Kiri',
        'introText' => 'Teks Pengantar',
        'outroText' => 'Teks Penutup',
        'showLogo' => 'Tampilkan Logo',
        'coverTitle' => 'Judul Cover',
        'coverSubtitle' => 'Subjudul Cover',
        'coverShowTeam' => 'Tampilkan Tim',
    ];

    // --- Internal cache ---
    private ?bool $cachedHasOverrides = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasRole('admin lppm') || Auth::user()?->hasRole('superadmin'), 403);
        $this->loadOverrides();
    }

    private function loadOverrides(): void
    {
        $keys = collect([
            "pdf_override_{$this->moduleKey}_font_family",
            "pdf_override_{$this->moduleKey}_font_size",
            "pdf_override_{$this->moduleKey}_paper_size",
            "pdf_override_{$this->moduleKey}_orientation",
            "pdf_override_{$this->moduleKey}_margin_top",
            "pdf_override_{$this->moduleKey}_margin_right",
            "pdf_override_{$this->moduleKey}_margin_bottom",
            "pdf_override_{$this->moduleKey}_margin_left",
            "pdf_content_{$this->moduleKey}_intro",
            "pdf_content_{$this->moduleKey}_outro",
            "pdf_override_{$this->moduleKey}_show_logo",
            "pdf_override_{$this->moduleKey}_cover_title",
            "pdf_override_{$this->moduleKey}_cover_subtitle",
            "pdf_override_{$this->moduleKey}_cover_show_team",
        ]);

        $overrides = Setting::whereIn('key', $keys->all())
            ->get()
            ->keyBy('key')
            ->map(fn ($s) => $s->value);

        $this->fontFamily = $overrides->get("pdf_override_{$this->moduleKey}_font_family", '');
        $this->fontSize = $overrides->get("pdf_override_{$this->moduleKey}_font_size", '');
        $this->paperSize = $overrides->get("pdf_override_{$this->moduleKey}_paper_size", '');
        $this->orientation = $overrides->get("pdf_override_{$this->moduleKey}_orientation", '');
        $this->marginTop = $overrides->get("pdf_override_{$this->moduleKey}_margin_top", '');
        $this->marginRight = $overrides->get("pdf_override_{$this->moduleKey}_margin_right", '');
        $this->marginBottom = $overrides->get("pdf_override_{$this->moduleKey}_margin_bottom", '');
        $this->marginLeft = $overrides->get("pdf_override_{$this->moduleKey}_margin_left", '');
        $this->introText = $overrides->get("pdf_content_{$this->moduleKey}_intro", '');
        $this->outroText = $overrides->get("pdf_content_{$this->moduleKey}_outro", '');
        $this->showLogo = $overrides->get("pdf_override_{$this->moduleKey}_show_logo", '');
        $this->coverTitle = $overrides->get("pdf_override_{$this->moduleKey}_cover_title", '');
        $this->coverSubtitle = $overrides->get("pdf_override_{$this->moduleKey}_cover_subtitle", '');
        $this->coverShowTeam = $overrides->get("pdf_override_{$this->moduleKey}_cover_show_team", '');
    }

    public function hasOverrides(): bool
    {
        if ($this->cachedHasOverrides !== null) {
            return $this->cachedHasOverrides;
        }

        $this->cachedHasOverrides = $this->fontFamily !== ''
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

        return $this->cachedHasOverrides;
    }

    public function updated(string $property): void
    {
        if ($property === 'showInlineEditor') {
            return;
        }

        $this->cachedHasOverrides = null;

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
            if ($this->$property !== '') {
                $this->validateOnly($property);
            }
            Setting::set($map[$property], $this->$property, 'string');
            $this->dispatch('module-override-updated', moduleKey: $this->moduleKey, hasOverrides: $this->hasOverrides());
        }
    }

    public function resetOverrides(): void
    {
        Setting::where(function ($q) {
            $q->where('key', 'like', "pdf_content_{$this->moduleKey}_%")
                ->orWhere('key', 'like', "pdf_override_{$this->moduleKey}_%");
        })->delete();

        $this->cachedHasOverrides = null;
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
        $familyConfig = config("pdf-modules.families.{$this->family}", []);

        $effectivePaper = $this->paperSize ?: Setting::get('pdf_paper_size', 'a4');
        $effectiveOrientation = $this->orientation ?: Setting::get('pdf_orientation', 'portrait');

        return view('livewire.settings.pdf-module-card', [
            'familyLabel' => $familyConfig['label'] ?? 'Unknown',
            'defaultFont' => $familyConfig['default_font'] ?? '',
            'defaultSize' => $familyConfig['default_size'] ?? '',
            'effectiveFont' => $this->fontFamily ?: ($familyConfig['default_font'] ?? ''),
            'effectiveSize' => $this->fontSize ?: ($familyConfig['default_size'] ?? ''),
            'effectivePaper' => $effectivePaper,
            'effectiveOrientation' => $effectiveOrientation,
            'hasOverrides' => $this->hasOverrides(),
        ]);
    }
}
