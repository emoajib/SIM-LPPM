<?php

namespace App\Enums;

enum StrataCategory: string
{
    case RESEARCH = 'research';
    case COMMUNITY_SERVICE = 'community_service';

    /**
     * Get label in Indonesian
     */
    public function label(): string
    {
        return match ($this) {
            self::RESEARCH => 'Penelitian',
            self::COMMUNITY_SERVICE => 'Pengabdian Masyarakat',
        };
    }
}
