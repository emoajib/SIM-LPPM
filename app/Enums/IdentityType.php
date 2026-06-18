<?php

namespace App\Enums;

enum IdentityType: string
{
    case DOSEN = 'dosen';
    case MAHASISWA = 'mahasiswa';
    case REVIEWER = 'reviewer';
    case TENDIK = 'tendik';

    /**
     * Get label in Indonesian
     */
    public function label(): string
    {
        return match ($this) {
            self::DOSEN => 'Dosen',
            self::MAHASISWA => 'Mahasiswa',
            self::REVIEWER => 'Reviewer',
            self::TENDIK => 'Tendik',
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
