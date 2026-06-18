<?php

namespace App\Enums;

enum ResearchSchemeStrata: string
{
    case DASAR = 'Dasar';
    case TERAPAN = 'Terapan';
    case PENGEMBANGAN = 'Pengembangan';
    case PKM = 'PKM';

    /**
     * Get label in Indonesian
     */
    public function label(): string
    {
        return match ($this) {
            self::DASAR => 'Dasar',
            self::TERAPAN => 'Terapan',
            self::PENGEMBANGAN => 'Pengembangan',
            self::PKM => 'PKM',
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
