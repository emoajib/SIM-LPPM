<?php

namespace App\Enums;

enum OutputStatusType: string
{
    case PUBLISHED = 'published';
    case ACCEPTED = 'accepted';
    case UNDER_REVIEW = 'under_review';
    case REJECTED = 'rejected';

    /**
     * Get label in Indonesian
     */
    public function label(): string
    {
        return match ($this) {
            self::PUBLISHED => 'Terbit',
            self::ACCEPTED => 'Diterima',
            self::UNDER_REVIEW => 'Dalam Review',
            self::REJECTED => 'Ditolak',
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
            self::PUBLISHED => 'primary',
            self::ACCEPTED => 'success',
            self::UNDER_REVIEW => 'warning',
            self::REJECTED => 'danger',
        };
    }
}