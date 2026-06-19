<?php

use App\Constants\PdfConstants;
use App\Models\Institution;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

if (! function_exists('active_role')) {
    /**
     * Get the currently active role for the authenticated user.
     */
    function active_role(): ?string
    {
        return session('active_role');
    }
}

if (! function_exists('active_role_is')) {
    /**
     * Check if the given role is the currently active role.
     */
    function active_role_is(string $role): bool
    {
        return active_role() === $role;
    }
}

if (! function_exists('format_role_name')) {
    /**
     * Format role name for display (convert to title case).
     */
    function format_role_name(string $role): string
    {
        // Special replacements
        $replacements = [
            'admin lppm' => 'Admin Lppm',
            'kepala lppm' => 'Kepala Lppm',
        ];

        $result = str_replace(array_keys($replacements), array_values($replacements), $role);

        return ucwords($result);
    }
}

if (! function_exists('active_has_role')) {
    /**
     * Check if the active role matches the given role.
     */
    function active_has_role(string $role): bool
    {
        $activeRole = active_role();

        return $activeRole === $role;
    }
}

if (! function_exists('active_has_any_role')) {
    /**
     * Check if the active role matches any of the given roles.
     */
    function active_has_any_role(array $roles): bool
    {
        $activeRole = active_role();

        return in_array($activeRole, $roles, true);
    }
}

if (! function_exists('sql_year')) {
    /**
     * Get the SQL expression for extracting year from a date column.
     */
    function sql_year(string $column = 'created_at'): string
    {
        $driver = strtolower(DB::getDriverName());

        return match ($driver) {
            'sqlite' => "strftime('%Y', {$column})",
            'pgsql' => "EXTRACT(YEAR FROM {$column})",
            default => "YEAR({$column})",
        };
    }
}

if (! function_exists('generate_qr_code_data_uri')) {
    /**
     * Generate a QR code as a data URI (SVG).
     */
    function generate_qr_code_data_uri(string $data, int $size = 150): string
    {
        if (! class_exists('\BaconQrCode\Renderer\ImageRenderer')) {
            // Fallback for missing dependency or SVG rendering
            // Use a simple placeholder or warning if impossible to generate
            return 'data:image/svg+xml;base64,'.base64_encode(
                '<svg width="'.$size.'" height="'.$size.'" xmlns="http://www.w3.org/2000/svg">'.
                '<rect width="100%" height="100%" fill="#eee"/>'.
                '<text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="8">QR Placeholder</text>'.
                '</svg>'
            );
        }

        try {
            $renderer = new ImageRenderer(
                new RendererStyle($size),
                new SvgImageBackEnd
            );
            $writer = new Writer($renderer);
            $svg = $writer->writeString($data);

            return 'data:image/svg+xml;base64,'.base64_encode($svg);
        } catch (Throwable $e) {
            Log::warning('QR Code Generation Failed: '.$e->getMessage());

            return 'data:image/svg+xml;base64,'.base64_encode(
                '<svg width="'.$size.'" height="'.$size.'" xmlns="http://www.w3.org/2000/svg">'.
                '<rect width="100%" height="100%" fill="#fef2f2"/>'.
                '<text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="8">Error QR</text>'.
                '</svg>'
            );
        }
    }
}

