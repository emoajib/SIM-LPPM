<?php

namespace App\Enums;

enum PolicyInvolvementLevel: string
{
    case INTERNASIONAL = 'Internasional';
    case NASIONAL = 'Nasional';
    case REGIONAL_INSTITUSI = 'Regional/Institusi';

    /**
     * Get label in Indonesian
     */
    public function label(): string
    {
        return match ($this) {
            self::INTERNASIONAL => 'Internasional',
            self::NASIONAL => 'Nasional',
            self::REGIONAL_INSTITUSI => 'Regional/Institusi',
        };
    }
}