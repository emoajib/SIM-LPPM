<?php

namespace App\Enums;

enum ReportingPeriod: string
{
    case SEMESTER_1 = 'semester_1';
    case SEMESTER_2 = 'semester_2';
    case ANNUAL = 'annual';
    case FINAL = 'final';

    /**
     * Get label in Indonesian
     */
    public function label(): string
    {
        return match ($this) {
            self::SEMESTER_1 => 'Semester 1',
            self::SEMESTER_2 => 'Semester 2',
            self::ANNUAL => 'Tahunan',
            self::FINAL => 'Akhir',
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
