<?php

namespace App\Enums;

enum SignatureMode: string
{
    case TTE = 'tte';
    case MANUAL = 'manual';
    case PUBLISHED = 'published';
    case READY_TO_PRINT = 'ready_to_print';

    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }

    /** @deprecated use values() */
    public static function getValues(): array
    {
        return self::values();
    }
}
