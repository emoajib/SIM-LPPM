<?php

namespace App\Enums;

enum BudgetGroupPercentageType: string
{
    case MIN = 'min';
    case MAX = 'max';

    /**
     * Get label in Indonesian
     */
    public function label(): string
    {
        return match ($this) {
            self::MIN => 'Minimum',
            self::MAX => 'Maksimum',
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
