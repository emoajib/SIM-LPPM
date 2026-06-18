<?php

namespace App\Enums;

enum AuthorStatus: string
{
    case FIRST_AUTHOR = 'first_author';
    case CO_AUTHOR = 'co_author';
    case CORRESPONDING_AUTHOR = 'corresponding_author';

    /**
     * Get label in Indonesian
     */
    public function label(): string
    {
        return match ($this) {
            self::FIRST_AUTHOR => 'Penulis Utama',
            self::CO_AUTHOR => 'Ko-Penulis',
            self::CORRESPONDING_AUTHOR => 'Penulis Korespondensi',
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

    /**
     * Get badge color
     */
    public function color(): string
    {
        return match ($this) {
            self::FIRST_AUTHOR => 'primary',
            self::CO_AUTHOR => 'info',
            self::CORRESPONDING_AUTHOR => 'warning',
        };
    }
}