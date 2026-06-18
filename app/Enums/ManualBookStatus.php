<?php

namespace App\Enums;

enum ManualBookStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    /**
     * Get label in Indonesian
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Aktif',
            self::INACTIVE => 'Tidak Aktif',
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