if (! function_exists('get_institution_config')) {
    function get_institution_config(?string $key = null): mixed
    {
        $config = config('institution', []);

        if ($key === null) {
            $result = [];
            foreach ($config as $k => $default) {
                $result[$k] = get_institution_config($k);
            }

            return $result;
        }

        $institution = Institution::first();

        $modelMap = [
            'name' => 'name',
            'short_name' => 'short_name',
            'address' => 'address',
            'phone' => 'phone',
            'email' => 'email',
            'website' => 'website',
            'lppm_head_name' => 'lppm_head_name',
            'lppm_head_id' => 'lppm_head_id',
        ];

        if ($institution && isset($modelMap[$key])) {
            $field = $modelMap[$key];
            $value = $institution->$field;
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        $setting = Setting::get("institution_{$key}");
        if ($setting !== null && $setting !== '') {
            return $setting;
        }

        if ($key === 'lppm_head_name' || $key === 'lppm_head_position') {
            $legacy = Setting::get($key);
            if ($legacy !== null && $legacy !== '') {
                return $legacy;
            }
        }

        return $config[$key] ?? null;
    }
}

if (! function_exists('format_name')) {
    /**
     * Build a full display name from a base name plus optional academic
     * prefixes/suffixes.  This is the canonical implementation used by all of
     * the PDF/report templates.  It mirrors the logic that used to be copied
     * verbatim into several views so that there is now a single place to make
     * adjustments (e.g. stripping dots or handling multiple titles) and so that
     * new views cannot accidentally forget to include the behaviour.
     *
     * @param  string  $prefix  gelar depan ("Dr.", "Prof.", etc.)
     * @param  string  $name  nama pokok
     * @param  string  $suffix  gelar belakang (", S.T.", ", M.Sc.", etc.)
     * @return string nama lengkap dengan gelar kalau tersedia
     */
    function format_name(?string $prefix = '', ?string $name = '', ?string $suffix = ''): string
    {
        $prefix = $prefix ?? '';
        $name = $name ?? '';
        $suffix = $suffix ?? '';

        $full = trim($name);

        if (
            ! empty($prefix)
            && ! str_starts_with($full, $prefix)
            && ! str_contains($full, $prefix.' ')
        ) {
            $full = $prefix.' '.$full;
        }

        if (
            ! empty($suffix)
            && ! str_ends_with($full, $suffix)
            && ! str_contains($full, ', '.$suffix)
        ) {
            $full = $full.', '.$suffix;
        }

        return trim($full, ' ,');
    }
}
if (! function_exists('to_roman')) {
    /**
     * Convert an integer to a Roman numeral string.
     */
    function to_roman(int $number): string
    {
        $map = [
            'M' => 1000,
            'CM' => 900,
            'D' => 500,
            'CD' => 400,
            'C' => 100,
            'XC' => 90,
            'L' => 50,
            'XL' => 40,
            'X' => 10,
            'IX' => 9,
            'V' => 5,
            'IV' => 4,
            'I' => 1,
        ];
        $result = '';
        foreach ($map as $roman => $value) {
            $matches = intval($number / $value);
            $result .= str_repeat($roman, $matches);
            $number %= $value;
        }

        return $result;
    }
}

if (! function_exists('get_logo_base64')) {
    /**
     * Get the logo image as a base64 encoded data URI.
     * Prefers JPG over PNG because DOMPDF handles JPG base64 more reliably
     * (transparent PNGs sometimes render as a huge X box).
     */
    function get_logo_base64(): ?string
    {
        $path = public_path('logo.jpg');
        if (! file_exists($path)) {
            $path = public_path('logo.png');
            if (! file_exists($path)) {
                return null;
            }
        }

        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);

        return 'data:image/'.$type.';base64,'.base64_encode($data);
    }
}

if (! function_exists('clean_proposal_title')) {
    /**
     * Clean redundant prefixes from proposal title for display.
     */
    function clean_proposal_title(?string $title): string
    {
        if (empty($title)) {
            return '-';
        }

        $prefixes = [
            'Penelitian:',
            'Pengabdian Masyarakat:',
            'Pemberdayaan Kemitraan Masyarakat:',
        ];

        $cleaned = $title;
        foreach ($prefixes as $prefix) {
            if (str_starts_with(strtolower($cleaned), strtolower($prefix))) {
                $cleaned = trim(substr($cleaned, strlen($prefix)));
            }
        }

        return $cleaned;
    }
}

if (! function_exists('normalize_paper_size')) {
    /**
     * Normalize paper size for DomPDF's setPaper().
     * DomPDF accepts standard sizes as strings ('a4', 'letter', etc.)
     * but does NOT accept 'folio' or 'f4' as strings. Map to array.
     */
    function normalize_paper_size(string|array $size): string|array
    {
        if (is_array($size)) {
            return $size;
        }

        $map = [
            'folio' => [0, 0, 612.00, 935.43],
            'f4' => [0, 0, 612.00, 935.43],
        ];

        return $map[strtolower($size)] ?? $size;
    }
}

