<?php

namespace App\Enums;

enum MonevReviewStatus: string
{
    case SANGAT_BAIK = 'sangat_baik';
    case BAIK = 'baik';
    case CUKUP = 'cukup';

    /**
     * Get label in Indonesian
     */
    public function label(): string
    {
        return match ($this) {
            self::SANGAT_BAIK => 'Sangat Baik',
            self::BAIK => 'Baik',
            self::CUKUP => 'Cukup',
        };
    }

    /**
     * Get badge color
     */
    public function color(): string
    {
        return match ($this) {
            self::SANGAT_BAIK => 'success',
            self::BAIK => 'primary',
            self::CUKUP => 'info',
        };
    }
}