<?php

namespace App\Enums;

enum ProposalUserRole: string
{
    case KETUA = 'ketua';
    case ANGGOTA = 'anggota';

    /**
     * Get label in Indonesian
     */
    public function label(): string
    {
        return match ($this) {
            self::KETUA => 'Ketua',
            self::ANGGOTA => 'Anggota',
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
