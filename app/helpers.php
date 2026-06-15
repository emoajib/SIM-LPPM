<?php

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\DB;

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

if (! function_exists('active_can')) {
    /**
     * Determine if the currently active role has a given permission.
     * This is more strict than $user->can() because it filters by current session role.
     */
    function active_can(string $permission): bool
    {
        static $activeRoleModel = null;
        static $checkedRoleName = null;

        $roleName = active_role();
        if (! $roleName) {
            return false;
        }

        // Cache the role model per request for performance
        if ($activeRoleModel === null || $checkedRoleName !== $roleName) {
            try {
                $activeRoleModel = Role::findByName($roleName, 'web');
                $checkedRoleName = $roleName;
            } catch (Throwable $e) {
                return false;
            }
        }

        return $activeRoleModel ? $activeRoleModel->hasPermissionTo($permission) : false;
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

if (! function_exists('active_has_all_roles')) {
    /**
     * Check if the active role matches any of the given roles.
     */
    function active_has_all_roles(array $roles): bool
    {
        $activeRole = active_role();

        return in_array($activeRole, $roles);
    }
}

if (! function_exists('sql_year')) {
    /**
     * Get the SQL expression for extracting year from a date column.
     */
    function sql_year(string $column = 'created_at'): string
    {
        $driver = strtolower(DB::getDriverName());

        return $driver === 'sqlite'
            ? "strftime('%Y', {$column})"
            : "YEAR({$column})";
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
     */
    function get_logo_base64(): ?string
    {
        $path = public_path('logo.png');
        if (! file_exists($path)) {
            return null;
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

if (! function_exists('get_pdf_config')) {
    /**
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     * Ambil konfigurasi PDF dari settings. Default = existing hardcode values.
     *
     * @param  string  $viewType  'letter' | 'report' | 'report_ba' | 'report_compact'
     */
    function get_pdf_config(string $viewType = 'letter'): array
    {
        $fontDefaults = [
            'letter' => 'Times New Roman, Times, serif',
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

        $settingFontKey = str_starts_with($viewType, 'report') ? 'pdf_report_font_family' : 'pdf_font_family';
        $settingFontSize = str_starts_with($viewType, 'report') ? 'pdf_report_font_size' : 'pdf_body_font_size';

        $isCompact = (bool) Setting::get('pdf_layout_compact', false);
        $pageMarginKey = Setting::get('pdf_page_margin', 'normal');
        $marginMap = [
            'narrow' => '1.5cm 1cm',
            'normal' => $marginDefaults[$viewType] ?? '2cm',
            'wide' => '4cm 3.5cm',
        ];

        return [
            'font_family' => Setting::get($settingFontKey, $fontDefaults[$viewType] ?? 'Arial, Helvetica, sans-serif'),
            'body_font_size' => (int) Setting::get($settingFontSize, $sizeDefaults[$viewType] ?? 11),
            'compact' => $isCompact,
            'show_logo' => (bool) Setting::get('pdf_show_logo', true),
            'page_margin' => $marginMap[$pageMarginKey] ?? $marginDefaults[$viewType],
            '_view_type' => $viewType,
        ];
    }
}
