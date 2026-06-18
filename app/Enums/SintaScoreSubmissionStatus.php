<?php

namespace App\Enums;

enum SintaScoreSubmissionStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    /**
     * Get label in Indonesian
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu',
            self::APPROVED => 'Disetujui',
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
}
