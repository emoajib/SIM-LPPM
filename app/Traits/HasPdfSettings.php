<?php

namespace App\Traits;

use App\Constants\PdfConstants;

/**
 * Trait to handle shared PDF setting field mappings.
 * Vetted by AI - Manual Review Required by Senior Engineer/Manager
 *
 * @phpstan-ignore trait.unused
 */
trait HasPdfSettings
{
    /**
     * Map property names to their corresponding PDF setting suffixes.
     *
     * @return array<string, string>
     */
    protected function getPdfOverrideMap(): array
    {
        return [
            'fontFamily' => PdfConstants::KEY_FONT_FAMILY,
            'fontSize' => PdfConstants::KEY_FONT_SIZE,
            'paperSize' => PdfConstants::KEY_PAPER_SIZE,
            'orientation' => PdfConstants::KEY_ORIENTATION,
            'marginTop' => PdfConstants::KEY_MARGIN_TOP,
            'marginRight' => PdfConstants::KEY_MARGIN_RIGHT,
            'marginBottom' => PdfConstants::KEY_MARGIN_BOTTOM,
            'marginLeft' => PdfConstants::KEY_MARGIN_LEFT,
            'showLogo' => PdfConstants::KEY_SHOW_LOGO,
            'coverTitle' => PdfConstants::KEY_COVER_TITLE,
            'coverSubtitle' => PdfConstants::KEY_COVER_SUBTITLE,
            'coverShowTeam' => PdfConstants::KEY_COVER_SHOW_TEAM,
        ];
    }

    /**
     * Map property names to their corresponding PDF content suffixes.
     *
     * @return array<string, string>
     */
    protected function getPdfContentMap(): array
    {
        return [
            'introText' => PdfConstants::KEY_INTRO,
            'outroText' => PdfConstants::KEY_OUTRO,
        ];
    }

    /**
     * Get the full setting key for a property.
     */
    protected function getFullSettingKey(string $moduleKey, string $property): ?string
    {
        $overrideMap = $this->getPdfOverrideMap();
        if (isset($overrideMap[$property])) {
            return PdfConstants::overrideKey($moduleKey, $overrideMap[$property]);
        }

        $contentMap = $this->getPdfContentMap();
        if (isset($contentMap[$property])) {
            return PdfConstants::contentKey($moduleKey, $contentMap[$property]);
        }

        return null;
    }

    /**
     * Get all setting keys for a module.
     *
     * @return string[]
     */
    protected function getAllModuleSettingKeys(string $moduleKey): array
    {
        $keys = [];
        foreach ($this->getPdfOverrideMap() as $suffix) {
            $keys[] = PdfConstants::overrideKey($moduleKey, $suffix);
        }
        foreach ($this->getPdfContentMap() as $suffix) {
            $keys[] = PdfConstants::contentKey($moduleKey, $suffix);
        }

        return $keys;
    }
}
