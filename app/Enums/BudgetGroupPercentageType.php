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
}
