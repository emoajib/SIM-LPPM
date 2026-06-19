<?php

namespace App\Livewire\Settings;

use App\Constants\PdfConstants;
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

    protected array $pendingSaves = [];

    public function mount(?array $prefetchedOverrides = null): void
    {
        abort_unless(Auth::user()?->hasRole('admin lppm') || Auth::user()?->hasRole('superadmin'), 403);
        $this->loadOverrides($prefetchedOverrides);
    }

    private function loadOverrides(?array $prefetchedOverrides = null): void
    {
        $keys = [
            PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_FONT_FAMILY),
            PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_FONT_SIZE),
            PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_PAPER_SIZE),
            PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_ORIENTATION),
            PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_MARGIN_TOP),
            PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_MARGIN_RIGHT),
            PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_MARGIN_BOTTOM),
            PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_MARGIN_LEFT),
            PdfConstants::contentKey($this->moduleKey, PdfConstants::KEY_INTRO),
            PdfConstants::contentKey($this->moduleKey, PdfConstants::KEY_OUTRO),
            PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_SHOW_LOGO),
            PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_COVER_TITLE),
            PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_COVER_SUBTITLE),
            PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_COVER_SHOW_TEAM),
        ];

        if ($prefetchedOverrides !== null) {
            $overrides = collect($prefetchedOverrides);
        } else {
            $overrides = Setting::whereIn('key', $keys)
                ->pluck('value', 'key');
        }

        $this->fontFamily = $overrides->get(PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_FONT_FAMILY), '');
        $this->fontSize = $overrides->get(PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_FONT_SIZE), '');
        $this->paperSize = $overrides->get(PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_PAPER_SIZE), '');
        $this->orientation = $overrides->get(PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_ORIENTATION), '');
        $this->marginTop = $overrides->get(PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_MARGIN_TOP), '');
        $this->marginRight = $overrides->get(PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_MARGIN_RIGHT), '');
        $this->marginBottom = $overrides->get(PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_MARGIN_BOTTOM), '');
        $this->marginLeft = $overrides->get(PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_MARGIN_LEFT), '');
        $this->introText = $overrides->get(PdfConstants::contentKey($this->moduleKey, PdfConstants::KEY_INTRO), '');
        $this->outroText = $overrides->get(PdfConstants::contentKey($this->moduleKey, PdfConstants::KEY_OUTRO), '');
        $this->showLogo = $overrides->get(PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_SHOW_LOGO), '');
        $this->coverTitle = $overrides->get(PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_COVER_TITLE), '');
        $this->coverSubtitle = $overrides->get(PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_COVER_SUBTITLE), '');
        $this->coverShowTeam = $overrides->get(PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_COVER_SHOW_TEAM), '');
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
            'fontFamily' => PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_FONT_FAMILY),
            'fontSize' => PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_FONT_SIZE),
            'paperSize' => PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_PAPER_SIZE),
            'orientation' => PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_ORIENTATION),
            'marginTop' => PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_MARGIN_TOP),
            'marginRight' => PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_MARGIN_RIGHT),
            'marginBottom' => PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_MARGIN_BOTTOM),
            'marginLeft' => PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_MARGIN_LEFT),
            'introText' => PdfConstants::contentKey($this->moduleKey, PdfConstants::KEY_INTRO),
            'outroText' => PdfConstants::contentKey($this->moduleKey, PdfConstants::KEY_OUTRO),
            'showLogo' => PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_SHOW_LOGO),
            'coverTitle' => PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_COVER_TITLE),
            'coverSubtitle' => PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_COVER_SUBTITLE),
            'coverShowTeam' => PdfConstants::overrideKey($this->moduleKey, PdfConstants::KEY_COVER_SHOW_TEAM),
        ];

        if (isset($map[$property])) {
            if ($this->$property !== '') {
                $this->validateOnly($property);
            }
            // Queue the save for dehydrate() bulk execution
            $this->pendingSaves[$map[$property]] = $this->$property;

            // Dispatch update to UI immediately so badges feel reactive
            $this->dispatch('module-override-updated', moduleKey: $this->moduleKey, hasOverrides: $this->hasOverrides());
        }
    }

    public function dehydrate(): void
    {
        if (! empty($this->pendingSaves)) {
            Setting::setMany($this->pendingSaves);
            $this->pendingSaves = [];

            clear_pdf_config_cache($this->moduleKey);
        }
    }

    public function resetOverrides(): void
    {
        Setting::where(function ($q) {
            $q->where('key', 'like', PdfConstants::PREFIX_CONTENT."{$this->moduleKey}_%")
                ->orWhere('key', 'like', PdfConstants::PREFIX_OVERRIDE."{$this->moduleKey}_%");
        })->delete();

        clear_pdf_config_cache($this->moduleKey);

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