if (! function_exists('get_pdf_config')) {
    /**
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     * Ambil konfigurasi PDF dari settings. Default = existing hardcode values.
     * Menggunakan cache layer untuk optimasi module-level caching (Phase 2 Performance).
     *
     * @param  string  $viewType  'letter' | 'report' | 'report_ba' | 'report_compact'
     */
    function get_pdf_config(string $viewType = 'letter', ?string $moduleKey = null): array
    {
        $cacheKey = "pdf_config_{$viewType}".($moduleKey ? "_{$moduleKey}" : '');

        return Cache::rememberForever($cacheKey, function () use ($viewType, $moduleKey) {
            $fontDefaults = [
                'letter' => "'Times New Roman', Times, serif",
                'report' => 'Arial, Helvetica, sans-serif',
                'report_ba' => 'Arial, Helvetica, sans-serif',
                'report_compact' => 'Arial, Helvetica, sans-serif',
            ];
            $sizeDefaults = [
                'letter' => 11,
                'report' => 9,
                'report_ba' => 11,
                'report_compact' => 7,
            ];
            $marginDefaults = [
                'letter' => '0cm 2cm 0.5cm 2cm',
                'report' => '3cm 3cm 3cm 4cm',
                'report_ba' => '4cm 3cm 3cm 4cm',
                'report_compact' => '1.5cm 1cm',
            ];

            $isReport = str_starts_with($viewType, 'report');
            $settingFontKey = $isReport ? PdfConstants::REPORT_FONT_FAMILY : PdfConstants::GLOBAL_FONT_FAMILY;
            $settingFontSize = $isReport ? PdfConstants::REPORT_FONT_SIZE : PdfConstants::GLOBAL_BODY_FONT_SIZE;
            $settingLineHeight = $isReport ? PdfConstants::REPORT_LINE_HEIGHT : PdfConstants::GLOBAL_LINE_HEIGHT;

            $isCompact = (bool) Setting::get(PdfConstants::GLOBAL_LAYOUT_COMPACT, false);
            $pageMarginKey = Setting::get(PdfConstants::GLOBAL_PAGE_MARGIN, 'normal');
            $marginMap = [
                'narrow' => '1.5cm 1cm',
                'normal' => $marginDefaults[$viewType] ?? '2cm',
                'wide' => '4cm 3.5cm',
            ];

            $customMargins = _build_custom_margins($viewType, $marginDefaults);

            $config = [
                'font_family' => Setting::get($settingFontKey, $fontDefaults[$viewType] ?? 'Arial, Helvetica, sans-serif'),
                'body_font_size' => (int) Setting::get($settingFontSize, $sizeDefaults[$viewType] ?? 11),
                'compact' => $isCompact,
                'show_logo' => (bool) Setting::get(PdfConstants::GLOBAL_SHOW_LOGO, true),
                'page_margin' => $marginMap[$pageMarginKey] ?? $marginDefaults[$viewType],
                'paper_size' => Setting::get(PdfConstants::GLOBAL_PAPER_SIZE, 'a4'),
                '_view_type' => $viewType,
                // Extended layout controls
                'logo_position' => Setting::get(PdfConstants::GLOBAL_LOGO_POSITION, 'left'),
                'logo_size' => (int) Setting::get(PdfConstants::GLOBAL_LOGO_SIZE, 110),
                'line_height' => Setting::get($settingLineHeight, '1.1'),
                'paragraph_spacing' => (int) Setting::get(PdfConstants::GLOBAL_PARAGRAPH_SPACING, 6),
                'paragraph_indent' => (int) Setting::get(PdfConstants::GLOBAL_PARAGRAPH_INDENT, 0),
                'custom_margins' => $customMargins,
                'orientation' => null,
                'intro_text' => '',
                'outro_text' => '',
                // Cover & Approval editor
                'cover_title' => Setting::get(PdfConstants::GLOBAL_COVER_TITLE, ''),
                'cover_subtitle' => Setting::get(PdfConstants::GLOBAL_COVER_SUBTITLE, ''),
                'cover_show_team' => (bool) Setting::get(PdfConstants::GLOBAL_COVER_SHOW_TEAM, true),
                'approval_custom_text' => Setting::get(PdfConstants::GLOBAL_APPROVAL_CUSTOM_TEXT, ''),
            ];

            // Apply Module-Specific Overrides
            if ($moduleKey) {
                $config['intro_text'] = Setting::get("pdf_content_{$moduleKey}_intro", '');
                $config['outro_text'] = Setting::get("pdf_content_{$moduleKey}_outro", '');

                if ($overrideFont = Setting::get("pdf_override_{$moduleKey}_font_family")) {
                    $config['font_family'] = $overrideFont;
                }
                if ($overrideSize = Setting::get("pdf_override_{$moduleKey}_font_size")) {
                    $config['body_font_size'] = (int) $overrideSize;
                }
                if ($overridePaper = Setting::get("pdf_override_{$moduleKey}_paper_size")) {
                    $config['paper_size'] = $overridePaper;
                }
                if ($overrideOrientation = Setting::get("pdf_override_{$moduleKey}_orientation")) {
                    $config['orientation'] = $overrideOrientation;
                }

                // Margin Override
                $mTop = Setting::get("pdf_override_{$moduleKey}_margin_top", '');
                $mRight = Setting::get("pdf_override_{$moduleKey}_margin_right", '');
                $mBottom = Setting::get("pdf_override_{$moduleKey}_margin_bottom", '');
                $mLeft = Setting::get("pdf_override_{$moduleKey}_margin_left", '');

                if ($mTop !== '' || $mRight !== '' || $mBottom !== '' || $mLeft !== '') {
                    // Parse default string to fallback array
                    $defaultMarginStr = $customMargins !== '' ? $customMargins : ($marginMap[$pageMarginKey] ?? $marginDefaults[$viewType] ?? '2cm 2cm 2cm 2cm');
                    $parts = preg_split('/\s+/', trim($defaultMarginStr)) ?: ['2cm', '2cm', '2cm', '2cm'];

                    $dTop = $parts[0];
                    $dRight = $parts[1] ?? $parts[0];
                    $dBottom = $parts[2] ?? $parts[0];
                    $dLeft = $parts[3] ?? ($parts[1] ?? $parts[0]);

                    $cTop = $mTop !== '' ? $mTop.'cm' : $dTop;
                    $cRight = $mRight !== '' ? $mRight.'cm' : $dRight;
                    $cBottom = $mBottom !== '' ? $mBottom.'cm' : $dBottom;
                    $cLeft = $mLeft !== '' ? $mLeft.'cm' : $dLeft;

                    $config['custom_margins'] = "{$cTop} {$cRight} {$cBottom} {$cLeft}";
                }

                // Additional per-module overrides
                if ($showLogo = Setting::get("pdf_override_{$moduleKey}_show_logo")) {
                    $config['show_logo'] = filter_var($showLogo, FILTER_VALIDATE_BOOLEAN);
                }
                if ($coverTitle = Setting::get("pdf_override_{$moduleKey}_cover_title")) {
                    $config['cover_title'] = $coverTitle;
                }
                if ($coverSubtitle = Setting::get("pdf_override_{$moduleKey}_cover_subtitle")) {
                    $config['cover_subtitle'] = $coverSubtitle;
                }
                if ($coverShowTeam = Setting::get("pdf_override_{$moduleKey}_cover_show_team")) {
                    $config['cover_show_team'] = filter_var($coverShowTeam, FILTER_VALIDATE_BOOLEAN);
                }
            }

            return $config;
        });
    }
}

