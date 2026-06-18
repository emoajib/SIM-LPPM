<?php

namespace App\Enums;

enum TeamSource: string
{
    case PROPOSAL = 'proposal';
    case MANUAL = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::PROPOSAL => 'Dari Proposal',
            self::MANUAL => 'Input Manual',
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
