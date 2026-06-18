<?php

namespace App\Enums;

enum BudgetGroupProposalType: string
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

    /**
     * Get all enum values as an array
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