if (! function_exists('clear_pdf_config_cache')) {
    /**
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     * Clear the cached pdf configuration.
     * If moduleKey is provided, only that module's cache is cleared.
     * If null, ALL pdf configs are cleared (e.g. on global layout change).
     */
    function clear_pdf_config_cache(?string $moduleKey = null): void
    {
        $types = ['letter', 'report', 'report_ba', 'report_compact'];

        if ($moduleKey) {
            foreach ($types as $type) {
                Cache::forget("pdf_config_{$type}_{$moduleKey}");
            }
        } else {
            // Global change, clear everything
            foreach ($types as $type) {
                Cache::forget("pdf_config_{$type}");

                // Clear all modules too
                $modules = config('pdf-modules.list', []);
                foreach ($modules as $module) {
                    Cache::forget("pdf_config_{$type}_{$module['key']}");
                }
            }
        }
    }
}

if (! function_exists('_build_custom_margins')) {
    /**
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     * Build custom margin string from per-side settings.
     * Returns empty string if no custom margins are set (fallback to preset).
     *
     * @param  array<string, string>  $defaults
     */
    function _build_custom_margins(string $viewType, array $defaults): string
    {
        $t = trim((string) Setting::get(PdfConstants::GLOBAL_MARGIN_TOP, ''));
        $r = trim((string) Setting::get(PdfConstants::GLOBAL_MARGIN_RIGHT, ''));
        $b = trim((string) Setting::get(PdfConstants::GLOBAL_MARGIN_BOTTOM, ''));
        $l = trim((string) Setting::get(PdfConstants::GLOBAL_MARGIN_LEFT, ''));

        // Only apply custom margins if at least one side is explicitly set
        if ($t === '' && $r === '' && $b === '' && $l === '') {
            return '';
        }

        // Parse the default margin to use as fallback for unset sides
        $defaultStr = $defaults[$viewType] ?? '2cm 2cm 2cm 2cm';
        $parts = preg_split('/\s+/', trim($defaultStr)) ?: ['2cm', '2cm', '2cm', '2cm'];

        // CSS shorthand: top right bottom left (4-value)
        $dTop = $parts[0];
        $dRight = $parts[1] ?? $parts[0];
        $dBottom = $parts[2] ?? $parts[0];
        $dLeft = $parts[3] ?? ($parts[1] ?? $parts[0]);

        $top = $t !== '' ? $t.'cm' : $dTop;
        $right = $r !== '' ? $r.'cm' : $dRight;
        $bottom = $b !== '' ? $b.'cm' : $dBottom;
        $left = $l !== '' ? $l.'cm' : $dLeft;

        return "{$top} {$right} {$bottom} {$left}";
    }
}

if (! function_exists('embed_attachment_image')) {
    function embed_attachment_image(Media $media): ?string
    {
        $path = $media->hasGeneratedConversion('pdf_image') && file_exists($media->getPath('pdf_image'))
            ? $media->getPath('pdf_image')
            : $media->getPath();

        return file_exists($path) ? $path : null;
    }
}
