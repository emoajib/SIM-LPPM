<?php

namespace App\Enums;

enum MonevReviewSemester: string
{
    case GANJIL = 'ganjil';
    case GENAP = 'genap';

    /**
     * Get label in Indonesian
     */
    public function label(): string
    {
        return match ($this) {
            self::GANJIL => 'Ganjil',
            self::GENAP => 'Genap',
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
