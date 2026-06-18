<?php

namespace App\Enums;

enum ReviewRecommendation: string
{
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case REVISION_NEEDED = 'revision_needed';

    /**
     * Get label in Indonesian
     */
    public function label(): string
    {
        return match ($this) {
            self::APPROVED => 'Disetujui',
            self::REJECTED => 'Ditolak',
            self::REVISION_NEEDED => 'Perlu Revisi',
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
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::REVISION_NEEDED => 'warning',
        };
    }
}
