<?php

namespace App\Constants;

/**
 * Constants for PDF configuration and overrides.
 * Vetted by AI - Manual Review Required by Senior Engineer/Manager
 */
class PdfConstants
{
    // --- Key Prefixes ---
    public const PREFIX_OVERRIDE = 'pdf_override_';

    public const PREFIX_CONTENT = 'pdf_content_';

    // --- Override Suffixes ---
    public const KEY_FONT_FAMILY = 'font_family';

    public const KEY_FONT_SIZE = 'font_size';

    public const KEY_PAPER_SIZE = 'paper_size';

    public const KEY_ORIENTATION = 'orientation';

    public const KEY_MARGIN_TOP = 'margin_top';

    public const KEY_MARGIN_RIGHT = 'margin_right';

    public const KEY_MARGIN_BOTTOM = 'margin_bottom';

    public const KEY_MARGIN_LEFT = 'margin_left';

    public const KEY_SHOW_LOGO = 'show_logo';

    public const KEY_COVER_TITLE = 'cover_title';

    public const KEY_COVER_SUBTITLE = 'cover_subtitle';

    public const KEY_COVER_SHOW_TEAM = 'cover_show_team';

    // --- Content Suffixes ---
    public const KEY_INTRO = 'intro';

    public const KEY_OUTRO = 'outro';

    // --- Valid Options ---
    public const PAPER_SIZES = ['a4', 'folio', 'letter', 'legal'];

    public const ORIENTATIONS = ['portrait', 'landscape'];

    // --- Global Setting Keys (Family A) ---
    public const GLOBAL_FONT_FAMILY = 'pdf_font_family';

    public const GLOBAL_BODY_FONT_SIZE = 'pdf_body_font_size';

    public const GLOBAL_LAYOUT_COMPACT = 'pdf_layout_compact';

    public const GLOBAL_SHOW_LOGO = 'pdf_show_logo';

    public const GLOBAL_PAGE_MARGIN = 'pdf_page_margin';

    public const GLOBAL_PAPER_SIZE = 'pdf_paper_size';

    public const GLOBAL_ORIENTATION = 'pdf_orientation';

    public const GLOBAL_LOGO_POSITION = 'pdf_logo_position';

    public const GLOBAL_LOGO_SIZE = 'pdf_logo_size';

    public const GLOBAL_LINE_HEIGHT = 'pdf_line_height';

    public const GLOBAL_PARAGRAPH_SPACING = 'pdf_paragraph_spacing';

    public const GLOBAL_PARAGRAPH_INDENT = 'pdf_paragraph_indent';

    public const GLOBAL_MARGIN_TOP = 'pdf_margin_top';

    public const GLOBAL_MARGIN_RIGHT = 'pdf_margin_right';

    public const GLOBAL_MARGIN_BOTTOM = 'pdf_margin_bottom';

    public const GLOBAL_MARGIN_LEFT = 'pdf_margin_left';

    public const GLOBAL_COVER_TITLE = 'pdf_cover_title';

    public const GLOBAL_COVER_SUBTITLE = 'pdf_cover_subtitle';

    public const GLOBAL_COVER_SHOW_TEAM = 'pdf_cover_show_team';

    public const GLOBAL_APPROVAL_CUSTOM_TEXT = 'pdf_approval_custom_text';

    // --- Global Setting Keys (Family B) ---
    public const REPORT_FONT_FAMILY = 'pdf_report_font_family';

    public const REPORT_FONT_SIZE = 'pdf_report_font_size';

    public const REPORT_LINE_HEIGHT = 'pdf_report_line_height';

    /**
     * Helper to get full override key for a module.
     */
    public static function overrideKey(string $moduleKey, string $suffix): string
    {
        return self::PREFIX_OVERRIDE."{$moduleKey}_{$suffix}";
    }

    /**
     * Helper to get full content key for a module.
     */
    public static function contentKey(string $moduleKey, string $suffix): string
    {
        return self::PREFIX_CONTENT."{$moduleKey}_{$suffix}";
    }
}
