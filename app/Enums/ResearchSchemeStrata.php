<?php

namespace App\Enums;

enum ResearchSchemeStrata: string
{
    case REGULER = 'Reguler';
    case KOLABORASI_INTERNAL = 'Kolaborasi Internal';
    case KERJA_SAMA_ANTAR_PT = 'Kerja Sama Antar PT';
    case PKM_KE = 'PKM-KE';
    case PKM_KI = 'PKM-KI';
    case PKM_REGULER = 'PKM-Reguler';

    public function label(): string
    {
        return match ($this) {
            self::REGULER => 'Reguler',
            self::KOLABORASI_INTERNAL => 'Kolaborasi Internal',
            self::KERJA_SAMA_ANTAR_PT => 'Kerja Sama Antar PT',
            self::PKM_KE => 'PKM-KE',
            self::PKM_KI => 'PKM-KI',
            self::PKM_REGULER => 'PKM-Reguler',
        };
    }

    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
