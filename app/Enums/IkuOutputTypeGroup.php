<?php

namespace App\Enums;

enum IkuOutputTypeGroup: string
{
    case PUBLICATION = 'publication';
    case HKI = 'hki';
    case PRODUCT = 'product';
    case PAKAR = 'pakar';

    /**
     * Get label in Indonesian
     */
    public function label(): string
    {
        return match ($this) {
            self::PUBLICATION => 'Publikasi',
            self::HKI => 'Hak Kekayaan Intelektual',
            self::PRODUCT => 'Produk',
            self::PAKAR => 'Pakar',
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
